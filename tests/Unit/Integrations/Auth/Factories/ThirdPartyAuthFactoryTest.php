<?php

use App\Enums\ThirdPartyAuthProviders;
use App\Integrations\Auth\Factories\ThirdPartyAuthFactory;
use App\Integrations\Auth\Strategies\GoogleAuthStrategy;

it('resolves google auth strategy from the container', function (): void {
    $strategy = new GoogleAuthStrategy;
    app()->instance(GoogleAuthStrategy::class, $strategy);

    expect(ThirdPartyAuthFactory::make(ThirdPartyAuthProviders::Google))->toBe($strategy);
});
