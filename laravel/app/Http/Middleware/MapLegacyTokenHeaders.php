<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The original Express API accepted tokens in `x-auth-token` (admins) and
 * `x-vote-token` (voters) as well as `Authorization: Bearer`. Sanctum only
 * reads the latter, so copy the legacy headers across when Bearer is absent.
 */
class MapLegacyTokenHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() === null) {
            $legacy = $request->header('x-auth-token') ?? $request->header('x-vote-token');

            if ($legacy !== null && $legacy !== '') {
                $request->headers->set('Authorization', 'Bearer '.$legacy);
            }
        }

        return $next($request);
    }
}
