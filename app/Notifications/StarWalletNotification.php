<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class StarWalletNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $amount,
        public int $balanceAfter,
        public string $transactionType,
        public string $description,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationType::StarWallet->value,
            'amount' => $this->amount,
            'balance_after' => $this->balanceAfter,
            'transaction_type' => $this->transactionType,
            'description' => $this->description,
            'title' => $this->amount >= 0 ? 'Nhận sao' : 'Sao đã được sử dụng',
            'body' => $this->description,
        ];
    }
}
