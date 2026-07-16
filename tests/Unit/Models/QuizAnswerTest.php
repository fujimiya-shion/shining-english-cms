<?php

use App\Models\QuizAnswer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('defines fillable attributes', function (): void {
    $model = new QuizAnswer;

    expect($model->getFillable())->toEqual([
        'quiz_question_id',
        'content',
        'is_correct',
        'sort_order',
    ]);
});

it('defines question relation', function (): void {
    $method = new ReflectionMethod(QuizAnswer::class, 'question');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
});
