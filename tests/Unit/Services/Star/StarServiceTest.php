<?php

use App\Enums\StarTransactionType;
use App\Models\Star;
use App\Repositories\Star\IStarRepository;
use App\Repositories\StarTransaction\IStarTransactionRepository;
use App\Services\Star\IStarService;
use App\Services\Star\StarService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

it('implements star service contract', function (): void {
    $repository = Mockery::mock(IStarRepository::class);
    $transactionRepository = Mockery::mock(IStarTransactionRepository::class);
    $service = new StarService($repository, $transactionRepository);

    assertServiceContract($service, IStarService::class, $repository);
});

it('creates star and logs transaction when none exists', function (): void {
    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(function (callable $callback): bool {
            return $callback();
        });

    $userId = 10;
    $amount = 5;
    $message = 'Reward';
    $createdStar = new Star;

    $repository = Mockery::mock(IStarRepository::class);
    $repository->shouldReceive('findForUpdateByUserId')
        ->once()
        ->with($userId)
        ->andReturnNull();
    $repository->shouldReceive('create')
        ->once()
        ->with([
            'user_id' => $userId,
            'amount' => $amount,
        ])
        ->andReturn($createdStar);

    $transactionRepository = Mockery::mock(IStarTransactionRepository::class);
    $transactionRepository->shouldReceive('create')
        ->once()
        ->with([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => StarTransactionType::Increase,
            'description' => $message,
        ]);

    $service = new StarService($repository, $transactionRepository);

    expect($service->addStarByUserId($amount, $userId, $message))->toBeTrue();
});

it('increments star and logs transaction when record exists', function (): void {
    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(function (callable $callback): bool {
            return $callback();
        });

    $userId = 8;
    $amount = -3;
    $message = 'Penalty';

    $record = Mockery::mock(Star::class)->makePartial();
    $record->shouldAllowMockingProtectedMethods();
    $record->shouldReceive('increment')
        ->once()
        ->with('amount', $amount);

    $repository = Mockery::mock(IStarRepository::class);
    $repository->shouldReceive('findForUpdateByUserId')
        ->once()
        ->with($userId)
        ->andReturn($record);

    $transactionRepository = Mockery::mock(IStarTransactionRepository::class);
    $transactionRepository->shouldReceive('create')
        ->once()
        ->with([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => StarTransactionType::Decrease,
            'description' => $message,
        ]);

    $service = new StarService($repository, $transactionRepository);

    expect($service->addStarByUserId($amount, $userId, $message))->toBeTrue();
});

it('retries when create hits unique constraint and then increments', function (): void {
    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(function (callable $callback): bool {
            return $callback();
        });

    $userId = 5;
    $amount = 4;
    $message = 'Retry';

    $record = Mockery::mock(Star::class)->makePartial();
    $record->shouldAllowMockingProtectedMethods();
    $record->shouldReceive('increment')
        ->once()
        ->with('amount', $amount);

    $repository = Mockery::mock(IStarRepository::class);
    $repository->shouldReceive('findForUpdateByUserId')
        ->once()
        ->with($userId)
        ->andReturnNull();
    $repository->shouldReceive('create')
        ->once()
        ->with([
            'user_id' => $userId,
            'amount' => $amount,
        ])
        ->andThrow(new QueryException('mysql', 'insert into stars', [], new Exception('duplicate')));
    $repository->shouldReceive('findForUpdateByUserId')
        ->once()
        ->with($userId)
        ->andReturn($record);

    $transactionRepository = Mockery::mock(IStarTransactionRepository::class);
    $transactionRepository->shouldReceive('create')
        ->once()
        ->with([
            'user_id' => $userId,
            'amount' => $amount,
            'type' => StarTransactionType::Increase,
            'description' => $message,
        ]);

    $service = new StarService($repository, $transactionRepository);

    expect($service->addStarByUserId($amount, $userId, $message))->toBeTrue();
});

it('throws when create fails and record is still missing', function (): void {
    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(function (callable $callback): bool {
            return $callback();
        });

    $userId = 6;
    $amount = 2;

    $repository = Mockery::mock(IStarRepository::class);
    $repository->shouldReceive('findForUpdateByUserId')
        ->once()
        ->with($userId)
        ->andReturnNull();
    $repository->shouldReceive('create')
        ->once()
        ->with([
            'user_id' => $userId,
            'amount' => $amount,
        ])
        ->andThrow(new QueryException('mysql', 'insert into stars', [], new Exception('duplicate')));
    $repository->shouldReceive('findForUpdateByUserId')
        ->once()
        ->with($userId)
        ->andReturnNull();

    $transactionRepository = Mockery::mock(IStarTransactionRepository::class);

    $service = new StarService($repository, $transactionRepository);

    expect(fn () => $service->addStarByUserId($amount, $userId, null))
        ->toThrow(QueryException::class);
});

it('returns balance from repository', function (): void {
    $repository = Mockery::mock(IStarRepository::class);
    $repository->shouldReceive('getBalanceByUserId')
        ->once()
        ->with(7)
        ->andReturn(42);

    $transactionRepository = Mockery::mock(IStarTransactionRepository::class);

    $service = new StarService($repository, $transactionRepository);

    expect($service->getBalance(7))->toBe(42);
});

it('spends star successfully when sufficient balance exists', function (): void {
    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(function (callable $callback): bool {
            return $callback();
        });

    $userId = 3;
    $amount = 5;
    $message = 'Course payment';

    $record = Mockery::mock(Star::class)->makePartial();
    $record->shouldAllowMockingProtectedMethods();
    $record->amount = 10;
    $record->shouldReceive('decrement')
        ->once()
        ->with('amount', $amount);

    $repository = Mockery::mock(IStarRepository::class);
    $repository->shouldReceive('findForUpdateByUserId')
        ->once()
        ->with($userId)
        ->andReturn($record);

    $transactionRepository = Mockery::mock(IStarTransactionRepository::class);
    $transactionRepository->shouldReceive('create')
        ->once()
        ->with([
            'user_id' => $userId,
            'amount' => -$amount,
            'type' => StarTransactionType::StarPayment,
            'description' => $message,
        ]);

    $service = new StarService($repository, $transactionRepository);

    expect($service->spendStarByUserId($amount, $userId, $message, StarTransactionType::StarPayment))->toBeTrue();
});

it('returns false when insufficient balance to spend', function (): void {
    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(function (callable $callback): bool {
            return $callback();
        });

    $userId = 3;
    $amount = 20;

    $record = Mockery::mock(Star::class)->makePartial();
    $record->shouldAllowMockingProtectedMethods();
    $record->amount = 5;

    $repository = Mockery::mock(IStarRepository::class);
    $repository->shouldReceive('findForUpdateByUserId')
        ->once()
        ->with($userId)
        ->andReturn($record);

    $transactionRepository = Mockery::mock(IStarTransactionRepository::class);

    $service = new StarService($repository, $transactionRepository);

    expect($service->spendStarByUserId($amount, $userId))->toBeFalse();
});

it('returns true when spending amount is zero or less', function (): void {
    $repository = Mockery::mock(IStarRepository::class);
    $transactionRepository = Mockery::mock(IStarTransactionRepository::class);

    $service = new StarService($repository, $transactionRepository);

    expect($service->spendStarByUserId(0, 1))->toBeTrue();
    expect($service->spendStarByUserId(-5, 1))->toBeTrue();
});

it('returns false when no star record exists', function (): void {
    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(function (callable $callback): bool {
            return $callback();
        });

    $repository = Mockery::mock(IStarRepository::class);
    $repository->shouldReceive('findForUpdateByUserId')
        ->once()
        ->with(4)
        ->andReturnNull();

    $transactionRepository = Mockery::mock(IStarTransactionRepository::class);

    $service = new StarService($repository, $transactionRepository);

    expect($service->spendStarByUserId(10, 4))->toBeFalse();
});
