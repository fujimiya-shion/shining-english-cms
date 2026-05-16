<?php

namespace App\Services\Star;

use App\Services\IService;

interface IStarService extends IService
{
    public function addStarByUserId(int $amount, int $userId, ?string $message): bool;

    public function spendStarByUserId(int $amount, int $userId, ?string $message): bool;
}
