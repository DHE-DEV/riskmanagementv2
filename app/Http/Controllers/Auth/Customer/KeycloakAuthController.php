<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class KeycloakAuthController extends Controller
{
    /**
     * Redirect to Keycloak login page.
     */
    public function redirect(): RedirectResponse
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

        // Try to find customer by keycloak provider_id
        $customer = Customer::where('provider', 'keycloak')
            ->where('provider_id', $keycloakUser->getId())
            ->first();

        // If not found, check if email exists
        if (!$customer && $keycloakUser->getEmail()) {
            $existing = Customer::where('email', $keycloakUser->getEmail())->first();

            if ($existing) {
                // Link Keycloak to existing customer
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

        // If still not found, create new customer
        if (!$customer) {
            $customer = Customer::create([
                'name' => $keycloakUser->getName() ?? $keycloakUser->getNickname() ?? 'User',
                'email' => $keycloakUser->getEmail(),
                'avatar' => $keycloakUser->getAvatar(),
                'provider' => 'keycloak',
                'provider_id' => $keycloakUser->getId(),
                'provider_token' => $keycloakUser->token,
                'provider_refresh_token' => $keycloakUser->refreshToken ?? null,
                'password' => null,
                'email_verified_at' => now(),
            ]);
        } else {
            // Update tokens
            $customer->update([
                'provider_token' => $keycloakUser->token,
                'provider_refresh_token' => $keycloakUser->refreshToken ?? $customer->provider_refresh_token,
                'avatar' => $keycloakUser->getAvatar() ?? $customer->avatar,
            ]);
        }

        Auth::guard('customer')->login($customer, true);

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
