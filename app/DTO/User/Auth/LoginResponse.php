<?php

namespace App\DTO\User\Auth;

use App\Models\User;

class LoginResponse
{
    public function __construct(
        public string $token,
        public User $user
    ) {}

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'user' => $this->user,
        ];
    }
}
