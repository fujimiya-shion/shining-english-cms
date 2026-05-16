<?php

namespace Tests\Unit\Services\Cart;

use App\Models\Cart;
use App\Models\Course;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Cart\ICartRepository;
use App\Repositories\Course\ICourseRepository;
use App\Services\Cart\CartService;
use App\Services\Cart\ICartService;
use App\Services\Enrollment\IEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use RuntimeException;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('implements shared service contract', function (): void {
    $model = new Cart;
    $repository = new CartRepository($model);
    $courseRepository = Mockery::mock(ICourseRepository::class);
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $service = new CartService($repository, $courseRepository, $enrollmentService);

    assertServiceContract($service, ICartService::class, $repository);
});

it('returns cart items via repository', function (): void {
    $items = new Collection([new Cart]);

    $repository = Mockery::mock(ICartRepository::class);
    $repository->shouldReceive('itemsByUserId')
        ->once()
        ->with(10)
        ->andReturn($items);

    $courseRepository = Mockery::mock(ICourseRepository::class);
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $service = new CartService($repository, $courseRepository, $enrollmentService);

    $result = $service->itemsByUserId(10);

    expect($result)->toBe($items);
});

it('returns cart counts via repository', function (): void {
    $repository = Mockery::mock(ICartRepository::class);
    $repository->shouldReceive('countByUserId')
        ->once()
        ->with(10)
        ->andReturn([
            'items' => 2,
            'quantity' => 3,
        ]);

    $courseRepository = Mockery::mock(ICourseRepository::class);
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $service = new CartService($repository, $courseRepository, $enrollmentService);

    $result = $service->countByUserId(10);

    expect($result)->toEqual([
        'items' => 2,
        'quantity' => 3,
    ]);
});

it('clears cart via repository', function (): void {
    $repository = Mockery::mock(ICartRepository::class);
    $repository->shouldReceive('clearByUserId')
        ->once()
        ->with(10)
        ->andReturnNull();

    $courseRepository = Mockery::mock(ICourseRepository::class);
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $service = new CartService($repository, $courseRepository, $enrollmentService);

    $service->clearByUserId(10);

    expect(true)->toBeTrue();
});

it('adds course to cart when course is active and user is not enrolled', function (): void {
    $course = new Course;
    $course->id = 20;
    $course->status = true;

    $cart = new Cart;
    $cart->course_id = 20;
    $cart->user_id = 10;
    $cart->quantity = 2;

    $repository = Mockery::mock(ICartRepository::class);
    $repository->shouldReceive('addCourse')
        ->once()
        ->with(10, 20, 2)
        ->andReturn($cart);

    $courseRepository = Mockery::mock(ICourseRepository::class);
    $courseRepository->shouldReceive('getById')
        ->once()
        ->with(20)
        ->andReturn($course);

    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')
        ->once()
        ->with(10, 20)
        ->andReturnFalse();

    $service = new CartService($repository, $courseRepository, $enrollmentService);

    $result = $service->addCourse(10, 20, 2);

    expect($result)->toBe($cart);
});

it('throws when adding a missing or inactive course', function (): void {
    $repository = Mockery::mock(ICartRepository::class);
    $repository->shouldNotReceive('addCourse');

    $courseRepository = Mockery::mock(ICourseRepository::class);
    $courseRepository->shouldReceive('getById')
        ->once()
        ->with(20)
        ->andReturnNull();

    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldNotReceive('isEnrolled');

    $service = new CartService($repository, $courseRepository, $enrollmentService);

    expect(fn () => $service->addCourse(10, 20))
        ->toThrow(RuntimeException::class, 'Course not found');
});

it('throws when adding a course that was already purchased', function (): void {
    $course = new Course;
    $course->id = 20;
    $course->status = true;

    $repository = Mockery::mock(ICartRepository::class);
    $repository->shouldNotReceive('addCourse');

    $courseRepository = Mockery::mock(ICourseRepository::class);
    $courseRepository->shouldReceive('getById')
        ->once()
        ->with(20)
        ->andReturn($course);

    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')
        ->once()
        ->with(10, 20)
        ->andReturnTrue();

    $service = new CartService($repository, $courseRepository, $enrollmentService);

    expect(fn () => $service->addCourse(10, 20))
        ->toThrow(RuntimeException::class, 'Course already purchased');
});

it('returns true when course exists in cart', function (): void {
    $cart = new Cart;

    $repository = Mockery::mock(ICartRepository::class);
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturn($cart);

    $courseRepository = Mockery::mock(ICourseRepository::class);
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $service = new CartService($repository, $courseRepository, $enrollmentService);

    expect($service->hasCourse(10, 20))->toBeTrue();
});

it('returns false when course does not exist in cart', function (): void {
    $repository = Mockery::mock(ICartRepository::class);
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturnNull();

    $courseRepository = Mockery::mock(ICourseRepository::class);
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $service = new CartService($repository, $courseRepository, $enrollmentService);

    expect($service->hasCourse(10, 20))->toBeFalse();
});
