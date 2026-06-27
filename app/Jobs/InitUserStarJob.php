<?php

namespace App\Jobs;

use App\Enums\StarTransactionType;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class InitUserStarJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $userId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $amount = (int) config('const.star.init');

        if ($amount <= 0) {
            return;
        }

        try {
            $success = app(\App\Services\Star\IStarService::class)->addStarByUserId(
                $amount,
                $this->userId,
                __('Bạn được tặng sao khi đăng ký tài khoản'),
                StarTransactionType::RegistrationBonus,
            );

            if ($success) {
                Log::info('Initialized user stars on registration.', [
                    'user_id' => $this->userId,
                    'amount' => $amount,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to initialize user stars on registration.', [
                'user_id' => $this->userId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
