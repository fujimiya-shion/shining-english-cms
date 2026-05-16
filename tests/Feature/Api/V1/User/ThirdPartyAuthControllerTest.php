<?php

use App\DTO\User\Auth\LoginResponse;
use App\Enums\ThirdPartyAuthProviders;
use App\Models\User;
use App\Services\User\IThirdPartyAuthService;
use App\Services\User\IUserService;
use App\ValueObjects\DeviceInfo;

beforeEach(function (): void {
    $this->withoutMiddleware();
});

it('logs in using third-party access token', function (): void {
    $user = new User;
    $user->id = 99;
    $user->name = 'Google User';
    $user->email = 'google@example.com';

    $service = Mockery::mock(IUserService::class, IThirdPartyAuthService::class);
    $service->shouldReceive('authenticateByAccessToken')
        ->once()
        ->with(
            ThirdPartyAuthProviders::Google,
            Mockery::type(DeviceInfo::class),
            'google-access-token',
        )
        ->andReturn(new LoginResponse('plain-token', $user));
    app()->instance(IUserService::class, $service);

    $response = $this->postJson('/api/v1/auth/third-party-login', [
        'provider' => 'google',
        'access_token' => 'google-access-token',
        'device_identifier' => 'device-1',
        'device_name' => 'Chrome',
        'platform' => 'web',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('message', 'Login by third-party successfully');
    $response->assertJsonPath('data.token', 'plain-token');
    $response->assertJsonPath('data.user.email', 'google@example.com');
});

it('validates third-party login payload', function (): void {
    $response = $this->postJson('/api/v1/auth/third-party-login', [
        'provider' => 'invalid-provider',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'provider',
        'access_token',
        'device_identifier',
    ]);
});

it('returns error when third-party service throws', function (): void {
    $service = Mockery::mock(IUserService::class, IThirdPartyAuthService::class);
    $service->shouldReceive('authenticateByAccessToken')
        ->once()
        ->andThrow(new Exception('Third-party login failed'));
    app()->instance(IUserService::class, $service);

    $response = $this->postJson('/api/v1/auth/third-party-login', [
        'provider' => 'google',
        'access_token' => 'google-access-token',
        'device_identifier' => 'device-1',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', 'Third-party login failed');
});
