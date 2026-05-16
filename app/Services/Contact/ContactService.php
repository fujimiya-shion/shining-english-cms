<?php

namespace App\Services\Contact;

use App\Jobs\SendContactReplyMailJob;
use App\Jobs\SendContactSubmittedMailJob;
use App\Models\Contact;
use App\Repositories\Contact\IContactRepository;
use App\Services\Service;

class ContactService extends Service implements IContactService
{
    public function __construct(IContactRepository $repository)
    {
        parent::__construct($repository);
    }

    public function submitContact(
        string $name,
        string $email,
        string $message,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Contact {
        /** @var Contact $contact */
        $contact = $this->create([
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        SendContactSubmittedMailJob::dispatch($contact->id);

        return $contact;
    }

    public function replyToContact(int $contactId, string $subject, string $message): Contact
    {
        /** @var Contact $contact */
        $contact = $this->update($contactId, [
            'reply_subject' => $subject,
            'reply_message' => $message,
            'replied_at' => now(),
        ]);

        SendContactReplyMailJob::dispatch($contact->id, $subject, $message);

        return $contact;
    }
}

