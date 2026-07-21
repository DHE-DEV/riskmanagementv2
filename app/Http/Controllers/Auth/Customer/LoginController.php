<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Auth\Customer\Concerns\ResolvesSsoLogout;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    use ResolvesSsoLogout;

    /**
     * Display the login view (or redirect straight to SSO).
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->has('redirect') && str_starts_with($request->query('redirect'), url('/'))) {
            redirect()->setIntendedUrl($request->query('redirect'));
        }

        // SSO als einziger Login-Weg: direkt zum aktiven SSO-Provider weiterleiten,
        // ohne eigene Login-Seite. Ausnahme: Liegt ein Fehler-Flash vom SSO-Callback
        // vor, wird die Seite (inkl. Fehlermeldung) gezeigt – sonst entstuende eine
        // Endlosschleife Login -> SSO -> Callback-Fehler -> Login.
        if (config('services.sso.force_redirect', true) && ! session()->has('error')) {
            return redirect()->route('auth.keycloak.redirect', $request->only('redirect'));
        }

        return view('auth.customer.login');
    }

    /**
     * Handle an incoming authentication request
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'password.required' => 'Bitte geben Sie Ihr Passwort ein.',
        ]);

        $this->ensureIsNotRateLimited($request);

        $authenticated = Auth::guard('customer')->attempt(
            $request->only('email', 'password'),
            $request->boolean('remember')
        );

        // Fallback: try MD5 legacy password
        if (! $authenticated) {
            $customer = Customer::where('email', $request->input('email'))->first();
            if ($customer && $customer->legacy_password_md5) {
                $md5Input = md5($request->input('password'));
                if (hash_equals($customer->legacy_password_md5, $md5Input)) {
                    // Migrate to bcrypt and login
                    $customer->update([
                        'password' => Hash::make($request->input('password')),
                        'legacy_password_md5' => null,
                    ]);
                    Auth::guard('customer')->login($customer, $request->boolean('remember'));
                    $authenticated = true;
                }
            }
        }

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('Die eingegebenen Anmeldedaten sind ungültig.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $customer = Auth::guard('customer')->user();

        if (! $customer->hasVerifiedEmail()) {
            Auth::guard('customer')->logout();

            throw ValidationException::withMessages([
                'email' => 'Bitte bestätigen Sie zuerst Ihre E-Mail-Adresse, bevor Sie sich einloggen.',
            ]);
        }

        $request->session()->regenerate();

        // Stammdaten (inkl. Firmenadresse) gegen die Passolution-API abgleichen,
        // damit eine dort geaenderte Adresse im Kundendatensatz ankommt. Ein
        // Fehlschlag darf die Anmeldung nicht blockieren.
        try {
            $customer->syncPdsAccountData(0);
        } catch (\Throwable $e) {
            Log::warning('Login: Stammdaten-Abgleich fehlgeschlagen', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()->intended(route('customer.dashboard'))
            ->with('success', 'Erfolgreich angemeldet!');
    }

    /**
     * Destroy an authenticated session
     */
    public function destroy(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        $wasKeycloak = $customer && $customer->provider === 'keycloak';
        $idToken = session('keycloak_id_token');

        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Endziel nach komplettem Logout.
        // Keycloak-End-Session NUR aufrufen, wenn wir die Session per id_token_hint
        // identifizieren koennen -> dann meldet Keycloak silent ab. Ohne id_token_hint
        // wuerde Keycloak eine "Wollen Sie sich abmelden?"-Bestaetigungsseite zeigen;
        // in dem Fall melden wir nur lokal ab (ein evtl. verbleibender Keycloak-SSO-
        // Login wird beim naechsten Anmelden ohnehin durch prompt=login erneuert).
        if ($wasKeycloak && $idToken) {
            $realm = config('services.keycloak.realms', 'passolution');
            $finalUrl = config('services.keycloak.base_url')
                .'/realms/'.$realm.'/protocol/openid-connect/logout'
                .'?post_logout_redirect_uri='.urlencode(env('OIDC_LOGOUT_REDIRECT_URI', config('app.url')))
                .'&client_id='.config('services.keycloak.client_id')
                .'&id_token_hint='.urlencode($idToken);
        } else {
            // Nicht-Keycloak (z. B. Laravel Passport): optionaler Provider-Logout
            // (SSO_LOGOUT_URL), sonst Login-Route (leitet ggf. direkt zu SSO weiter).
            $finalUrl = $this->ssoLogoutUrl();
        }

        // Hinweis Single-Logout: Die pds-homepage liegt auf einer anderen Domain
        // (getrennter Cookie-Jar) und laesst sich vom Plattform-Logout aus per
        // Top-Level-Redirect NICHT zuverlaessig ausloggen (Cross-Site-Cookie wird
        // nicht mitgesendet). Die Homepage-Session wird daher beim naechsten
        // TRS-Aufruf IM iframe-Kontext beendet (siehe passolution.blade.php:
        // anonym -> /auth/sso/logout). Hier nur Plattform-Session beenden und zur
        // Keycloak-End-Session bzw. Login weiterleiten.
        return redirect($finalUrl);
    }

    /**
     * Ensure the login request is not rate limited
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }
}
