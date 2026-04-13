<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    protected $attributes = [
        'branch_management_active' => true,
    ];

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
        'legacy_client_account_id',
        'legacy_passolution_company_id',
        'legacy_account_id',
        'legacy_organization_id',
        'legacy_language_id',
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
        // PDS sync & Travel Links
        'pds_sync_enabled',
        'pds_last_synced_at',
        'travel_links_enabled',
        // Notification settings
        'notifications_enabled',
        'has_seen_platform_tour',
        'has_seen_travel_alert_tour',
        'has_seen_gtm_tour',
        'has_seen_trs_tour',
        'has_seen_entry_conditions_tour',
        'has_seen_travel_data_tour',
        'has_seen_travel_links_tour',
        'has_seen_booking_tour',
        'has_seen_airports_tour',
        'has_seen_branches_tour',
        'has_seen_my_travelers_tour',
        'has_seen_customer_events_tour',
        'has_seen_cruise_tour',
        'has_seen_business_visa_tour',
        'has_seen_visumpoint_tour',
        'has_seen_settings_tour',
        // Login code
        'login_code',
        'login_code_expires_at',
        // App code
        'app_code',
        'assign_to',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'pds_api_token', // Hide API token from serialization
        'login_code',
    ];

    protected $casts = [
        'login_code_expires_at' => 'datetime',
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
        // PDS sync & Travel Links
        'pds_sync_enabled' => 'boolean',
        'pds_last_synced_at' => 'datetime',
        'travel_links_enabled' => 'boolean',
        // Notifications
        'notifications_enabled' => 'boolean',
        'has_seen_platform_tour' => 'boolean',
        'has_seen_travel_alert_tour' => 'boolean',
        'has_seen_gtm_tour' => 'boolean',
        'has_seen_trs_tour' => 'boolean',
        'has_seen_entry_conditions_tour' => 'boolean',
        'has_seen_travel_data_tour' => 'boolean',
        'has_seen_travel_links_tour' => 'boolean',
        'has_seen_booking_tour' => 'boolean',
        'has_seen_airports_tour' => 'boolean',
        'has_seen_branches_tour' => 'boolean',
        'has_seen_my_travelers_tour' => 'boolean',
        'has_seen_customer_events_tour' => 'boolean',
        'has_seen_cruise_tour' => 'boolean',
        'has_seen_business_visa_tour' => 'boolean',
        'has_seen_visumpoint_tour' => 'boolean',
        'has_seen_settings_tour' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            $customer->app_code = self::generateUniqueAppCode();
        });

        static::created(function (Customer $customer) {
            $adminGroup = EmployeeGroup::create([
                'customer_id' => $customer->id,
                'name' => 'Administratoren',
                'description' => 'Systemadministratoren in der Passolution Travel Information Platform',
                'is_system' => true,
            ]);

            EmployeeGroup::create([
                'customer_id' => $customer->id,
                'name' => 'Mitarbeiter',
                'description' => 'Mitarbeiter der Organisation',
            ]);

            // Create an employee entry for the owner and assign to Administratoren
            $nameParts = explode(' ', $customer->name, 2);
            $ownerEmployee = Employee::create([
                'customer_id' => $customer->id,
                'first_name' => $nameParts[0] ?? $customer->name,
                'last_name' => $nameParts[1] ?? '',
                'email' => $customer->email,
                'phone' => $customer->phone ?? '',
                'position' => 'Inhaber / Administrator',
                'is_active' => true,
            ]);

            $ownerEmployee->groups()->attach($adminGroup->id);
        });
    }

    /**
     * Generate a unique 4-character alphanumeric app code.
     */
    public static function generateUniqueAppCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        } while (
            self::where('app_code', $code)->exists() ||
            Branch::where('app_code', $code)->exists()
        );

        return $code;
    }

    /**
     * Send the email verification notification in German.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Send the password reset notification in German.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'assign_to');
    }

    public function assignedCustomers(): HasMany
    {
        return $this->hasMany(self::class, 'assign_to');
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

    public function legacyOptions(): HasOne
    {
        return $this->hasOne(CustomerLegacyOption::class, 'account_id');
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    public function notificationRules(): HasMany
    {
        return $this->hasMany(NotificationRule::class);
    }

    public function notificationTemplates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    public function customEvents(): HasMany
    {
        return $this->hasMany(CustomEvent::class);
    }

    /**
     * Accounts this customer can access (granted by admin).
     */
    public function accessibleAccounts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'customer_access', 'accessor_customer_id', 'customer_id')
            ->withTimestamps();
    }

    /**
     * Users who can access this customer's account.
     */
    public function accessors(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'customer_access', 'customer_id', 'accessor_customer_id')
            ->withTimestamps();
    }

    /**
     * Get all accounts this customer can work with (own + granted).
     */
    public function allAccessibleAccounts()
    {
        return collect([$this])->merge($this->accessibleAccounts);
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
