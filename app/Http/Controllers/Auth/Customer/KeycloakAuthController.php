<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Auth\Customer\Concerns\ResolvesSsoLogout;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Employee;
use App\Services\PassolutionApiService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;

/**
 * Wickelt den SSO-Login der Kunden ab.
 *
 * Der konkrete Socialite-Treiber ist ueber config('services.sso.driver')
 * (ENV: SSO_DRIVER) umschaltbar:
 *   - 'keycloak'        : Passolution Keycloak (OIDC)               [Standard]
 *   - 'laravelpassport' : Passolution Laravel Passport (OAuth2)
 *
 * Damit laesst sich Keycloak per .env deaktivieren und der Laravel-Passport-
 * Login aktivieren, ohne Code-Aenderungen. Die Routen-Namen (auth.keycloak.*)
 * bleiben aus Kompatibilitaetsgruenden erhalten.
 */
class KeycloakAuthController extends Controller
{
    use ResolvesSsoLogout;

    /**
     * Aktiver SSO-Treiber laut Konfiguration.
     */
    protected function ssoDriver(): string
    {
        return config('services.sso.driver', 'keycloak');
    }

    /**
     * End any existing session, then redirect to the actual login.
     */
    public function redirect(Request $request): RedirectResponse
    {
        // Frueher wurde hier ZUERST ein Keycloak-Logout ausgeloest, um einen frischen
        // Login zu erzwingen. Ohne id_token_hint (z. B. direkt nach dem Abmelden)
        // zeigt Keycloak dabei aber eine "Wollen Sie sich abmelden?"-Bestaetigungsseite.
        // Stattdessen erzwingen wir den frischen Login direkt ueber prompt=login
        // (siehe startLogin) – ohne Logout-Umweg und ohne Bestaetigungsseite.
        return $this->startLogin($request);
    }

    /**
     * Actually start the SSO login (called after session was cleared).
     */
    public function startLogin(Request $request): RedirectResponse
    {
        // Rücksprungziel (falls lokal) als intended-URL merken, damit der Callback
        // nach erfolgreichem Login wieder dorthin zurückführt.
        if ($request->filled('redirect') && str_starts_with($request->query('redirect'), url('/'))) {
            redirect()->setIntendedUrl($request->query('redirect'));
        }

        // Wurde der Login aus dem eingebetteten iframe-Modal gestartet, merken wir
        // das in der Session. Der Callback leitet dann statt aufs Dashboard auf eine
        // Mini-Seite, die das Parent-Fenster per postMessage benachrichtigt.
        if ($request->query('from') === 'iframe') {
            $request->session()->put('sso_iframe_login', true);
        }

        $driver = $this->ssoDriver();

        if ($driver === 'keycloak') {
            // prompt=login erzwingt eine frische Authentifizierung (Login-Formular),
            // auch wenn noch eine Keycloak-SSO-Session besteht – ersetzt den frueheren
            // Logout-vor-Login-Umweg und vermeidet die Abmelde-Bestaetigungsseite.
            return Socialite::driver('keycloak')
                ->setScopes(['openid', 'profile', 'email'])
                ->with(['prompt' => 'login'])
                ->enablePKCE()
                ->redirect();
        }

        // Laravel Passport (Passolution) – Standard-OAuth2 Authorization-Code-Flow.
        // Optional prompt=login, um eine frische Anmeldung zu erzwingen (damit ein
        // Logout nicht durch eine bestehende Provider-Session sofort wieder
        // ueberschrieben wird) – wirkt nur, wenn der Auth-Server den Parameter kennt.
        $passport = Socialite::driver($driver);

        if (config('services.sso.prompt_login')) {
            $passport->with(['prompt' => 'login']);
        }

        return $passport->redirect();
    }

    /**
     * Handle callback from the SSO provider.
     */
    public function callback(): RedirectResponse
    {
        $driver = $this->ssoDriver();

        try {
            $socialite = Socialite::driver($driver);

            // Keycloak-Flow nutzt PKCE; der Laravel-Passport-Treiber nicht.
            if ($driver === 'keycloak') {
                $socialite = $socialite->enablePKCE();
            }

            $ssoUser = $socialite->user();
        } catch (Exception $e) {
            Log::error('SSO login failed', ['driver' => $driver, 'error' => $e->getMessage()]);

            return redirect()->route('customer.login')
                ->with('error', 'Anmeldung fehlgeschlagen: '.$e->getMessage());
        }

        $email = $ssoUser->getEmail();

        // Roh-Payload des SSO-userinfo-Response. Laravel Passport liefert hier auch
        // account_id/username/name, die Socialite NICHT auf das typisierte User-Objekt
        // mappt (nur id/nickname/name/email/avatar). Keycloak liefert diese Keys nicht,
        // daher durchweg null-safe ausgelesen.
        $raw = method_exists($ssoUser, 'getRaw') ? (array) $ssoUser->getRaw() : (array) ($ssoUser->user ?? []);
        $ssoName = isset($raw['name']) && trim((string) $raw['name']) !== '' ? trim((string) $raw['name']) : null;
        $ssoUsername = $raw['username'] ?? null;
        // Top-level account_id; faellt auf das verschachtelte account.id zurueck
        // (selbe Account-ID, neuer userinfo-Response mit account-Objekt).
        $ssoAccountId = $raw['account_id'] ?? data_get($raw, 'account.id');

        $customer = null;
        $employee = null;

        // 1. Try to find customer by SSO provider_id
        $customer = Customer::where('provider', $driver)
            ->where('provider_id', $ssoUser->getId())
            ->first();

        // 2. If not found by provider_id, try by email as customer
        if (! $customer && $email) {
            $existing = Customer::where('email', $email)->first();

            if ($existing) {
                $existing->update([
                    'provider' => $driver,
                    'provider_id' => $ssoUser->getId(),
                    'provider_token' => $ssoUser->token,
                    'provider_refresh_token' => $ssoUser->refreshToken ?? null,
                    'avatar' => $ssoUser->getAvatar() ?? $existing->avatar,
                    'email_verified_at' => $existing->email_verified_at ?? now(),
                ]);
                $customer = $existing;
            }
        }

        // 3. If not a customer, try to find as employee
        if (! $customer && $email) {
            $employee = Employee::where('email', $email)
                ->where('is_active', true)
                ->first();

            if ($employee && $employee->isCurrentlyActive() && $employee->customer) {
                $customer = $employee->customer;
            }
        }

        // 4. Kein Kunde/Mitarbeiter gefunden -> automatisch als Kunde anlegen
        //    (Just-in-Time-Provisioning). Stammdaten werden – falls vorhanden –
        //    per Service-Key aus der Passolution-API (account/info per E-Mail) gezogen.
        if (! $customer && $email) {
            $account = app(PassolutionApiService::class)->fetchAccountByEmail($email);

            $person = trim(($account['first_name'] ?? '').' '.($account['last_name'] ?? ''));

            $customer = Customer::create([
                'name' => $person !== '' ? $person : ($account['name'] ?? $email),
                'email' => $email,
                'provider' => $driver,
                'provider_id' => $ssoUser->getId(),
                'provider_token' => $ssoUser->token,
                'provider_refresh_token' => $ssoUser->refreshToken ?? null,
                'avatar' => $ssoUser->getAvatar(),
                'email_verified_at' => now(),
                // Stammdaten aus der Passolution-API (nur wenn gefunden)
                'company_name' => $account['name'] ?? null,
                'company_street' => $account['address_line_1'] ?? null,
                'company_postal_code' => $account['zip_code'] ?? null,
                'company_city' => $account['city'] ?? null,
                'company_country' => $account['country_code'] ?? null,
            ]);

            Log::info('SSO login: neuer Kunde automatisch angelegt', [
                'driver' => $driver,
                'email' => $email,
                'stammdaten' => $account !== null,
            ]);
        }

        // 4b. Ohne E-Mail kein Konto möglich -> ablehnen
        if (! $customer) {
            Log::warning('SSO login: no email in token, cannot provision', ['driver' => $driver, 'email' => $email]);

            return redirect()->route('customer.login')
                ->with('error', 'Anmeldung fehlgeschlagen: keine E-Mail-Adresse vom Login-Anbieter erhalten.');
        }

        // Update tokens on customer (only set provider_id if this is a direct customer login, not employee)
        $updateData = [
            'provider' => $driver,
            'provider_token' => $ssoUser->token,
            'provider_refresh_token' => $ssoUser->refreshToken ?? $customer->provider_refresh_token,
        ];

        if (! $employee) {
            $updateData['provider_id'] = $ssoUser->getId();
            $updateData['avatar'] = $ssoUser->getAvatar() ?? $customer->avatar;

            // Weitere Felder aus dem SSO-Response bei JEDEM Login synchronisieren,
            // damit Aenderungen auf der Plattform lokal durchschlagen. Nur setzen,
            // wenn der Anbieter den Wert geliefert hat (sonst bestehenden behalten).
            if ($ssoName !== null) {
                $updateData['name'] = $ssoName;
            }
            if ($email) {
                $updateData['email'] = $email;
            }
            // Rohwerte des userinfo-Response zusaetzlich 1:1 in dedizierten Spalten
            // ablegen (username + pds_*). NUR schreiben, wenn die Spalte existiert –
            // sonst wuerde ein noch nicht deployter Migration-Stand den kompletten
            // Login brechen (update() auf unbekannte Spalte -> SQL-Fehler -> keine Auth).
            $ssoRawFields = [
                'username' => $ssoUsername,
                'pds_id' => $raw['id'] ?? $ssoUser->getId(),
                'pds_name' => $raw['name'] ?? null,
                'pds_username' => $ssoUsername,
                'pds_email' => $raw['email'] ?? null,
                'pds_account_id' => $ssoAccountId,
                // Verschachteltes account-Objekt flach ablegen.
                'pds_account_type' => data_get($raw, 'account.type'),
                'pds_account_name' => data_get($raw, 'account.name'),
                'pds_account_first_name' => data_get($raw, 'account.first_name'),
                'pds_account_last_name' => data_get($raw, 'account.last_name'),
                'pds_account_email' => data_get($raw, 'account.email'),
                'pds_account_phone' => data_get($raw, 'account.phone'),
                'pds_account_address_line_1' => data_get($raw, 'account.address_line_1'),
                'pds_account_zip_code' => data_get($raw, 'account.zip_code'),
                'pds_account_city' => data_get($raw, 'account.city'),
                'pds_account_state' => data_get($raw, 'account.state'),
                'pds_account_country' => data_get($raw, 'account.country'),
                'pds_account_subscription_type' => data_get($raw, 'account.subscription.type'),
            ];
            foreach ($ssoRawFields as $ssoColumn => $ssoValue) {
                if ($ssoValue !== null && Schema::hasColumn('customers', $ssoColumn)) {
                    $updateData[$ssoColumn] = $ssoValue;
                }
            }
        }

        // Der SSO-Anbieter hat die Identitaet (inkl. E-Mail) bereits verifiziert ->
        // keine erneute E-Mail-Bestaetigung auf der Plattform noetig.
        if (! $customer->email_verified_at) {
            $updateData['email_verified_at'] = now();
        }

        $customer->update($updateData);

        Auth::guard('customer')->login($customer, true);

        // Store SSO id_token (falls vorhanden, z. B. Keycloak OIDC) fuer sauberes
        // Logout/Re-Login plus die tatsaechliche Anmelde-E-Mail. (Customer im
        // customer-Guard kann die Firma sein – die echte Anmelde-Identität brauchen
        // z. B. iframe-SSO und Dashboard-Anzeige.) Die Session-Keys bleiben aus
        // Kompatibilitaetsgruenden mit 'keycloak_'-Praefix erhalten.
        session([
            'keycloak_id_token' => $ssoUser->accessTokenResponseBody['id_token'] ?? null,
            'keycloak_email' => $email,
        ]);

        // Store employee context in session
        if ($employee) {
            session([
                'logged_in_employee_id' => $employee->id,
                'logged_in_employee_name' => $employee->first_name.' '.$employee->last_name,
                'logged_in_employee_email' => $employee->email,
            ]);

            Log::info('Employee logged in via SSO', [
                'driver' => $driver,
                'employee_id' => $employee->id,
                'email' => $employee->email,
                'customer_id' => $customer->id,
            ]);
        }

        // iframe-Login: nicht aufs Dashboard im iframe leiten, sondern auf die
        // Done-Seite, die das Parent-Fenster benachrichtigt (Parent laedt dann neu).
        if (session()->pull('sso_iframe_login')) {
            return redirect()->route('auth.keycloak.iframe-done');
        }

        return redirect()->intended('/customer/dashboard')
            ->with('success', 'Erfolgreich angemeldet!');
    }

    /**
     * Logout from the SSO provider.
     *
     * Keycloak: OIDC end-session (beendet auch die Keycloak-Session).
     * Laravel Passport: lokaler Logout + Rueckleitung zur Login-Seite
     * (kein OIDC-End-Session-Endpunkt).
     */
    public function logout(): RedirectResponse
    {
        $driver = $this->ssoDriver();

        if ($driver === 'keycloak') {
            $realm = config('services.keycloak.realms', 'passolution');
            $logoutRedirect = config('services.keycloak.base_url')
                .'/realms/'.$realm.'/protocol/openid-connect/logout'
                .'?post_logout_redirect_uri='.urlencode(env('OIDC_LOGOUT_REDIRECT_URI', config('app.url')))
                .'&client_id='.config('services.keycloak.client_id');

            Auth::guard('customer')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect($logoutRedirect);
        }

        // Laravel Passport: lokal abmelden. Ist ein Provider-Logout-Endpunkt
        // (SSO_LOGOUT_URL) konfiguriert, wird dorthin weitergeleitet, sonst zur
        // Login-Seite (bzw. SSO_LOGOUT_REDIRECT_URI).
        Auth::guard('customer')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect($this->ssoLogoutUrl());
    }
}
