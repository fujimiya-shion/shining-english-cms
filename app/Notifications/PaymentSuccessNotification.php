<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $orderCode,
        public int $totalAmount,
        public string $courseNames,
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
            'type' => NotificationType::PaymentSuccess->value,
            'order_id' => $this->orderId,
            'order_code' => $this->orderCode,
            'total_amount' => $this->totalAmount,
            'course_names' => $this->courseNames,
            'title' => 'Thanh toán thành công',
            'body' => "Đơn hàng #{$this->orderId} - {$this->courseNames} đã được thanh toán thành công.",
        ];
    }
}
