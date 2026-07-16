<?php

use App\Filament\Forms\Components\QuizQuestionsInput;

it('can be created', function (): void {
    $component = QuizQuestionsInput::make('questions');

    expect($component->getName())->toBe('questions');
    expect($component->getMinAnswers())->toBe(2);
    expect($component->getMaxAnswers())->toBe(10);
});

it('configures options', function (): void {
    $component = QuizQuestionsInput::make('questions')
        ->minAnswers(2)
        ->maxAnswers(6)
        ->reorderable()
        ->radioCorrect();

    expect($component->getMinAnswers())->toBe(2);
    expect($component->getMaxAnswers())->toBe(6);
    expect($component->isReorderable())->toBeTrue();
    expect($component->isRadioCorrect())->toBeTrue();
});
