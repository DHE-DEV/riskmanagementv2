<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PassolutionService
{
    /**
     * Fetch and update subscription information for a customer
     */
    public function updateSubscription(Customer $customer): bool
    {
        if (!$customer->hasActivePassolution()) {
            return false;
        }

        try {
            $response = Http::withToken($customer->passolution_access_token)
                ->get('https://api.passolution.eu/api/v2/account/subscription');

            if (!$response->successful()) {
                Log::warning('Passolution subscription fetch failed', [
                    'customer_id' => $customer->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // If token is expired (401), try to refresh it
                if ($response->status() === 401) {
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
