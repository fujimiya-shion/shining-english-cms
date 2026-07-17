<?php

namespace Tests\Unit\Services\Notification;

use App\Repositories\Notification\INotificationRepository;
use App\Services\Notification\INotificationService;
use App\Services\Notification\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->repository = Mockery::mock(INotificationRepository::class);
    $this->service = new NotificationService($this->repository);
});

it('implements contract', function (): void {
    assertServiceContract($this->service, INotificationService::class, $this->repository);
});

it('delegates listByUserId to repository', function (): void {
    $paginator = Mockery::mock(LengthAwarePaginator::class);

    $this->repository->shouldReceive('listByUserId')
        ->once()
        ->with(10, 15, 1)
        ->andReturn($paginator);

    expect($this->service->listByUserId(10, 15, 1))->toBe($paginator);
});

it('delegates unreadCount to repository', function (): void {
    $this->repository->shouldReceive('unreadCount')
        ->once()
        ->with(10)
        ->andReturn(3);

    expect($this->service->unreadCount(10))->toBe(3);
});

it('delegates markAsRead to repository', function (): void {
    $this->repository->shouldReceive('markAsRead')
        ->once()
        ->with('uuid-abc', 10)
        ->andReturn(true);

    expect($this->service->markAsRead('uuid-abc', 10))->toBeTrue();
});

it('delegates markAllAsRead to repository', function (): void {
    $this->repository->shouldReceive('markAllAsRead')
        ->once()
        ->with(10)
        ->andReturn(5);

    expect($this->service->markAllAsRead(10))->toBe(5);
});
