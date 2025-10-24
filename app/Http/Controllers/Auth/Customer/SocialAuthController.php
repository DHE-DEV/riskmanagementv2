<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class SocialAuthController extends Controller
{
    /**
     * Supported social providers
     */
    private const SUPPORTED_PROVIDERS = ['facebook', 'google', 'linkedin', 'twitter'];

    /**
     * Redirect to provider's authentication page
     */
    public function redirect(string $provider): RedirectResponse
    {
        if (!$this->isProviderSupported($provider)) {
            return redirect()->route('customer.login')
                ->with('error', 'Provider nicht unterstützt.');
        }

        try {
            return Socialite::driver($provider)->redirect();
        } catch (Exception $e) {
            return redirect()->route('customer.login')
                ->with('error', 'Fehler bei der Weiterleitung zum Provider: ' . $e->getMessage());
        }
    }

    /**
     * Handle provider callback
     */
    public function callback(string $provider): RedirectResponse
    {
        if (!$this->isProviderSupported($provider)) {
            return redirect()->route('customer.login')
                ->with('error', 'Provider nicht unterstützt.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Exception $e) {
            return redirect()->route('customer.login')
                ->with('error', 'Fehler beim Abrufen der Benutzerdaten: ' . $e->getMessage());
        }

        // Try to find customer by provider and provider_id
        $customer = Customer::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        // If not found, check if email exists
        if (!$customer && $socialUser->getEmail()) {
            $existingCustomer = Customer::where('email', $socialUser->getEmail())->first();

            if ($existingCustomer) {
                // Link social account to existing customer
                $customer = $this->linkSocialAccount($existingCustomer, $provider, $socialUser);
            }
        }

        // If still not found, create new customer
        if (!$customer) {
            $customer = $this->createCustomerFromSocial($provider, $socialUser);
        } else {
            // Update existing customer tokens
            $this->updateCustomerTokens($customer, $socialUser);
        }

        // Login customer using the customer guard
        Auth::guard('customer')->login($customer, true);

        return redirect()->intended('/customer/dashboard')
            ->with('success', 'Erfolgreich angemeldet!');
    }

    /**
     * Link social account to existing customer
     */
    private function linkSocialAccount(Customer $customer, string $provider, $socialUser): Customer
    {
        $customer->update([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'provider_token' => $socialUser->token,
            'provider_refresh_token' => $socialUser->refreshToken ?? null,
            'avatar' => $socialUser->getAvatar() ?? $customer->avatar,
            'email_verified_at' => $customer->email_verified_at ?? now(), // Auto-verify if linking social
        ]);

        return $customer;
    }

    /**
     * Create new customer from social provider data
     */
    private function createCustomerFromSocial(string $provider, $socialUser): Customer
    {
        return Customer::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'avatar' => $socialUser->getAvatar(),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'provider_token' => $socialUser->token,
            'provider_refresh_token' => $socialUser->refreshToken ?? null,
            'password' => null, // No password for social login
            'email_verified_at' => now(), // Auto-verify email for social login
        ]);
    }

    /**
     * Update customer's social tokens
     */
    private function updateCustomerTokens(Customer $customer, $socialUser): void
    {
        $customer->update([
            'provider_token' => $socialUser->token,
            'provider_refresh_token' => $socialUser->refreshToken ?? $customer->provider_refresh_token,
            'avatar' => $socialUser->getAvatar() ?? $customer->avatar,
        ]);
    }

    /**
     * Check if provider is supported
     */
    private function isProviderSupported(string $provider): bool
    {
        return in_array($provider, self::SUPPORTED_PROVIDERS);
    }
}
