<?php

use App\Filament\Resources\Categories\Schemas\CategoryForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

it('builds name slug and parent fields', function (): void {
    $schema = CategoryForm::configure(makeSchema());
    $components = schemaComponentMap($schema);

    expect(array_keys($components))->toEqual([
        'name',
        'slug',
        'parent_id',
    ]);

    expect($components['name'])->toBeInstanceOf(TextInput::class);
    expect($components['slug'])->toBeInstanceOf(TextInput::class);
    expect($components['parent_id'])->toBeInstanceOf(Select::class);
});
