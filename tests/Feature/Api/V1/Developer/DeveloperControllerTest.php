<?php

use App\Models\Developer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

uses(DatabaseTransactions::class);

it('returns a developer access token for valid credentials', function (): void {
    $email = 'developer-'.Str::lower(Str::random(10)).'@example.com';

    $developer = Developer::query()->create([
        'email' => $email,
        'password' => 'secret123',
    ]);

    $response = $this->postJson('/api/v1/access-token', [
        'email' => $email,
        'password' => 'secret123',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('message', 'OK');

    $plainTextToken = $response->json('data.access_token');

    expect($plainTextToken)->toBeString()->not->toBe('');

    $accessToken = PersonalAccessToken::findToken($plainTextToken);

    expect($accessToken)->not->toBeNull();
    expect($accessToken->name)->toBe('developer_access_token');
    expect($accessToken->tokenable->is($developer))->toBeTrue();
});

it('returns unauthorized for invalid developer credentials', function (): void {
    $email = 'developer-'.Str::lower(Str::random(10)).'@example.com';

    Developer::query()->create([
        'email' => $email,
        'password' => 'secret123',
    ]);

    $response = $this->postJson('/api/v1/access-token', [
        'email' => $email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401);
    $response->assertJsonFragment([
        'message' => 'Unauthorized',
        'status' => false,
        'status_code' => 401,
    ]);
});

it('validates developer login payload', function (): void {
    $response = $this->postJson('/api/v1/access-token', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.email.0', 'The email field must be a valid email address.');
    $response->assertJsonPath('errors.password.0', 'The password field is required.');
});
