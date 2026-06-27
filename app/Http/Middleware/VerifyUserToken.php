<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Traits\Jsonable;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class VerifyUserToken
{
    use Jsonable;

    public function handle(Request $request, Closure $next): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $token = $request->header('User-Authorization');

        if (! is_string($token) || trim($token) === '') {
            return $this->error('Unauthenticated', 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken || ! $accessToken->tokenable instanceof User) {
            return $this->error('Unauthenticated', 401);
        }

        $user = $accessToken->tokenable;
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
