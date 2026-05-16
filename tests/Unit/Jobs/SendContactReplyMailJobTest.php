<?php

use App\Jobs\SendContactReplyMailJob;
use App\Mail\ContactReplyMail;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('skips reply mail when contact does not exist', function (): void {
    Mail::fake();
    Log::spy();

    $job = new SendContactReplyMailJob(999999, 'Subject', 'Message');
    $job->handle();

    Mail::assertNothingSent();
});

it('sends reply mail to requester', function (): void {
    Mail::fake();
    Log::spy();

    $contact = Contact::factory()->create([
        'email' => 'requester@example.com',
    ]);

    $job = new SendContactReplyMailJob($contact->id, 'Re: Support', 'Cam on ban da lien he.');
    $job->handle();

    Mail::assertSent(ContactReplyMail::class, function (ContactReplyMail $mail) use ($contact): bool {
        return $mail->hasTo('requester@example.com')
            && $mail->contact->id === $contact->id
            && $mail->replySubject === 'Re: Support';
    });
});
