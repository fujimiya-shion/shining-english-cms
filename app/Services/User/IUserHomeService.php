<?php

declare(strict_types=1);

namespace App\Services\User;

use App\DTO\User\Page\Home\HomeResponse;

interface IUserHomeService
{
    public function getHomeData(?string $userToken): HomeResponse;
}
