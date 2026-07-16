<?php

use App\Filament\Forms\Components\QuizQuestionsInput;

it('hydrates string state via schema', function (): void {
    $schema = makeSchema();
    $component = QuizQuestionsInput::make('questions');
    $schema->components([$component]);

    $component->state('[{"content":"Q1","answers":[{"content":"A1","is_correct":true}]}]');

    expect($component->getQuestionsFromState())->toHaveCount(1);
});

it('returns empty array for null state', function (): void {
    $schema = makeSchema();
    $component = QuizQuestionsInput::make('questions');
    $schema->components([$component]);

    expect($component->getQuestionsFromState())->toBe([]);
});

it('handles array state', function (): void {
    $schema = makeSchema();
    $component = QuizQuestionsInput::make('questions');
    $schema->components([$component]);

    $component->state([['content' => 'Q2', 'answers' => []]]);

    expect($component->getQuestionsFromState())->toHaveCount(1);
});
