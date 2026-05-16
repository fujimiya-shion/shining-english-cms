<?php

namespace App\Services\Contact;

use App\Models\Contact;
use App\Services\IService;

interface IContactService extends IService
{
    public function submitContact(
        string $name,
        string $email,
        string $message,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Contact;

    public function replyToContact(int $contactId, string $subject, string $message): Contact;
}

