<?php
namespace App\Integrations\Auth\Contracts;

use App\DTO\User\Auth\ThirdPartyUserResponse;
use App\Enums\ThirdPartyAuthProviders;
interface ThirdPartyAuthStrategy {
    public function provider(): ThirdPartyAuthProviders;
    public function getUserFromAccessToken(string $accessToken): ThirdPartyUserResponse;
    public function getUserFromIdToken(String $idToken): ThirdPartyUserResponse;
}