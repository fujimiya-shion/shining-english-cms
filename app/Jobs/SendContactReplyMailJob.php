<?php

namespace App\Jobs;

use App\Mail\ContactReplyMail;
use App\Models\Contact;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendContactReplyMailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $contactId,
        private readonly string $subject,
        private readonly string $message,
    ) {}

    public function handle(): void
    {
        $contact = Contact::query()->find($this->contactId);
        if (! $contact instanceof Contact) {
            return;
        }

        try {
            Mail::to($contact->email)->send(new ContactReplyMail(
                contact: $contact,
                replyMessage: $this->message,
                replySubject: $this->subject,
            ));
            Log::info('Contact reply mail sent.', [
                'contact_id' => $this->contactId,
                'email' => $contact->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send contact reply mail.', [
                'contact_id' => $this->contactId,
                'email' => $contact->email,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

