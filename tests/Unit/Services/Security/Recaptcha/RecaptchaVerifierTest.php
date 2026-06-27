<?php

use App\Services\Security\Recaptcha\IRecaptchaVerifier;
use App\Services\Security\Recaptcha\RecaptchaVerificationException;
use App\Services\Security\Recaptcha\RecaptchaVerifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('implements service contract', function (): void {
    $service = new RecaptchaVerifier;
    expect($service)->toBeInstanceOf(IRecaptchaVerifier::class);
});

it('throws failed when secret key is empty', function (): void {
    config(['recaptcha.secret_key' => '']);

    $service = new RecaptchaVerifier;

    expect(fn () => $service->verifyOrFail('some-token', 'login'))
        ->toThrow(RecaptchaVerificationException::class);
});

it('throws failed when token is empty', function (): void {
    config(['recaptcha.secret_key' => 'valid-key']);

    $service = new RecaptchaVerifier;

    expect(fn () => $service->verifyOrFail('', 'login'))
        ->toThrow(RecaptchaVerificationException::class);
});

it('throws unavailable when HTTP request fails', function (): void {
    config(['recaptcha.secret_key' => 'valid-key']);

    Http::fake(fn () => throw new RuntimeException('Connection failed'));

    $service = new RecaptchaVerifier;

    expect(fn () => $service->verifyOrFail('valid-token', 'login'))
        ->toThrow(RecaptchaVerificationException::class);
});

it('throws unavailable when HTTP response is not ok', function (): void {
    config(['recaptcha.secret_key' => 'valid-key']);

    Http::fake(['*' => Http::response('', 500)]);

    $service = new RecaptchaVerifier;

    expect(fn () => $service->verifyOrFail('valid-token', 'login'))
        ->toThrow(RecaptchaVerificationException::class);
});

it('throws failed when response has no success field', function (): void {
    config(['recaptcha.secret_key' => 'valid-key']);

    Http::fake(['*' => Http::response(['action' => 'login', 'score' => 0.9], 200)]);

    $service = new RecaptchaVerifier;

    expect(fn () => $service->verifyOrFail('valid-token', 'login'))
        ->toThrow(RecaptchaVerificationException::class);
});

it('throws failed when action does not match', function (): void {
    config(['recaptcha.secret_key' => 'valid-key']);

    Http::fake(['*' => Http::response(['success' => true, 'action' => 'register', 'score' => 0.9], 200)]);

    $service = new RecaptchaVerifier;

    expect(fn () => $service->verifyOrFail('valid-token', 'login'))
        ->toThrow(RecaptchaVerificationException::class);
});

it('throws failed when score is below minimum', function (): void {
    config(['recaptcha.secret_key' => 'valid-key']);
    config(['recaptcha.min_score' => 0.5]);

    Http::fake(['*' => Http::response(['success' => true, 'action' => 'login', 'score' => 0.3], 200)]);

    $service = new RecaptchaVerifier;

    expect(fn () => $service->verifyOrFail('valid-token', 'login'))
        ->toThrow(RecaptchaVerificationException::class);
});

it('passes when all checks succeed', function (): void {
    config(['recaptcha.secret_key' => 'valid-key']);
    config(['recaptcha.min_score' => 0.5]);

    Http::fake(['*' => Http::response(['success' => true, 'action' => 'login', 'score' => 0.9], 200)]);

    $service = new RecaptchaVerifier;

    expect($service->verifyOrFail('valid-token', 'login'))->toBeNull();
});
