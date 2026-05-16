<?php

namespace App\Integrations\Auth\Strategies;

use App\DTO\User\Auth\ThirdPartyUserResponse;
use App\Enums\ThirdPartyAuthProviders;
use App\Integrations\Auth\Contracts\ThirdPartyAuthStrategy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;
use Throwable;

class GoogleAuthStrategy implements ThirdPartyAuthStrategy
{
    public function provider(): ThirdPartyAuthProviders
    {
        return ThirdPartyAuthProviders::Google;
    }

    public function getUserFromAccessToken(string $accessToken): ThirdPartyUserResponse
    {
        try {
            Log::info('Google auth strategy fetching user from access token', [
                'provider' => $this->provider()->value,
                'has_access_token' => filled($accessToken),
            ]);

            /** @var AbstractProvider $provider */
            $provider = Socialite::driver($this->provider()->value);

            $googleUser = $provider
                ->stateless()
                ->userFromToken($accessToken);
        } catch (Throwable $exception) {
            Log::error('Google auth strategy failed to fetch user from access token', [
                'provider' => $this->provider()->value,
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]);
            throw new RuntimeException('Failed to fetch Google user from access token.', previous: $exception);
        }

        Log::info('Google auth strategy fetched user from access token', [
            'provider' => $this->provider()->value,
            'email' => $googleUser->getEmail(),
            'name' => $googleUser->getName(),
            'has_avatar' => filled($googleUser->getAvatar()),
        ]);

        return new ThirdPartyUserResponse(
            name: $googleUser->getName() ?: $googleUser->getNickname() ?: $googleUser->getEmail() ?: 'Google User',
            email: $googleUser->getEmail() ?: throw new RuntimeException('Google account email is missing.'),
            avatar: $googleUser->getAvatar(),
        );
    }

    public function getUserFromIdToken(string $idToken): ThirdPartyUserResponse
    {
        $response = Http::acceptJson()
            ->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Failed to verify Google id token.');
        }

        $payload = $response->json();

        if (! is_array($payload) || empty($payload['email'])) {
            throw new RuntimeException('Google id token payload is invalid.');
        }

        return new ThirdPartyUserResponse(
            name: $payload['name'] ?? $payload['email'],
            email: $payload['email'],
            avatar: $payload['picture'] ?? null,
        );
    }
}
