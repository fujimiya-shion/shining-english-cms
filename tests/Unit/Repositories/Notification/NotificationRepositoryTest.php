<?php

namespace Tests\Unit\Repositories\Notification;

use App\Models\User;
use App\Notifications\PaymentSuccessNotification;
use App\Repositories\Notification\INotificationRepository;
use App\Repositories\Notification\NotificationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->repository = new NotificationRepository(new DatabaseNotification);
});

it('implements contract', function (): void {
    $model = new DatabaseNotification;
    $repository = new NotificationRepository($model);

    assertRepositoryContract($repository, INotificationRepository::class, $model);
});

it('lists notifications by user id with pagination', function (): void {
    $user = User::factory()->create();

    $user->notify(new PaymentSuccessNotification(1, 'ORD-1', 100, 'Course A'));
    $user->notify(new PaymentSuccessNotification(2, 'ORD-2', 200, 'Course B'));

    $result = $this->repository->listByUserId($user->id, 15, 1);

    expect($result->total())->toBe(2);
    expect($result->currentPage())->toBe(1);
});

it('counts unread notifications', function (): void {
    $user = User::factory()->create();
    $user->notify(new PaymentSuccessNotification(1, 'ORD-1', 100, 'A'));
    $user->notify(new PaymentSuccessNotification(2, 'ORD-2', 100, 'B'));

    expect($this->repository->unreadCount($user->id))->toBe(2);

    $this->repository->markAllAsRead($user->id);

    expect($this->repository->unreadCount($user->id))->toBe(0);
});

it('marks a single notification as read', function (): void {
    $user = User::factory()->create();
    $user->notify(new PaymentSuccessNotification(1, 'ORD-1', 100, 'A'));

    $notification = DatabaseNotification::query()
        ->where('notifiable_id', $user->id)
        ->first();

    expect($this->repository->markAsRead($notification->id, $user->id))->toBeTrue();
    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('returns false when marking non-existent notification as read', function (): void {
    expect($this->repository->markAsRead('00000000-0000-0000-0000-000000000000', 1))->toBeFalse();
});

it('marks all notifications as read', function (): void {
    $user = User::factory()->create();
    $user->notify(new PaymentSuccessNotification(1, 'ORD-1', 100, 'A'));
    $user->notify(new PaymentSuccessNotification(2, 'ORD-2', 100, 'B'));

    $count = $this->repository->markAllAsRead($user->id);

    expect($count)->toBe(2);
    expect(DatabaseNotification::query()->where('notifiable_id', $user->id)->whereNull('read_at')->count())->toBe(0);
});
