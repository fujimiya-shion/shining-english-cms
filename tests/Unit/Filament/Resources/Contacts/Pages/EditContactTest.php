<?php

use App\Filament\Resources\Contacts\Pages\EditContact;
use App\Models\Contact;
use App\Services\Contact\IContactService;
use Filament\Actions\Action;

test('edit contact page defines reply header action', function (): void {
    $page = new EditContact;

    $actions = invokeProtectedMethod($page, 'getHeaderActions');

    expect(actionClassList($actions))->toEqual([
        Action::class,
    ]);
});

test('edit contact reply action delegates to contact service and refreshes fields', function (): void {
    $page = new class extends EditContact
    {
        public array $refreshed = [];

        public function refreshFormData(array $statePaths): void
        {
            $this->refreshed = $statePaths;
        }
    };

    $contact = new Contact;
    $contact->id = 123;

    $reflection = new ReflectionProperty($page, 'record');
    $reflection->setValue($page, $contact);

    $service = Mockery::mock(IContactService::class);
    $service->shouldReceive('replyToContact')
        ->once()
        ->with(123, 'Reply subject', 'Reply body');
    app()->instance(IContactService::class, $service);

    $actions = invokeProtectedMethod($page, 'getHeaderActions');
    $actions[0]->getActionFunction()([
        'subject' => 'Reply subject',
        'message' => 'Reply body',
    ]);

    expect($page->refreshed)->toBe([
        'reply_subject',
        'reply_message',
        'replied_at',
    ]);
});
