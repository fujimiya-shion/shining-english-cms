<?php

use App\Http\Requests\Api\V1\User\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\User\LoginRequest;
use App\Http\Requests\Api\V1\User\RegisterRequest;
use App\Http\Requests\Api\V1\User\ResetPasswordRequest;
use App\Http\Requests\Api\V1\User\UserUpdateRequest;

it('defines expected forgot password request rules and messages', function (): void {
    $request = new ForgotPasswordRequest;

    expect($request->authorize())->toBeTrue();
    expect($request->rules())->toBe([
        'email' => ['required', 'email'],
    ]);
    expect($request->messages())->toBe([
        'email.required' => 'Email is required.',
        'email.email' => 'Email must be valid.',
    ]);
});

it('defines expected login request rules and messages', function (): void {
    $request = new LoginRequest;

    expect($request->authorize())->toBeTrue();
    expect($request->rules())->toBe([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
        'device_identifier' => ['required', 'string'],
        'device_name' => ['nullable', 'string'],
        'platform' => ['nullable', 'string'],
        'ip_address' => ['nullable', 'ip'],
        'user_agent' => ['nullable', 'string'],
    ]);
    expect($request->messages())->toBe([
        'email.required' => 'Email is required.',
        'email.email' => 'Email must be valid.',
        'password.required' => 'Password is required.',
        'device_identifier.required' => 'Device identifier is required.',
        'ip_address.ip' => 'IP address must be valid.',
    ]);
});

it('defines expected register request rules and messages', function (): void {
    $request = new RegisterRequest;

    expect($request->authorize())->toBeTrue();
    expect($request->rules())->toBe([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'phone' => ['required', 'string', 'max:30'],
        'password' => ['required', 'string', 'min:6'],
        'recaptcha_token' => ['required', 'string'],
    ]);
    expect($request->messages())->toBe([
        'name.required' => 'Name is required.',
        'email.required' => 'Email is required.',
        'email.email' => 'Email must be valid.',
        'email.unique' => 'Email already exists.',
        'phone.required' => 'Phone is required.',
        'password.required' => 'Password is required.',
        'password.min' => 'Password must be at least 6 characters.',
        'recaptcha_token.required' => 'reCAPTCHA token is required.',
    ]);
});

it('defines expected reset password request rules and messages', function (): void {
    $request = new ResetPasswordRequest;

    expect($request->authorize())->toBeTrue();
    expect($request->rules())->toBe([
        'email' => ['required', 'email'],
        'token' => ['required', 'string'],
        'password' => ['required', 'string', 'min:6', 'confirmed'],
    ]);
    expect($request->messages())->toBe([
        'email.required' => 'Email is required.',
        'email.email' => 'Email must be valid.',
        'token.required' => 'Reset token is required.',
        'password.required' => 'Password is required.',
        'password.min' => 'Password must be at least 6 characters.',
        'password.confirmed' => 'Password confirmation does not match.',
    ]);
});

it('defines expected user update request rules and messages', function (): void {
    $request = new UserUpdateRequest;
    $rules = $request->rules();

    expect($request->authorize())->toBeTrue();
    expect($rules['name'])->toEqual(['sometimes', 'filled', 'string', 'max:255']);
    expect($rules['phone'])->toEqual(['sometimes', 'filled', 'string', 'max:30']);
    expect($rules['birthday'])->toEqual(['sometimes', 'filled', 'date']);
    expect($rules['city_id'])->toEqual(['sometimes', 'filled', 'integer', 'exists:cities,id']);
    expect($rules['password'])->toEqual(['sometimes', 'filled', 'string', 'min:6']);
    expect($rules['avatar'])->toBeArray();
    expect($rules['avatar'][0])->toBe('sometimes');
    expect($request->messages())->toBe([
        'name.filled' => 'Name is required.',
        'birthday.date' => 'Birthday must be a valid date.',
        'phone.filled' => 'Phone is required.',
        'city_id.integer' => 'City id must be an integer.',
        'city_id.exists' => 'City not found.',
        'password.min' => 'Password must be at least 6 characters.',
    ]);
});
