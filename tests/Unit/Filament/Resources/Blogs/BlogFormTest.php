<?php

use App\Filament\Forms\Components\OptimizeFileUpload;
use App\Filament\Resources\Blogs\Schemas\BlogForm;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

test('blog form defines expected components', function (): void {
    $schema = BlogForm::configure(makeSchema());
    $components = schemaComponentMap($schema);

    expect(array_keys($components))->toEqual([
        'status',
        'title',
        'slug',
        'customize_slug',
        'tag_id',
        'short_description',
        'description',
        'content',
        'thumbnail_source',
        'thumbnail',
        'thumbnail_file',
    ]);

    expect($components['status'])->toBeInstanceOf(Toggle::class);
    expect($components['title'])->toBeInstanceOf(TextInput::class);
    expect($components['slug'])->toBeInstanceOf(TextInput::class);
    expect($components['customize_slug'])->toBeInstanceOf(Toggle::class);
    expect($components['tag_id'])->toBeInstanceOf(Select::class);
    expect($components['short_description'])->toBeInstanceOf(Textarea::class);
    expect($components['description'])->toBeInstanceOf(Textarea::class);
    expect($components['content'])->toBeInstanceOf(RichEditor::class);
    expect($components['thumbnail_source'])->toBeInstanceOf(Select::class);
    expect($components['thumbnail'])->toBeInstanceOf(Hidden::class);
    expect($components['thumbnail_file'])->toBeInstanceOf(OptimizeFileUpload::class);
});

test('blog form marks required and dehydrated fields', function (): void {
    $schema = BlogForm::configure(makeSchema());
    $components = schemaComponentMap($schema);

    expect($components['status']->isRequired())->toBeTrue();
    expect($components['title']->isRequired())->toBeTrue();
    expect($components['description']->isRequired())->toBeTrue();
    expect($components['content']->isRequired())->toBeTrue();
    expect($components['customize_slug']->isDehydrated())->toBeFalse();
    expect($components['thumbnail']->isDehydrated())->toBeTrue();
    expect($components['thumbnail_file']->isDehydrated())->toBeTrue();
});
