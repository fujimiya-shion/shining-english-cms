<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use App\Models\Contact;
use App\Services\Contact\IContactService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Reply')
                ->icon('heroicon-o-paper-airplane')
                ->form([
                    TextInput::make('subject')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('message')
                        ->required()
                        ->rows(6),
                ])
                ->action(function (array $data): void {
                    /** @var Contact $contact */
                    $contact = $this->record;

                    /** @var IContactService $service */
                    $service = app(IContactService::class);

                    $service->replyToContact(
                        contactId: $contact->id,
                        subject: (string) $data['subject'],
                        message: (string) $data['message'],
                    );

                    Notification::make()
                        ->title('Reply queued successfully.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'reply_subject',
                        'reply_message',
                        'replied_at',
                    ]);
                }),
        ];
    }
}
