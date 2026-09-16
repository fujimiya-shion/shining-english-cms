<?php

declare(strict_types=1);

namespace App\Enums;

enum GatewayType: string
{
    case Payos = 'payos';
    case Cod = 'cod';
    case Star = 'star';

    public function label(): string
    {
        return match ($this) {
            self::Payos => 'PayOS',
            self::Cod => 'Thanh toán khi nhận hàng',
            self::Star => 'Thanh toán bằng sao',
        };
    }
}
