<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Display the login view
     */
    public function create(Request $request): View
    {
        if ($request->has('redirect') && str_starts_with($request->query('redirect'), url('/'))) {
            redirect()->setIntendedUrl($request->query('redirect'));
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

        if (!$customer->hasVerifiedEmail()) {
            Auth::guard('customer')->logout();

            throw ValidationException::withMessages([
                'email' => 'Bitte bestätigen Sie zuerst Ihre E-Mail-Adresse, bevor Sie sich einloggen.',
            ]);
        }

        $request->session()->regenerate();

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
        if ($wasKeycloak) {
            $realm = config('services.keycloak.realms', 'passolution');
            $finalUrl = config('services.keycloak.base_url')
                . '/realms/' . $realm . '/protocol/openid-connect/logout'
                . '?post_logout_redirect_uri=' . urlencode(env('OIDC_LOGOUT_REDIRECT_URI', config('app.url')))
                . '&client_id=' . config('services.keycloak.client_id');

            if ($idToken) {
                $finalUrl .= '&id_token_hint=' . urlencode($idToken);
            }
        } else {
            $finalUrl = route('customer.login');
        }

        // Single-Logout: zuerst die pds-homepage-Session beenden (die per SSO
        // aufgebaute Session bleibt sonst stehen), dann weiter zum Endziel
        // (Keycloak-End-Session bzw. Login). Top-Level-Redirect, damit das
        // Homepage-Cookie (Cross-Site) zuverlaessig mitgesendet wird.
        $webUrl = rtrim((string) config('services.passolution.web_url'), '/');
        if ($webUrl !== '') {
            return redirect($webUrl . '/auth/sso/logout?return_to=' . urlencode($finalUrl));
        }

        return redirect($finalUrl);
    }

    /**
     * Ensure the login request is not rate limited
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
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
        return Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());
    }
}
