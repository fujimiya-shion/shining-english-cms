<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('verifies the user email through signed link and redirects to frontend', function (): void {
    config()->set('app.frontend_email_verification_url', 'http://localhost:3000/login?verified=1');

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ],
    );

    $response = $this->get($url);

    $response->assertRedirect('http://localhost:3000/login?verified=1');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
