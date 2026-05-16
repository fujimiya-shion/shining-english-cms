<?php

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use App\Services\Category\ICategoryService;
use Illuminate\Database\Eloquent\Builder;

it('uses category model and proper title attribute', function (): void {
    expect(CategoryResource::getModel())->toBe(Category::class);
    expect(CategoryResource::getRecordTitleAttribute())->toBe('name');
    expect(is_subclass_of(CategoryResource::class, BaseResource::class))->toBeTrue();
});

it('declares expected pages', function (): void {
    $pages = CategoryResource::getPages();

    expect($pages)->toHaveKeys(['index', 'create', 'edit']);
});

it('provides schema and table', function (): void {
    $schema = CategoryResource::form(makeSchema());
    $table = CategoryResource::table(makeTable());

    expect($schema)->toBeInstanceOf(Filament\Schemas\Schema::class);
    expect($table)->toBeInstanceOf(Filament\Tables\Table::class);
});

it('builds list query via category service', function (): void {
    $query = CategoryResource::getEloquentQuery();

    expect($query)->toBeInstanceOf(Builder::class);
});

it('resolves the category service', function (): void {
    $method = new ReflectionMethod(CategoryResource::class, 'service');
    $method->setAccessible(true);
    $service = $method->invoke(null);

    expect($service)->toBeInstanceOf(ICategoryService::class);
});
