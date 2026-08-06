<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Synchronisiert den Anmeldestatus dieser Plattform mit dem der
 * platform.passolution.de-Seite.
 *
 * Die Plattform haengt an ihre Links den aktuellen Login-Status des Nutzers an:
 *   - ?user-state=logged-out : der Nutzer ist auf der Plattform abgemeldet
 *   - ?user-state=logged-in  : der Nutzer ist auf der Plattform angemeldet
 *
 * Klickt ein Nutzer einen solchen Link, gleichen wir unseren lokalen
 * (customer-Guard) Zustand daran an:
 *   - logged-out : lokale Session beenden (Single-Logout mitziehen)
 *   - logged-in  : ist lokal noch niemand angemeldet, den SSO-Login anstossen.
 *     Da bereits eine SSO-Session besteht, kommt der Nutzer mit Auth-Info zurueck
 *     und wird anschliessend auf die urspruenglich aufgerufene Seite geleitet.
 *
 * Nur bei einem tatsaechlichen Statuswechsel wird umgeleitet (und dabei der
 * user-state-Parameter aus der URL entfernt). Stimmt der lokale Zustand bereits
 * mit dem der Plattform ueberein, laeuft die Anfrage unveraendert durch.
 */
class SyncPlatformUserState
{
    /** Query-Parameter, den die Plattform an ihre Links anhaengt. */
    public const PARAM = 'user-state';

    public function handle(Request $request, Closure $next): Response
    {
        // Nur GET-Navigationen tragen den Plattform-Status. Der Admin-/Filament-
        // Bereich (eigener Guard) und die Auth-Routen selbst bleiben unberuehrt,
        // damit der Login-/Logout-Flow nicht unterbrochen wird.
        //
        // embed/* ist ebenfalls ausgenommen: diese Routen laufen in Kunden-iframes
        // auf fremden Seiten und authentifizieren sich ueber den ?key=pk_live_*
        // Plugin-Key, nicht ueber den customer-Guard. Ein SSO-Redirect wuerde dort
        // ins Leere laufen, weil Keycloak die Einbettung per X-Frame-Options
        // verweigert - der Kunde saehe ein leeres iframe.
        if (! $request->isMethod('GET')
            || ! $request->has(self::PARAM)
            || $request->is('admin', 'admin/*', 'auth/*', 'customer/auth/*', 'customer/logout*', 'embed', 'embed/*')
        ) {
            return $next($request);
        }

        $state = $request->query(self::PARAM);
        $guard = Auth::guard('customer');

        // Plattform abgemeldet -> lokale Session ebenfalls beenden.
        if ($state === 'logged-out') {
            if ($guard->check()) {
                $guard->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->to($this->urlWithoutState($request));
            }

            return $next($request);
        }

        // Plattform angemeldet -> lokal sicherstellen, dass jemand angemeldet ist.
        if ($state === 'logged-in') {
            if (! $guard->check()) {
                // SSO-Login STILL anstossen (silent=1): kein prompt=login, damit die
                // bereits bestehende SSO-Session genutzt wird und der Nutzer nicht auf
                // der Login-Seite landet. Danach zurueck auf die urspruengliche Seite
                // (ohne user-state-Parameter).
                return redirect()->route('auth.keycloak.redirect', [
                    'redirect' => $this->urlWithoutState($request),
                    'silent' => 1,
                ]);
            }

            return $next($request);
        }

        return $next($request);
    }

    /**
     * Baut die aktuelle URL ohne den user-state-Parameter neu auf, um eine
     * saubere Ziel-/Rueckkehr-URL zu erhalten (uebrige Query-Parameter bleiben).
     */
    private function urlWithoutState(Request $request): string
    {
        $query = $request->query();
        unset($query[self::PARAM]);

        $url = $request->url();

        return $query ? $url.'?'.http_build_query($query) : $url;
    }
}
