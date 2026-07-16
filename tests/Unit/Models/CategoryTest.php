<?php

use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('defines fillable attributes', function (): void {
    $model = new Category;

    expect($model->getFillable())->toEqual([
        'name',
        'slug',
        'parent_id',
        'order',
    ]);
});

it('defines parent relation', function (): void {
    $model = new Category;

    $relation = $model->parent();

    expect($relation)->toBeInstanceOf(BelongsTo::class);
});

it('defines courses relation', function (): void {
    $model = new Category;

    $relation = $model->courses();

    expect($relation)->toBeInstanceOf(HasMany::class);
});

it('defines children relation', function (): void {
    $model = new Category;

    $relation = $model->children();

    expect($relation)->toBeInstanceOf(HasMany::class);
});
