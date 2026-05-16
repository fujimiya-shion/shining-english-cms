<?php

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Order\IOrderService;
use Illuminate\Database\Eloquent\Builder;

test('order resource uses order model and title attribute', function (): void {
    expect(OrderResource::getModel())->toBe(Order::class);
    expect(OrderResource::getRecordTitleAttribute())->toBe('id');
    expect(is_subclass_of(OrderResource::class, \App\Filament\Resources\BaseResource::class))->toBeTrue();
});

test('order resource defines expected pages', function (): void {
    $pages = OrderResource::getPages();

    expect($pages)->toHaveKeys(['index', 'view', 'edit']);
});

test('order resource configures form table and infolist', function (): void {
    $schema = OrderResource::form(makeSchema());
    $table = OrderResource::table(makeTable());
    $infolist = OrderResource::infolist(makeSchema());

    expect($schema)->toBeInstanceOf(\Filament\Schemas\Schema::class);
    expect($table)->toBeInstanceOf(\Filament\Tables\Table::class);
    expect($infolist)->toBeInstanceOf(\Filament\Schemas\Schema::class);

    $formComponents = schemaComponentMap($schema);
    expect($formComponents)->toHaveKeys([
        'id',
        'user_name',
        'user_email',
        'total_amount',
        'status',
        'payment_method',
        'placed_at',
        'created_at',
        'updated_at',
    ]);

    $components = schemaComponentMap($infolist);
    expect($components)->toHaveKeys([
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

test('order resource builds query with eager loads', function (): void {
    $query = OrderResource::getEloquentQuery();

    expect($query)->toBeInstanceOf(Builder::class);

    $eagerLoads = $query->getEagerLoads();
    expect($eagerLoads)->toHaveKey('user');
});

test('order resource resolves the order service', function (): void {
    $method = new ReflectionMethod(OrderResource::class, 'service');
    $method->setAccessible(true);
    $service = $method->invoke(null);

    expect($service)->toBeInstanceOf(IOrderService::class);
});

test('order resource builds record query with item eager loads', function (): void {
    $query = OrderResource::getRecordRouteBindingEloquentQuery();

    expect($query)->toBeInstanceOf(Builder::class);

    $eagerLoads = $query->getEagerLoads();
    expect($eagerLoads)->toHaveKey('user');
    expect($eagerLoads)->toHaveKey('items.course');
});
