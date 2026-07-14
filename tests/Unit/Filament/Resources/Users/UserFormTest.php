<?php

use App\Filament\Forms\Components\OptimizeFileUpload;
use App\Filament\Resources\Users\Schemas\UserForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

test('user form defines expected components', function (): void {
    $schema = UserForm::configure(makeSchema());
    $components = schemaComponentMap($schema);

    expect(array_keys($components))->toEqual([
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

    expect($components['name'])->toBeInstanceOf(TextInput::class);
    expect($components['nickname'])->toBeInstanceOf(TextInput::class);
    expect($components['email'])->toBeInstanceOf(TextInput::class);
    expect($components['phone'])->toBeInstanceOf(TextInput::class);
    expect($components['birthday'])->toBeInstanceOf(DatePicker::class);
    expect($components['city_id'])->toBeInstanceOf(Select::class);
    expect($components['avatar'])->toBeInstanceOf(FileUpload::class);
    expect($components['avatar'])->toBeInstanceOf(OptimizeFileUpload::class);
    expect($components['email_verified_at'])->toBeInstanceOf(DateTimePicker::class);
    expect($components['password'])->toBeInstanceOf(TextInput::class);
});

test('user form marks required fields', function (): void {
    $schema = UserForm::configure(makeSchema());
    $components = schemaComponentMap($schema);

    expect($components['name']->isRequired())->toBeTrue();
    expect($components['email']->isRequired())->toBeTrue();
    expect($components['phone']->isRequired())->toBeTrue();
});
