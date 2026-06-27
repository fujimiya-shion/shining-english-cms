<?php

use App\DTO\User\Page\Home\HomeResponse;
use App\Repositories\User\IUserHomeRepository;
use App\Services\User\IUserHomeService;
use App\Services\User\UserHomeService;
use Tests\TestCase;

uses(TestCase::class);

it('implements service contract', function (): void {
    $repository = Mockery::mock(IUserHomeRepository::class);
    $service = new UserHomeService($repository);

    expect($service)->toBeInstanceOf(IUserHomeService::class);
});

it('delegates to repository and returns HomeResponse', function (): void {
    $repository = Mockery::mock(IUserHomeRepository::class);
    $repository->shouldReceive('getUserHomeData')
        ->once()
        ->with('test-token')
        ->andReturn(new HomeResponse([]));

    $service = new UserHomeService($repository);
    $result = $service->getHomeData('test-token');

    expect($result)->toBeInstanceOf(HomeResponse::class);
    expect($result->toArray())->toBe(['payloads' => []]);
});
