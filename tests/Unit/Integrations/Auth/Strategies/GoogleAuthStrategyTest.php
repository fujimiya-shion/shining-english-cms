<?php

use App\DTO\User\Auth\ThirdPartyUserResponse;
use App\Enums\ThirdPartyAuthProviders;
use App\Integrations\Auth\Strategies\GoogleAuthStrategy;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Tests\TestCase;

uses(TestCase::class);

it('returns google as its provider', function (): void {
    $strategy = new GoogleAuthStrategy;

    expect($strategy->provider())->toBe(ThirdPartyAuthProviders::Google);
});

it('maps user data from google access token', function (): void {
    $socialiteUser = Mockery::mock(SocialiteUserContract::class);
    $socialiteUser->shouldReceive('getName')->andReturn('Google User');
    $socialiteUser->shouldReceive('getEmail')->andReturn('google@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->once()->andReturnSelf();
    $provider->shouldReceive('userFromToken')->once()->with('access-token')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

    $response = (new GoogleAuthStrategy)->getUserFromAccessToken('access-token');

    expect($response)->toBeInstanceOf(ThirdPartyUserResponse::class);
    expect($response->toJson())->toBe([
        'name' => 'Google User',
        'email' => 'google@example.com',
        'avatar' => 'https://example.com/avatar.png',
    ]);
});

it('throws when fetching user from google access token fails', function (): void {
    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->once()->andReturnSelf();
    $provider->shouldReceive('userFromToken')
        ->once()
        ->with('bad-access-token')
        ->andThrow(new RuntimeException('google failed'));

    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

    expect(fn () => (new GoogleAuthStrategy)->getUserFromAccessToken('bad-access-token'))
        ->toThrow(RuntimeException::class, 'Failed to fetch Google user from access token.');
});

it('throws when google access token response does not include email', function (): void {
    $socialiteUser = Mockery::mock(SocialiteUserContract::class);
    $socialiteUser->shouldReceive('getName')->andReturn(null);
    $socialiteUser->shouldReceive('getNickname')->andReturn(null);
    $socialiteUser->shouldReceive('getEmail')->andReturn(null);
    $socialiteUser->shouldReceive('getAvatar')->andReturn(null);

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->once()->andReturnSelf();
    $provider->shouldReceive('userFromToken')->once()->with('access-token')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

    expect(fn () => (new GoogleAuthStrategy)->getUserFromAccessToken('access-token'))
        ->toThrow(RuntimeException::class, 'Google account email is missing.');
});

it('maps user data from google id token payload', function (): void {
    Http::fake([
        'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
            'name' => 'Google User',
            'email' => 'google@example.com',
            'picture' => 'https://example.com/avatar.png',
        ], 200),
    ]);

    $response = (new GoogleAuthStrategy)->getUserFromIdToken('id-token');

    expect($response->toJson())->toBe([
        'name' => 'Google User',
        'email' => 'google@example.com',
        'avatar' => 'https://example.com/avatar.png',
    ]);
});

it('throws when google id token verification fails', function (): void {
    Http::fake([
        'https://oauth2.googleapis.com/tokeninfo*' => Http::response([], 400),
    ]);

    expect(fn () => (new GoogleAuthStrategy)->getUserFromIdToken('bad-token'))
        ->toThrow(RuntimeException::class, 'Failed to verify Google id token.');
});

it('throws when google id token payload is missing email', function (): void {
    Http::fake([
        'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
            'name' => 'Google User',
        ], 200),
    ]);

    expect(fn () => (new GoogleAuthStrategy)->getUserFromIdToken('bad-token'))
        ->toThrow(RuntimeException::class, 'Google id token payload is invalid.');
});
