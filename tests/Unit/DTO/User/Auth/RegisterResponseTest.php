<?php

use App\DTO\User\Auth\RegisterResponse;
use App\Models\User;

it('converts register response to array', function (): void {
    $user = new User;
    $response = new RegisterResponse($user);

    expect($response->toArray())->toEqual([
        'user' => $user,
        'email_verification_sent' => true,
    ]);
});
