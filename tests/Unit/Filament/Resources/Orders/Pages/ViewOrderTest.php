<?php

use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;

function setViewOrderRecord(ViewOrder $page, ?Order $record): void
{
    $reflection = new ReflectionProperty($page, 'record');
    $reflection->setAccessible(true);
    $reflection->setValue($page, $record);
}

it('includes view on website action when order has order code', function (): void {
    $page = new ViewOrder;
    setViewOrderRecord($page, new Order(['order_code' => 'ORD-001']));

    $actions = invokeProtectedMethod($page, 'getHeaderActions');
    $names = array_map(fn ($a) => $a->getName(), $actions);

    expect($names)->toContain('viewOnWebsite');
});

it('excludes view on website action when order has no order code', function (): void {
    $page = new ViewOrder;
    setViewOrderRecord($page, new Order(['order_code' => null]));

    $actions = invokeProtectedMethod($page, 'getHeaderActions');
    $names = array_map(fn ($a) => $a->getName(), $actions);

    expect($names)->not->toContain('viewOnWebsite');
});

it('excludes view on website action when record is not set', function (): void {
    $page = new ViewOrder;

    $actions = rescue(fn () => invokeProtectedMethod($page, 'getHeaderActions'), [], false);
    $names = array_map(fn ($a) => $a->getName(), $actions);

    expect($names)->not->toContain('viewOnWebsite');
});
