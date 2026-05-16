<?php

use App\Jobs\SendContactSubmittedMailJob;
use App\Mail\ContactSubmittedMail;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('skips when contact does not exist', function (): void {
    Mail::fake();
    Log::spy();

    $job = new SendContactSubmittedMailJob(999999);
    $job->handle();

    Mail::assertNothingSent();
});

it('skips when company email is missing', function (): void {
    Mail::fake();
    Log::spy();
    config()->set('mail.company_email', '');

    $contact = Contact::factory()->create();

    $job = new SendContactSubmittedMailJob($contact->id);
    $job->handle();

    Mail::assertNothingSent();
});

it('sends company contact notification mail', function (): void {
    Mail::fake();
    Log::spy();
    config()->set('mail.company_email', 'company@example.com');

    $contact = Contact::factory()->create([
        'email' => 'user@example.com',
    ]);

    $job = new SendContactSubmittedMailJob($contact->id);
    $job->handle();

    Mail::assertSent(ContactSubmittedMail::class, function (ContactSubmittedMail $mail) use ($contact): bool {
        return $mail->hasTo('company@example.com') && $mail->contact->id === $contact->id;
    });
});
