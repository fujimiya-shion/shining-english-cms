<?php

namespace App\Repositories\User;

use App\Repositories\IRepository;

interface IUserDeviceRepository extends IRepository
{
    public function markLoggedOutByTokenId(int $tokenId): int;
}
