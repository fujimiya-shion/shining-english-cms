<?php

use App\Models\CourseReview;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('defines fillable attributes', function (): void {
    $model = new CourseReview;

    expect($model->getFillable())->toEqual([
        'course_id',
        'user_id',
        'rating',
        'content',
    ]);
});

it('casts rating as integer', function (): void {
    $model = new CourseReview;

    expect($model->getCasts())->toHaveKey('rating', 'integer');
});

it('defines course relation', function (): void {
    $method = new ReflectionMethod(CourseReview::class, 'course');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new CourseReview)->course())->toBeInstanceOf(BelongsTo::class);
});

it('defines user relation', function (): void {
    $method = new ReflectionMethod(CourseReview::class, 'user');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new CourseReview)->user())->toBeInstanceOf(BelongsTo::class);
});

it('uses soft deletes', function (): void {
    $model = new CourseReview;

    expect(method_exists($model, 'trashed'))->toBeTrue();
});
