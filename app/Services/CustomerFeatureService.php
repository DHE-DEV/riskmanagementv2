<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use Illuminate\Support\Facades\Cache;

class CustomerFeatureService
{
    /**
     * Check if a feature is enabled for a customer.
     * First checks customer-specific overrides, then the override of the
     * customer's agency (assign_to) – for keys in INHERITED_BY_SUB_ACCOUNTS
     * always, for all others when the agency lists the key in
     * inherited_features – then falls back to .env config.
     */
    public function isFeatureEnabled(string $featureKey, ?Customer $customer = null): bool
    {
        // If no customer, use default config
        if (! $customer) {
            $customer = auth('customer')->user();
        }

        if (! $customer) {
            return $this->getDefaultConfig($featureKey);
        }

        // Check for customer-specific override
        $override = $this->getCustomerOverride($customer->id, $featureKey);

        if ($override !== null) {
            return $override;
        }

        // Unteraccount: Einstellung der Zentrale uebernehmen.
        if ($customer->assign_to && $this->inheritsFromAgency((int) $customer->assign_to, $featureKey)) {
            $override = $this->getCustomerOverride((int) $customer->assign_to, $featureKey);

            if ($override !== null) {
                return $override;
            }
        }

        // Fall back to default config
        return $this->getDefaultConfig($featureKey);
    }

    /**
     * Get customer-specific override for a feature.
     * Returns null if no override is set.
     */
    protected function getCustomerOverride(int $customerId, string $featureKey): ?bool
    {
        $cacheKey = "customer_feature_override_{$customerId}";

        $overrides = Cache::remember($cacheKey, 300, function () use ($customerId) {
            $override = CustomerFeatureOverride::where('customer_id', $customerId)->first();

            return $override ? $override->toArray() : null;
        });

        if (! $overrides || ! isset($overrides[$featureKey])) {
            return null;
        }

        return $overrides[$featureKey];
    }

    /**
     * Uebernimmt ein Unteraccount dieses Feature von seiner Zentrale?
     */
    protected function inheritsFromAgency(int $agencyId, string $featureKey): bool
    {
        if (in_array($featureKey, CustomerFeatureOverride::INHERITED_BY_SUB_ACCOUNTS, true)) {
            return true;
        }

        $inherited = Cache::remember("customer_feature_inherit_{$agencyId}", 300,
            fn () => (array) (Customer::withTrashed()->find($agencyId, ['id', 'inherited_features'])?->inherited_features ?? []));

        return in_array($featureKey, $inherited, true);
    }

    /**
     * Get the default config value from .env.
     */
    protected function getDefaultConfig(string $featureKey): bool
    {
        if ($section = CustomerFeatureOverride::SETTINGS_SECTIONS[$featureKey] ?? null) {
            return (bool) config("customer_settings.sections.{$section}", true);
        }

        // Convert feature key to config key (e.g., navigation_events_enabled -> app.navigation_events_enabled)
        return (bool) config("app.{$featureKey}", true);
    }

    /**
     * Clear the cache for a customer's overrides.
     */
    public function clearCache(int $customerId): void
    {
        Cache::forget("customer_feature_override_{$customerId}");
        Cache::forget("customer_feature_inherit_{$customerId}");
    }

    /**
     * Get all feature states for a customer.
     */
    public function getAllFeatures(?Customer $customer = null): array
    {
        $features = [];

        foreach (CustomerFeatureOverride::getFeatureKeys() as $key) {
            $features[$key] = $this->isFeatureEnabled($key, $customer);
        }

        return $features;
    }

    /**
     * Check if navigation item should be shown.
     * Convenience method for use in views.
     */
    public function showNavigation(string $item, ?Customer $customer = null): bool
    {
        $featureKey = "navigation_{$item}_enabled";

        return $this->isFeatureEnabled($featureKey, $customer);
    }
}
