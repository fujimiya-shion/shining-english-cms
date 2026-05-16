<?php
namespace App\Services\Developer;

use App\Models\Developer;
use App\Services\IService;
interface IDeveloperService extends IService {
    public function login(string $email, string $password): Developer|null;
}