<?php

namespace App\Repositories\Star;

use App\Models\Star;
use App\Repositories\IRepository;

interface IStarRepository extends IRepository
{
    public function findForUpdateByUserId(int $userId): ?Star;

    public function getBalanceByUserId(int $userId): int;
}
