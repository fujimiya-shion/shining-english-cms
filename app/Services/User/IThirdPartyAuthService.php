<?php
namespace App\Services\User;

use App\DTO\User\Auth\LoginResponse;
use App\Enums\ThirdPartyAuthProviders;
use App\Models\User;
use App\ValueObjects\DeviceInfo;
interface IThirdPartyAuthService {
    public function authenticateByAccessToken(ThirdPartyAuthProviders $provider, DeviceInfo $deviceInfo, string $accessToken): LoginResponse;
    public function authenticateByIdToken(ThirdPartyAuthProviders $provider, DeviceInfo $deviceInfo, string $idToken): LoginResponse;
}