<?php

use App\Models\Cart;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withHeader('Authorization', createDeveloperAccessToken());
});

it('returns cart items for authenticated user', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('cart')->plainTextToken;

    $course = Course::factory()->create();
    Cart::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'quantity' => 2,
    ]);

    $response = $this->getJson('/api/v1/cart/items', [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'status' => true,
    ]);
    $response->assertJsonPath('data.0.course_id', $course->id);
});

it('returns cart count for authenticated user', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('cart')->plainTextToken;

    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();

    Cart::query()->create([
        'user_id' => $user->id,
        'course_id' => $courseA->id,
        'quantity' => 2,
    ]);
    Cart::query()->create([
        'user_id' => $user->id,
        'course_id' => $courseB->id,
        'quantity' => 1,
    ]);

    $response = $this->getJson('/api/v1/cart/count', [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.items', 2);
    $response->assertJsonPath('data.quantity', 3);
});

it('clears cart for authenticated user', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('cart')->plainTextToken;

    $course = Course::factory()->create();
    Cart::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'quantity' => 1,
    ]);

    $response = $this->deleteJson('/api/v1/cart/clear', [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'message' => 'Cart cleared',
        'status' => true,
    ]);
    expect(Cart::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('rejects requests without user authorization header', function (): void {
    $response = $this->getJson('/api/v1/cart/items');

    $response->assertStatus(401);
    $response->assertJsonFragment([
        'message' => 'Unauthenticated',
        'status' => false,
        'status_code' => 401,
    ]);
});

it('rejects requests with invalid token', function (): void {
    $response = $this->getJson('/api/v1/cart/items', [
        'User-Authorization' => 'invalid-token',
    ]);

    $response->assertStatus(401);
    $response->assertJsonFragment([
        'message' => 'Unauthenticated',
        'status' => false,
        'status_code' => 401,
    ]);
});

it('adds course to cart for authenticated user', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('cart')->plainTextToken;
    $course = Course::factory()->create([
        'status' => true,
    ]);

    $response = $this->postJson('/api/v1/cart/items', [
        'course_id' => $course->id,
        'quantity' => 2,
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment([
        'message' => 'Course added to cart',
        'status' => true,
    ]);
    $response->assertJsonPath('data.course_id', $course->id);
    $response->assertJsonPath('data.in_cart', true);
    $response->assertJsonPath('data.enrolled', false);

    $cartItem = Cart::query()
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->first();

    expect($cartItem)->not->toBeNull();
    expect($cartItem?->quantity)->toBe(2);
});

it('returns validation errors when adding course to cart with invalid payload', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('cart')->plainTextToken;

    $response = $this->postJson('/api/v1/cart/items', [
        'quantity' => 0,
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.course_id.0', 'The course id field is required.');
    $response->assertJsonPath('errors.quantity.0', 'The quantity field must be at least 1.');
});

it('returns business error when adding already purchased course to cart', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('cart')->plainTextToken;
    $course = Course::factory()->create([
        'status' => true,
    ]);

    $user->enrollments()->create([
        'course_id' => $course->id,
        'status' => true,
    ]);

    $response = $this->postJson('/api/v1/cart/items', [
        'course_id' => $course->id,
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'Course already purchased',
        'status' => false,
        'status_code' => 422,
    ]);
});
