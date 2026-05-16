<?php

use App\Mail\ContactSubmittedMail;
use App\Models\Contact;
use Tests\TestCase;

uses(TestCase::class);

it('builds contact submitted mail content', function (): void {
    $contact = Contact::factory()->make([
        'name' => 'Nguyen Van A',
        'email' => 'a@example.com',
    ]);

    $mail = new ContactSubmittedMail($contact);

    expect($mail->envelope()->subject)->toBe('New contact request received');
    expect($mail->content()->view)->toBe('emails.contact-submitted-text');
});
