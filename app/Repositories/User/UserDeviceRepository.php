<?php

namespace App\Repositories\User;

use App\Models\UserDevice;
use App\Repositories\Repository;

class UserDeviceRepository extends Repository implements IUserDeviceRepository
{
    public function __construct(UserDevice $model)
    {
        parent::__construct($model);
    }

    public function markLoggedOutByTokenId(int $tokenId): int
    {
        return $this->model
            ->newQuery()
            ->where('personal_access_token_id', $tokenId)
            ->update([
                'logged_out_at' => now(),
                'last_seen_at' => now(),
            ]);
    }
}
