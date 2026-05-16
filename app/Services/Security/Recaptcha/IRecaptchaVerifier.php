<?php

namespace App\Services\Security\Recaptcha;

interface IRecaptchaVerifier
{
    public function verifyOrFail(string $token, string $expectedAction, ?string $ipAddress = null): void;
}

