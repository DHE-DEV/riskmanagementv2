<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'iso_code',
        'iso3_code',
        'name_translations',
        'is_eu_member',
        'currency_code',
        'currency_name',
        'currency_symbol',
        'phone_prefix',
        'languages',
        'timezone',
        'risk_factors',
        'travel_advisories',
        'climate_zones',
        'population',
        'area_km2',
        'lat',
        'lng',
        'continent_id',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'is_eu_member' => 'boolean',
        'languages' => 'array',
        'risk_factors' => 'array',
        'travel_advisories' => 'array',
        'climate_zones' => 'array',
        'population' => 'integer',
        'area_km2' => 'decimal:2',
        'lat' => 'decimal:6',
        'lng' => 'decimal:6',
    ];

    /**
     * Get the continent for this country.
     */
    public function continent(): BelongsTo
    {
        return $this->belongsTo(Continent::class);
    }

    /**
     * Get the regions for this country.
     */
    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    /**
     * Get the cities for this country.
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Get the airports for this country.
     */
    public function airports(): HasMany
    {
        return $this->hasMany(Airport::class);
    }

    /**
     * Get the disaster events for this country.
     */
    public function disasterEvents(): HasMany
    {
        return $this->hasMany(DisasterEvent::class);
    }

    /**
     * Get the name in a specific language.
     */
    public function getName(string $language = 'de'): string
    {
        $translations = $this->name_translations ?? [];
        return $translations[$language] ?? $translations['en'] ?? $this->iso_code ?? 'Unknown';
    }

    /**
     * Scope a query to only include EU member countries.
     */
    public function scopeEuMembers($query)
    {
        return $query->where('is_eu_member', true);
    }

    /**
     * Scope a query to search countries by name or code.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('iso_code', 'like', "%{$search}%")
              ->orWhere('iso3_code', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to only include countries by continent.
     */
    public function scopeByContinent($query, $continentId)
    {
        return $query->where('continent_id', $continentId);
    }
}
