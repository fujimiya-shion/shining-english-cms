<?php

use App\Enums\PaymentMethod;
use App\Http\Controllers\Api\V1\Transaction\OrderController;
use App\Http\Requests\Api\V1\Transaction\OrderStoreRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\Order\IOrderService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    \Mockery::close();
});

it('lists orders for the authenticated user', function (): void {
    $user = new User;
    $user->id = 4;
    $items = new Collection([['id' => 21]]);
    $paginator = new LengthAwarePaginator($items, 1, 15, 1);

    $service = \Mockery::mock(IOrderService::class);
    $service->shouldReceive('listByUserId')
        ->once()
        ->with(4, \Mockery::type(App\ValueObjects\QueryOption::class))
        ->andReturn($paginator);
    app()->instance(IOrderService::class, $service);

    $controller = app()->make(OrderController::class);
    $request = Request::create('/api/v1/orders', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->index($request);

    assertJsonResponsePayload($response, 200, [
        'status' => true,
        'status_code' => 200,
        'meta' => [
            'page' => 1,
            'per_page' => 15,
            'total' => 1,
            'page_count' => 1,
        ],
    ]);
});

it('shows an order when it exists for the authenticated user', function (): void {
    $user = new User;
    $user->id = 4;
    $order = new Order(['total_amount' => 123]);
    $order->id = 21;

    $service = \Mockery::mock(IOrderService::class);
    $service->shouldReceive('detailByUserId')->once()->with(4, 21)->andReturn($order);
    app()->instance(IOrderService::class, $service);

    $controller = app()->make(OrderController::class);
    $request = Request::create('/api/v1/orders/21', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->show($request, 21);

    assertJsonResponsePayload($response, 200, [
        'status' => true,
        'status_code' => 200,
        'data' => [
            'id' => 21,
            'total_amount' => 123,
        ],
    ]);
});

it('returns not found when an order does not exist for the authenticated user', function (): void {
    $user = new User;
    $user->id = 4;

    $service = \Mockery::mock(IOrderService::class);
    $service->shouldReceive('detailByUserId')->once()->with(4, 21)->andReturnNull();
    app()->instance(IOrderService::class, $service);

    $controller = app()->make(OrderController::class);
    $request = Request::create('/api/v1/orders/21', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->show($request, 21);

    assertJsonResponsePayload($response, 404, [
        'message' => 'Order not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('creates an order from cart or buy now payloads', function (): void {
    $user = new User;
    $user->id = 4;
    $cartOrder = new Order(['total_amount' => 100]);
    $cartOrder->id = 1;
    $buyNowOrder = new Order(['total_amount' => 200]);
    $buyNowOrder->id = 2;

    $service = \Mockery::mock(IOrderService::class);
    $service->shouldReceive('createFromCart')->once()->with(4, PaymentMethod::Cod)->andReturn($cartOrder);
    $service->shouldReceive('createBuyNow')->once()->with(4, 99, 2, PaymentMethod::Payos)->andReturn($buyNowOrder);
    app()->instance(IOrderService::class, $service);

    $controller = app()->make(OrderController::class);

    $cartRequest = OrderStoreRequest::create('/api/v1/orders', 'POST', [
        'type' => 'cart',
    ]);
    $cartRequest->setContainer(app())->setRedirector(app('redirect'));
    $cartRequest->setUserResolver(fn (): User => $user);
    $cartRequest->validateResolved();

    $cartResponse = $controller->store($cartRequest);

    assertJsonResponsePayload($cartResponse, 201, [
        'message' => 'Order created',
        'status' => true,
        'status_code' => 201,
        'data' => [
            'id' => 1,
            'total_amount' => 100,
        ],
    ]);

    $buyNowRequest = OrderStoreRequest::create('/api/v1/orders', 'POST', [
        'type' => 'buy_now',
        'course_id' => 99,
        'quantity' => 2,
        'payment_method' => 'payos',
    ]);
    $buyNowRequest->setContainer(app())->setRedirector(app('redirect'));
    $buyNowRequest->setUserResolver(fn (): User => $user);
    $buyNowRequest->validateResolved();

    $buyNowResponse = $controller->store($buyNowRequest);

    assertJsonResponsePayload($buyNowResponse, 201, [
        'message' => 'Order created',
        'status' => true,
        'status_code' => 201,
        'data' => [
            'id' => 2,
            'total_amount' => 200,
        ],
    ]);
});

it('returns an error payload when order creation fails', function (): void {
    $user = new User;
    $user->id = 4;

    $service = \Mockery::mock(IOrderService::class);
    $service->shouldReceive('createFromCart')->once()->with(4, PaymentMethod::Cod)->andThrow(new RuntimeException('Cart is empty'));
    app()->instance(IOrderService::class, $service);

    $controller = app()->make(OrderController::class);
    $request = OrderStoreRequest::create('/api/v1/orders', 'POST', [
        'type' => 'cart',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->setUserResolver(fn (): User => $user);
    $request->validateResolved();

    $response = $controller->store($request);

    assertJsonResponsePayload($response, 422, [
        'message' => 'Cart is empty',
        'status' => false,
        'status_code' => 422,
    ]);
});

it('cancels an order when it exists and returns not found otherwise', function (): void {
    $user = new User;
    $user->id = 4;

    $service = \Mockery::mock(IOrderService::class);
    $service->shouldReceive('cancelByUserId')->once()->with(4, 21)->andReturnTrue();
    $service->shouldReceive('cancelByUserId')->once()->with(4, 22)->andReturnFalse();
    app()->instance(IOrderService::class, $service);

    $controller = app()->make(OrderController::class);

    $request = Request::create('/api/v1/orders/21/cancel', 'POST');
    $request->setUserResolver(fn (): User => $user);
    $successResponse = $controller->cancel($request, 21);
    assertJsonResponsePayload($successResponse, 200, [
        'message' => 'Order cancelled',
        'status' => true,
        'status_code' => 200,
    ]);

    $missingRequest = Request::create('/api/v1/orders/22/cancel', 'POST');
    $missingRequest->setUserResolver(fn (): User => $user);
    $missingResponse = $controller->cancel($missingRequest, 22);
    assertJsonResponsePayload($missingResponse, 404, [
        'message' => 'Order not found',
        'status' => false,
        'status_code' => 404,
    ]);
});
