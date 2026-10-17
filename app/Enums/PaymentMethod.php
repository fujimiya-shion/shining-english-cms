<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Payos = 'payos';
    case Star = 'star';

    public function label(): string
    {
        return match ($this) {
            self::Payos => 'PayOS',
            self::Cod => 'Thanh toán khi nhận hàng',
            self::Star => 'Thanh toán bằng sao',
        };
    }

    public function factoryName(): string {
        return match ($this) {
            self::Payos => 'pay_os',
            self::Cod => 'cod',
            self::Star => 'star',
        };
    }
}
