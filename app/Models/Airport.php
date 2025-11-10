<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Airport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'iata_code',
        'icao_code',
        'city_id',
        'country_id',
        'website',
        'security_timeslot_url',
        'lat',
        'lng',
        'altitude',
        'timezone',
        'dst_timezone',
        'type',
        'is_active',
        'source',
        'operates_24h',
        'lounges',
        'nearby_hotels',
        'mobility_options',
    ];

    protected $casts = [
        'lat' => 'decimal:16',
        'lng' => 'decimal:16',
        'altitude' => 'integer',
        'is_active' => 'boolean',
        'operates_24h' => 'boolean',
        'lounges' => 'array',
        'nearby_hotels' => 'array',
        'mobility_options' => 'array',
    ];

    /**
     * Get the city for this airport.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the country for this airport.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the airlines that serve this airport.
     */
    public function airlines(): BelongsToMany
    {
        return $this->belongsToMany(Airline::class, 'airline_airport')
            ->withPivot('direction')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include airports by country.
     */
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope a query to only include airports by city.
     */
    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    /**
     * Scope a query to search airports by name or code.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('iata_code', 'like', "%{$search}%")
              ->orWhere('icao_code', 'like', "%{$search}%");
        });
    }

    /**
     * Get the airport type options.
     */
    public static function getTypeOptions(): array
    {
        return [
            'international' => 'Internationaler Flughafen',
            'large_airport' => 'Großer Flughafen',
            'medium_airport' => 'Mittlerer Flughafen',
            'small_airport' => 'Kleiner Flughafen',
            'heliport' => 'Hubschrauberlandeplatz',
            'seaplane_base' => 'Wasserflugzeugbasis',
        ];
    }
}
