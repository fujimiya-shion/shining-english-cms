<?php

use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Pages\EditAdmin;
use App\Filament\Resources\Admins\Pages\ListAdmins;
use App\Filament\Resources\Admins\Schemas\AdminForm;
use App\Filament\Resources\Admins\Tables\AdminsTable;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Tests\TestCase;

uses(TestCase::class);

it('admin form defines expected components', function (): void {
    $schema = AdminForm::configure(makeSchema());

    $components = schemaComponentMap($schema);

    expect(array_keys($components))->toEqual([
        'name',
        'email',
        'password',
        'roles',
    ]);

    expect($components['name'])->toBeInstanceOf(TextInput::class);
    expect($components['email'])->toBeInstanceOf(TextInput::class);
    expect($components['password'])->toBeInstanceOf(TextInput::class);
    expect($components['roles'])->toBeInstanceOf(Select::class);
});

it('admin form marks create fields required', function (): void {
    $schema = AdminForm::configure(makeSchema());

    $components = schemaComponentMap($schema);

    expect($components['name']->isRequired())->toBeTrue();
    expect($components['email']->isRequired())->toBeTrue();
});

it('admins table defines expected columns', function (): void {
    $table = AdminsTable::configure(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'name',
        'email',
        'email_verified_at',
        'created_at',
        'updated_at',
    ]);
});

it('admins table registers record actions', function (): void {
    $table = AdminsTable::configure(makeTable());

    $actions = $table->getRecordActions();

    expect(actionClassList($actions))->toEqual([
        EditAction::class,
        \Filament\Actions\DeleteAction::class,
    ]);
});

it('create admin page has correct resource', function (): void {
    $page = new CreateAdmin;

    expect($page::getResource())->toBe(\App\Filament\Resources\Admins\AdminResource::class);
});

it('edit admin page has delete action', function (): void {
    $page = new EditAdmin;

    $actions = $page->getHeaderActions();
    expect(actionClassList($actions))->toEqual([DeleteAction::class]);
});

it('list admins page has create action', function (): void {
    $page = new ListAdmins;

    $actions = $page->getHeaderActions();
    expect(actionClassList($actions))->toEqual([CreateAction::class]);
});
