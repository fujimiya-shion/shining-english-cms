<?php

namespace App\Services\User;

use App\DTO\User\Auth\LoginResponse;
use App\DTO\User\Auth\RegisterResponse;
use App\Enums\AuthenticatedBy;
use App\Enums\ThirdPartyAuthProviders;
use App\Integrations\Auth\Factories\ThirdPartyAuthFactory;
use App\Jobs\InitUserStarJob;
use App\Jobs\SendEmailVerificationJob;
use App\Models\User;
use App\Repositories\User\IUserDeviceRepository;
use App\Repositories\User\IUserRepository;
use App\Services\OptimizedImageService;
use App\Services\Service;
use App\ValueObjects\DeviceInfo;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class UserService extends Service implements IThirdPartyAuthService, IUserService
{
    protected IUserRepository $userRepository;

    protected IUserDeviceRepository $userDeviceRepository;

    public function __construct(
        IUserRepository $repository,
        IUserDeviceRepository $userDeviceRepository,
    ) {
        parent::__construct($repository);
        $this->userRepository = $repository;
        $this->userDeviceRepository = $userDeviceRepository;
    }

    public function register(
        string $name,
        string $email,
        ?string $phone = null,
        ?string $password = null,
        AuthenticatedBy $authenticatedBy = AuthenticatedBy::Local
    ): RegisterResponse {
        try {
            Log::info('User registration started', [
                'email' => $email,
                'authenticated_by' => $authenticatedBy->value,
                'has_phone' => ! empty($phone),
                'has_password' => ! empty($password),
            ]);

            $payload = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
            ];

            if ($authenticatedBy !== AuthenticatedBy::Local) {
                $payload['authenticated_by'] = $authenticatedBy->value;
            }

            $created = $this->userRepository->create($payload);

            if ($created instanceof User) {
                if ($created->id !== null) {
                    Log::info('User registration created model', [
                        'user_id' => $created->id,
                        'email' => $created->email,
                        'authenticated_by' => $authenticatedBy->value,
                    ]);

                    dispatch(new InitUserStarJob($created->id));
                    if ($authenticatedBy === AuthenticatedBy::Local) {
                        dispatch(new SendEmailVerificationJob($created->id));
                    }
                }

                return new RegisterResponse($created);
            }
            throw new Exception('return model is not instance of user');
        } catch (Throwable $e) {
            Log::error('User registration failed', [
                'email' => $email,
                'authenticated_by' => $authenticatedBy->value,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $e;
        }
    }

    public function login(string $email, ?string $password, DeviceInfo $device): LoginResponse
    {
        try {
            $user = $this->userRepository->findByEmail($email);

            if (! $user instanceof User || ($password && ! Hash::check($password, $user->password))) {
                throw new Exception('Invalid credentials');
            }

            if (! $user->hasVerifiedEmail()) {
                throw new Exception('Email is not verified.');
            }

            $tokenResult = $user->createToken('user_auth_token');
            $this->createUserDevice($user, $device, $tokenResult->accessToken->id ?? null);

            return new LoginResponse($tokenResult->plainTextToken, $user);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function logoutByToken(string $token): bool
    {
        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return false;
        }

        $this->userDeviceRepository->markLoggedOutByTokenId($accessToken->id);

        $accessToken->delete();

        return true;
    }

    public function updateProfile(User $user, array $data): User
    {
        if (($data['avatar'] ?? null) instanceof UploadedFile) {
            $oldAvatar = (string) ($user->getRawOriginal('avatar') ?? '');

            $data['avatar'] = app(OptimizedImageService::class)->storeUploadedImage(
                $data['avatar'],
                disk: 'public',
                directory: 'users',
            );

            if ($oldAvatar !== '' && $oldAvatar !== $data['avatar']) {
                app(OptimizedImageService::class)->deleteStoredImage($oldAvatar, 'public');
            }
        }

        /** @var User $updated */
        $updated = $this->update((int) $user->id, $data);
        $updated->loadMissing('city:id,name');

        return $updated;
    }

    public function sendPasswordResetLink(string $email): void
    {
        Password::broker('users')->sendResetLink([
            'email' => $email,
        ]);
    }

    public function resetPassword(string $email, string $token, string $password): bool
    {
        $status = Password::broker('users')->reset(
            [
                'email' => $email,
                'token' => $token,
                'password' => $password,
                'password_confirmation' => $password,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET;
    }

    public function authenticateByAccessToken(ThirdPartyAuthProviders $provider, DeviceInfo $deviceInfo, string $accessToken): LoginResponse
    {
        try {
            Log::info('Third-party authentication by access token started', [
                'provider' => $provider->value,
                'device_identifier' => $deviceInfo->identifier,
                'has_access_token' => filled($accessToken),
            ]);

            $strategy = ThirdPartyAuthFactory::make($provider);
            Log::info('Third-party strategy resolved', [
                'provider' => $provider->value,
                'strategy' => get_class($strategy),
            ]);

            $thirdPartyUser = $strategy->getUserFromAccessToken($accessToken);
            Log::info('Third-party user fetched from provider', [
                'provider' => $provider->value,
                'email' => $thirdPartyUser->email,
                'name' => $thirdPartyUser->name,
                'has_avatar' => filled($thirdPartyUser->avatar),
            ]);

            $user = $this->userRepository->getBy(['email' => $thirdPartyUser->email])->first();
            Log::info('Third-party user lookup completed', [
                'provider' => $provider->value,
                'email' => $thirdPartyUser->email,
                'user_found' => $user instanceof User,
                'user_id' => $user instanceof User ? $user->id : null,
            ]);

            if (! $user) {
                Log::info('Third-party user not found locally, creating new user', [
                    'provider' => $provider->value,
                    'email' => $thirdPartyUser->email,
                ]);

                $registerResponse = $this->register(
                    name: $thirdPartyUser->name,
                    email: $thirdPartyUser->email,
                    authenticatedBy: AuthenticatedBy::from($provider->value),
                );

                if (! $registerResponse->isSuccessfully()) {
                    throw new Exception('Đăng ký qua dịch vụ thứ ba thất bại');
                }

                $registerResponse->user->update([
                    'email_verified_at' => now(),
                    'avatar' => $thirdPartyUser->avatar,
                ]);

                Log::info('Third-party user created and marked verified', [
                    'provider' => $provider->value,
                    'email' => $thirdPartyUser->email,
                    'user_id' => $registerResponse->user->id,
                ]);

                return $this->login($thirdPartyUser->email, password: null, device: $deviceInfo);
            }

            Log::info('Third-party user exists locally, continuing login', [
                'provider' => $provider->value,
                'email' => $user->email,
                'user_id' => $user->id,
            ]);

            return $this->login($user->email, password: null, device: $deviceInfo);
        } catch (Throwable $e) {
            Log::error('Third-party authentication by access token failed', [
                'provider' => $provider->value,
                'device_identifier' => $deviceInfo->identifier,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'previous_message' => $e->getPrevious()?->getMessage(),
                'previous_exception' => $e->getPrevious() ? get_class($e->getPrevious()) : null,
            ]);
            throw new Exception('Có lỗi xảy ra trong quá trình đăng nhập', previous: $e);
        }
    }

    public function authenticateByIdToken(ThirdPartyAuthProviders $provider, DeviceInfo $deviceInfo, string $idToken): LoginResponse
    {
        try {
            $strategy = ThirdPartyAuthFactory::make($provider);
            $thirdPartyUser = $strategy->getUserFromIdToken($idToken);
            $user = $this->userRepository->getBy(['email' => $thirdPartyUser->email])->first();

            if (! $user) {
                $registerResponse = $this->register(
                    name: $thirdPartyUser->name,
                    email: $thirdPartyUser->email,
                    authenticatedBy: AuthenticatedBy::from($provider->value),
                );

                if (! $registerResponse->isSuccessfully()) {
                    throw new Exception('Failed to register third-party user.');
                }

                $registerResponse->user->update([
                    'email_verified_at' => now(),
                ]);

                return $this->login($thirdPartyUser->email, password: null, device: $deviceInfo);
            }

            return $this->login($user->email, password: null, device: $deviceInfo);
        } catch (Throwable $e) {
            throw new Exception('Có lỗi xảy ra trong quá trình đăng nhập', previous: $e);
        }
    }

    protected function createUserDevice(User $user, DeviceInfo $device, ?int $tokenId): void
    {
        $this->userDeviceRepository->create([
            'user_id' => $user->id,
            'personal_access_token_id' => $tokenId,
            'device_identifier' => $device->identifier,
            'device_name' => $device->name,
            'platform' => $device->platform,
            'ip_address' => $device->ipAddress,
            'user_agent' => $device->userAgent,
            'logged_in_at' => now(),
            'last_seen_at' => now(),
        ]);
    }
}
