<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('defines fillable attributes', function (): void {
    $order = new Order;

    expect($order->getFillable())->toEqual([
        'user_id',
        'total_amount',
        'status',
        'payment_method',
        'placed_at',
    ]);
});

it('casts attributes correctly', function (): void {
    $order = new Order;

    expect($order->getCasts())->toMatchArray([
        'total_amount' => 'integer',
        'placed_at' => 'datetime',
        'status' => OrderStatus::class,
        'payment_method' => PaymentMethod::class,
    ]);
});

it('defines user relation', function (): void {
    $order = new Order;

    expect($order->user())->toBeInstanceOf(BelongsTo::class);
});

it('defines items relation', function (): void {
    $order = new Order;

    expect($order->items())->toBeInstanceOf(HasMany::class);
});

it('defines enrollments relation', function (): void {
    $order = new Order;

    expect($order->enrollments())->toBeInstanceOf(HasMany::class);
});

it('normalizes status values before persisting', function (): void {
    $order = new Order;
    $order->status = ' Paid ';

    expect($order->getAttributes()['status'])->toBe('paid');

    $order->status = OrderStatus::Cancelled;

    expect($order->getAttributes()['status'])->toBe('cancelled');
});
