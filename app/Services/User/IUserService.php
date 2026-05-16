<?php

namespace App\Services\User;

use App\DTO\User\Auth\LoginResponse;
use App\DTO\User\Auth\RegisterResponse;
use App\Enums\AuthenticatedBy;
use App\Models\User;
use App\Services\IService;
use App\ValueObjects\DeviceInfo;

interface IUserService extends IService
{
    public function register(
        string $name,
        string $email,
        ?string $phone = null,
        ?string $password = null,
        AuthenticatedBy $authenticatedBy = AuthenticatedBy::Local
    ): RegisterResponse;

    public function login(string $email, ?string $password, DeviceInfo $device): LoginResponse;

    public function logoutByToken(string $token): bool;

    public function sendPasswordResetLink(string $email): void;

    public function resetPassword(string $email, string $token, string $password): bool;

    public function updateProfile(User $user, array $data): User;
}
