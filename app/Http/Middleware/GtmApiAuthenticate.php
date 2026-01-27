<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GtmApiAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Check if the token has gtm:read ability
        if (!$user->tokenCan('gtm:read')) {
            return response()->json([
                'success' => false,
                'message' => 'Token does not have GTM API access. Please regenerate your API token.',
            ], 403);
        }

        // Check if GTM API is enabled for this customer
        if (!$user->gtm_api_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'GTM API access is not enabled for your account.',
            ], 403);
        }

        return $next($request);
    }
}
