<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Repositories\Order\IOrderRepository;
use App\Repositories\Order\OrderRepository;
use App\ValueObjects\QueryOption;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

it('implements shared repository contract', function (): void {
    $model = new Order;
    $repository = new OrderRepository($model);

    assertRepositoryContract($repository, IOrderRepository::class, $model);
});

it('paginates orders by user id', function (): void {
    $user = User::factory()->create();
    Order::query()->create([
        'user_id' => $user->id,
        'total_amount' => 100,
        'status' => OrderStatus::Pending,
        'payment_method' => PaymentMethod::Cod,
        'placed_at' => now(),
    ]);
    Order::query()->create([
        'user_id' => $user->id,
        'total_amount' => 200,
        'status' => OrderStatus::Paid,
        'payment_method' => PaymentMethod::Payos,
        'placed_at' => now(),
    ]);

    $repository = new OrderRepository(new Order);
    $paginator = $repository->paginateByUserId($user->id, new QueryOption(perPage: 15));

    expect($paginator->total())->toBe(2);
});

it('finds an order by user id with eager loaded items and returns null when missing', function (): void {
    $user = User::factory()->create();
    $course = App\Models\Course::factory()->create();
    $order = Order::query()->create([
        'user_id' => $user->id,
        'total_amount' => 100,
        'status' => OrderStatus::Pending,
        'payment_method' => PaymentMethod::Cod,
        'placed_at' => now(),
    ]);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'course_id' => $course->id,
        'quantity' => 1,
        'price' => 100,
    ]);

    $repository = new OrderRepository(new Order);
    $found = $repository->findByUserId($user->id, $order->id);
    $missing = $repository->findByUserId($user->id, 999999);

    expect($found?->is($order))->toBeTrue();
    expect($found?->relationLoaded('items'))->toBeTrue();
    expect($missing)->toBeNull();
});
