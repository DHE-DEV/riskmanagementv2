<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Leitet Alias-Hostnamen der API (z. B. api.global-travel-monitor.eu) dauerhaft auf die
 * konfigurierte API-Domain (app.api_domain) um - Pfad und Query bleiben erhalten.
 *
 * Die kurzen /v1-Routen und die Dokumentation sind nur an eine Domain gebunden; so
 * funktionieren alle Aliase, ohne Routen mehrfach zu registrieren.
 */
class RedirectApiDomainAliases
{
    public function handle(Request $request, Closure $next): Response
    {
        $target = (string) config('app.api_domain');
        $aliases = (array) config('app.api_domain_aliases', []);

        if ($target !== '' && in_array($request->getHost(), $aliases, true)) {
            return redirect()->away('https://'.$target.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
