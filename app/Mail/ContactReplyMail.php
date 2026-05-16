<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Contact $contact,
        public readonly string $replyMessage,
        public readonly string $replySubject,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->replySubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-reply-text',
            with: [
                'contact' => $this->contact,
                'replyMessage' => $this->replyMessage,
            ],
        );
    }
}
