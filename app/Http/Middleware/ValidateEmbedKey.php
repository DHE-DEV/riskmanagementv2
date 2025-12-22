<?php

namespace App\Http\Middleware;

use App\Models\PluginClient;
use App\Models\PluginKey;
use App\Models\PluginUsageEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateEmbedKey
{
    /**
     * Domains that are allowed to embed without an API key (for documentation/demos).
     */
    protected array $allowedDomainsWithoutKey = [
        'global-travel-monitor.de',
        'global-travel-monitor.eu',
        'livetest.global-travel-monitor.eu',
        'localhost',
        '127.0.0.1',
    ];

    /**
     * Handle an incoming request.
     *
     * Validates the API key for embed routes and tracks usage.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if request comes from an allowed domain (for docs/demos)
        if ($this->isFromAllowedDomain($request)) {
            return $next($request);
        }

        $apiKey = $request->query('key');

        // Check if key is provided
        if (empty($apiKey)) {
            return $this->unauthorizedResponse('API-Key erforderlich. Fügen Sie ?key=YOUR_API_KEY zur URL hinzu.');
        }

        // Validate key format
        if (!str_starts_with($apiKey, 'pk_live_')) {
            return $this->unauthorizedResponse('Ungültiges API-Key Format.');
        }

        // Find the key
        $pluginKey = PluginKey::where('public_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$pluginKey) {
            return $this->unauthorizedResponse('Ungültiger oder deaktivierter API-Key.');
        }

        // Get the client
        $client = $pluginKey->pluginClient;

        if (!$client || $client->status !== 'active') {
            return $this->unauthorizedResponse('Plugin-Konto ist nicht aktiv.');
        }

        // Optional: Validate domain (check Referer header)
        $referer = $request->header('Referer');
        if ($referer) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            if ($refererHost && !$this->isDomainAllowed($client, $refererHost)) {
                return $this->unauthorizedResponse('Domain nicht autorisiert: ' . $refererHost);
            }
        }

        // Track usage event
        $this->trackUsage($client, $request);

        // Store client in request for potential use in views
        $request->attributes->set('plugin_client', $client);

        return $next($request);
    }

    /**
     * Check if request comes from an allowed domain that doesn't need an API key.
     */
    protected function isFromAllowedDomain(Request $request): bool
    {
        $referer = $request->header('Referer');

        if (!$referer) {
            return false;
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);

        if (!$refererHost) {
            return false;
        }

        // Remove www. prefix for comparison
        $refererHost = preg_replace('/^www\./', '', strtolower($refererHost));

        foreach ($this->allowedDomainsWithoutKey as $allowedDomain) {
            $allowedDomain = preg_replace('/^www\./', '', strtolower($allowedDomain));

            // Exact match or subdomain match
            if ($refererHost === $allowedDomain || str_ends_with($refererHost, '.' . $allowedDomain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the domain is allowed for this client.
     */
    protected function isDomainAllowed(PluginClient $client, string $domain): bool
    {
        // Remove www. prefix for comparison
        $domain = preg_replace('/^www\./', '', strtolower($domain));

        // Check if client has any domains registered
        $allowedDomains = $client->domains->pluck('domain')->map(function ($d) {
            return preg_replace('/^www\./', '', strtolower($d));
        })->toArray();

        // If no domains registered, allow all (for testing)
        if (empty($allowedDomains)) {
            return true;
        }

        return in_array($domain, $allowedDomains);
    }

    /**
     * Track usage event.
     */
    protected function trackUsage(PluginClient $client, Request $request): void
    {
        try {
            PluginUsageEvent::create([
                'plugin_client_id' => $client->id,
                'event_type' => 'embed_view',
                'domain' => $request->header('Referer') ? parse_url($request->header('Referer'), PHP_URL_HOST) : null,
                'path' => $request->path(),
                'ip_hash' => PluginUsageEvent::hashIp($request->ip()),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Don't fail the request if tracking fails
            \Log::warning('Failed to track embed usage: ' . $e->getMessage());
        }
    }

    /**
     * Return unauthorized response.
     */
    protected function unauthorizedResponse(string $message): Response
    {
        return response()->view('errors.embed-unauthorized', [
            'message' => $message,
        ], 403);
    }
}
