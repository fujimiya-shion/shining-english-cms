<?php

namespace App\Jobs;

use App\Mail\ContactSubmittedMail;
use App\Models\Contact;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendContactSubmittedMailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $contactId,
    ) {}

    public function handle(): void
    {
        $contact = Contact::query()->find($this->contactId);
        if (! $contact instanceof Contact) {
            return;
        }

        $companyEmail = (string) config('mail.company_email');
        if ($companyEmail === '') {
            return;
        }

        try {
            Mail::to($companyEmail)->send(new ContactSubmittedMail($contact));
            Log::info('Contact submission mail sent to company.', [
                'contact_id' => $this->contactId,
                'company_email' => $companyEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send contact submission mail to company.', [
                'contact_id' => $this->contactId,
                'company_email' => $companyEmail,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
