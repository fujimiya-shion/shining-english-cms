<?php

namespace App\Services\Star;

use App\Enums\StarTransactionType;
use App\Services\IService;

interface IStarService extends IService
{
    public function addStarByUserId(int $amount, int $userId, ?string $message = null, ?StarTransactionType $type = null): bool;

    public function spendStarByUserId(int $amount, int $userId, ?string $message = null, ?StarTransactionType $type = null): bool;

    public function getBalance(int $userId): int;
}
