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
     * Domains that are allowed to embed without an API key (for local development only).
     */
    protected array $allowedDomainsWithoutKey = [
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
        $apiKey = $request->query('key');
        $isFromAllowedDomain = $this->isFromAllowedDomain($request);

        // If no key provided, only allow from whitelisted domains
        if (empty($apiKey)) {
            if ($isFromAllowedDomain) {
                return $next($request);
            }
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

        // Validate domain (check Referer header) - skip for allowed dev domains
        if (!$isFromAllowedDomain) {
            $referer = $request->header('Referer');
            $refererHost = $referer ? parse_url($referer, PHP_URL_HOST) : null;

            // App-Modus: Kein Referer vorhanden
            if (empty($refererHost)) {
                if ($client->allow_app_access) {
                    // App-Zugang erlaubt - track as app_view
                    $this->trackUsage($client, $request, $apiKey, 'app_view', 'app');
                    $request->attributes->set('plugin_client', $client);
                    $request->attributes->set('access_mode', 'app');
                    return $next($request);
                }

                return $this->unauthorizedResponse('App-Zugang nicht aktiviert. Bitte im Dashboard aktivieren.');
            }

            // Web-Embed-Modus: Domain validieren
            $domainStatus = $this->getDomainStatus($client, $refererHost);
            if ($domainStatus === 'deactivated') {
                return $this->unauthorizedResponse('Diese Domain wurde vorübergehend deaktiviert. Bitte kontaktieren Sie den Administrator.');
            } elseif ($domainStatus === 'not_registered') {
                return $this->unauthorizedResponse('Domain nicht autorisiert: ' . $refererHost);
            }
        }

        // Track usage event (Web-Embed)
        $this->trackUsage($client, $request, $apiKey, 'embed_view', 'embed');

        // Store client in request for potential use in views
        $request->attributes->set('plugin_client', $client);
        $request->attributes->set('access_mode', 'embed');

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
     * Get domain status for this client.
     *
     * @return string 'allowed', 'deactivated', or 'not_registered'
     */
    protected function getDomainStatus(PluginClient $client, string $domain): string
    {
        // Remove www. prefix for comparison
        $domain = preg_replace('/^www\./', '', strtolower($domain));

        // If no domains registered at all, allow all (for testing)
        if ($client->domains->isEmpty()) {
            return 'allowed';
        }

        // Check all domains
        foreach ($client->domains as $registeredDomain) {
            $normalizedDomain = preg_replace('/^www\./', '', strtolower($registeredDomain->domain));

            if ($normalizedDomain === $domain) {
                // Domain found - check if active
                return $registeredDomain->is_active ? 'allowed' : 'deactivated';
            }
        }

        // Domain not found in registered domains
        return 'not_registered';
    }

    /**
     * Track usage event.
     */
    protected function trackUsage(PluginClient $client, Request $request, string $publicKey, string $eventType = 'embed_view', string $accessMode = 'embed'): void
    {
        try {
            $referer = $request->header('Referer');
            $domain = $accessMode === 'app' ? 'app' : ($referer ? parse_url($referer, PHP_URL_HOST) : 'direct');

            PluginUsageEvent::create([
                'plugin_client_id' => $client->id,
                'public_key' => $publicKey,
                'event_type' => $eventType,
                'domain' => $domain,
                'path' => $request->path(),
                'ip_hash' => PluginUsageEvent::hashIp($request->ip()),
                'user_agent' => $request->userAgent() ? substr($request->userAgent(), 0, 255) : null,
                'created_at' => now(),
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
