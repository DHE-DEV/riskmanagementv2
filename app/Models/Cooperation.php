<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kooperation in der Touristik (z. B. eine Reisebuero-Kette), der Kunden fuer
 * bestimmte Zeitraeume angehoeren (CustomerBillingPeriod). Meist rechnet die
 * Kooperation gesammelt fuer alle Mitglieder ab und bekommt einen Kickback.
 */
class Cooperation extends Model
{
    public const MEMBER_BILLING = [
        'cooperation' => 'Gesammelt über die Kooperation',
        'self_payer' => 'Jedes Büro einzeln (Selbstzahler)',
    ];

    public const BILLING_INTERVALS = [
        'monthly' => 'Monatlich',
        'half_yearly' => 'Halbjährlich',
        'yearly' => 'Jährlich',
    ];

    /** Festbetrag je Buero oder als Pauschale fuer alle Mitglieder */
    public const FIXED_PRICE_MODES = [
        'per_office' => 'pro Büro',
        'flat' => 'pauschal für alle Mitglieder',
    ];

    public const KICKBACK_TYPES = [
        'none' => 'Kein Kickback',
        'percent' => 'Prozentual',
        'fixed' => 'Fester Betrag',
    ];

    protected $fillable = [
        'name',
        'customer_id',
        'member_billing',
        'billing_interval',
        'fixed_price_per_office',
        'fixed_price_mode',
        'kickback_type',
        'kickback_value',
        'invoice_emails',
        'tax_rate',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'kickback_value' => 'decimal:2',
        'fixed_price_per_office' => 'decimal:2',
        'is_active' => 'boolean',
        'invoice_emails' => 'array',
        'tax_rate' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function billingPeriods(): HasMany
    {
        return $this->hasMany(CustomerBillingPeriod::class);
    }

    /** Zeitraeume, die heute laufen – die aktuellen Mitglieder. */
    public function currentPeriods(): HasMany
    {
        return $this->billingPeriods()->current();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name')->orderBy('id');
    }

    /** Pauschale fuer alle Mitglieder statt Betrag je Buero? */
    public function hasFlatFee(): bool
    {
        return $this->fixed_price_per_office !== null && $this->fixed_price_mode === 'flat';
    }

    /** z. B. "4,90 € pro Büro/Monat" oder "Pauschale 5.000,00 €/Monat für alle Mitglieder"; null ohne Festbetrag. */
    public function fixedPriceLabel(): ?string
    {
        if ($this->fixed_price_per_office === null) {
            return null;
        }

        $amount = number_format((float) $this->fixed_price_per_office, 2, ',', '.').' €';

        return $this->fixed_price_mode === 'flat'
            ? 'Pauschale '.$amount.'/Monat für alle Mitglieder'
            : 'Festbetrag '.$amount.' pro Büro/Monat';
    }

    /** z. B. "10 %" oder "2,50 €"; null ohne Kickback. */
    public function kickbackLabel(): ?string
    {
        if ($this->kickback_type === 'none' || $this->kickback_value === null) {
            return null;
        }

        $value = number_format((float) $this->kickback_value, 2, ',', '.');

        return $this->kickback_type === 'percent' ? rtrim(rtrim($value, '0'), ',').' %' : $value.' €';
    }
}
