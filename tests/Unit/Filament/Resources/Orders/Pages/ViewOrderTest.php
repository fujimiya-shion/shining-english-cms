<?php

use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;

function makeViewOrderWithOrderCode(?string $code): ViewOrder
{
    $order = new Order;
    $order->order_code = $code;

    $page = new ViewOrder;
    $reflection = new ReflectionProperty($page, 'record');
    $reflection->setValue($page, $order);

    return $page;
}

it('includes view on website action when order has order code', function (): void {
    $page = makeViewOrderWithOrderCode('ORD-001');

    $actions = invokeProtectedMethod($page, 'getHeaderActions');
    $viewActions = array_filter($actions, fn ($a) => $a->getName() === 'viewOnWebsite');

    expect($viewActions)->toHaveCount(1);
});

it('excludes view on website action when order has no order code', function (): void {
    $page = makeViewOrderWithOrderCode(null);

    $actions = invokeProtectedMethod($page, 'getHeaderActions');
    $viewActions = array_filter($actions, fn ($a) => $a->getName() === 'viewOnWebsite');

    expect($viewActions)->toHaveCount(0);
});

it('excludes view on website action when record is not set', function (): void {
    $page = new ViewOrder;
    $actions = rescue(fn () => invokeProtectedMethod($page, 'getHeaderActions'), [], false);
    $viewActions = array_filter($actions, fn ($a) => $a->getName() === 'viewOnWebsite');

    expect($viewActions)->toHaveCount(0);
});
