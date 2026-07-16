<?php

use App\Models\QuizQuestion;

it('casts sort_order as integer', function (): void {
    $question = new QuizQuestion;

    expect($question->getCasts())->toMatchArray([
        'sort_order' => 'integer',
    ]);
});

it('applies sorted scope', function (): void {
    $query = QuizQuestion::query()->sorted();

    $orders = $query->getQuery()->orders;
    expect($orders)->toHaveCount(2);
    expect($orders[0]['column'])->toBe('sort_order');
    expect($orders[1]['column'])->toBe('quiz_questions.id');
});
