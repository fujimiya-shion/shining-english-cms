<?php

use App\Filament\Forms\Components\QuizQuestionsInput;

it('configures min and max questions', function (): void {
    $component = QuizQuestionsInput::make('q')
        ->minQuestions(1)
        ->maxQuestions(5);

    expect($component->getMinQuestions())->toBe(1);
    expect($component->getMaxQuestions())->toBe(5);
});

it('parses questions from state', function (): void {
    $component = QuizQuestionsInput::make('q');
    $component->state('[{"content":"Q1","answers":[{"content":"A1","is_correct":true}]}]');

    $questions = $component->getQuestionsFromState();
    expect($questions)->toHaveCount(1);
    expect($questions[0]['content'])->toBe('Q1');
});

it('returns empty array when state is invalid json', function (): void {
    $component = QuizQuestionsInput::make('q');
    $component->state('invalid');

    expect($component->getQuestionsFromState())->toBe([]);
});

it('returns empty array when state is empty', function (): void {
    $component = QuizQuestionsInput::make('q');
    expect($component->getQuestionsFromState())->toBe([]);
});

it('handles array state via parseValue', function (): void {
    $component = QuizQuestionsInput::make('q');
    $component->state([['content' => 'test']]);

    expect($component->getQuestionsFromState())->toHaveCount(1);
});
