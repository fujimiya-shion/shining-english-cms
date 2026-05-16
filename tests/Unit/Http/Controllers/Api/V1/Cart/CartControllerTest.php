<?php

use App\Http\Controllers\Api\V1\Cart\CartController;
use App\Http\Requests\Api\V1\Cart\CartStoreRequest;
use App\Models\User;
use App\Services\Cart\ICartService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    \Mockery::close();
});

it('returns cart items for the authenticated user', function (): void {
    $user = new User;
    $user->id = 7;
    $items = collect([['course_id' => 11, 'quantity' => 2]]);

    $service = \Mockery::mock(ICartService::class);
    $service->shouldReceive('itemsByUserId')->once()->with(7)->andReturn($items);
    app()->instance(ICartService::class, $service);

    $controller = app()->make(CartController::class);
    $request = Request::create('/api/v1/cart/items', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->items($request);

    assertJsonResponsePayload($response, 200, [
        'message' => 'OK',
        'status' => true,
        'status_code' => 200,
        'data' => $items->toArray(),
    ]);
});

it('stores a course in cart', function (): void {
    $user = new User;
    $user->id = 9;

    $service = \Mockery::mock(ICartService::class);
    $service->shouldReceive('addCourse')->once()->with(9, 55, 3);
    app()->instance(ICartService::class, $service);

    $controller = app()->make(CartController::class);
    $request = CartStoreRequest::create('/api/v1/cart', 'POST', [
        'course_id' => 55,
        'quantity' => 3,
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->setUserResolver(fn (): User => $user);
    $request->validateResolved();

    $response = $controller->store($request);

    assertJsonResponsePayload($response, 201, [
        'message' => 'Course added to cart',
        'status' => true,
        'status_code' => 201,
        'data' => [
            'course_id' => 55,
            'enrolled' => false,
            'pending_access' => false,
            'in_cart' => true,
        ],
    ]);
});

it('returns validation error payload when storing a cart item fails', function (): void {
    $user = new User;
    $user->id = 5;

    $service = \Mockery::mock(ICartService::class);
    $service->shouldReceive('addCourse')
        ->once()
        ->with(5, 44, 1)
        ->andThrow(new \RuntimeException('Already purchased'));
    app()->instance(ICartService::class, $service);

    $controller = app()->make(CartController::class);
    $request = CartStoreRequest::create('/api/v1/cart', 'POST', [
        'course_id' => 44,
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->setUserResolver(fn (): User => $user);
    $request->validateResolved();

    $response = $controller->store($request);

    assertJsonResponsePayload($response, 422, [
        'message' => 'Already purchased',
        'status' => false,
        'status_code' => 422,
    ]);
});

it('returns cart counts for the authenticated user', function (): void {
    $user = new User;
    $user->id = 3;
    $counts = ['item_count' => 2, 'quantity' => 4];

    $service = \Mockery::mock(ICartService::class);
    $service->shouldReceive('countByUserId')->once()->with(3)->andReturn($counts);
    app()->instance(ICartService::class, $service);

    $controller = app()->make(CartController::class);
    $request = Request::create('/api/v1/cart/count', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->count($request);

    assertJsonResponsePayload($response, 200, [
        'data' => $counts,
        'status' => true,
        'status_code' => 200,
    ]);
});

it('clears the authenticated user cart', function (): void {
    $user = new User;
    $user->id = 12;

    $service = \Mockery::mock(ICartService::class);
    $service->shouldReceive('clearByUserId')->once()->with(12);
    app()->instance(ICartService::class, $service);

    $controller = app()->make(CartController::class);
    $request = Request::create('/api/v1/cart/clear', 'DELETE');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->clear($request);

    assertJsonResponsePayload($response, 200, [
        'message' => 'Cart cleared',
        'status' => true,
        'status_code' => 200,
    ]);
});
