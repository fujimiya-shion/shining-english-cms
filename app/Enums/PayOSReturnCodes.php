<?php
namespace App\Enums;
enum PayOSReturnCodes: int {
    case SUCCESS = 0;
    case ORDER_EXISTED = 231;
}