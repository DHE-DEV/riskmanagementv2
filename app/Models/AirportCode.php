<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AirportCode extends Model
{
    use SoftDeletes;

    protected $table = 'airport_codes_1';

    protected $fillable = [
        'ident',
        'type',
        'name',
        'latitude_deg',
        'longitude_deg',
        'elevation_ft',
        'timezone',
        'dst_timezone',
        'continent',
        'iso_country',
        'iso_region',
        'city_id',
        'country_id',
        'municipality',
        'scheduled_service',
        'is_active',
        'operates_24h',
        'lounges',
        'nearby_hotels',
        'mobility_options',
        'icao_code',
        'iata_code',
        'gps_code',
        'local_code',
        'home_link',
        'website',
        'wikipedia_link',
        'security_timeslot_url',
        'keywords',
        'source',
    ];

    protected $casts = [
        'latitude_deg' => 'decimal:8',
        'longitude_deg' => 'decimal:8',
        'elevation_ft' => 'integer',
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
        return $this->belongsToMany(Airline::class, 'airline_airport_code', 'airport_code_id', 'airline_id')
            ->withPivot('direction', 'terminal')
            ->withTimestamps();
    }

    public static function getTypeOptions(): array
    {
        return [
            'large_airport' => 'Großer Flughafen',
            'medium_airport' => 'Mittlerer Flughafen',
            'small_airport' => 'Kleiner Flughafen',
            'heliport' => 'Hubschrauberlandeplatz',
            'seaplane_base' => 'Wasserflugzeugbasis',
            'closed' => 'Geschlossen',
            'balloonport' => 'Ballonhafen',
        ];
    }

    public static function getContinentOptions(): array
    {
        return [
            'AF' => 'Afrika',
            'AN' => 'Antarktis',
            'AS' => 'Asien',
            'EU' => 'Europa',
            'NA' => 'Nordamerika',
            'OC' => 'Ozeanien',
            'SA' => 'Südamerika',
        ];
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('iata_code', 'like', "%{$search}%")
              ->orWhere('icao_code', 'like', "%{$search}%")
              ->orWhere('ident', 'like', "%{$search}%")
              ->orWhere('municipality', 'like', "%{$search}%");
        });
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
}
