<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_translations',
        'country_id',
        'region_id',
        'population',
        'lat',
        'lng',
        'is_capital',
        'is_regional_capital',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'population' => 'integer',
        'lat' => 'decimal:6',
        'lng' => 'decimal:6',
        'is_capital' => 'boolean',
        'is_regional_capital' => 'boolean',
    ];

    /**
     * Get the country for this city.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the region for this city.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the airports for this city.
     */
    public function airports(): HasMany
    {
        return $this->hasMany(Airport::class);
    }

    /**
     * Get the disaster events for this city.
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
        return $translations[$language] ?? $translations['en'] ?? 'Unknown';
    }

    /**
     * Scope a query to only include capital cities.
     */
    public function scopeCapitals($query)
    {
        return $query->where('is_capital', true);
    }

    /**
     * Scope a query to only include cities by country.
     */
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope a query to only include cities by region.
     */
    public function scopeByRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    /**
     * Scope a query to search cities by name.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name_translations', 'like', "%{$search}%");
        });
    }
}
