<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_translations',
        'code',
        'country_id',
        'description',
        'keywords',
        'lat',
        'lng',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'keywords' => 'array',
        'lat' => 'decimal:6',
        'lng' => 'decimal:6',
    ];

    /**
     * Get the country for this region.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the cities for this region.
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Get the disaster events for this region.
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
        return $translations[$language] ?? $translations['en'] ?? $this->code ?? 'Unknown';
    }

    /**
     * Scope a query to only include regions by country.
     */
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope a query to search regions by name or code.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
