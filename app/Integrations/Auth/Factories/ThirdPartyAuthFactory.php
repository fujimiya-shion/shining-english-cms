<?php

namespace App\Integrations\Auth\Factories;

use App\Enums\ThirdPartyAuthProviders;
use App\Integrations\Auth\Contracts\ThirdPartyAuthStrategy;
use App\Integrations\Auth\Strategies\GoogleAuthStrategy;

class ThirdPartyAuthFactory
{
    public static function make(ThirdPartyAuthProviders $provider): ThirdPartyAuthStrategy
    {
        return match ($provider) {
            ThirdPartyAuthProviders::Google => app(GoogleAuthStrategy::class),
        };
    }
}
