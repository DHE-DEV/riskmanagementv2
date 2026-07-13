<?php

namespace App\Http\Controllers\Auth\Customer\Concerns;

trait ResolvesSsoLogout
{
    /**
     * Ermittelt die Logout-Ziel-URL fuer Nicht-Keycloak-SSO (z. B. Laravel Passport).
     *
     * Ist ein Provider-Logout-Endpunkt (services.sso.logout_url / SSO_LOGOUT_URL)
     * konfiguriert, wird dorthin weitergeleitet, um auch die Provider-Session zu
     * beenden – mit angehaengtem return_to auf das Post-Logout-Ziel. Da der Auth-Host
     * (pds-homepage SSO, /auth/sso/logout) die geteilte pds_sso_session beendet, wird
     * damit auch die im iframe eingebettete Homepage ueberall ausgeloggt
     * (Single-Logout). Sonst wird direkt auf das Post-Logout-Ziel (Standard:
     * Startseite "/") weitergeleitet.
     */
    protected function ssoLogoutUrl(): string
    {
        // Nach dem Abmelden auf die Startseite "/" fuehren (nicht auf die
        // Login-Seite, die bei force_redirect sofort wieder zum SSO leiten wuerde).
        // Ein explizit gesetztes SSO_LOGOUT_REDIRECT_URI hat weiterhin Vorrang.
        $postLogout = config('services.sso.logout_redirect') ?: url('/');

        $logoutUrl = config('services.sso.logout_url');

        if ($logoutUrl) {
            $separator = str_contains($logoutUrl, '?') ? '&' : '?';

            return $logoutUrl.$separator.'return_to='.urlencode($postLogout);
        }

        return $postLogout;
    }
}
