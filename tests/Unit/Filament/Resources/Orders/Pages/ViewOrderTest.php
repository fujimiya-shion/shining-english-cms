<?php

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use Filament\Actions\EditAction;

it('view order page binds the order resource', function (): void {
    expect(getProtectedPropertyValue(new ViewOrder, 'resource'))->toBe(OrderResource::class);
});

it('view order page defines edit header action', function (): void {
    $page = new ViewOrder;
    $actions = invokeProtectedMethod($page, 'getHeaderActions');

    expect($actions)->toHaveCount(1);
    expect($actions[0])->toBeInstanceOf(EditAction::class);
});
