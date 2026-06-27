<?php

use App\Filament\Resources\Users\Tables\UsersTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

test('users table defines expected columns', function (): void {
    $table = UsersTable::configure(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'avatar',
        'name',
        'nickname',
        'email',
        'phone',
        'birthday',
        'city.name',
        'email_verified_at',
        'created_at',
        'updated_at',
    ]);
});

test('users table registers filters', function (): void {
    $table = UsersTable::configure(makeTable());

    $filters = array_values($table->getFilters());

    expect(actionClassList($filters))->toEqual([
        SelectFilter::class,
        TernaryFilter::class,
    ]);
});

test('users table registers actions', function (): void {
    $table = UsersTable::configure(makeTable());

    $recordActions = $table->getRecordActions();
    expect(actionClassList($recordActions))->toEqual([
        EditAction::class,
        \Filament\Actions\Action::class,
        \Filament\Actions\DeleteAction::class,
    ]);

    $toolbarActions = $table->getToolbarActions();
    expect($toolbarActions)->toHaveCount(1);
    expect($toolbarActions[0])->toBeInstanceOf(BulkActionGroup::class);

    $groupActions = $toolbarActions[0]->getActions();
    expect(actionClassList($groupActions))->toEqual([DeleteBulkAction::class]);
});
