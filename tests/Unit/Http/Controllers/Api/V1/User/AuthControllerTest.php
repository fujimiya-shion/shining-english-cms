<?php

use App\DTO\User\Auth\LoginResponse;
use App\DTO\User\Auth\RegisterResponse;
use App\Http\Controllers\Api\V1\User\AuthController;
use App\Http\Requests\Api\V1\User\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\User\LoginRequest;
use App\Http\Requests\Api\V1\User\RegisterRequest;
use App\Http\Requests\Api\V1\User\ResetPasswordRequest;
use App\Http\Requests\Api\V1\User\ThirdPartyLoginRequest;
use App\Services\Security\Recaptcha\IRecaptchaVerifier;
use App\Services\Security\Recaptcha\RecaptchaVerificationException;
use App\Models\User;
use App\Services\User\IThirdPartyAuthService;
use App\Services\User\IUserService;
use App\ValueObjects\DeviceInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

afterEach(function (): void {
    \Mockery::close();
});

it('registers a user and returns a created response', function (): void {
    $user = new User(['name' => 'Shion', 'email' => 'shion@example.com']);
    $result = new RegisterResponse($user);

    $service = \Mockery::mock(IUserService::class);
    $service->shouldReceive('register')
        ->once()
        ->with('Shion', 'shion@example.com', '0123', 'secret123')
        ->andReturn($result);
    app()->instance(IUserService::class, $service);
    $recaptchaVerifier = \Mockery::mock(IRecaptchaVerifier::class);
    $recaptchaVerifier->shouldReceive('verifyOrFail')
        ->once()
        ->with('token-ok', 'register', \Mockery::any())
        ->andReturnNull();
    app()->instance(IRecaptchaVerifier::class, $recaptchaVerifier);

    $controller = app()->make(AuthController::class);
    $request = RegisterRequest::create('/api/v1/auth/register', 'POST', [
        'name' => 'Shion',
        'email' => 'shion@example.com',
        'phone' => '0123',
        'password' => 'secret123',
        'recaptcha_token' => 'token-ok',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $response = $controller->register($request);

    assertJsonResponsePayload($response, 201, [
        'message' => 'Register successfully',
        'status' => true,
        'status_code' => 201,
    ]);
    expect($response->getData(true)['data'])->toMatchArray([
        'email_verification_sent' => true,
        'user' => [
            'name' => 'Shion',
            'email' => 'shion@example.com',
            'city_name' => null,
            'city' => null,
        ],
    ]);
});

it('returns an error response when registration fails', function (): void {
    $service = \Mockery::mock(IUserService::class);
    $service->shouldReceive('register')->never();
    app()->instance(IUserService::class, $service);
    $recaptchaVerifier = \Mockery::mock(IRecaptchaVerifier::class);
    $recaptchaVerifier->shouldReceive('verifyOrFail')
        ->once()
        ->andThrow(RecaptchaVerificationException::failed());
    app()->instance(IRecaptchaVerifier::class, $recaptchaVerifier);

    $controller = app()->make(AuthController::class);
    $request = RegisterRequest::create('/api/v1/auth/register', 'POST', [
        'name' => 'Shion',
        'email' => 'shion@example.com',
        'phone' => '0123',
        'password' => 'secret123',
        'recaptcha_token' => 'token-bad',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $response = $controller->register($request);

    assertJsonResponsePayload($response, 422, [
        'message' => 'reCAPTCHA verification failed.',
        'status' => false,
        'status_code' => 422,
    ]);
});

it('logs in a user with request fallback device info and returns an error on failure', function (): void {
    $user = new User(['email' => 'shion@example.com']);
    $result = new LoginResponse('user-token', $user);

    $service = \Mockery::mock(IUserService::class);
    $service->shouldReceive('login')
        ->once()
        ->with(
            'shion@example.com',
            'secret123',
            \Mockery::on(function (DeviceInfo $device): bool {
                return $device->identifier === 'device-1'
                    && $device->name === 'Pixel'
                    && $device->platform === 'android'
                    && $device->ipAddress === '10.0.0.1'
                    && $device->userAgent === 'Pest';
            })
        )
        ->andReturn($result);
    $service->shouldReceive('login')
        ->once()
        ->with('error@example.com', 'secret123', \Mockery::type(DeviceInfo::class))
        ->andThrow(new RuntimeException('Invalid credentials'));
    app()->instance(IUserService::class, $service);

    $controller = app()->make(AuthController::class);

    $request = LoginRequest::create('/api/v1/auth/login', 'POST', [
        'email' => 'shion@example.com',
        'password' => 'secret123',
        'device_identifier' => 'device-1',
        'device_name' => 'Pixel',
        'platform' => 'android',
    ], [], [], [
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTP_USER_AGENT' => 'Pest',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $response = $controller->login($request);
    assertJsonResponsePayload($response, 200, [
        'message' => 'Login successfully',
        'status' => true,
        'status_code' => 200,
    ]);
    expect($response->getData(true)['data'])->toMatchArray([
        'token' => 'user-token',
        'user' => [
            'email' => 'shion@example.com',
            'city_name' => null,
            'city' => null,
        ],
    ]);

    $errorRequest = LoginRequest::create('/api/v1/auth/login', 'POST', [
        'email' => 'error@example.com',
        'password' => 'secret123',
        'device_identifier' => 'device-2',
    ]);
    $errorRequest->setContainer(app())->setRedirector(app('redirect'));
    $errorRequest->validateResolved();

    $errorResponse = $controller->login($errorRequest);
    assertJsonResponsePayload($errorResponse, 422, [
        'message' => 'Invalid credentials',
        'status' => false,
        'status_code' => 422,
    ]);
});

it('returns the authenticated user and handles logout state', function (): void {
    $user = new User(['email' => 'shion@example.com']);

    $service = \Mockery::mock(IUserService::class);
    $service->shouldReceive('logoutByToken')->once()->with('plain-token')->andReturnTrue();
    app()->instance(IUserService::class, $service);

    $controller = app()->make(AuthController::class);

    $meRequest = Request::create('/api/v1/auth/me', 'GET');
    $meRequest->setUserResolver(fn (): User => $user);
    $meResponse = $controller->me($meRequest);
    assertJsonResponsePayload($meResponse, 200, [
        'status' => true,
        'status_code' => 200,
    ]);

    $logoutRequest = Request::create('/api/v1/auth/logout', 'POST', [], [], [], [
        'HTTP_USER_AUTHORIZATION' => 'plain-token',
    ]);
    $logoutResponse = $controller->logout($logoutRequest);
    assertJsonResponsePayload($logoutResponse, 200, [
        'message' => 'Logged out',
        'status' => true,
        'status_code' => 200,
    ]);

    $unauthenticatedResponse = $controller->logout(Request::create('/api/v1/auth/logout', 'POST'));
    assertJsonResponsePayload($unauthenticatedResponse, 401, [
        'message' => 'Unauthenticated',
        'status' => false,
        'status_code' => 401,
    ]);
});

it('sends forgot password links and resets passwords', function (): void {
    $service = \Mockery::mock(IUserService::class);
    $service->shouldReceive('sendPasswordResetLink')->once()->with('shion@example.com');
    $service->shouldReceive('resetPassword')->once()->with('shion@example.com', 'reset-token', 'secret123')->andReturnTrue();
    $service->shouldReceive('resetPassword')->once()->with('shion@example.com', 'bad-token', 'secret123')->andReturnFalse();
    app()->instance(IUserService::class, $service);

    $controller = app()->make(AuthController::class);

    $forgotRequest = ForgotPasswordRequest::create('/api/v1/auth/forgot-password', 'POST', [
        'email' => 'shion@example.com',
    ]);
    $forgotRequest->setContainer(app())->setRedirector(app('redirect'));
    $forgotRequest->validateResolved();
    $forgotResponse = $controller->forgotPassword($forgotRequest);
    assertJsonResponsePayload($forgotResponse, 200, [
        'message' => 'If your email exists, a password reset link has been sent.',
        'status' => true,
        'status_code' => 200,
    ]);

    $resetRequest = ResetPasswordRequest::create('/api/v1/auth/reset-password', 'POST', [
        'email' => 'shion@example.com',
        'token' => 'reset-token',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);
    $resetRequest->setContainer(app())->setRedirector(app('redirect'));
    $resetRequest->validateResolved();
    $resetResponse = $controller->resetPassword($resetRequest);
    assertJsonResponsePayload($resetResponse, 200, [
        'message' => 'Password reset successfully.',
        'status' => true,
        'status_code' => 200,
    ]);

    $invalidResetRequest = ResetPasswordRequest::create('/api/v1/auth/reset-password', 'POST', [
        'email' => 'shion@example.com',
        'token' => 'bad-token',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);
    $invalidResetRequest->setContainer(app())->setRedirector(app('redirect'));
    $invalidResetRequest->validateResolved();
    $invalidResetResponse = $controller->resetPassword($invalidResetRequest);
    assertJsonResponsePayload($invalidResetResponse, 422, [
        'message' => 'Invalid or expired reset token.',
        'status' => false,
        'status_code' => 422,
    ]);
});

it('authenticates by third-party access token and returns an error payload on failure', function (): void {
    $user = new User(['email' => 'shion@example.com']);
    $result = new LoginResponse('third-party-token', $user);

    $service = \Mockery::mock(IUserService::class.', '.IThirdPartyAuthService::class);
    $service->shouldReceive('authenticateByAccessToken')
        ->once()
        ->with(
            \App\Enums\ThirdPartyAuthProviders::Google,
            \Mockery::on(function (DeviceInfo $device): bool {
                return $device->identifier === 'device-3'
                    && $device->ipAddress === '10.0.0.2';
            }),
            'google-access-token'
        )
        ->andReturn($result);
    $service->shouldReceive('authenticateByAccessToken')
        ->once()
        ->with(\App\Enums\ThirdPartyAuthProviders::Google, \Mockery::type(DeviceInfo::class), 'bad-token')
        ->andThrow(new RuntimeException('Google login failed'));
    app()->instance(IUserService::class, $service);

    $controller = app()->make(AuthController::class);

    $request = ThirdPartyLoginRequest::create('/api/v1/auth/third-party', 'POST', [
        'provider' => 'google',
        'access_token' => 'google-access-token',
        'device_identifier' => 'device-3',
        'ip_address' => '10.0.0.2',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();
    $response = $controller->thirdPartyLogin($request);
    assertJsonResponsePayload($response, 200, [
        'message' => 'Login by third-party successfully',
        'status' => true,
        'status_code' => 200,
    ]);
    expect($response->getData(true)['data'])->toMatchArray([
        'token' => 'third-party-token',
        'user' => [
            'email' => 'shion@example.com',
            'city_name' => null,
            'city' => null,
        ],
    ]);

    $errorRequest = ThirdPartyLoginRequest::create('/api/v1/auth/third-party', 'POST', [
        'provider' => 'google',
        'access_token' => 'bad-token',
        'device_identifier' => 'device-4',
    ]);
    $errorRequest->setContainer(app())->setRedirector(app('redirect'));
    $errorRequest->validateResolved();
    $errorResponse = $controller->thirdPartyLogin($errorRequest);
    assertJsonResponsePayload($errorResponse, 422, [
        'message' => 'Google login failed',
        'status' => false,
        'status_code' => 422,
    ]);
});
