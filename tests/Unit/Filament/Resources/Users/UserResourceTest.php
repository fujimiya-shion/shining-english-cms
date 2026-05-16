<?php

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\User\IUserService;
use Illuminate\Database\Eloquent\Builder;

it('uses user model and title attribute', function (): void {
    expect(UserResource::getModel())->toBe(User::class);
    expect(UserResource::getRecordTitleAttribute())->toBe('Account');
    expect(is_subclass_of(UserResource::class, BaseResource::class))->toBeTrue();
});

it('declares expected pages', function (): void {
    $pages = UserResource::getPages();

    expect($pages)->toHaveKeys(['index', 'create', 'edit']);
});

it('provides schema and table', function (): void {
    $schema = UserResource::form(makeSchema());
    $table = UserResource::table(makeTable());

    expect($schema)->toBeInstanceOf(Filament\Schemas\Schema::class);
    expect($table)->toBeInstanceOf(Filament\Tables\Table::class);
});

it('builds list query via user service', function (): void {
    $query = UserResource::getEloquentQuery();

    expect($query)->toBeInstanceOf(Builder::class);
});

it('resolves the user service', function (): void {
    $method = new ReflectionMethod(UserResource::class, 'service');
    $method->setAccessible(true);
    $service = $method->invoke(null);

    expect($service)->toBeInstanceOf(IUserService::class);
});
