<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\DTO\User\Page\Home\HomeResponse;

interface IUserHomeRepository
{
    public function getUserHomeData(?string $token): HomeResponse;
}
