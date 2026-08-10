<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eine vorgemerkte Feature-Freischaltung fuer eine pds_account_id.
 *
 * Wird beim Login in ein CustomerFeatureOverride uebersetzt - siehe
 * App\Services\CustomerFeaturePreauthorizationService.
 */
class CustomerFeaturePreauthorization extends Model
{
    protected $fillable = [
        'pds_account_id',
        'feature_key',
        'enabled',
        'note',
        'applied_at',
        'applied_customer_id',
    ];

    protected $casts = [
        'pds_account_id' => 'integer',
        'enabled' => 'boolean',
        'applied_at' => 'datetime',
    ];

    /**
     * Kunden mit dieser pds_account_id. Es koennen mehrere sein, etwa wenn zu
     * einem Account sowohl die Firma als auch Mitarbeiterzugaenge existieren.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'pds_account_id', 'pds_account_id');
    }

    public function scopeForFeature($query, string $featureKey)
    {
        return $query->where('feature_key', $featureKey);
    }

    public function scopePending($query)
    {
        return $query->whereNull('applied_at');
    }
}
