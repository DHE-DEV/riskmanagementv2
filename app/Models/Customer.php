<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class Customer extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

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
        'passolution_features',
        'passolution_subscription_updated_at',
        'hide_profile_completion',
        'directory_listing_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'business_type' => 'array',
        'passolution_token_expires_at' => 'datetime',
        'passolution_refresh_token_expires_at' => 'datetime',
        'passolution_features' => 'array',
        'passolution_subscription_updated_at' => 'datetime',
    ];

    /**
     * Check if customer has verified email
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if customer uses social login
     */
    public function isSocialLogin(): bool
    {
        return !is_null($this->provider);
    }

    /**
     * Check if Passolution integration is active and token is valid
     */
    public function hasActivePassolution(): bool
    {
        return !is_null($this->passolution_access_token)
            && !is_null($this->passolution_token_expires_at)
            && $this->passolution_token_expires_at->isFuture();
    }
}
