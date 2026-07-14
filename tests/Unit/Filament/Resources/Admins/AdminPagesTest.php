<?php

use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Schemas\AdminForm;
use App\Filament\Resources\Admins\Tables\AdminsTable;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

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

it('admin resource returns correct service', function (): void {
    $resource = new \App\Filament\Resources\Admins\AdminResource;

    $service = invokeProtectedMethod($resource, 'service');

    expect($service)->toBeInstanceOf(\App\Services\Admin\IAdminService::class);
});

it('admin resource configures form', function (): void {
    $schema = \App\Filament\Resources\Admins\AdminResource::form(makeSchema());

    expect($schema)->not->toBeNull();
});

it('admin resource configures table', function (): void {
    $table = \App\Filament\Resources\Admins\AdminResource::table(makeTable());

    expect($table)->not->toBeNull();
});

it('admin resource returns empty relations', function (): void {
    expect(\App\Filament\Resources\Admins\AdminResource::getRelations())->toBe([]);
});

it('admin resource returns pages', function (): void {
    $pages = \App\Filament\Resources\Admins\AdminResource::getPages();

    expect($pages)->toHaveKeys(['index', 'create', 'edit']);
});

it('create admin page has correct resource', function (): void {
    $page = new CreateAdmin;

    expect($page::getResource())->toBe(\App\Filament\Resources\Admins\AdminResource::class);
});

it('create admin page sets email_verified_at on create', function (): void {
    $page = new CreateAdmin;

    $result = invokeProtectedMethod($page, 'mutateFormDataBeforeCreate', [[]]);

    expect($result['email_verified_at'])->not->toBeNull();
});

it('edit admin page has delete header action', function (): void {
    $page = new \App\Filament\Resources\Admins\Pages\EditAdmin;

    $actions = invokeProtectedMethod($page, 'getHeaderActions');

    expect($actions[0])->toBeInstanceOf(\Filament\Actions\DeleteAction::class);
});

it('list admins page has create header action', function (): void {
    $page = new \App\Filament\Resources\Admins\Pages\ListAdmins;

    $actions = invokeProtectedMethod($page, 'getHeaderActions');

    expect($actions[0])->toBeInstanceOf(\Filament\Actions\CreateAction::class);
});
