<?php

namespace App\Services\TravelDetail;

use App\Models\AirportCode;
use App\Models\TravelDetail\TdTrip;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CountryDeriver
{
    /**
     * Cache TTL for IATA to country mappings
     */
    private int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = config('travel_detail.cache.country_ttl', 86400);
    }

    /**
     * Derive all visited countries for a trip and update the trip record
     */
    public function deriveForTrip(TdTrip $trip): void
    {
        $countryCodes = [];

        // Collect from flight segments
        foreach ($trip->flightSegments as $segment) {
            // Departure airport country
            if ($segment->departure_airport_code) {
                $countryCode = $this->getCountryForIata($segment->departure_airport_code);
                if ($countryCode) {
                    $countryCodes[$countryCode] = true;

                    // Update segment with country code if not set
                    if (!$segment->departure_country_code) {
                        $segment->update(['departure_country_code' => $countryCode]);
                    }
                }
            }

            // Arrival airport country
            if ($segment->arrival_airport_code) {
                $countryCode = $this->getCountryForIata($segment->arrival_airport_code);
                if ($countryCode) {
                    $countryCodes[$countryCode] = true;

                    // Update segment with country code if not set
                    if (!$segment->arrival_country_code) {
                        $segment->update(['arrival_country_code' => $countryCode]);
                    }
                }
            }
        }

        // Collect from stays
        foreach ($trip->stays as $stay) {
            if ($stay->country_code) {
                $countryCodes[$stay->country_code] = true;
            }
        }

        // Also update air legs with country codes
        foreach ($trip->airLegs as $leg) {
            if ($leg->origin_airport_code && !$leg->origin_country_code) {
                $leg->update([
                    'origin_country_code' => $this->getCountryForIata($leg->origin_airport_code),
                ]);
            }
            if ($leg->destination_airport_code && !$leg->destination_country_code) {
                $leg->update([
                    'destination_country_code' => $this->getCountryForIata($leg->destination_airport_code),
                ]);
            }
        }

        // Update trip with all visited countries
        $trip->update([
            'countries_visited' => array_keys($countryCodes),
        ]);

        if (config('travel_detail.logging.enabled')) {
            Log::channel(config('travel_detail.logging.channel'))
                ->debug('Derived countries for trip', [
                    'trip_id' => $trip->id,
                    'countries' => array_keys($countryCodes),
                ]);
        }
    }

    /**
     * Get ISO country code for an IATA airport code
     */
    public function getCountryForIata(string $iataCode): ?string
    {
        $iataCode = strtoupper(trim($iataCode));

        if (strlen($iataCode) !== 3) {
            return null;
        }

        return Cache::remember(
            config('travel_detail.cache.prefix') . "iata_country_{$iataCode}",
            $this->cacheTtl,
            function () use ($iataCode) {
                $airport = AirportCode::where('iata_code', $iataCode)->first();
                return $airport?->iso_country;
            }
        );
    }

    /**
     * Get full geo data for an IATA airport code
     */
    public function getGeoDataForIata(string $iataCode): ?array
    {
        $iataCode = strtoupper(trim($iataCode));

        if (strlen($iataCode) !== 3) {
            return null;
        }

        return Cache::remember(
            config('travel_detail.cache.prefix') . "iata_geo_{$iataCode}",
            config('travel_detail.cache.airport_ttl', 3600),
            function () use ($iataCode) {
                $airport = AirportCode::where('iata_code', $iataCode)->first();

                if (!$airport) {
                    return null;
                }

                return [
                    'lat' => $airport->latitude_deg,
                    'lng' => $airport->longitude_deg,
                    'country_code' => $airport->iso_country,
                    'name' => $airport->name,
                    'municipality' => $airport->municipality,
                ];
            }
        );
    }

    /**
     * Enrich a segment with geo data from IATA codes
     */
    public function enrichSegmentGeoData(array $segmentData): array
    {
        // Enrich departure
        if (isset($segmentData['departure_airport_code'])) {
            $geoData = $this->getGeoDataForIata($segmentData['departure_airport_code']);
            if ($geoData) {
                $segmentData['departure_lat'] = $segmentData['departure_lat'] ?? $geoData['lat'];
                $segmentData['departure_lng'] = $segmentData['departure_lng'] ?? $geoData['lng'];
                $segmentData['departure_country_code'] = $segmentData['departure_country_code'] ?? $geoData['country_code'];
            }
        }

        // Enrich arrival
        if (isset($segmentData['arrival_airport_code'])) {
            $geoData = $this->getGeoDataForIata($segmentData['arrival_airport_code']);
            if ($geoData) {
                $segmentData['arrival_lat'] = $segmentData['arrival_lat'] ?? $geoData['lat'];
                $segmentData['arrival_lng'] = $segmentData['arrival_lng'] ?? $geoData['lng'];
                $segmentData['arrival_country_code'] = $segmentData['arrival_country_code'] ?? $geoData['country_code'];
            }
        }

        return $segmentData;
    }

    /**
     * Clear cached data for an IATA code
     */
    public function clearCache(string $iataCode): void
    {
        $iataCode = strtoupper(trim($iataCode));
        $prefix = config('travel_detail.cache.prefix');

        Cache::forget("{$prefix}iata_country_{$iataCode}");
        Cache::forget("{$prefix}iata_geo_{$iataCode}");
    }
}
