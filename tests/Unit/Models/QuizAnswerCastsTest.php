<?php

use App\Models\QuizAnswer;

it('casts is_correct and sort_order as integers', function (): void {
    $answer = new QuizAnswer;

    expect($answer->getCasts())->toMatchArray([
        'is_correct' => 'boolean',
        'sort_order' => 'integer',
    ]);
});

it('applies sorted scope', function (): void {
    $query = QuizAnswer::query()->sorted();

    $orders = $query->getQuery()->orders;
    expect($orders)->toHaveCount(2);
    expect($orders[0]['column'])->toBe('sort_order');
    expect($orders[1]['column'])->toBe('quiz_answers.id');
});
