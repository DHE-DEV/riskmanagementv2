<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PassolutionService
{
    /**
     * Fetch and update subscription information for a customer
     * Uses SSO token (pds_api_token) if available, otherwise OAuth token
     */
    public function updateSubscription(Customer $customer): bool
    {
        $token = $customer->getActiveApiToken();
        $tokenSource = $customer->getActiveTokenSource();

        if (!$token) {
            return false;
        }

        try {
            $baseUrl = config('services.pds_api.base_url', 'https://api.passolution.eu/api/v2');

            Log::info('PassolutionService: Fetching subscription', [
                'customer_id' => $customer->id,
                'token_source' => $tokenSource,
                'base_url' => $baseUrl,
            ]);

            $response = Http::withToken($token)
                ->get("{$baseUrl}/account/subscription");

            if (!$response->successful()) {
                Log::warning('Passolution subscription fetch failed', [
                    'customer_id' => $customer->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'token_source' => $tokenSource,
                ]);

                // If token is expired (401), try to refresh it (only for OAuth tokens)
                // SSO tokens are renewed on each SSO login
                if ($response->status() === 401 && $tokenSource === 'oauth') {
                    $this->refreshTokenIfNeeded($customer);
                }

                return false;
            }

            $data = $response->json();

            $customer->update([
                'passolution_subscription_type' => $data['type'] ?? null,
                'passolution_features' => $data['features'] ?? [],
                'passolution_subscription_updated_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Passolution subscription update error', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Check if Passolution integration is active for a customer
     * Returns true if SSO token or OAuth token is available
     */
    public function isActive(Customer $customer): bool
    {
        return $customer->hasAnyActiveToken();
    }

    /**
     * Get token information for display
     */
    public function getTokenInfo(Customer $customer): array
    {
        return [
            'is_active' => $customer->hasAnyActiveToken(),
            'source' => $customer->getActiveTokenSource(),
            'has_sso_token' => $customer->hasValidPdsApiToken(),
            'has_oauth_token' => $customer->hasActivePassolution(),
            'sso_expires_at' => $customer->pds_api_token_expires_at?->toIso8601String(),
            'oauth_expires_at' => $customer->passolution_token_expires_at?->toIso8601String(),
        ];
    }

    /**
     * Check if subscription data needs updating (older than 1 hour)
     */
    public function needsUpdate(Customer $customer): bool
    {
        if (!$customer->passolution_subscription_updated_at) {
            return true;
        }

        return $customer->passolution_subscription_updated_at->addHour()->isPast();
    }

    /**
     * Refresh access token if needed
     */
    private function refreshTokenIfNeeded(Customer $customer): bool
    {
        if (!$customer->passolution_refresh_token) {
            return false;
        }

        try {
            $response = Http::withBasicAuth(
                config('services.passolution.client_id'),
                config('services.passolution.client_secret')
            )->asForm()->post('https://web.passolution.eu/oauth/token/refresh', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $customer->passolution_refresh_token,
            ]);

            if (!$response->successful()) {
                return false;
            }

            $tokenData = $response->json();

            $tokenExpiresAt = now()->addSeconds($tokenData['expires_in'] ?? 3600);
            $refreshTokenExpiresAt = $tokenExpiresAt->copy()->addMonths(6);

            $customer->update([
                'passolution_access_token' => $tokenData['access_token'],
                'passolution_token_expires_at' => $tokenExpiresAt,
                'passolution_refresh_token' => $tokenData['refresh_token'] ?? $customer->passolution_refresh_token,
                'passolution_refresh_token_expires_at' => $refreshTokenExpiresAt,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get feature labels for display
     */
    public function getFeatureLabels(array $features): array
    {
        $labels = [
            'content.country' => 'Länder-Inhalte',
            'content.cruise' => 'Kreuzfahrt-Inhalte',
            'content.individual' => 'Individuelle Inhalte',
            'content.tour_operator' => 'Veranstalter-Inhalte',
            'customer.send_emails' => 'E-Mail versenden',
            'customer.travel_detail_link.create' => 'Reisedetail-Links erstellen',
            'customer.travel_detail_link.manage' => 'Reisedetail-Links verwalten',
            'customer.travel_detail_link.advert.manage' => 'Werbung verwalten',
            'customer.travel_detail_link.email_subscriptions' => 'E-Mail Abonnements',
            'customer.travel_detail_link.inspiration.manage' => 'Inspirationen verwalten',
            'customer.travel_detail_link.media.manage' => 'Medien verwalten',
            'embed.corona' => 'Corona Einbettung',
            'subscription' => 'Abonnement',
        ];

        return array_map(function($feature) use ($labels) {
            return $labels[$feature] ?? $feature;
        }, $features);
    }
}
