<?php

namespace Tests\Unit\Services\User;

use App\DTO\User\Auth\LoginResponse;
use App\DTO\User\Auth\RegisterResponse;
use App\Enums\AuthenticatedBy;
use App\Integrations\Auth\Strategies\GoogleAuthStrategy;
use App\Jobs\InitUserStarJob;
use App\Jobs\SendEmailVerificationJob;
use App\Models\User;
use App\Repositories\User\IUserDeviceRepository;
use App\Repositories\User\IUserRepository;
use App\Services\User\IUserService;
use App\Services\User\UserService;
use App\ValueObjects\DeviceInfo;
use Closure;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('implements shared service contract', function (): void {
    $userRepository = Mockery::mock(IUserRepository::class);
    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);
    $service = new UserService($userRepository, $deviceRepository);

    assertServiceContract($service, IUserService::class, $userRepository);
});

it('registers user and dispatches verification + init jobs', function (): void {
    $user = new User;
    $user->id = 1;

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('create')
        ->once()
        ->with([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0900000000',
            'password' => 'secret',
        ])
        ->andReturn($user);

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);

    Bus::fake();

    $service = new UserService($userRepository, $deviceRepository);
    $result = $service->register('Test User', 'test@example.com', '0900000000', 'secret');

    expect($result)->toBeInstanceOf(RegisterResponse::class);
    expect($result->user)->toBe($user);
    Bus::assertDispatched(InitUserStarJob::class);
    Bus::assertDispatched(SendEmailVerificationJob::class);
});

it('registers third-party user without dispatching verification job', function (): void {
    $user = new User;
    $user->id = 2;

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('create')
        ->once()
        ->with([
            'name' => 'Google User',
            'email' => 'google@example.com',
            'phone' => null,
            'password' => null,
            'authenticated_by' => 'google',
        ])
        ->andReturn($user);

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);

    Bus::fake();

    $service = new UserService($userRepository, $deviceRepository);
    $result = $service->register(
        'Google User',
        'google@example.com',
        null,
        null,
        AuthenticatedBy::Google,
    );

    expect($result)->toBeInstanceOf(RegisterResponse::class);
    Bus::assertDispatched(InitUserStarJob::class);
    Bus::assertNotDispatched(SendEmailVerificationJob::class);
});

it('logs in user and records device', function (): void {
    $user = Mockery::mock(User::class)->makePartial();
    $user->password = Hash::make('secret');
    $user->id = 10;
    $user->shouldReceive('hasVerifiedEmail')->once()->andReturnTrue();
    $user->shouldReceive('createToken')
        ->once()
        ->with('user_auth_token')
        ->andReturn((object) [
            'plainTextToken' => 'token-xyz',
            'accessToken' => (object) ['id' => 55],
        ]);

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('findByEmail')
        ->once()
        ->with('test@example.com')
        ->andReturn($user);

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);
    $deviceRepository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $data): bool {
            return $data['user_id'] === 10
                && $data['personal_access_token_id'] === 55
                && $data['device_identifier'] === 'device-1';
        }));

    $service = new UserService($userRepository, $deviceRepository);
    $device = new DeviceInfo('device-1', 'iPhone', 'ios', '127.0.0.1', 'agent');

    $result = $service->login('test@example.com', 'secret', $device);

    expect($result)->toBeInstanceOf(LoginResponse::class);
    expect($result->token)->toBe('token-xyz');
});

it('throws when credentials are invalid', function (): void {
    $user = Mockery::mock(User::class)->makePartial();
    $user->password = Hash::make('secret');

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('findByEmail')
        ->once()
        ->with('test@example.com')
        ->andReturn($user);

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);

    $service = new UserService($userRepository, $deviceRepository);
    $device = new DeviceInfo('device-1');

    expect(fn () => $service->login('test@example.com', 'wrong', $device))
        ->toThrow(Exception::class, 'Invalid credentials');
});

it('authenticates existing third-party user by access token', function (): void {
    $user = Mockery::mock(User::class)->makePartial();
    $user->id = 10;
    $user->email = 'google@example.com';
    $user->password = null;
    $user->shouldReceive('hasVerifiedEmail')->once()->andReturnTrue();
    $user->shouldReceive('createToken')
        ->once()
        ->with('user_auth_token')
        ->andReturn((object) [
            'plainTextToken' => 'token-xyz',
            'accessToken' => (object) ['id' => 55],
        ]);

    $strategy = Mockery::mock(GoogleAuthStrategy::class);
    $strategy->shouldReceive('getUserFromAccessToken')
        ->once()
        ->with('google-access-token')
        ->andReturn(new \App\DTO\User\Auth\ThirdPartyUserResponse(
            name: 'Google User',
            email: 'google@example.com',
            avatar: 'https://example.com/avatar.png',
        ));
    app()->instance(GoogleAuthStrategy::class, $strategy);

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('getBy')
        ->once()
        ->with(['email' => 'google@example.com'])
        ->andReturn(new EloquentCollection([$user]));
    $userRepository->shouldReceive('findByEmail')
        ->once()
        ->with('google@example.com')
        ->andReturn($user);

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);
    $deviceRepository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $data): bool {
            return $data['user_id'] === 10
                && $data['personal_access_token_id'] === 55
                && $data['device_identifier'] === 'device-1';
        }));

    $service = new UserService($userRepository, $deviceRepository);
    $device = new DeviceInfo('device-1', 'Chrome', 'web', '127.0.0.1', 'agent');

    $result = $service->authenticateByAccessToken(
        \App\Enums\ThirdPartyAuthProviders::Google,
        $device,
        'google-access-token',
    );

    expect($result)->toBeInstanceOf(LoginResponse::class);
    expect($result->token)->toBe('token-xyz');
});

it('registers a new third-party user by access token when user does not exist', function (): void {
    $newUser = Mockery::mock(User::class)->makePartial();
    $newUser->id = 20;
    $newUser->email = 'new-google@example.com';
    $newUser->password = null;
    $newUser->shouldReceive('update')
        ->once()
        ->with(Mockery::on(function (array $data): bool {
            return array_key_exists('email_verified_at', $data)
                && $data['avatar'] === 'https://example.com/avatar.png';
        }))
        ->andReturnTrue();
    $newUser->shouldReceive('hasVerifiedEmail')->once()->andReturnTrue();
    $newUser->shouldReceive('createToken')
        ->once()
        ->with('user_auth_token')
        ->andReturn((object) [
            'plainTextToken' => 'new-token',
            'accessToken' => (object) ['id' => 77],
        ]);

    $strategy = Mockery::mock(GoogleAuthStrategy::class);
    $strategy->shouldReceive('getUserFromAccessToken')
        ->once()
        ->with('google-access-token')
        ->andReturn(new \App\DTO\User\Auth\ThirdPartyUserResponse(
            name: 'New Google User',
            email: 'new-google@example.com',
            avatar: 'https://example.com/avatar.png',
        ));
    app()->instance(GoogleAuthStrategy::class, $strategy);

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('getBy')
        ->once()
        ->with(['email' => 'new-google@example.com'])
        ->andReturn(new EloquentCollection([]));
    $userRepository->shouldReceive('create')
        ->once()
        ->with([
            'name' => 'New Google User',
            'email' => 'new-google@example.com',
            'phone' => null,
            'password' => null,
            'authenticated_by' => 'google',
        ])
        ->andReturn($newUser);
    $userRepository->shouldReceive('findByEmail')
        ->once()
        ->with('new-google@example.com')
        ->andReturn($newUser);

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);
    $deviceRepository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $data): bool {
            return $data['user_id'] === 20
                && $data['personal_access_token_id'] === 77
                && $data['device_identifier'] === 'device-2';
        }));

    Bus::fake();

    $service = new UserService($userRepository, $deviceRepository);
    $device = new DeviceInfo('device-2', 'Chrome', 'web', '127.0.0.1', 'agent');

    $result = $service->authenticateByAccessToken(
        \App\Enums\ThirdPartyAuthProviders::Google,
        $device,
        'google-access-token',
    );

    expect($result->token)->toBe('new-token');
    Bus::assertDispatched(InitUserStarJob::class);
    Bus::assertNotDispatched(SendEmailVerificationJob::class);
});

it('throws a wrapped exception when third-party registration fails by access token', function (): void {
    $strategy = Mockery::mock(GoogleAuthStrategy::class);
    $strategy->shouldReceive('getUserFromAccessToken')
        ->once()
        ->andReturn(new \App\DTO\User\Auth\ThirdPartyUserResponse(
            name: 'Google User',
            email: 'google@example.com',
            avatar: null,
        ));
    app()->instance(GoogleAuthStrategy::class, $strategy);

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('getBy')
        ->once()
        ->with(['email' => 'google@example.com'])
        ->andReturn(new EloquentCollection([]));
    $userRepository->shouldReceive('create')
        ->once()
        ->andReturn(new \App\Models\UserDevice);

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);

    $service = new UserService($userRepository, $deviceRepository);

    expect(fn () => $service->authenticateByAccessToken(
        \App\Enums\ThirdPartyAuthProviders::Google,
        new DeviceInfo('device-3'),
        'google-access-token',
    ))->toThrow(Exception::class, 'Có lỗi xảy ra trong quá trình đăng nhập');
});

it('wraps the specific third-party registration failure by access token', function (): void {
    $strategy = Mockery::mock(GoogleAuthStrategy::class);
    $strategy->shouldReceive('getUserFromAccessToken')
        ->once()
        ->andReturn(new \App\DTO\User\Auth\ThirdPartyUserResponse(
            name: 'Google User',
            email: 'google2@example.com',
            avatar: null,
        ));
    app()->instance(GoogleAuthStrategy::class, $strategy);

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('getBy')
        ->once()
        ->with(['email' => 'google2@example.com'])
        ->andReturn(new EloquentCollection([]));

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);

    $service = Mockery::mock(UserService::class, [$userRepository, $deviceRepository])
        ->makePartial();
    $failedRegisterResponse = Mockery::mock(RegisterResponse::class, [Mockery::mock(User::class)]);
    $failedRegisterResponse->shouldReceive('isSuccessfully')->once()->andReturnFalse();
    $service->shouldReceive('register')
        ->once()
        ->with(
            'Google User',
            'google2@example.com',
            null,
            null,
            AuthenticatedBy::Google,
        )
        ->andReturn($failedRegisterResponse);

    try {
        $service->authenticateByAccessToken(
            \App\Enums\ThirdPartyAuthProviders::Google,
            new DeviceInfo('device-3b'),
            'google-access-token',
        );
        $this->fail('Expected exception was not thrown.');
    } catch (Exception $exception) {
        expect($exception->getMessage())->toBe('Có lỗi xảy ra trong quá trình đăng nhập');
        expect($exception->getPrevious())->toBeInstanceOf(Exception::class);
        expect($exception->getPrevious()->getMessage())->toBe('Đăng ký qua dịch vụ thứ ba thất bại');
    }
});

it('authenticates existing third-party user by id token', function (): void {
    $user = Mockery::mock(User::class)->makePartial();
    $user->id = 30;
    $user->email = 'idtoken@example.com';
    $user->password = null;
    $user->shouldReceive('hasVerifiedEmail')->once()->andReturnTrue();
    $user->shouldReceive('createToken')
        ->once()
        ->with('user_auth_token')
        ->andReturn((object) [
            'plainTextToken' => 'id-token-login',
            'accessToken' => (object) ['id' => 88],
        ]);

    $strategy = Mockery::mock(GoogleAuthStrategy::class);
    $strategy->shouldReceive('getUserFromIdToken')
        ->once()
        ->with('google-id-token')
        ->andReturn(new \App\DTO\User\Auth\ThirdPartyUserResponse(
            name: 'Id Token User',
            email: 'idtoken@example.com',
            avatar: null,
        ));
    app()->instance(GoogleAuthStrategy::class, $strategy);

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('getBy')
        ->once()
        ->with(['email' => 'idtoken@example.com'])
        ->andReturn(new EloquentCollection([$user]));
    $userRepository->shouldReceive('findByEmail')
        ->once()
        ->with('idtoken@example.com')
        ->andReturn($user);

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);
    $deviceRepository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $data): bool {
            return $data['user_id'] === 30
                && $data['personal_access_token_id'] === 88
                && $data['device_identifier'] === 'device-4';
        }));

    $service = new UserService($userRepository, $deviceRepository);

    $result = $service->authenticateByIdToken(
        \App\Enums\ThirdPartyAuthProviders::Google,
        new DeviceInfo('device-4'),
        'google-id-token',
    );

    expect($result->token)->toBe('id-token-login');
});

it('registers a new third-party user by id token when user does not exist', function (): void {
    $newUser = Mockery::mock(User::class)->makePartial();
    $newUser->id = 31;
    $newUser->email = 'new-idtoken@example.com';
    $newUser->password = null;
    $newUser->shouldReceive('update')
        ->once()
        ->with(Mockery::on(function (array $data): bool {
            return array_key_exists('email_verified_at', $data)
                && count($data) === 1;
        }))
        ->andReturnTrue();
    $newUser->shouldReceive('hasVerifiedEmail')->once()->andReturnTrue();
    $newUser->shouldReceive('createToken')
        ->once()
        ->with('user_auth_token')
        ->andReturn((object) [
            'plainTextToken' => 'id-new-token',
            'accessToken' => (object) ['id' => 89],
        ]);

    $strategy = Mockery::mock(GoogleAuthStrategy::class);
    $strategy->shouldReceive('getUserFromIdToken')
        ->once()
        ->with('google-id-token')
        ->andReturn(new \App\DTO\User\Auth\ThirdPartyUserResponse(
            name: 'New Id Token User',
            email: 'new-idtoken@example.com',
            avatar: null,
        ));
    app()->instance(GoogleAuthStrategy::class, $strategy);

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('getBy')
        ->once()
        ->with(['email' => 'new-idtoken@example.com'])
        ->andReturn(new EloquentCollection([]));
    $userRepository->shouldReceive('create')
        ->once()
        ->with([
            'name' => 'New Id Token User',
            'email' => 'new-idtoken@example.com',
            'phone' => null,
            'password' => null,
            'authenticated_by' => 'google',
        ])
        ->andReturn($newUser);
    $userRepository->shouldReceive('findByEmail')
        ->once()
        ->with('new-idtoken@example.com')
        ->andReturn($newUser);

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);
    $deviceRepository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $data): bool {
            return $data['user_id'] === 31
                && $data['personal_access_token_id'] === 89
                && $data['device_identifier'] === 'device-5';
        }));

    Bus::fake();

    $service = new UserService($userRepository, $deviceRepository);

    $result = $service->authenticateByIdToken(
        \App\Enums\ThirdPartyAuthProviders::Google,
        new DeviceInfo('device-5'),
        'google-id-token',
    );

    expect($result->token)->toBe('id-new-token');
});

it('wraps third-party registration failure by id token', function (): void {
    $strategy = Mockery::mock(GoogleAuthStrategy::class);
    $strategy->shouldReceive('getUserFromIdToken')
        ->once()
        ->with('google-id-token')
        ->andReturn(new \App\DTO\User\Auth\ThirdPartyUserResponse(
            name: 'Broken Id Token User',
            email: 'broken-idtoken@example.com',
            avatar: null,
        ));
    app()->instance(GoogleAuthStrategy::class, $strategy);

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('getBy')
        ->once()
        ->with(['email' => 'broken-idtoken@example.com'])
        ->andReturn(new EloquentCollection([]));

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);

    $service = Mockery::mock(UserService::class, [$userRepository, $deviceRepository])
        ->makePartial();
    $failedRegisterResponse = Mockery::mock(RegisterResponse::class, [Mockery::mock(User::class)]);
    $failedRegisterResponse->shouldReceive('isSuccessfully')->once()->andReturnFalse();
    $service->shouldReceive('register')
        ->once()
        ->with(
            'Broken Id Token User',
            'broken-idtoken@example.com',
            null,
            null,
            AuthenticatedBy::Google,
        )
        ->andReturn($failedRegisterResponse);

    try {
        $service->authenticateByIdToken(
            \App\Enums\ThirdPartyAuthProviders::Google,
            new DeviceInfo('device-6'),
            'google-id-token',
        );
        $this->fail('Expected exception was not thrown.');
    } catch (Exception $exception) {
        expect($exception->getMessage())->toBe('Có lỗi xảy ra trong quá trình đăng nhập');
        expect($exception->getPrevious())->toBeInstanceOf(Exception::class);
        expect($exception->getPrevious()->getMessage())->toBe('Failed to register third-party user.');
    }
});

it('throws when email is not verified', function (): void {
    $user = Mockery::mock(User::class)->makePartial();
    $user->password = Hash::make('secret');
    $user->shouldReceive('hasVerifiedEmail')->once()->andReturnFalse();

    $userRepository = Mockery::mock(IUserRepository::class);
    $userRepository->shouldReceive('findByEmail')
        ->once()
        ->with('test@example.com')
        ->andReturn($user);

    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);

    $service = new UserService($userRepository, $deviceRepository);
    $device = new DeviceInfo('device-1');

    expect(fn () => $service->login('test@example.com', 'secret', $device))
        ->toThrow(Exception::class, 'Email is not verified.');
});

it('logs out by token', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('logout')->plainTextToken;
    $accessToken = PersonalAccessToken::findToken($token);

    $userRepository = Mockery::mock(IUserRepository::class);
    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);
    $deviceRepository->shouldReceive('markLoggedOutByTokenId')
        ->once()
        ->with($accessToken->id)
        ->andReturn(1);

    $service = new UserService($userRepository, $deviceRepository);

    expect($service->logoutByToken($token))->toBeTrue();
    expect(PersonalAccessToken::findToken($token))->toBeNull();
});

it('returns false when token is invalid', function (): void {
    $userRepository = Mockery::mock(IUserRepository::class);
    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);
    $deviceRepository->shouldReceive('markLoggedOutByTokenId')->never();

    $service = new UserService($userRepository, $deviceRepository);

    expect($service->logoutByToken('invalid-token'))->toBeFalse();
});

it('sends a password reset link through the broker', function (): void {
    Password::shouldReceive('broker')
        ->once()
        ->with('users')
        ->andReturnSelf();
    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => 'test@example.com'])
        ->andReturn(Password::RESET_LINK_SENT);

    $userRepository = Mockery::mock(IUserRepository::class);
    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);
    $service = new UserService($userRepository, $deviceRepository);

    $service->sendPasswordResetLink('test@example.com');
});

it('resets password and revokes existing tokens', function (): void {
    $tokenRelation = new class
    {
        public bool $deleted = false;

        public function delete(): void
        {
            $this->deleted = true;
        }
    };

    $user = Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('forceFill')
        ->once()
        ->with(Mockery::on(function (array $data): bool {
            return $data['password'] === 'new-password'
                && is_string($data['remember_token'])
                && strlen($data['remember_token']) === 60;
        }))
        ->andReturnSelf();
    $user->shouldReceive('save')->once();
    $user->shouldReceive('tokens')->once()->andReturn($tokenRelation);

    Event::fake([PasswordReset::class]);

    Password::shouldReceive('broker')
        ->once()
        ->with('users')
        ->andReturnSelf();
    Password::shouldReceive('reset')
        ->once()
        ->with(
            [
                'email' => 'test@example.com',
                'token' => 'reset-token',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ],
            Mockery::type(Closure::class),
        )
        ->andReturnUsing(function (array $payload, Closure $callback) use ($user) {
            $callback($user, $payload['password']);

            return Password::PASSWORD_RESET;
        });

    $userRepository = Mockery::mock(IUserRepository::class);
    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);
    $service = new UserService($userRepository, $deviceRepository);

    expect($service->resetPassword('test@example.com', 'reset-token', 'new-password'))->toBeTrue();
    Event::assertDispatched(PasswordReset::class);
    expect($tokenRelation->deleted)->toBeTrue();
});

it('returns false when password reset token is invalid', function (): void {
    Password::shouldReceive('broker')
        ->once()
        ->with('users')
        ->andReturnSelf();
    Password::shouldReceive('reset')
        ->once()
        ->andReturn(Password::INVALID_TOKEN);

    $userRepository = Mockery::mock(IUserRepository::class);
    $deviceRepository = Mockery::mock(IUserDeviceRepository::class);
    $service = new UserService($userRepository, $deviceRepository);

    expect($service->resetPassword('test@example.com', 'invalid-token', 'new-password'))->toBeFalse();
});
