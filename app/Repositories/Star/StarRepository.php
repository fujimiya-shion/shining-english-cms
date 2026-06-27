<?php

namespace App\Repositories\Star;

use App\Models\Star;
use App\Repositories\Repository;

class StarRepository extends Repository implements IStarRepository
{
    public function __construct(Star $model)
    {
        parent::__construct($model);
    }

    public function findForUpdateByUserId(int $userId): ?Star
    {
        return $this->model
            ->newQuery()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();
    }

    public function getBalanceByUserId(int $userId): int
    {
        $record = $this->model
            ->newQuery()
            ->where('user_id', $userId)
            ->first();

        return $record ? (int) $record->amount : 0;
    }
}
