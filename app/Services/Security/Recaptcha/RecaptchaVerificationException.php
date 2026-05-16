<?php

namespace App\Services\Security\Recaptcha;

use RuntimeException;

class RecaptchaVerificationException extends RuntimeException
{
    public static function failed(): self
    {
        return new self('reCAPTCHA verification failed.');
    }

    public static function unavailable(): self
    {
        return new self('reCAPTCHA verification is unavailable. Please try again.');
    }
}

