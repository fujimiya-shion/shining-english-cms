<?php

use App\Mail\ContactReplyMail;
use App\Models\Contact;
use Tests\TestCase;

uses(TestCase::class);

it('builds contact reply mail content', function (): void {
    $contact = Contact::factory()->make([
        'name' => 'Nguyen Van A',
        'email' => 'a@example.com',
    ]);

    $mail = new ContactReplyMail($contact, 'Cam on ban da lien he.', 'Re: Tu van');

    expect($mail->envelope()->subject)->toBe('Re: Tu van');
    expect($mail->content()->text)->toBe('emails.contact-reply-text');
});
