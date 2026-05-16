<?php

use App\Filament\Resources\Contacts\Tables\ContactsTable;
use Filament\Actions\EditAction;

test('contacts table defines expected columns', function (): void {
    $table = ContactsTable::configure(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'id',
        'name',
        'email',
        'message',
        'replied_at',
        'created_at',
    ]);
});

test('contacts table registers record actions', function (): void {
    $table = ContactsTable::configure(makeTable());

    $actions = $table->getRecordActions();

    expect(actionClassList($actions))->toEqual([EditAction::class]);
});

