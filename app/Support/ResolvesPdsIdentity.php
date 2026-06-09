<?php

namespace App\Support;

use App\Models\Customer;

/**
 * Zentrale Aufloesung der SSO-Anmelde-Identitaet fuer Service-Token-Abrufe.
 *
 * Hintergrund: platform.passolution.de und die pds-api/Homepage liegen auf
 * getrennten Domains. Beim Keycloak-SSO-Login wird KEIN per-User-pds_api_token
 * gesetzt – ein evtl. am Customer haengender Token ist daher veraltet und zeigt
 * ggf. auf einen falschen Account. Sobald eine SSO-Identitaet vorliegt, hat der
 * Service-Token-Pfad (per E-Mail gegen die __internal-Endpunkte) Vorrang vor
 * einem hinterlegten per-User-Token.
 */
trait ResolvesPdsIdentity
{
    /**
     * Echte Anmelde-E-Mail fuer Service-Token-Abrufe.
     * Reihenfolge: Keycloak-Login -> Employee-Login -> Customer-E-Mail.
     */
    protected function resolveSsoEmail(?Customer $customer = null): ?string
    {
        return session('keycloak_email')
            ?: session('logged_in_employee_email')
            ?: ($customer?->email);
    }

    /**
     * Liegt eine SSO-Identitaet vor? Dann hat der Service-Token-Pfad Vorrang
     * vor einem (moeglicherweise veralteten) per-User-Token.
     */
    protected function hasSsoIdentity(): bool
    {
        return (bool) (session('keycloak_email') ?: session('logged_in_employee_email'));
    }
}
