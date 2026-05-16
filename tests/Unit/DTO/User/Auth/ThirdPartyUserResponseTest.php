<?php

use App\DTO\User\Auth\ThirdPartyUserResponse;

it('maps third party user response from json', function (): void {
    $dto = ThirdPartyUserResponse::fromJson([
        'name' => 'Google User',
        'email' => 'google@example.com',
        'avatar' => 'https://example.com/avatar.png',
    ]);

    expect($dto->name)->toBe('Google User');
    expect($dto->email)->toBe('google@example.com');
    expect($dto->avatar)->toBe('https://example.com/avatar.png');
});

it('normalizes empty avatar to null', function (): void {
    $dto = ThirdPartyUserResponse::fromJson([
        'name' => 'Google User',
        'email' => 'google@example.com',
        'avatar' => '',
    ]);

    expect($dto->avatar)->toBeNull();
});

it('converts third party user response to json', function (): void {
    $dto = new ThirdPartyUserResponse(
        name: 'Google User',
        email: 'google@example.com',
        avatar: 'https://example.com/avatar.png',
    );

    expect($dto->toJson())->toBe([
        'name' => 'Google User',
        'email' => 'google@example.com',
        'avatar' => 'https://example.com/avatar.png',
    ]);
});
