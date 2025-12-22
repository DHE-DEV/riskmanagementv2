<?php

namespace App\Http\Controllers\Api\Plugin;

use App\Http\Controllers\Controller;
use App\Models\PluginKey;
use App\Models\PluginUsageEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HandshakeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'domain' => ['required', 'string'],
            'path' => ['nullable', 'string', 'max:2048'],
            'event_type' => ['nullable', 'string', 'max:50'],
            'meta' => ['nullable', 'array'],
        ]);

        $publicKey = $validated['key'];
        $domain = $this->normalizeDomain($validated['domain']);
        $path = $validated['path'] ?? null;
        $eventType = $validated['event_type'] ?? 'page_load';
        $meta = $validated['meta'] ?? null;

        // Find the key
        $pluginKey = PluginKey::where('public_key', $publicKey)
            ->where('is_active', true)
            ->first();

        if (!$pluginKey) {
            return response()->json([
                'allowed' => false,
                'error' => 'Invalid or inactive key',
            ], 403);
        }

        $pluginClient = $pluginKey->pluginClient;

        // Check if client is active
        if (!$pluginClient->isActive()) {
            return response()->json([
                'allowed' => false,
                'error' => 'Client account is not active',
            ], 403);
        }

        // Check domain whitelist
        if (!$pluginClient->hasDomain($domain)) {
            return response()->json([
                'allowed' => false,
                'error' => 'Domain not authorized',
            ], 403);
        }

        // Log usage event
        PluginUsageEvent::log(
            client: $pluginClient,
            publicKey: $publicKey,
            domain: $domain,
            path: $path,
            eventType: $eventType,
            meta: $meta,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return response()->json([
            'allowed' => true,
            'config' => [
                // Future: add widget configuration options here
            ],
        ]);
    }

    protected function normalizeDomain(string $domain): string
    {
        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $domain);
        // Remove www.
        $domain = preg_replace('#^www\.#', '', $domain);
        // Remove path and port
        $domain = explode('/', $domain)[0];
        $domain = explode(':', $domain)[0];

        return strtolower(trim($domain));
    }
}
