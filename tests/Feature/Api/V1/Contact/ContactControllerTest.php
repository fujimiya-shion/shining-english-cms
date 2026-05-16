<?php

use App\Jobs\SendContactSubmittedMailJob;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withHeader('Authorization', createDeveloperAccessToken());
    config()->set('recaptcha.secret_key', 'test-secret');
    config()->set('recaptcha.contact_action', 'contact');
});

it('stores contact when recaptcha is valid', function (): void {
    Bus::fake();
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
            'action' => 'contact',
            'score' => 0.9,
        ], 200),
    ]);

    $response = $this->postJson('/api/v1/contact', [
        'name' => 'Nguyen Van A',
        'email' => 'a@example.com',
        'message' => 'Can tu van khoa hoc.',
        'recaptcha_token' => 'token-ok',
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment([
        'message' => 'Contact submitted successfully.',
    ]);
    expect(Contact::query()->count())->toBe(1);
    Bus::assertDispatched(SendContactSubmittedMailJob::class);
});

it('returns validation errors for missing contact fields', function (): void {
    $response = $this->postJson('/api/v1/contact', []);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.name.0', 'Name is required.');
    $response->assertJsonPath('errors.email.0', 'Email is required.');
    $response->assertJsonPath('errors.message.0', 'Message is required.');
    $response->assertJsonPath('errors.recaptcha_token.0', 'reCAPTCHA token is required.');
});

it('rejects contact when recaptcha fails', function (): void {
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => false,
            'action' => 'contact',
            'score' => 0.1,
        ], 200),
    ]);

    $response = $this->postJson('/api/v1/contact', [
        'name' => 'Nguyen Van A',
        'email' => 'a@example.com',
        'message' => 'Can tu van khoa hoc.',
        'recaptcha_token' => 'token-bad',
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'reCAPTCHA verification failed.',
    ]);
});

it('rejects contact when recaptcha provider is unavailable', function (): void {
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([], 500),
    ]);

    $response = $this->postJson('/api/v1/contact', [
        'name' => 'Nguyen Van A',
        'email' => 'a@example.com',
        'message' => 'Can tu van khoa hoc.',
        'recaptcha_token' => 'token-error',
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'reCAPTCHA verification is unavailable. Please try again.',
    ]);
});
