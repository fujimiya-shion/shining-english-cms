<?php

namespace App\Http\Controllers\Api\V1\Developer;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Developer\DeveloperLoginRequest;
use App\Services\Developer\IDeveloperService;

class DeveloperController extends ApiController
{
    public function __construct(
        protected IDeveloperService $service,
    ) {}

    public function accessToken(DeveloperLoginRequest $request) {
        $credentials = $request->validated();
        $developer = $this->service->login(
            $credentials["email"], 
            $credentials["password"]
        );
        if(! $developer)
            return $this->unauthorized();

        $accessToken = $developer->createToken('developer_access_token');

        return $this->success(data: [
            'access_token' => $accessToken->plainTextToken,
        ]);
        
    }
}
