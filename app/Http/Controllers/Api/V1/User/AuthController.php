<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Enums\ThirdPartyAuthProviders;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\User\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\User\LoginRequest;
use App\Http\Requests\Api\V1\User\RegisterRequest;
use App\Http\Requests\Api\V1\User\ResetPasswordRequest;
use App\Http\Requests\Api\V1\User\ThirdPartyLoginRequest;
use App\Services\Security\Recaptcha\IRecaptchaVerifier;
use App\Services\Security\Recaptcha\RecaptchaVerificationException;
use App\Services\Star\IStarService;
use App\Services\User\IThirdPartyAuthService;
use App\Services\User\IUserService;
use App\Traits\Jsonable;
use App\ValueObjects\DeviceInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends ApiController
{
    use Jsonable;

    public function __construct(
        private IUserService $service,
        private readonly IRecaptchaVerifier $recaptchaVerifier,
        private readonly IStarService $starService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $this->recaptchaVerifier->verifyOrFail(
                token: $data['recaptcha_token'],
                expectedAction: (string) config('recaptcha.register_action'),
                ipAddress: $request->ip(),
            );

            $result = $this->service->register(
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['password'],
            );

            return $this->created($result->toArray(), 'Register successfully');
        } catch (RecaptchaVerificationException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $device = DeviceInfo::fromArray([
                'device_identifier' => $data['device_identifier'],
                'device_name' => $data['device_name'] ?? null,
                'platform' => $data['platform'] ?? null,
                'ip_address' => $data['ip_address'] ?? $request->ip(),
                'user_agent' => $data['user_agent'] ?? $request->userAgent(),
            ]);

            $result = $this->service->login($data['email'], $data['password'], $device);

            return $this->success('Login successfully', $result->toArray());
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user?->loadMissing('city:id,name');

        $data = $user->toArray();
        $data['star_balance'] = $this->starService->getBalance((int) $user->id);

        return $this->success(data: $data);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->header('User-Authorization');
        $loggedOut = is_string($token)
            ? $this->service->logoutByToken($token)
            : false;

        if (! $loggedOut) {
            return $this->error('Unauthenticated', 401);
        }

        return $this->success('Logged out');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->service->sendPasswordResetLink($request->validated('email'));

        return $this->success('If your email exists, a password reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $reset = $this->service->resetPassword(
            $data['email'],
            $data['token'],
            $data['password'],
        );

        if (! $reset) {
            return $this->error('Invalid or expired reset token.', 422);
        }

        return $this->success('Password reset successfully.');
    }

    public function thirdPartyLogin(ThirdPartyLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            Log::info('Third-party login request received', [
                'provider' => $data['provider'] ?? null,
                'device_identifier' => $data['device_identifier'] ?? null,
                'has_access_token' => ! empty($data['access_token']),
                'has_id_token' => ! empty($data['id_token']),
                'request_ip' => $request->ip(),
            ]);

            $device = DeviceInfo::fromArray([
                'device_identifier' => $data['device_identifier'],
                'device_name' => $data['device_name'] ?? null,
                'platform' => $data['platform'] ?? null,
                'ip_address' => $data['ip_address'] ?? $request->ip(),
                'user_agent' => $data['user_agent'] ?? $request->userAgent(),
            ]);

            /** @var IUserService&IThirdPartyAuthService $service */
            $service = $this->service;
            $response = $service->authenticateByAccessToken(
                accessToken: $data['access_token'],
                provider: ThirdPartyAuthProviders::from($data['provider']),
                deviceInfo: $device,
            );

            Log::info('Third-party login request completed', [
                'provider' => $data['provider'] ?? null,
                'device_identifier' => $data['device_identifier'] ?? null,
            ]);

            return $this->success('Login by third-party successfully', $response->toArray());
        } catch (Throwable $e) {
            Log::error('Third-party login request failed', [
                'provider' => $data['provider'] ?? null,
                'device_identifier' => $data['device_identifier'] ?? null,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return $this->error($e->getMessage(), 422);
        }
    }
}
