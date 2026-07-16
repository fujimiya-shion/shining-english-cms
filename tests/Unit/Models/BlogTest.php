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
        'order',
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

it('computes read time and normalizes thumbnail URLs', function (): void {
    config(['app.url' => 'https://app.test']);

    $empty = new Blog(['content' => '']);
    $long = new Blog(['content' => str_repeat('word ', 401)]);

    expect($empty->read_time_minutes)->toBe(1);
    expect($long->read_time_minutes)->toBe(3);

    expect((new Blog)->getThumbnailAttribute(null))->toBeNull();
    expect((new Blog)->getThumbnailAttribute('https://cdn.test/blog.jpg'))->toBe('https://cdn.test/blog.jpg');
    expect((new Blog)->getThumbnailAttribute('/storage/blogs/a.jpg'))->toBe('https://app.test/storage/blogs/a.jpg');
    expect((new Blog)->getThumbnailAttribute('public/blogs/a.jpg'))->toBe('https://app.test/storage/blogs/a.jpg');
    expect((new Blog)->getThumbnailAttribute('blogs/a.jpg'))->toBe('https://app.test/storage/blogs/a.jpg');
});
