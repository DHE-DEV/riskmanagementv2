<?php

namespace App\Http\Middleware;

use App\Models\PluginKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePluginKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->bearerToken();

        if (empty($apiKey)) {
            return response()->json([
                'error' => 'API-Key erforderlich. Senden Sie den Key als Bearer Token im Authorization Header.',
            ], 401);
        }

        if (! str_starts_with($apiKey, 'pk_live_')) {
            return response()->json([
                'error' => 'Ungültiges API-Key Format.',
            ], 401);
        }

        $pluginKey = PluginKey::where('public_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (! $pluginKey) {
            return response()->json([
                'error' => 'Ungültiger oder deaktivierter API-Key.',
            ], 401);
        }

        $client = $pluginKey->pluginClient;

        if (! $client || $client->status !== 'active') {
            return response()->json([
                'error' => 'Plugin-Konto ist nicht aktiv.',
            ], 403);
        }

        $request->attributes->set('plugin_client', $client);

        return $next($request);
    }
}
