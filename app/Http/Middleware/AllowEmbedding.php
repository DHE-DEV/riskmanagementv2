<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowEmbedding
{
    /**
     * Handle an incoming request.
     *
     * Removes X-Frame-Options header to allow iframe embedding.
     * Also sets appropriate CORS headers for cross-origin embedding.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Remove X-Frame-Options to allow embedding in iframes
        $response->headers->remove('X-Frame-Options');

        // Set Content-Security-Policy to allow embedding from any origin
        // You can restrict this to specific domains if needed:
        // $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' https://example.com");
        $response->headers->set('Content-Security-Policy', "frame-ancestors *");

        // Allow cross-origin access for embed resources
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        return $response;
    }
}
