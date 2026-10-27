<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Produkt bzw. Feature des Travel Information Service (Admin v2 › System ›
 * Produkte). Hat eine oder mehrere Auspraegungen (ProductVersion, z. B.
 * Allgemein oder Standard/Premium) mit Preisen und einen Leistungsumfang
 * (ProductFeature) fuer die Gegenueberstellung.
 */
class Product extends Model
{
    /** Worauf sich der Preis bezieht */
    public const PRICE_BASES = [
        'per_location' => 'je Büro, Agentur bzw. Reiseberater',
        'per_customer' => 'pauschal je Kunde',
    ];

    protected $fillable = [
        'key',
        'requires_product_id',
        'requires_product_version_id',
        'price_basis',
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

    public function versions(): HasMany
    {
        return $this->hasMany(ProductVersion::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Leistungsumfang: Punkte, die je Auspraegung enthalten sind oder nicht */
    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Nur buchbar zusammen mit diesem Produkt */
    public function requiredProduct(): BelongsTo
    {
        return $this->belongsTo(self::class, 'requires_product_id');
    }

    /** Nur mit dieser Auspraegung des vorausgesetzten Produkts (leer = beliebige) */
    public function requiredVersion(): BelongsTo
    {
        return $this->belongsTo(ProductVersion::class, 'requires_product_version_id');
    }

    /** z. B. "Travel Requirements Service (Premium)"; null ohne Voraussetzung. */
    public function requirementLabel(): ?string
    {
        if (! $this->requiredProduct) {
            return null;
        }

        return $this->requiredProduct->name.($this->requiredVersion ? ' ('.$this->requiredVersion->name.')' : '');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Kuerzel => Name aller Produkte mit Kuerzel – die Module, fuer die sich
     * z. B. das Abrechnungsintervall einzeln festlegen laesst.
     *
     * @return array<string, string>
     */
    public static function moduleOptions(): array
    {
        return self::query()->whereNotNull('key')->ordered()->pluck('name', 'key')->all();
    }
}
