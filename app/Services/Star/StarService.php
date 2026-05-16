<?php

namespace App\Services\Star;

use App\Enums\StarTransactionType;
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

    public function addStarByUserId(int $amount, int $userId, ?string $message): bool
    {
        return DB::transaction(function () use ($amount, $userId, $message): bool {
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
                'type' => $amount >= 0 ? StarTransactionType::Increase : StarTransactionType::Decrease,
                'description' => $message,
            ]);

            return true;
        });
    }

    public function spendStarByUserId(int $amount, int $userId, ?string $message): bool
    {
        if ($amount <= 0) {
            return true;
        }

        return DB::transaction(function () use ($amount, $userId, $message): bool {
            $record = $this->starRepository->findForUpdateByUserId($userId);

            if ($record === null || (int) $record->amount < $amount) {
                return false;
            }

            $record->decrement('amount', $amount);

            $this->starTransactionRepository->create([
                'user_id' => $userId,
                'amount' => -$amount,
                'type' => StarTransactionType::Decrease,
                'description' => $message,
            ]);

            return true;
        });
    }
}
