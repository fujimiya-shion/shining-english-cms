<?php

namespace App\Http\Controllers\Api\V1\City;

use App\Http\Controllers\Api\ApiController;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class CityController extends ApiController
{
    public function index(): JsonResponse
    {
        $cities = City::query()
            ->select(['id', 'name', 'sort_order'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success(data: $cities);
    }
}

