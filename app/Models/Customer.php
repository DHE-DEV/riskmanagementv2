<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'provider',
        'provider_id',
        'provider_token',
        'provider_refresh_token',
        'email_verified_at',
        'customer_type',
        'business_type',
        'company_name',
        'company_additional',
        'company_street',
        'company_house_number',
        'company_postal_code',
        'company_city',
        'company_country',
        'billing_company_name',
        'billing_additional',
        'billing_street',
        'billing_house_number',
        'billing_postal_code',
        'billing_city',
        'billing_country',
        'passolution_access_token',
        'passolution_token_expires_at',
        'passolution_refresh_token',
        'passolution_refresh_token_expires_at',
        'passolution_subscription_type',
        'passolution_roles',
        'passolution_features',
        'passolution_subscription_updated_at',
        'hide_profile_completion',
        'directory_listing_active',
        'branch_management_active',
        // SSO fields
        'agent_id',
        'service1_customer_id',
        'pds_customer_number',
        'phone',
        'address',
        'account_type',
        // PDS API Token for calling pds-api
        'pds_api_token',
        'pds_api_token_expires_at',
        // Auto-refresh settings for My Travelers
        'auto_refresh_travelers',
        'travelers_refresh_interval',
        // GTM API settings
        'gtm_api_enabled',
        'gtm_api_rate_limit',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'pds_api_token', // Hide API token from serialization
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'business_type' => 'array',
        'passolution_token_expires_at' => 'datetime',
        'passolution_refresh_token_expires_at' => 'datetime',
        'passolution_roles' => 'array',
        'passolution_features' => 'array',
        'passolution_subscription_updated_at' => 'datetime',
        // SSO fields
        'address' => 'array',
        // PDS API Token
        'pds_api_token_expires_at' => 'datetime',
        // GTM API
        'gtm_api_enabled' => 'boolean',
        'gtm_api_rate_limit' => 'integer',
    ];

    /**
     * Check if customer has verified email
     */
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Check if customer uses social login
     */
    public function isSocialLogin(): bool
    {
        return ! is_null($this->provider);
    }

    /**
     * Check if Passolution integration is active and token is valid
     */
    public function hasActivePassolution(): bool
    {
        return ! is_null($this->passolution_access_token)
            && ! is_null($this->passolution_token_expires_at)
            && $this->passolution_token_expires_at->isFuture();
    }

    /**
     * Check if PDS API token is valid and not expired
     * Prüft ob der PDS API Token gültig und nicht abgelaufen ist
     */
    public function hasValidPdsApiToken(): bool
    {
        return ! is_null($this->pds_api_token)
            && ! is_null($this->pds_api_token_expires_at)
            && $this->pds_api_token_expires_at->isFuture();
    }

    /**
     * Check if any Passolution API token is available (SSO or OAuth)
     * Prüft ob ein Passolution API Token verfügbar ist (SSO oder OAuth)
     */
    public function hasAnyActiveToken(): bool
    {
        return $this->hasValidPdsApiToken() || $this->hasActivePassolution();
    }

    /**
     * Get the active API token for Passolution API calls
     * Gibt den aktiven API Token für Passolution API-Aufrufe zurück
     *
     * Priority: PDS API Token (SSO) > Passolution OAuth Token
     * Priorität: PDS API Token (SSO) > Passolution OAuth Token
     */
    public function getActiveApiToken(): ?string
    {
        // First check SSO token (PDS API Token)
        if ($this->hasValidPdsApiToken()) {
            return $this->pds_api_token;
        }

        // Fall back to OAuth token
        if ($this->hasActivePassolution()) {
            return $this->passolution_access_token;
        }

        return null;
    }

    /**
     * Get the source of the active token
     * Gibt die Quelle des aktiven Tokens zurück
     */
    public function getActiveTokenSource(): ?string
    {
        if ($this->hasValidPdsApiToken()) {
            return 'sso';
        }

        if ($this->hasActivePassolution()) {
            return 'oauth';
        }

        return null;
    }

    /**
     * Beziehung zu BookingLocations
     */
    public function bookingLocations()
    {
        return $this->hasMany(BookingLocation::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function pluginClient(): HasOne
    {
        return $this->hasOne(PluginClient::class);
    }

    public function hasPluginClient(): bool
    {
        return $this->pluginClient()->exists();
    }

    public function gtmApiRequestLogs(): HasMany
    {
        return $this->hasMany(GtmApiRequestLog::class);
    }

    public function featureOverrides(): HasOne
    {
        return $this->hasOne(CustomerFeatureOverride::class);
    }

    /**
     * Check if a specific feature is enabled for this customer.
     * Uses customer-specific overrides or falls back to .env defaults.
     */
    public function isFeatureEnabled(string $featureKey): bool
    {
        return app(\App\Services\CustomerFeatureService::class)->isFeatureEnabled($featureKey, $this);
    }
}
