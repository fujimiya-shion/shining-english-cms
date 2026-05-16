<?php
namespace App\DTO\User\Auth;

use App\Models\User;

class RegisterResponse {
    public function __construct(
        public User $user
    ) {}

    public function toArray(): array {
        return [
            'user' => $this->user,
            'email_verification_sent' => true,
        ];
    }

    public function isSuccessfully(): bool {
        return $this->user != null;
    }
}
