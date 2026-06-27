<?php

use App\Models\Blog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defines fillable attributes', function (): void {
    $model = new Blog;

    expect($model->getFillable())->toEqual([
        'title',
        'description',
        'short_description',
        'thumbnail',
        'content',
        'slug',
        'status',
        'tag_id',
    ]);
});

it('casts attributes correctly', function (): void {
    $model = new Blog;

    expect($model->getCasts())->toMatchArray([
        'status' => 'boolean',
    ]);
});

it('defines tag relation', function (): void {
    $method = new ReflectionMethod(Blog::class, 'tag');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new Blog)->tag())->toBeInstanceOf(BelongsTo::class);
});
