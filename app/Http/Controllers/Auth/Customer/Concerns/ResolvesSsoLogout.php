<?php

namespace App\Http\Controllers\Auth\Customer\Concerns;

trait ResolvesSsoLogout
{
    /**
     * Ermittelt die Logout-Ziel-URL fuer Nicht-Keycloak-SSO (z. B. Laravel Passport).
     *
     * Ist ein Provider-Logout-Endpunkt (services.sso.logout_url / SSO_LOGOUT_URL)
     * konfiguriert, wird dorthin weitergeleitet, um auch die Provider-Session zu
     * beenden – mit angehaengtem redirect_uri auf das Post-Logout-Ziel. Sonst wird
     * direkt auf das Post-Logout-Ziel (Standard: Login-Seite) weitergeleitet.
     */
    protected function ssoLogoutUrl(): string
    {
        $postLogout = config('services.sso.logout_redirect') ?: route('customer.login');

        $logoutUrl = config('services.sso.logout_url');

        if ($logoutUrl) {
            $separator = str_contains($logoutUrl, '?') ? '&' : '?';

            return $logoutUrl.$separator.'redirect_uri='.urlencode($postLogout);
        }

        return $postLogout;
    }
}
