<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Payos = 'payos';
    case Star = 'star';
}
