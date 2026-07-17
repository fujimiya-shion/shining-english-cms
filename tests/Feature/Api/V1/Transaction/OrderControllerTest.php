<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

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
    if (! config('services.payos.client_id')) {
        test()->markTestSkipped('PayOS configuration is not set.');
    }

    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $course = Course::factory()->create(['price' => 150]);

    $response = $this->postJson('/api/v1/orders', [
        'type' => 'buy_now',
        'course_id' => $course->id,
        'quantity' => 3,
        'payment_method' => 'cod',
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(201);

    $order = Order::query()->where('user_id', $user->id)->first();
    expect($order->total_amount)->toBe(450);
    expect($order->payment_method)->toBe(PaymentMethod::Cod);
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

it('regenerates payment link for pending PayOS order', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;
    $course = Course::factory()->create(['price' => 200]);

    $order = Order::query()->create([
        'user_id' => $user->id,
        'total_amount' => 200,
        'status' => OrderStatus::Pending,
        'payment_method' => PaymentMethod::Payos,
        'payment_reference' => 'plink_repay_test',
        'placed_at' => now(),
    ]);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'course_id' => $course->id,
        'quantity' => 1,
        'price' => 200,
    ]);

    config([
        'payos.client_id' => 'client',
        'payos.api_key' => 'key',
        'payos.checksum_key' => 'checksum',
        'payos.base_url' => 'https://payos.test',
        'app.frontend_app_url' => 'https://frontend.test',
    ]);

    Http::fake([
        'https://payos.test/v2/payment-requests/plink_repay_test' => Http::response([
            'code' => '00',
            'data' => ['status' => 'PENDING'],
        ]),
        'https://payos.test/v2/payment-requests' => Http::response([
            'code' => '00',
            'data' => [
                'checkoutUrl' => 'https://checkout.test/new-link',
                'paymentLinkId' => 'plink_new',
                'status' => 'PENDING',
            ],
        ]),
    ]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/repay", [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.payment_action.type', 'redirect');
    $response->assertJsonPath('data.payment_action.url', 'https://checkout.test/new-link');
});

it('returns error when repaying non-existent order', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $response = $this->postJson('/api/v1/orders/999999/repay', [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'Order not found',
    ]);
});

it('returns error when repaying already paid order', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $order = Order::query()->create([
        'user_id' => $user->id,
        'total_amount' => 100,
        'status' => OrderStatus::Paid,
        'payment_method' => PaymentMethod::Payos,
        'payment_reference' => 'plink_paid',
        'placed_at' => now(),
        'paid_at' => now(),
    ]);

    config([
        'payos.client_id' => 'client',
        'payos.api_key' => 'key',
        'payos.checksum_key' => 'checksum',
        'payos.base_url' => 'https://payos.test',
    ]);

    Http::fake([
        'https://payos.test/v2/payment-requests/plink_paid' => Http::response([
            'code' => '00',
            'data' => ['status' => 'PAID'],
        ]),
    ]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/repay", [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'Order has already been paid',
    ]);
});

it('returns error when repaying non-PayOS order', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('order')->plainTextToken;

    $order = Order::query()->create([
        'user_id' => $user->id,
        'total_amount' => 100,
        'status' => OrderStatus::Pending,
        'payment_method' => PaymentMethod::Cod,
        'placed_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/repay", [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'Only online payment orders can be retried',
    ]);
});
