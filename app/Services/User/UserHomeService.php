<?php

declare(strict_types=1);

namespace App\Services\User;

use App\DTO\User\Page\Home\HomeResponse;
use App\Repositories\User\IUserHomeRepository;

class UserHomeService implements IUserHomeService
{
    public function __construct(
        private IUserHomeRepository $repository,
    ) {}

    public function getHomeData(?string $userToken): HomeResponse
    {
        return $this->repository->getUserHomeData($userToken);
    }
}
