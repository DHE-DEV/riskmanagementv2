<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiClientAuthenticate
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

        if (!$user instanceof ApiClient) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token type. This endpoint requires an API client token.',
            ], 403);
        }

        if (!$user->tokenCan('events:write')) {
            return response()->json([
                'success' => false,
                'message' => 'Token does not have the required permissions.',
            ], 403);
        }

        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'API client account is not active.',
            ], 403);
        }

        $request->attributes->set('api_client', $user);

        return $next($request);
    }
}
