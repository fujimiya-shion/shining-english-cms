<?php

use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Requests\Api\V1\User\UserUpdateRequest;
use App\Models\User;
use App\Services\User\IUserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    \Mockery::close();
});

it('updates the authenticated user', function (): void {
    $user = new User;
    $user->id = 6;
    $updated = new User(['name' => 'Updated']);
    $updated->id = 6;

    $service = \Mockery::mock(IUserService::class);
    $service->shouldReceive('updateProfile')->once()->with($user, ['name' => 'Updated'])->andReturn($updated);
    app()->instance(IUserService::class, $service);

    $controller = app()->make(UserController::class);
    $request = UserUpdateRequest::create('/api/v1/user', 'PATCH', [
        'name' => 'Updated',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->setUserResolver(fn (): User => $user);
    $request->validateResolved();

    $response = $controller->update($request);

    assertJsonResponsePayload($response, 200, [
        'message' => 'Updated',
        'status' => true,
        'status_code' => 200,
        'data' => [
            'id' => 6,
            'name' => 'Updated',
            'city_name' => null,
            'city' => null,
        ],
    ]);
});

it('returns not found when updating a missing user', function (): void {
    $user = new User;
    $user->id = 6;

    $service = \Mockery::mock(IUserService::class);
    $service->shouldReceive('updateProfile')->once()->andThrow(new ModelNotFoundException);
    app()->instance(IUserService::class, $service);

    $controller = app()->make(UserController::class);
    $request = UserUpdateRequest::create('/api/v1/user', 'PATCH', [
        'name' => 'Updated',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->setUserResolver(fn (): User => $user);
    $request->validateResolved();

    $response = $controller->update($request);

    assertJsonResponsePayload($response, 404, [
        'message' => 'User not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('returns an error response when updating a user fails unexpectedly', function (): void {
    $user = new User;
    $user->id = 6;

    $service = \Mockery::mock(IUserService::class);
    $service->shouldReceive('updateProfile')->once()->andThrow(new RuntimeException('boom'));
    app()->instance(IUserService::class, $service);

    $controller = app()->make(UserController::class);
    $request = UserUpdateRequest::create('/api/v1/user', 'PATCH', [
        'name' => 'Updated',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->setUserResolver(fn (): User => $user);
    $request->validateResolved();

    $response = $controller->update($request);

    assertJsonResponsePayload($response, 422, [
        'message' => 'Update failed',
        'status' => false,
        'status_code' => 422,
    ]);
});
