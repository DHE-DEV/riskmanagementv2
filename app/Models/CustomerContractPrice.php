<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Individueller Preis einer Auspraegung in einem Kundenvertrag, optional an
 * eine Anzahl PAX gebunden. Leere Betraege fallen auf den Standardpreis
 * zurueck; kostenlos geht vor.
 */
class CustomerContractPrice extends Model
{
    protected $fillable = [
        'customer_contract_id',
        'product_version_id',
        'price_monthly',
        'price_yearly',
        'price_one_time',
        'price_setup',
        'is_free',
        'pax',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'price_one_time' => 'decimal:2',
        'price_setup' => 'decimal:2',
        'is_free' => 'boolean',
        'pax' => 'integer',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(CustomerContract::class, 'customer_contract_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ProductVersion::class, 'product_version_id');
    }

    /** z. B. "8,50 € pro Monat" oder "kostenlos" – nur die individuellen Werte */
    public function label(): string
    {
        if ($this->is_free) {
            return 'kostenlos';
        }

        $parts = [];
        foreach (ProductVersionPrice::AMOUNTS as $column => $suffix) {
            if ($this->{$column} !== null) {
                $parts[] = number_format((float) $this->{$column}, 2, ',', '.').' € '.$suffix;
            }
        }

        if ($this->pax) {
            $parts[] = 'für '.number_format($this->pax, 0, ',', '.').' PAX';
        }

        return implode(' · ', $parts);
    }
}
