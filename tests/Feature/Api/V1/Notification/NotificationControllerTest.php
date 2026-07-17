<?php

use App\Models\User;
use App\Notifications\PaymentSuccessNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withHeader('Authorization', createDeveloperAccessToken());
});

it('returns empty list when no notifications', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->getJson('/api/v1/notifications', [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data', []);
    $response->assertJsonPath('meta.total', 0);
});

it('returns paginated notifications', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $user->notify(new PaymentSuccessNotification(1, 'ORD-001', 100000, 'Khóa học A'));
    $user->notify(new PaymentSuccessNotification(2, 'ORD-002', 200000, 'Khóa học B'));

    $response = $this->getJson('/api/v1/notifications', [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('meta.total', 2);
    $orderIds = collect($response->json('data'))->pluck('data.order_id')->sort()->values()->all();
    expect($orderIds)->toBe([1, 2]);
});

it('respects per_page and page', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    foreach (range(1, 5) as $i) {
        $user->notify(new PaymentSuccessNotification($i, "ORD-{$i}", 100000, 'Course'));
    }

    $response = $this->getJson('/api/v1/notifications?per_page=2&page=2', [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
    expect($response->json('meta.total'))->toBe(5);
    expect($response->json('meta.per_page'))->toBe(2);
    expect($response->json('meta.current_page'))->toBe(2);
});

it('only returns current user notifications', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $tokenA = $userA->createToken('test')->plainTextToken;

    $userA->notify(new PaymentSuccessNotification(1, 'ORD-A', 100000, 'A'));
    $userB->notify(new PaymentSuccessNotification(2, 'ORD-B', 200000, 'B'));

    $response = $this->getJson('/api/v1/notifications', [
        'User-Authorization' => $tokenA,
    ]);

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.data.order_code', 'ORD-A');
});

it('returns unread count', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $user->notify(new PaymentSuccessNotification(1, 'ORD-1', 100000, 'A'));
    $user->notify(new PaymentSuccessNotification(2, 'ORD-2', 200000, 'B'));

    $response = $this->getJson('/api/v1/notifications/unread-count', [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.unread_count', 2);
});

it('returns zero unread when all read', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $notification = $user->notifications()->create([
        'id' => (string) str()->uuid(),
        'type' => PaymentSuccessNotification::class,
        'data' => ['type' => 'payment_success'],
        'read_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/notifications/unread-count', [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.unread_count', 0);
});

it('marks single notification as read', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $notification = $user->notifications()->create([
        'id' => (string) str()->uuid(),
        'type' => PaymentSuccessNotification::class,
        'data' => ['type' => 'payment_success'],
    ]);

    $response = $this->patchJson("/api/v1/notifications/{$notification->id}/read", [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('returns 404 when marking non-existent notification', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->patchJson('/api/v1/notifications/00000000-0000-0000-0000-000000000000/read', [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(404);
});

it('cannot mark another user notification as read', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $tokenA = $userA->createToken('test')->plainTextToken;

    $notification = $userB->notifications()->create([
        'id' => (string) str()->uuid(),
        'type' => PaymentSuccessNotification::class,
        'data' => ['type' => 'payment_success'],
    ]);

    $response = $this->patchJson("/api/v1/notifications/{$notification->id}/read", [], [
        'User-Authorization' => $tokenA,
    ]);

    $response->assertStatus(404);
    expect($notification->fresh()->read_at)->toBeNull();
});

it('marks all as read', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $user->notify(new PaymentSuccessNotification(1, 'ORD-1', 100000, 'A'));
    $user->notify(new PaymentSuccessNotification(2, 'ORD-2', 200000, 'B'));

    $response = $this->patchJson('/api/v1/notifications/read-all', [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.updated_count', 2);
    expect(DatabaseNotification::query()->where('notifiable_id', $user->id)->whereNull('read_at')->count())->toBe(0);
});

it('requires authentication for all endpoints', function (): void {
    $this->getJson('/api/v1/notifications')->assertStatus(401);
    $this->getJson('/api/v1/notifications/unread-count')->assertStatus(401);
    $this->patchJson('/api/v1/notifications/00000000-0000-0000-0000-000000000000/read')->assertStatus(401);
    $this->patchJson('/api/v1/notifications/read-all')->assertStatus(401);
});
