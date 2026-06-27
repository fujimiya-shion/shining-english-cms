<?php

namespace App\Services\Security\Recaptcha;

use Illuminate\Support\Facades\Http;
use Throwable;

class RecaptchaVerifier implements IRecaptchaVerifier
{
    public function verifyOrFail(string $token, string $expectedAction, ?string $ipAddress = null): void
    {
        $secretKey = (string) config('recaptcha.secret_key');
        $minScore = (float) config('recaptcha.min_score');
        $timeoutSeconds = (int) config('recaptcha.timeout_seconds', 8);
        $verifyUrl = (string) config('recaptcha.verify_url');

        if ($secretKey === '' || $token === '') {
            throw RecaptchaVerificationException::failed();
        }

        try {
            $response = Http::timeout($timeoutSeconds)->asForm()->post($verifyUrl, [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $ipAddress,
            ]);
        } catch (Throwable) {
            throw RecaptchaVerificationException::unavailable();
        }

        if (! $response->ok()) {
            throw RecaptchaVerificationException::unavailable();
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw RecaptchaVerificationException::failed();
        }

        $isSuccess = ($payload['success'] ?? false) === true;
        $action = is_string($payload['action'] ?? null) ? $payload['action'] : null;
        $score = is_numeric($payload['score'] ?? null) ? (float) $payload['score'] : null;

        if (! $isSuccess || $action !== $expectedAction || $score === null || $score < $minScore) {
            throw RecaptchaVerificationException::failed();
        }
    }
}
