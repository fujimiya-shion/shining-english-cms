<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\User\UserUpdateRequest;
use App\Services\User\IUserService;
use App\Traits\Jsonable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Throwable;

class UserController extends ApiController
{
    use Jsonable;

    public function __construct(
        protected IUserService $service
    ) {}

    public function update(UserUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        try {
            $updated = $this->service->updateProfile($user, $data);

            return $this->success('Updated', $updated);
        } catch (ModelNotFoundException $e) {
            return $this->notfound('User not found');
        } catch (Throwable $e) {
            return $this->error('Update failed', 422);
        }
    }
}
