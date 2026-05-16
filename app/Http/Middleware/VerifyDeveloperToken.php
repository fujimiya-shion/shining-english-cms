<?php

namespace App\Http\Middleware;

use App\Traits\Jsonable;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeveloperToken
{
    use Jsonable;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->header('Authorization');

        if (! is_string($token) || trim($token) === '') {
            return $this->unauthorized('Access Token is not set or invalid');
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken || $accessToken->name !== 'developer_access_token') {
            return $this->unauthorized('Access Token is not set or invalid');
        }

        if (! $accessToken->tokenable instanceof \App\Models\Developer) {
            return $this->unauthorized('Access Token is not set or invalid');
        }

        return $next($request);
    }
}
