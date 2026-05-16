<?php

use App\Filament\Resources\Orders\Tables\OrdersTable;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

test('orders table defines expected columns', function (): void {
    $table = OrdersTable::configure(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'id',
        'user.name',
        'user.email',
        'total_amount',
        'status',
        'payment_method',
        'placed_at',
        'created_at',
        'updated_at',
    ]);
});

test('orders table registers view and edit record actions', function (): void {
    $table = OrdersTable::configure(makeTable());

    $actions = $table->getRecordActions();

    expect(actionClassList($actions))->toEqual([ViewAction::class, EditAction::class]);
});
