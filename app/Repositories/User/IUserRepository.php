<?php
namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\IRepository;
interface IUserRepository extends IRepository {
    public function findByEmail(string $email): ?User;
}
