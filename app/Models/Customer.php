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

    /**
     * Firmenadress-Spalten, die im /admin-Datensatz gepflegt werden und deren
     * Aenderung den CustomerObserver (BookingLocation/Geocoding) ausloesen muss.
     *
     * @var list<string>
     */
    public const COMPANY_ADDRESS_FIELDS = [
        'company_name',
        'company_street',
        'company_house_number',
        'company_postal_code',
        'company_city',
        'company_country',
    ];

    protected $attributes = [
        'branch_management_active' => true,
        'customer_type' => 'business',
    ];

    protected $fillable = [
        'name',
        'email',
        'username',
        'pds_account_id',
        'pds_id',
        'pds_name',
        'pds_username',
        'pds_email',
        'pds_account_type',
        'pds_account_name',
        'pds_account_first_name',
        'pds_account_last_name',
        'pds_account_email',
        'pds_account_phone',
        'pds_account_address_line_1',
        'pds_account_zip_code',
        'pds_account_city',
        'pds_account_state',
        'pds_account_country',
        'pds_account_subscription_type',
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
     * Account-Stammdaten (pds_account_*) gegen die pds-api abgleichen – gedrosselt
     * ueber pds_last_synced_at (TTL). Wird beim Dashboard-Aufruf genutzt, damit z. B.
     * ein Abo-Wechsel ohne erneuten Login sichtbar wird.
     *
     * Quellen (die __internal-Endpunkte haben unterschiedliche Parameter):
     * - Stammdaten: /account/info erwartet die Login-/User-E-Mail (KEIN account_id).
     * - Abo:        /account/subscription unterstuetzt account_id.
     *
     * Nur gelieferte (nicht-null) Werte werden uebernommen; bei komplettem Fehlschlag
     * bleibt der alte Stand erhalten, der Zeitstempel wird dennoch gesetzt (Drosselung).
     */
    public function syncPdsAccountData(int $ttlMinutes = 5): void
    {
        if ($this->pds_last_synced_at && $this->pds_last_synced_at->gt(now()->subMinutes($ttlMinutes))) {
            return;
        }

        $api = app(\App\Services\PassolutionApiService::class);

        // Stammdaten per Login-/User-E-Mail (account/info unterstuetzt kein account_id).
        $email = $this->email ?: ($this->pds_username ?: $this->pds_email);
        $account = $email ? $api->fetchAccountByEmail($email) : null;

        // Abo + Features bevorzugt mit dem EIGENEN Token des Kunden ueber
        // /account/subscription (liefert type UND features des Token-Inhabers).
        $token = $this->getActiveApiToken() ?: $this->provider_token;
        $sub = $api->fetchSubscriptionWithToken($token);

        // Fallback ohne (gueltigen) Token: __internal per account_id -> nur type.
        // ID bevorzugt aus der frischen account/info-Antwort, sonst die gespeicherte.
        if (! $sub) {
            $accountId = data_get($account, 'id') ?: $this->pds_account_id;
            $sub = $accountId ? $api->fetchSubscriptionById($accountId) : null;
        }

        $subscriptionType = data_get($sub, 'type');
        $features = data_get($sub, 'features');

        if (! $account && ! $sub) {
            $this->forceFill(['pds_last_synced_at' => now()])->saveQuietly();

            return;
        }

        $mapped = [];

        if ($account) {
            $mapped = [
                'pds_account_id' => data_get($account, 'id'),
                'pds_account_name' => data_get($account, 'name'),
                'pds_account_first_name' => data_get($account, 'first_name'),
                'pds_account_last_name' => data_get($account, 'last_name'),
                'pds_account_email' => data_get($account, 'email'),
                'pds_account_phone' => data_get($account, 'phone'),
                'pds_account_address_line_1' => data_get($account, 'address_line_1'),
                'pds_account_zip_code' => data_get($account, 'zip_code'),
                'pds_account_city' => data_get($account, 'city'),
                'pds_account_state' => data_get($account, 'state'),
                'pds_account_country' => data_get($account, 'country_code'),
            ];
        }

        // Firmen-Stammdaten (company_*) mitziehen – das sind die Felder, die im
        // /admin-Datensatz und in der BookingLocation angezeigt werden. Ohne diesen
        // Abgleich wuerde eine Adressaenderung auf der Plattform nur in den
        // pds_account_*-Spiegelspalten landen und im Admin veralten.
        if ($account) {
            $mapped = array_merge($mapped, $this->mapAccountToCompanyFields($account));

            // Auch das allgemeine Name-Feld (/admin) mitfuehren – wie bei der
            // Neuanlage: Kontaktperson, ersatzweise Firmenname. Liefert die API
            // keines von beidem, bleibt der bestehende Name erhalten (null wird
            // unten herausgefiltert).
            $mapped['name'] = self::resolveContactName($account) ?? self::resolveCompanyName($account);
        }

        if ($subscriptionType) {
            // pds_account_* (Dashboard) UND die von den Settings genutzten
            // passolution_*-Felder gleichermassen pflegen.
            $mapped['pds_account_subscription_type'] = $subscriptionType;
            $mapped['passolution_subscription_type'] = $subscriptionType;
            $mapped['passolution_subscription_updated_at'] = now();
        }

        // Feature-Liste nur uebernehmen, wenn die API sie liefert (Array). Solange
        // der __internal-Endpunkt keine features schickt, bleibt der Bestand erhalten.
        if (is_array($features)) {
            $mapped['passolution_features'] = $features;
        }

        $mapped = array_filter($mapped, fn ($value) => $value !== null);

        // Sonderfall Hausnummer: Liefert die API eine Strasse OHNE Nummer, muss die
        // alte Nummer geleert werden – sonst bliebe sie an der neuen Strasse haengen.
        if ($account && data_get($account, 'address_line_1') !== null) {
            $mapped['company_house_number'] = self::parseHouseNumber(data_get($account, 'address_line_1'));
        }

        $mapped['pds_last_synced_at'] = now();

        $this->forceFill($mapped);

        // Aendert sich die Firmenadresse, muss der CustomerObserver laufen
        // (BookingLocation/Geocoding). Reine pds_*-Spiegelungen bleiben still.
        if ($this->isDirty(self::COMPANY_ADDRESS_FIELDS)) {
            $this->save();

            return;
        }

        $this->saveQuietly();
    }

    /**
     * Firmen-Stammdaten aus einer account/info-Antwort auf die company_*-Spalten
     * abbilden. `address_line_1` enthaelt Strasse und Hausnummer zusammen und wird
     * hier – wie beim Import – aufgeteilt.
     *
     * @param  array<string, mixed>  $account
     * @return array<string, string|null>
     */
    protected function mapAccountToCompanyFields(array $account): array
    {
        return [
            'company_name' => self::resolveCompanyName($account),
            'company_additional' => self::resolveContactName($account),
            'company_street' => self::parseStreet(data_get($account, 'address_line_1')),
            'company_house_number' => self::parseHouseNumber(data_get($account, 'address_line_1')),
            'company_postal_code' => data_get($account, 'zip_code'),
            'company_city' => data_get($account, 'city'),
            'company_country' => data_get($account, 'country_code') ?: data_get($account, 'country'),
        ];
    }

    /**
     * Kontaktperson (Vor- und Nachname) aus einer account/info-Antwort zu einem
     * Namen zusammensetzen – wird als Zusatz (company_additional) gefuehrt.
     * Gibt null zurueck, wenn kein Name geliefert wird, damit ein bestehender
     * Zusatz nicht durch einen leeren Wert ueberschrieben wird.
     *
     * @param  array<string, mixed>  $account
     */
    public static function resolveContactName(array $account): ?string
    {
        $name = trim(
            (string) (data_get($account, 'first_name') ?? '').' '.
            (string) (data_get($account, 'last_name') ?? '')
        );

        return $name !== '' ? $name : null;
    }

    /**
     * Firmenname aus einer account/info-Antwort ermitteln. Das `name`-Feld ist in
     * der PDS-API ueberladen (mal Firma, mal E-Mail, mal Abo-/Filialname), daher
     * zuerst die dedizierten Firmen-Felder und nur ersatzweise `name`.
     *
     * @param  array<string, mixed>  $account
     */
    public static function resolveCompanyName(array $account): ?string
    {
        foreach (['company_name', 'company', 'name'] as $key) {
            $value = data_get($account, $key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * Strassenname aus einer kombinierten Adresszeile ("Musterweg 12a").
     */
    public static function parseStreet(?string $addressLine): ?string
    {
        if (empty($addressLine)) {
            return null;
        }

        if (preg_match('/^(.+?)\s+(\d+.*)$/u', trim($addressLine), $matches)) {
            return trim($matches[1]);
        }

        return trim($addressLine);
    }

    /**
     * Hausnummer aus einer kombinierten Adresszeile ("Musterweg 12a").
     */
    public static function parseHouseNumber(?string $addressLine): ?string
    {
        if (empty($addressLine)) {
            return null;
        }

        if (preg_match('/^.+?\s+(\d+.*)$/u', trim($addressLine), $matches)) {
            return trim($matches[1]);
        }

        return null;
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
