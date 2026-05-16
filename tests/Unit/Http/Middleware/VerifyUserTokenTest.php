<?php

use App\Http\Middleware\VerifyUserToken;
use App\Models\Developer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('returns unauthenticated json when header is missing', function (): void {
    $middleware = new VerifyUserToken;
    $request = Request::create('/api/v1/cart/items', 'GET');

    $response = $middleware->handle($request, fn () => new Response);

    expect($response->getStatusCode())->toBe(401);
    expect($response->getData(true))->toMatchArray([
        'message' => 'Unauthenticated',
        'status' => false,
        'status_code' => 401,
    ]);
});

it('resolves user from token without setting auth user', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('cart')->plainTextToken;

    $middleware = new VerifyUserToken;
    $request = Request::create('/api/v1/cart/items', 'GET', [], [], [], [
        'HTTP_USER_AUTHORIZATION' => $token,
    ]);

    $response = $middleware->handle($request, function (Request $request): Response {
        expect($request->user())->not->toBeNull();
        expect($request->user()->is($request->user()))->toBeTrue();
        expect(auth()->user())->toBeNull();

        return new Response(status: 200);
    });

    expect($response->getStatusCode())->toBe(200);
});

it('returns unauthenticated json when token belongs to a non-user model', function (): void {
    $developer = Developer::query()->create([
        'email' => 'dev@example.com',
        'password' => 'secret123',
    ]);
    $token = $developer->createToken('developer')->plainTextToken;

    $middleware = new VerifyUserToken;
    $request = Request::create('/api/v1/cart/items', 'GET', [], [], [], [
        'HTTP_USER_AUTHORIZATION' => $token,
    ]);

    $response = $middleware->handle($request, fn () => new Response);

    expect($response->getStatusCode())->toBe(401);
    expect($response->getData(true))->toMatchArray([
        'message' => 'Unauthenticated',
        'status' => false,
        'status_code' => 401,
    ]);
});
