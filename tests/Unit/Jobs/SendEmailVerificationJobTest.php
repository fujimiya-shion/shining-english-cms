<?php

use App\Jobs\SendEmailVerificationJob;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class);

it('skips when user does not exist', function (): void {
    Notification::fake();
    Log::shouldReceive('info')->never();
    Log::shouldReceive('error')->never();

    $job = new SendEmailVerificationJob(999999);
    $job->handle();
});

it('skips when user is already verified', function (): void {
    Notification::fake();
    Log::shouldReceive('info')->never();
    Log::shouldReceive('error')->never();

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $job = new SendEmailVerificationJob($user->id);
    $job->handle();

    Notification::assertNothingSent();
});

it('sends verification email and logs success', function (): void {
    Notification::fake();
    Log::spy();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $job = new SendEmailVerificationJob($user->id);
    $job->handle();

    Notification::assertSentTo($user, VerifyEmail::class);
    Log::shouldHaveReceived('info')
        ->once()
        ->with('Sent email verification notification.', ['user_id' => $user->id]);
});

it('logs error when sending verification email fails', function (): void {
    Log::spy();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    Notification::shouldReceive('send')
        ->once()
        ->andThrow(new Exception('boom'));

    $job = new SendEmailVerificationJob($user->id);
    $job->handle();

    Log::shouldHaveReceived('error')
        ->once()
        ->with('Failed to send email verification notification.', [
            'user_id' => $user->id,
            'message' => 'boom',
        ]);
});
