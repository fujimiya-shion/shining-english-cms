<?php

use App\Filament\Forms\Components\QuizQuestionsInput;

it('can be created with options', function (): void {
    $component = QuizQuestionsInput::make('q')
        ->label('Questions')
        ->minAnswers(2)
        ->maxAnswers(6)
        ->reorderable()
        ->radioCorrect();

    expect($component->getName())->toBe('q');
    expect($component->getLabel())->toBe('Questions');
    expect($component->getMinAnswers())->toBe(2);
    expect($component->getMaxAnswers())->toBe(6);
    expect($component->isReorderable())->toBeTrue();
    expect($component->isRadioCorrect())->toBeTrue();
});
