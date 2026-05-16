<?php

use App\Http\Controllers\Api\V1\Developer\DeveloperController;
use App\Http\Requests\Api\V1\Developer\DeveloperLoginRequest;
use App\Models\Developer;
use App\Services\Developer\IDeveloperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

afterEach(function (): void {
    \Mockery::close();
});

it('returns unauthorized when developer credentials are invalid', function (): void {
    $service = \Mockery::mock(IDeveloperService::class);
    $service->shouldReceive('login')->once()->with('dev@example.com', 'secret')->andReturnNull();
    app()->instance(IDeveloperService::class, $service);

    $controller = app()->make(DeveloperController::class);
    $request = DeveloperLoginRequest::create('/api/v1/developer/access-token', 'POST', [
        'email' => 'dev@example.com',
        'password' => 'secret',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $response = $controller->accessToken($request);

    assertJsonResponsePayload($response, 401, [
        'message' => 'Unauthorized',
        'status' => false,
        'status_code' => 401,
    ]);
});

it('returns a developer access token for valid credentials', function (): void {
    $developer = Developer::query()->create([
        'email' => 'dev@example.com',
        'password' => 'secret123',
    ]);

    $service = \Mockery::mock(IDeveloperService::class);
    $service->shouldReceive('login')->once()->with('dev@example.com', 'secret123')->andReturn($developer);
    app()->instance(IDeveloperService::class, $service);

    $controller = app()->make(DeveloperController::class);
    $request = DeveloperLoginRequest::create('/api/v1/developer/access-token', 'POST', [
        'email' => 'dev@example.com',
        'password' => 'secret123',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $response = $controller->accessToken($request);
    $payload = $response->getData(true);

    expect($payload['status'])->toBeTrue();
    expect($payload['data']['access_token'])->toBeString()->not->toBe('');
});
