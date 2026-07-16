<?php

use App\Filament\Resources\Quizzes\Schemas\QuizForm;
use Filament\Forms\Components\TextInput;

test('quiz form defines expected components', function (): void {
    $schema = QuizForm::configure(makeSchema());

    $components = schemaComponentMap($schema);

    expect(array_keys($components))->toEqual([
        'name',
        'pass_percent',
    ]);

    expect($components['name'])->toBeInstanceOf(TextInput::class);
    expect($components['pass_percent'])->toBeInstanceOf(TextInput::class);
});

test('quiz form marks required fields', function (): void {
    $schema = QuizForm::configure(makeSchema());

    $components = schemaComponentMap($schema);

    expect($components['name']->isRequired())->toBeTrue();
    expect($components['pass_percent']->isRequired())->toBeTrue();
});

test('quiz form configures numeric pass percent', function (): void {
    $schema = QuizForm::configure(makeSchema());

    $components = schemaComponentMap($schema);

    expect($components['pass_percent']->isNumeric())->toBeTrue();
});
