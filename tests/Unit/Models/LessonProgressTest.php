<?php

use App\Models\LessonProgress;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('defines fillable attributes', function (): void {
    $model = new LessonProgress;

    expect($model->getFillable())->toEqual([
        'user_id',
        'course_id',
        'lesson_id',
        'is_current',
        'completed_at',
    ]);
});

it('casts attributes correctly', function (): void {
    $model = new LessonProgress;

    expect($model->getCasts())->toMatchArray([
        'is_current' => 'boolean',
        'completed_at' => 'datetime',
    ]);
});

it('defines user relation', function (): void {
    $method = new ReflectionMethod(LessonProgress::class, 'user');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new LessonProgress)->user())->toBeInstanceOf(BelongsTo::class);
});

it('defines course relation', function (): void {
    $method = new ReflectionMethod(LessonProgress::class, 'course');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new LessonProgress)->course())->toBeInstanceOf(BelongsTo::class);
});

it('defines lesson relation', function (): void {
    $method = new ReflectionMethod(LessonProgress::class, 'lesson');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new LessonProgress)->lesson())->toBeInstanceOf(BelongsTo::class);
});
