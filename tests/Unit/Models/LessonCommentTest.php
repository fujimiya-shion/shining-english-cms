<?php

use App\Models\LessonComment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('defines fillable attributes', function (): void {
    $model = new LessonComment;

    expect($model->getFillable())->toEqual([
        'lesson_id',
        'user_id',
        'content',
    ]);
});

it('defines lesson relation', function (): void {
    $method = new ReflectionMethod(LessonComment::class, 'lesson');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new LessonComment)->lesson())->toBeInstanceOf(BelongsTo::class);
});

it('defines user relation', function (): void {
    $method = new ReflectionMethod(LessonComment::class, 'user');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new LessonComment)->user())->toBeInstanceOf(BelongsTo::class);
});

it('uses soft deletes', function (): void {
    $model = new LessonComment;

    expect(method_exists($model, 'trashed'))->toBeTrue();
});
