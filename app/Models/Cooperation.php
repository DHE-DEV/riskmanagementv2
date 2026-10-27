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
        'kickback_type',
        'kickback_value',
        'contact_email',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'kickback_value' => 'decimal:2',
        'is_active' => 'boolean',
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
