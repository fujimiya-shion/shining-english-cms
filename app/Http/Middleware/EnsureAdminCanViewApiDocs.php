<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminCanViewApiDocs
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment(['local', 'staging'])) {
            return $next($request);
        }

        $admin = auth('admin')->user();

        if ($admin?->can('View:ApiDocs')) {
            return $next($request);
        }

        abort(403);
    }
}
