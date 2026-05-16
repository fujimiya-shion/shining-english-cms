<?php

use App\Filament\Forms\Components\OptimizeFileUpload;
use App\Filament\Resources\Courses\Schemas\CourseForm;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

test('course form defines expected components', function (): void {
    $schema = CourseForm::configure(makeSchema());

    $components = schemaComponentMap($schema);

    expect(array_keys($components))->toEqual([
        'status',
        'name',
        'slug',
        'category_id',
        'level_id',
        'price',
        'rating',
        'learned',
        'thumbnail_source',
        'thumbnail',
        'thumbnail_file',
        'description',
    ]);

    expect($components['status'])->toBeInstanceOf(Toggle::class);
    expect($components['name'])->toBeInstanceOf(TextInput::class);
    expect($components['slug'])->toBeInstanceOf(TextInput::class);
    expect($components['category_id'])->toBeInstanceOf(Select::class);
    expect($components['level_id'])->toBeInstanceOf(Select::class);
    expect($components['price'])->toBeInstanceOf(TextInput::class);
    expect($components['rating'])->toBeInstanceOf(TextInput::class);
    expect($components['learned'])->toBeInstanceOf(TextInput::class);
    expect($components['thumbnail'])->toBeInstanceOf(Hidden::class);
    expect($components['thumbnail_file'])->toBeInstanceOf(FileUpload::class);
    expect($components['thumbnail_file'])->toBeInstanceOf(OptimizeFileUpload::class);
    expect($components['description'])->toBeInstanceOf(RichEditor::class);
});

test('course form marks required fields', function (): void {
    $schema = CourseForm::configure(makeSchema());

    $components = schemaComponentMap($schema);

    expect($components['name']->isRequired())->toBeTrue();
    expect($components['slug']->isRequired())->toBeFalse();
    expect($components['price']->isRequired())->toBeTrue();
    expect($components['status']->isRequired())->toBeTrue();
    expect($components['category_id']->isRequired())->toBeTrue();
    expect($components['level_id']->isRequired())->toBeTrue();
});

test('course form configures numeric price input', function (): void {
    $schema = CourseForm::configure(makeSchema());

    $components = schemaComponentMap($schema);

    expect($components['price']->isNumeric())->toBeTrue();
});
