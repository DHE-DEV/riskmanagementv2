<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bestimmt die aktive Anzeige-Sprache für das Frontend (u. a. mehrsprachige
 * Events) anhand von ?lang=, Session, Cookie und Browser-Sprache.
 *
 * Die gewählte Sprache wird in der Session (sofern vorhanden) UND in einem
 * unverschlüsselten Cookie abgelegt. So erbt auch der zustandslose API-Aufruf
 * (z. B. /api/dashboard-events, vom Frontend per fetch geladen) dieselbe
 * Sprache wie die zuvor geladene Seite. Der Admin-Bereich bleibt unberührt
 * (Ausgangssprache).
 */
class SetEventLocale
{
    public const COOKIE = 'app_locale';

    public function handle(Request $request, Closure $next): Response
    {
        // Admin-/Filament-Bereich nicht beeinflussen.
        if ($request->is('admin', 'admin/*')) {
            return $next($request);
        }

        $supported = $this->supportedLocales();
        $locale = $this->resolveLocale($request, $supported);

        if ($locale !== null) {
            App::setLocale($locale);

            if ($request->hasSession()) {
                $request->session()->put('app_locale', $locale);
            }

            // Cookie nur erneuern, wenn sich der Wert geändert hat. Greift dort,
            // wo die Cookie-Queue aktiv ist (web-Gruppe); auf der API wird nur
            // gelesen. Ein Jahr Gültigkeit.
            if ($request->cookie(self::COOKIE) !== $locale) {
                Cookie::queue(self::COOKIE, $locale, 60 * 24 * 365);
            }
        }

        return $next($request);
    }

    /**
     * @param  array<int, string>  $supported
     */
    protected function resolveLocale(Request $request, array $supported): ?string
    {
        // 1. Expliziter Wechsel per ?lang=xx
        $requested = strtolower((string) $request->query('lang', ''));
        if ($requested !== '' && in_array($requested, $supported, true)) {
            return $requested;
        }

        // 2. Zuvor gewählte Sprache aus der Session
        if ($request->hasSession()) {
            $session = strtolower((string) $request->session()->get('app_locale', ''));
            if ($session !== '' && in_array($session, $supported, true)) {
                return $session;
            }
        }

        // 3. Cookie (für zustandslose API-Aufrufe auf derselben Domain)
        $cookie = strtolower((string) $request->cookie(self::COOKIE, ''));
        if ($cookie !== '' && in_array($cookie, $supported, true)) {
            return $cookie;
        }

        // 4. Browser-Sprache (Accept-Language)
        $preferred = $request->getPreferredLanguage(array_map('strtoupper', $supported));
        if ($preferred) {
            $preferred = strtolower(substr($preferred, 0, 2));
            if (in_array($preferred, $supported, true)) {
                return $preferred;
            }
        }

        // 5. Standard: Ausgangssprache der App (kein expliziter Wechsel).
        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function supportedLocales(): array
    {
        $raw = (string) config('app.event_languages', 'de,en,nl');
        $source = strtolower((string) config('app.event_source_language', 'de'));

        $locales = array_values(array_filter(array_map(
            fn ($code) => strtolower(trim($code)),
            explode(',', $raw)
        )));

        return array_values(array_unique(array_merge([$source], $locales)));
    }
}
