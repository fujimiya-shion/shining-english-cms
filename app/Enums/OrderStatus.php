<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Chờ thanh toán'),
            self::Paid => __('Đã thanh toán'),
            self::Cancelled => __('Đã hủy'),
        };
    }
}
