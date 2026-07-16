<?php

use App\Filament\Forms\Components\QuizQuestionsInput;

it('can be created', function (): void {
    $component = QuizQuestionsInput::make('questions');

    expect($component)->toBeInstanceOf(QuizQuestionsInput::class);
    expect($component->getName())->toBe('questions');
});

it('has default min answers', function (): void {
    $component = QuizQuestionsInput::make('questions');

    expect($component->getMinAnswers())->toBe(2);
});

it('has default max answers', function (): void {
    $component = QuizQuestionsInput::make('questions');

    expect($component->getMaxAnswers())->toBe(10);
});

it('returns empty questions when state is invalid', function (): void {
    $component = QuizQuestionsInput::make('questions');

    expect($component->getQuestionsFromState())->toBe([]);
});

it('parses json state to questions', function (): void {
    $component = QuizQuestionsInput::make('questions');

    $component->state('[{"id":1,"content":"Q1","answers":[{"content":"A1","is_correct":true}]}]');

    $questions = $component->getQuestionsFromState();
    expect($questions)->toHaveCount(1);
    expect($questions[0]['content'])->toBe('Q1');
    expect($questions[0]['answers'])->toHaveCount(1);
});

it('configures min max answers', function (): void {
    $component = QuizQuestionsInput::make('questions')
        ->minAnswers(2)
        ->maxAnswers(6);

    expect($component->getMinAnswers())->toBe(2);
    expect($component->getMaxAnswers())->toBe(6);
});

it('configures reorderable', function (): void {
    $component = QuizQuestionsInput::make('questions')->reorderable();

    expect($component->isReorderable())->toBeTrue();
});

it('configures radio correct', function (): void {
    $component = QuizQuestionsInput::make('questions')->radioCorrect();

    expect($component->isRadioCorrect())->toBeTrue();
});
