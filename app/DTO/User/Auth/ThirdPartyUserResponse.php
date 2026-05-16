<?php
namespace App\DTO\User\Auth;
class ThirdPartyUserResponse {
    public function __construct(
        public string $name,
        public string $email,
        public ?string $avatar,
    ) {}

    public static function fromJson(array $json): self {
        return new self(
            $json['name'],
            $json['email'],
            empty($json['avatar']) ? null : $json['avatar'],
        );
    }

    public function toJson(): array {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
        ];
    }
}