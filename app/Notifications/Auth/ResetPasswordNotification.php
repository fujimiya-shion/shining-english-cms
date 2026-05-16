<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your password')
            ->greeting('Hello!')
            ->line('We received a request to reset your password.')
            ->action('Reset password', $this->buildResetUrl($notifiable))
            ->line('If you did not request a password reset, no further action is required.');
    }

    private function buildResetUrl(object $notifiable): string
    {
        $baseUrl = rtrim(
            config('app.frontend_reset_password_url', env('FRONTEND_RESET_PASSWORD_URL', 'http://localhost:3000/reset-password')),
            '/'
        );

        $query = http_build_query([
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return "{$baseUrl}?{$query}";
    }
}
