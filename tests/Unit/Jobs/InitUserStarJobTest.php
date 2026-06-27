<?php

use App\Enums\StarTransactionType;
use App\Jobs\InitUserStarJob;
use App\Services\Star\IStarService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

it('skips initialization when configured amount is not positive', function (): void {
    config(['const.star.init' => 0]);

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('addStarByUserId')->never();
    app()->instance(IStarService::class, $starService);

    Log::shouldReceive('info')->never();
    Log::shouldReceive('error')->never();

    $job = new InitUserStarJob(1);
    $job->handle();
});

it('logs success when stars are initialized', function (): void {
    config(['const.star.init' => 5]);

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('addStarByUserId')
        ->once()
        ->with(5, 2, Mockery::type('string'), StarTransactionType::RegistrationBonus)
        ->andReturnTrue();
    app()->instance(IStarService::class, $starService);

    Log::spy();

    $job = new InitUserStarJob(2);
    $job->handle();

    Log::shouldHaveReceived('info')
        ->once()
        ->with('Initialized user stars on registration.', Mockery::on(function (array $context): bool {
            return ($context['user_id'] ?? null) === 2 && ($context['amount'] ?? null) === 5;
        }));
});

it('logs error when initialization fails', function (): void {
    config(['const.star.init' => 7]);

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('addStarByUserId')
        ->once()
        ->andThrow(new Exception('fail'));
    app()->instance(IStarService::class, $starService);

    Log::spy();

    $job = new InitUserStarJob(3);
    $job->handle();

    Log::shouldHaveReceived('error')
        ->once()
        ->with('Failed to initialize user stars on registration.', Mockery::on(function (array $context): bool {
            return ($context['user_id'] ?? null) === 3 && ($context['amount'] ?? null) === 7;
        }));
});
