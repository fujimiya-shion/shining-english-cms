<?php

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Support\HtmlString;
use Tests\TestCase;

uses(TestCase::class);

it('builds frontend reset password mail message', function (): void {
    config()->set('app.frontend_reset_password_url', 'http://localhost:3000/reset-password');

    $user = new User;
    $user->email = 'notify@example.com';

    $notification = new ResetPasswordNotification('reset-token');
    $mailMessage = $notification->toMail($user);
    $rendered = $mailMessage->render();

    expect($notification->via($user))->toBe(['mail']);
    expect($rendered)->toBeInstanceOf(HtmlString::class);
    expect(str_contains($rendered, 'http://localhost:3000/reset-password?token=reset-token&amp;email=notify%40example.com'))->toBeTrue();
    expect(str_contains($rendered, 'We received a request to reset your password.'))->toBeTrue();
});
