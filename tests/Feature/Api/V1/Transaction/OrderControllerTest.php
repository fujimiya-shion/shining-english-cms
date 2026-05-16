<?php

use App\Models\Cart;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withHeader('Authorization', createDeveloperAccessToken());
});

it('creates order from cart', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $course = Course::factory()->create(['price' => 200]);
    Cart::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'quantity' => 2,
    ]);

    $response = $this->postJson('/api/v1/orders', [
        'type' => 'cart',
        'payment_method' => 'cod',
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment([
        'message' => 'Order created',
    ]);

    $order = Order::query()->where('user_id', $user->id)->first();
    expect($order)->not->toBeNull();
    expect($order->total_amount)->toBe(400);
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->payment_method)->toBe(PaymentMethod::Cod);
    expect(OrderItem::query()->where('order_id', $order->id)->count())->toBe(1);
    expect(Cart::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('returns error when cart is empty', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $response = $this->postJson('/api/v1/orders', [
        'type' => 'cart',
        'payment_method' => 'cod',
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'Cart is empty',
    ]);
});

it('creates order with buy now', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $course = Course::factory()->create(['price' => 150]);

    $response = $this->postJson('/api/v1/orders', [
        'type' => 'buy_now',
        'course_id' => $course->id,
        'quantity' => 3,
        'payment_method' => 'payos',
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(201);

    $order = Order::query()->where('user_id', $user->id)->first();
    expect($order->total_amount)->toBe(450);
    expect($order->payment_method)->toBe(PaymentMethod::Payos);
    expect(OrderItem::query()->where('order_id', $order->id)->count())->toBe(1);
});

it('returns validation error when buy now is missing course id', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $response = $this->postJson('/api/v1/orders', [
        'type' => 'buy_now',
        'quantity' => 1,
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'Course id is required for buy now.',
    ]);
});

it('lists and shows user orders', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $order = Order::query()->create([
        'user_id' => $user->id,
        'total_amount' => 100,
        'status' => OrderStatus::Pending,
        'payment_method' => PaymentMethod::Cod,
        'placed_at' => now(),
    ]);

    $list = $this->getJson('/api/v1/orders', [
        'User-Authorization' => $token,
    ]);

    $list->assertStatus(200);
    $list->assertJsonPath('data.0.id', $order->id);

    $show = $this->getJson("/api/v1/orders/{$order->id}", [
        'User-Authorization' => $token,
    ]);

    $show->assertStatus(200);
    $show->assertJsonPath('data.id', $order->id);
});

it('returns not found when order does not exist', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $response = $this->getJson('/api/v1/orders/999999', [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(404);
    $response->assertJsonFragment([
        'message' => 'Order not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('cancels an order', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $order = Order::query()->create([
        'user_id' => $user->id,
        'total_amount' => 100,
        'status' => OrderStatus::Pending,
        'payment_method' => PaymentMethod::Cod,
        'placed_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/cancel", [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled);
});

it('returns not found when cancelling missing order', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $response = $this->postJson('/api/v1/orders/999999/cancel', [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(404);
    $response->assertJsonFragment([
        'message' => 'Order not found',
        'status' => false,
        'status_code' => 404,
    ]);
});
