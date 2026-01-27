<?php

namespace App\Http\Middleware;

use App\Models\GtmApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GtmApiRequestLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('gtm_request_start', microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $startTime = $request->attributes->get('gtm_request_start');
        $responseTimeMs = $startTime ? (int) round((microtime(true) - $startTime) * 1000) : null;

        $user = $request->user();
        if (!$user) {
            return;
        }

        try {
            GtmApiRequestLog::create([
                'customer_id' => $user->id,
                'token_id' => $user->currentAccessToken()?->id,
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'query_params' => $request->query() ?: null,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 512),
                'response_status' => $response->getStatusCode(),
                'response_time_ms' => $responseTimeMs,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('GTM API request logging failed: ' . $e->getMessage());
        }
    }
}
