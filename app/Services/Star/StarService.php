<?php

namespace App\Services\Star;

use App\Enums\StarTransactionType;
use App\Models\User;
use App\Notifications\StarWalletNotification;
use App\Repositories\Star\IStarRepository;
use App\Repositories\StarTransaction\IStarTransactionRepository;
use App\Services\Service;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class StarService extends Service implements IStarService
{
    protected IStarRepository $starRepository;

    protected IStarTransactionRepository $starTransactionRepository;

    public function __construct(
        IStarRepository $repository,
        IStarTransactionRepository $starTransactionRepository
    ) {
        parent::__construct($repository);
        $this->starRepository = $repository;
        $this->starTransactionRepository = $starTransactionRepository;
    }

    public function addStarByUserId(int $amount, int $userId, ?string $message = null, ?StarTransactionType $type = null): bool
    {
        $result = DB::transaction(function () use ($amount, $userId, $message, $type): bool {
            $record = $this->starRepository->findForUpdateByUserId($userId);

            if ($record === null) {
                try {
                    $record = $this->starRepository->create([
                        'user_id' => $userId,
                        'amount' => $amount,
                    ]);
                } catch (QueryException $e) {
                    $record = $this->starRepository->findForUpdateByUserId($userId);

                    if ($record === null) {
                        throw $e;
                    }

                    $record->increment('amount', $amount);
                }
            } else {
                $record->increment('amount', $amount);
            }

            $this->starTransactionRepository->create([
                'user_id' => $userId,
                'amount' => $amount,
                'type' => $type ?? ($amount >= 0 ? StarTransactionType::Increase : StarTransactionType::Decrease),
                'description' => $message,
            ]);

            return true;
        });

        if ($result) {
            $balance = $this->getBalance($userId);
            $user = User::query()->find($userId);
            if ($user) {
                $user->notify(new StarWalletNotification(
                    amount: $amount,
                    balanceAfter: $balance,
                    transactionType: $type?->value ?? StarTransactionType::Increase->value,
                    description: $message ?? ($amount >= 0 ? 'Nhận sao' : 'Sao đã được sử dụng'),
                ));
            }
        }

        return $result;
    }

    public function getBalance(int $userId): int
    {
        return $this->starRepository->getBalanceByUserId($userId);
    }

    public function spendStarByUserId(int $amount, int $userId, ?string $message = null, ?StarTransactionType $type = null): bool
    {
        if ($amount <= 0) {
            return true;
        }

        $result = DB::transaction(function () use ($amount, $userId, $message, $type): bool {
            $record = $this->starRepository->findForUpdateByUserId($userId);

            if ($record === null || (int) $record->amount < $amount) {
                return false;
            }

            $record->decrement('amount', $amount);

            $this->starTransactionRepository->create([
                'user_id' => $userId,
                'amount' => -$amount,
                'type' => $type ?? StarTransactionType::Decrease,
                'description' => $message,
            ]);

            return true;
        });

        if ($result) {
            $balance = $this->getBalance($userId);
            $user = User::query()->find($userId);
            if ($user) {
                $user->notify(new StarWalletNotification(
                    amount: -$amount,
                    balanceAfter: $balance,
                    transactionType: $type?->value ?? StarTransactionType::Decrease->value,
                    description: $message ?? 'Sao đã được sử dụng',
                ));
            }
        }

        return $result;
    }
}
