<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Api\ApiController;
use App\Services\User\IUserHomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends ApiController
{
    public function __construct(
        private IUserHomeService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userToken = $request->header('User-Authorization');
        $data = $this->service->getHomeData($userToken);

        return $this->success(data: $data->toArray());
    }
}
