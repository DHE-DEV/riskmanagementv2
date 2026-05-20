<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminToolsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('admin-tools.enabled')) {
            abort(404);
        }

        $configured = (string) config('admin-tools.token');
        if ($configured === '') {
            abort(404);
        }

        $provided = $request->header('X-Admin-Token') ?: $request->input('t');
        if (!hash_equals($configured, (string) $provided)) {
            abort(404);
        }

        $user = Auth::guard('web')->user();
        if (!$user || !($user->is_admin ?? false)) {
            Log::warning('AdminTools: access denied (no admin login)', [
                'ip' => $request->ip(),
                'user_id' => $user?->id,
            ]);
            abort(404);
        }

        return $next($request);
    }
}
