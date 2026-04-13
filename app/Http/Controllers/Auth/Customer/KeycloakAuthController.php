<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Employee;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class KeycloakAuthController extends Controller
{
    /**
     * End any existing Keycloak session, then redirect to the actual login.
     */
    public function redirect(): RedirectResponse
    {
        $realm = config('services.keycloak.realms', 'passolution');
        $baseUrl = config('services.keycloak.base_url');
        $clientId = config('services.keycloak.client_id');

        // Redirect to Keycloak logout, which redirects back to our startLogin route
        $logoutUrl = $baseUrl . '/realms/' . $realm . '/protocol/openid-connect/logout'
            . '?post_logout_redirect_uri=' . urlencode(route('auth.keycloak.start'))
            . '&client_id=' . $clientId;

        return redirect($logoutUrl);
    }

    /**
     * Actually start the Keycloak login (called after session was cleared).
     */
    public function startLogin(): RedirectResponse
    {
        return Socialite::driver('keycloak')
            ->setScopes(['openid', 'profile', 'email'])
            ->enablePKCE()
            ->redirect();
    }

    /**
     * Handle callback from Keycloak.
     */
    public function callback(): RedirectResponse
    {
        try {
            $keycloakUser = Socialite::driver('keycloak')->enablePKCE()->user();
        } catch (Exception $e) {
            Log::error('Keycloak login failed', ['error' => $e->getMessage()]);

            return redirect()->route('customer.login')
                ->with('error', 'Anmeldung über Keycloak fehlgeschlagen: ' . $e->getMessage());
        }

        $email = $keycloakUser->getEmail();
        $customer = null;
        $employee = null;

        // 1. Try to find customer by keycloak provider_id
        $customer = Customer::where('provider', 'keycloak')
            ->where('provider_id', $keycloakUser->getId())
            ->first();

        // 2. If not found by provider_id, try by email as customer
        if (! $customer && $email) {
            $existing = Customer::where('email', $email)->first();

            if ($existing) {
                $existing->update([
                    'provider' => 'keycloak',
                    'provider_id' => $keycloakUser->getId(),
                    'provider_token' => $keycloakUser->token,
                    'provider_refresh_token' => $keycloakUser->refreshToken ?? null,
                    'avatar' => $keycloakUser->getAvatar() ?? $existing->avatar,
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

        // 4. If still not found, reject login
        if (! $customer) {
            Log::warning('Keycloak login: no matching customer or employee', ['email' => $email]);

            return redirect()->route('customer.login')
                ->with('error', 'Kein Konto mit dieser E-Mail-Adresse gefunden.');
        }

        // Update tokens on customer (only set provider_id if this is a direct customer login, not employee)
        $updateData = [
            'provider' => 'keycloak',
            'provider_token' => $keycloakUser->token,
            'provider_refresh_token' => $keycloakUser->refreshToken ?? $customer->provider_refresh_token,
        ];

        if (! $employee) {
            $updateData['provider_id'] = $keycloakUser->getId();
            $updateData['avatar'] = $keycloakUser->getAvatar() ?? $customer->avatar;
        }

        $customer->update($updateData);

        Auth::guard('customer')->login($customer, true);

        // Store employee context in session
        if ($employee) {
            session([
                'logged_in_employee_id' => $employee->id,
                'logged_in_employee_name' => $employee->first_name . ' ' . $employee->last_name,
                'logged_in_employee_email' => $employee->email,
            ]);

            Log::info('Employee logged in via Keycloak', [
                'employee_id' => $employee->id,
                'email' => $employee->email,
                'customer_id' => $customer->id,
            ]);
        }

        return redirect()->intended('/customer/dashboard')
            ->with('success', 'Erfolgreich angemeldet!');
    }

    /**
     * Logout from Keycloak (OIDC end session).
     */
    public function logout(): RedirectResponse
    {
        $realm = config('services.keycloak.realms', 'passolution');
        $logoutRedirect = config('services.keycloak.base_url')
            . '/realms/' . $realm . '/protocol/openid-connect/logout'
            . '?post_logout_redirect_uri=' . urlencode(env('OIDC_LOGOUT_REDIRECT_URI', config('app.url')))
            . '&client_id=' . config('services.keycloak.client_id');

        Auth::guard('customer')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect($logoutRedirect);
    }
}
