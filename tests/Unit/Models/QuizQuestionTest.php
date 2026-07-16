<?php

use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('defines fillable attributes', function (): void {
    $model = new QuizQuestion;

    expect($model->getFillable())->toEqual([
        'quiz_id',
        'content',
        'sort_order',
    ]);
});

it('defines quiz relation', function (): void {
    $method = new ReflectionMethod(QuizQuestion::class, 'quiz');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
});

it('defines answers relation', function (): void {
    $method = new ReflectionMethod(QuizQuestion::class, 'answers');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
});
