<?php

namespace App\Http\Middleware;

use App\Models\ApiClientRequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiClientRequestLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('api_client_request_start', microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $startTime = $request->attributes->get('api_client_request_start');
        $responseTimeMs = $startTime ? (int) round((microtime(true) - $startTime) * 1000) : null;

        $apiClient = $request->attributes->get('api_client');
        if (!$apiClient) {
            return;
        }

        try {
            ApiClientRequestLog::create([
                'api_client_id' => $apiClient->id,
                'token_id' => $apiClient->currentAccessToken()?->id,
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
            \Illuminate\Support\Facades\Log::warning('API client request logging failed: ' . $e->getMessage());
        }
    }
}
