<?php

use App\Filament\Forms\Components\OptimizeFileUpload;
use App\Filament\Resources\Users\Schemas\UserForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

test('user form defines expected components', function (): void {
    $schema = UserForm::configure(makeSchema());

    $components = $schema->getComponents(withActions: false, withHidden: true);
    $grid = collect($components)->first(fn (object $component): bool => $component instanceof Grid);

    expect($grid)->toBeInstanceOf(Grid::class);

    $rawChildComponents = invokeProtectedMethod($grid, 'getDefaultChildComponents');
    $gridSchema = $rawChildComponents instanceof \Filament\Schemas\Schema
        ? $rawChildComponents
        : \Filament\Schemas\Schema::make()->components($rawChildComponents);
    $gridChildren = $gridSchema->getComponents(withActions: false, withHidden: true);

    $childNames = array_values(array_filter(array_map(
        fn (object $child): ?string => method_exists($child, 'getName') ? $child->getName() : null,
        $gridChildren,
    )));

    expect($childNames)->toEqual([
        'name',
        'nickname',
        'email',
        'phone',
        'birthday',
        'city_id',
        'avatar',
        'email_verified_at',
        'password',
    ]);

    expect($gridChildren[0] ?? null)->toBeInstanceOf(TextInput::class);
    expect($gridChildren[1] ?? null)->toBeInstanceOf(TextInput::class);
    expect($gridChildren[2] ?? null)->toBeInstanceOf(TextInput::class);
    expect($gridChildren[3] ?? null)->toBeInstanceOf(TextInput::class);
    expect($gridChildren[4] ?? null)->toBeInstanceOf(DatePicker::class);
    expect($gridChildren[5] ?? null)->toBeInstanceOf(Select::class);
    expect($gridChildren[6] ?? null)->toBeInstanceOf(FileUpload::class);
    expect($gridChildren[6] ?? null)->toBeInstanceOf(OptimizeFileUpload::class);
    expect($gridChildren[7] ?? null)->toBeInstanceOf(DateTimePicker::class);
    expect($gridChildren[8] ?? null)->toBeInstanceOf(TextInput::class);
});

test('user form marks required fields', function (): void {
    $schema = UserForm::configure(makeSchema());

    $components = schemaComponentMap($schema);

    expect($components['name']->isRequired())->toBeTrue();
    expect($components['email']->isRequired())->toBeTrue();
    expect($components['phone']->isRequired())->toBeTrue();
});
