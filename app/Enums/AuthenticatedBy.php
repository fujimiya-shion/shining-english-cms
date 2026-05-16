<?php

namespace App\Enums;

enum AuthenticatedBy: string
{
    case Local = 'local';
    case Google = 'google';
}
