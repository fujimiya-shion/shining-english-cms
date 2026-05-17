<?php
declare(strict_types=1);
namespace App\Services\User;

use App\DTO\User\Page\Home\HomeResponse;
use App\Repositories\User\IUserHomeRepository;
use App\Services\User\IUserHomeService;
class UserHomeService implements IUserHomeService {

    public function __construct(
        private IUserHomeRepository $repository,
    ) {}

    public function getHomeData(string|null $userToken): HomeResponse {
        return $this->repository->getUserHomeData($userToken);
    }
}