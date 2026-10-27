<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Standardpreis einer Auspraegung ab einem Stichtag – gilt, bis ein neuerer
 * Preisstand beginnt. Kostenlos geht vor allen Betraegen.
 */
class ProductVersionPrice extends Model
{
    /** Preisspalte => Bezeichnung */
    public const AMOUNTS = [
        'price_monthly' => 'pro Monat',
        'price_yearly' => 'pro Jahr',
        'price_one_time' => 'einmalig',
        'price_setup' => 'Einrichtung',
    ];

    protected $fillable = [
        'product_version_id',
        'valid_from',
        'price_monthly',
        'price_yearly',
        'price_one_time',
        'price_setup',
        'is_free',
        'notes',
    ];

    protected $casts = [
        'valid_from' => 'date:Y-m-d',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'price_one_time' => 'decimal:2',
        'price_setup' => 'decimal:2',
        'is_free' => 'boolean',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(ProductVersion::class, 'product_version_id');
    }

    /** z. B. "9,90 € pro Monat · 99,00 € pro Jahr" oder "kostenlos" */
    public function label(): string
    {
        if ($this->is_free) {
            return 'kostenlos';
        }

        $parts = [];
        foreach (self::AMOUNTS as $column => $suffix) {
            if ($this->{$column} !== null) {
                $parts[] = number_format((float) $this->{$column}, 2, ',', '.').' € '.$suffix;
            }
        }

        return $parts ? implode(' · ', $parts) : 'Preis offen';
    }
}
