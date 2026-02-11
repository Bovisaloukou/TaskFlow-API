<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->organization_id) {
            return response()->json([
                'message' => 'No organization context found.',
            ], 403);
        }

        return $next($request);
    }
}
