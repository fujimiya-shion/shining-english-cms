<?php

use App\Enums\ThirdPartyAuthProviders;
use App\Http\Requests\Api\V1\User\ThirdPartyLoginRequest;
use Illuminate\Validation\Rules\Enum;

it('authorizes third-party login requests', function (): void {
    $request = new ThirdPartyLoginRequest;

    expect($request->authorize())->toBeTrue();
});

it('defines expected third-party login validation rules', function (): void {
    $request = new ThirdPartyLoginRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'access_token',
        'provider',
        'device_identifier',
        'device_name',
        'platform',
        'ip_address',
        'user_agent',
    ]);

    expect($rules['access_token'])->toBe(['required', 'string']);
    expect($rules['device_identifier'])->toBe(['required', 'string']);
    expect($rules['device_name'])->toBe(['nullable', 'string']);
    expect($rules['platform'])->toBe(['nullable', 'string']);
    expect($rules['ip_address'])->toBe(['nullable', 'ip']);
    expect($rules['user_agent'])->toBe(['nullable', 'string']);

    expect($rules['provider'][0])->toBe('required');
    expect($rules['provider'][1])->toBeInstanceOf(Enum::class);
    expect($rules['provider'][1])->toEqual(new Enum(ThirdPartyAuthProviders::class));
});
