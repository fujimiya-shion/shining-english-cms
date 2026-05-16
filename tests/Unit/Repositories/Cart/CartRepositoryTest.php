<?php

use App\Models\Cart;
use App\Models\Course;
use App\Models\User;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Cart\ICartRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('implements shared repository contract', function (): void {
    $model = new Cart;
    $repository = new CartRepository($model);

    assertRepositoryContract($repository, ICartRepository::class, $model);
});

it('returns cart items by user id', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    Cart::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'quantity' => 1,
    ]);

    $repository = new CartRepository(new Cart);

    $items = $repository->itemsByUserId($user->id);

    expect($items)->toHaveCount(1);
    expect($items->first()?->course_id)->toBe($course->id);
    expect($items->first()?->relationLoaded('course'))->toBeTrue();
});

it('counts cart items and quantity by user id', function (): void {
    $user = User::factory()->create();
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

    $repository = new CartRepository(new Cart);

    $counts = $repository->countByUserId($user->id);

    expect($counts['items'])->toBe(2);
    expect($counts['quantity'])->toBe(3);
});

it('clears cart items by user id', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    Cart::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'quantity' => 1,
    ]);

    $repository = new CartRepository(new Cart);

    $repository->clearByUserId($user->id);

    expect(Cart::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('finds cart item by user and course', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    $cart = Cart::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'quantity' => 1,
    ]);

    $repository = new CartRepository(new Cart);

    $result = $repository->findByUserAndCourse($user->id, $course->id);

    expect($result?->id)->toBe($cart->id);
});

it('adds course to cart and reuses existing record for the same user and course', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    $repository = new CartRepository(new Cart);

    $created = $repository->addCourse($user->id, $course->id, 2);
    $reused = $repository->addCourse($user->id, $course->id, 5);

    expect($created->id)->toBe($reused->id);
    expect($reused->quantity)->toBe(2);
    expect(
        Cart::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->count()
    )->toBe(1);
});
