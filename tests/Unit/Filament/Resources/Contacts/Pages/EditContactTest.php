<?php

use App\Filament\Resources\Contacts\Pages\EditContact;
use Filament\Actions\Action;

test('edit contact page defines reply header action', function (): void {
    $page = new EditContact;

    $actions = invokeProtectedMethod($page, 'getHeaderActions');

    expect(actionClassList($actions))->toEqual([
        Action::class,
    ]);
});

