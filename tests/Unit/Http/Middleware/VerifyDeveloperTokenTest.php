<?php

use App\Http\Middleware\VerifyDeveloperToken;
use App\Models\Developer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

uses(TestCase::class);
uses(DatabaseTransactions::class);

it('returns unauthorized json when developer token is missing', function (): void {
    $middleware = new VerifyDeveloperToken;
    $request = Request::create('/api/v1/courses', 'GET');

    $response = $middleware->handle($request, fn () => new Response);

    expect($response->getStatusCode())->toBe(401);
    expect($response->getData(true))->toMatchArray([
        'message' => 'Access Token is not set or invalid',
        'status' => false,
        'status_code' => 401,
    ]);
});

it('returns unauthorized json when token is invalid', function (): void {
    $middleware = new VerifyDeveloperToken;
    $request = Request::create('/api/v1/courses', 'GET', [], [], [], [
        'HTTP_AUTHORIZATION' => 'invalid-token',
    ]);

    $response = $middleware->handle($request, fn () => new Response);

    expect($response->getStatusCode())->toBe(401);
    expect($response->getData(true))->toMatchArray([
        'message' => 'Access Token is not set or invalid',
        'status' => false,
        'status_code' => 401,
    ]);
});

it('returns unauthorized json when token does not belong to a developer', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('developer_access_token')->plainTextToken;

    $middleware = new VerifyDeveloperToken;
    $request = Request::create('/api/v1/courses', 'GET', [], [], [], [
        'HTTP_AUTHORIZATION' => "Bearer {$token}",
    ]);

    $response = $middleware->handle($request, fn () => new Response);

    expect($response->getStatusCode())->toBe(401);
    expect($response->getData(true))->toMatchArray([
        'message' => 'Access Token is not set or invalid',
        'status' => false,
        'status_code' => 401,
    ]);
});

it('accepts a valid developer bearer token', function (): void {
    $developer = Developer::query()->create([
        'email' => 'developer@example.com',
        'password' => 'secret123',
    ]);
    $token = $developer->createToken('developer_access_token')->plainTextToken;

    $middleware = new VerifyDeveloperToken;
    $request = Request::create('/api/v1/courses', 'GET', [], [], [], [
        'HTTP_AUTHORIZATION' => "Bearer {$token}",
    ]);

    $response = $middleware->handle($request, fn () => new Response(status: 204));

    expect($response->getStatusCode())->toBe(204);
});
