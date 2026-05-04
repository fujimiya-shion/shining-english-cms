<?php

use App\Models\Course;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('defines fillable attributes', function (): void {
    $model = new Course;

    expect($model->getFillable())->toEqual([
        'name',
        'slug',
        'price',
        'status',
        'thumbnail',
        'category_id',
        'level_id',
        'description',
        'rating',
        'learned',
    ]);
});

it('defines category relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'category');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new Course)->category())->toBeInstanceOf(BelongsTo::class);
});

it('defines lessons relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'lessons');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
    expect((new Course)->lessons())->toBeInstanceOf(HasMany::class);
});

it('defines lesson groups relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'lessonGroups');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
    expect((new Course)->lessonGroups())->toBeInstanceOf(HasMany::class);
});

it('defines level relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'level');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new Course)->level())->toBeInstanceOf(BelongsTo::class);
});

it('defines enrollments relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'enrollments');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
    expect((new Course)->enrollments())->toBeInstanceOf(HasMany::class);
});

it('defines reviews relation', function (): void {
    $method = new ReflectionMethod(Course::class, 'reviews');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
    expect((new Course)->reviews())->toBeInstanceOf(HasMany::class);
});

it('uses soft deletes', function (): void {
    $model = new Course;

    expect(method_exists($model, 'trashed'))->toBeTrue();
});
