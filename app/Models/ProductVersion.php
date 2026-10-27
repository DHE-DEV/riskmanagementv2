<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Auspraegung eines Produkts (z. B. Allgemein, Standard, Premium) mit
 * Preisen je Stichtag (ProductVersionPrice). Die Preisspalten am Datensatz
 * stammen aus der frueheren Produktverwaltung und werden nicht mehr gepflegt.
 */
class ProductVersion extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'subtitle',
        'description',
        'internal_description',
        'price_monthly',
        'price_yearly',
        'price_one_time',
        'price_setup',
        'currency',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'price_one_time' => 'decimal:2',
        'price_setup' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Preisstaende, neuester zuerst */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductVersionPrice::class)->orderByDesc('valid_from');
    }

    /** Der am Tag $date geltende Preis (Standard: heute) */
    public function priceOn($date = null): ?ProductVersionPrice
    {
        $day = ($date ? \Illuminate\Support\Carbon::parse($date) : today())->toDateString();

        return $this->prices->first(fn (ProductVersionPrice $price) => $price->valid_from->toDateString() <= $day);
    }

    public function featureValues(): HasMany
    {
        return $this->hasMany(ProductFeatureValue::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('sort_order');
    }
}
