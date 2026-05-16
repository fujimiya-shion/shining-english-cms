<?php

use App\Models\LessonNote;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('defines fillable attributes', function (): void {
    $model = new LessonNote;

    expect($model->getFillable())->toEqual([
        'lesson_id',
        'user_id',
        'content',
    ]);
});

it('defines lesson relation', function (): void {
    $method = new ReflectionMethod(LessonNote::class, 'lesson');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new LessonNote)->lesson())->toBeInstanceOf(BelongsTo::class);
});

it('defines user relation', function (): void {
    $method = new ReflectionMethod(LessonNote::class, 'user');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new LessonNote)->user())->toBeInstanceOf(BelongsTo::class);
});

it('uses soft deletes', function (): void {
    $model = new LessonNote;

    expect(method_exists($model, 'trashed'))->toBeTrue();
});
