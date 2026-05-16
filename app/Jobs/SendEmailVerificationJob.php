<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendEmailVerificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $userId,
    ) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if (! $user instanceof User || $user->hasVerifiedEmail()) {
            return;
        }

        try {
            $user->sendEmailVerificationNotification();

            Log::info('Sent email verification notification.', [
                'user_id' => $this->userId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send email verification notification.', [
                'user_id' => $this->userId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
