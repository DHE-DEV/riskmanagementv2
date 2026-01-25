<?php

namespace App\Services\Folder;

use App\Models\AirportCode;
use App\Models\Country;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AirportLookupService
{
    /**
     * Find airport by IATA code (3-letter code).
     *
     * @return array|null Returns ['airport_id' => int, 'country_id' => int, 'lat' => float, 'lng' => float, 'country_code' => string] or null
     */
    public function findAirportByIataCode(string $iataCode): ?array
    {
        if (empty($iataCode) || strlen($iataCode) !== 3) {
            return null;
        }

        // Use cache to avoid repeated database queries (cache for 24 hours)
        $cacheKey = "airport_iata_{$iataCode}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($iataCode) {
            try {
                $airport = AirportCode::where('iata_code', strtoupper($iataCode))
                    ->where('is_active', true)
                    ->with('country:id,iso_code')
                    ->first();

                if (! $airport) {
                    Log::debug("Airport not found for IATA code: {$iataCode}");

                    return null;
                }

                // Try to get country_id - first from airport, then by looking up iso_country
                $countryId = $airport->country_id;
                if (! $countryId && $airport->iso_country) {
                    $countryId = $this->findCountryByIsoCode($airport->iso_country);
                }

                return [
                    'airport_id' => $airport->id,
                    'country_id' => $countryId,
                    'lat' => $airport->latitude_deg ? (float) $airport->latitude_deg : null,
                    'lng' => $airport->longitude_deg ? (float) $airport->longitude_deg : null,
                    'country_code' => $airport->country?->iso_code ?? $airport->iso_country,
                ];
            } catch (\Exception $e) {
                Log::error("Error looking up airport {$iataCode}: ".$e->getMessage());

                return null;
            }
        });
    }

    /**
     * Find country by ISO code (2-letter code).
     *
     * @return int|null Returns country_id or null
     */
    public function findCountryByIsoCode(string $isoCode): ?int
    {
        if (empty($isoCode) || strlen($isoCode) !== 2) {
            return null;
        }

        // Use cache to avoid repeated database queries (cache for 24 hours)
        $cacheKey = "country_iso_{$isoCode}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($isoCode) {
            try {
                $country = Country::where('iso_code', strtoupper($isoCode))->first();

                if (! $country) {
                    Log::debug("Country not found for ISO code: {$isoCode}");

                    return null;
                }

                return $country->id;
            } catch (\Exception $e) {
                Log::error("Error looking up country {$isoCode}: ".$e->getMessage());

                return null;
            }
        });
    }

    /**
     * Enrich segment data with airport and country IDs.
     */
    public function enrichSegmentData(array $segmentData): array
    {
        // Lookup departure airport
        if (isset($segmentData['departure_airport_code'])) {
            $departureAirport = $this->findAirportByIataCode($segmentData['departure_airport_code']);
            if ($departureAirport) {
                $segmentData['departure_airport_id'] = $departureAirport['airport_id'];
                $segmentData['departure_country_id'] = $departureAirport['country_id'];

                // Fill in lat/lng if not already set
                if (! isset($segmentData['departure_lat']) && $departureAirport['lat']) {
                    $segmentData['departure_lat'] = $departureAirport['lat'];
                }
                if (! isset($segmentData['departure_lng']) && $departureAirport['lng']) {
                    $segmentData['departure_lng'] = $departureAirport['lng'];
                }
                if (! isset($segmentData['departure_country_code']) && $departureAirport['country_code']) {
                    $segmentData['departure_country_code'] = $departureAirport['country_code'];
                }
            }
        }

        // Lookup arrival airport
        if (isset($segmentData['arrival_airport_code'])) {
            $arrivalAirport = $this->findAirportByIataCode($segmentData['arrival_airport_code']);
            if ($arrivalAirport) {
                $segmentData['arrival_airport_id'] = $arrivalAirport['airport_id'];
                $segmentData['arrival_country_id'] = $arrivalAirport['country_id'];

                // Fill in lat/lng if not already set
                if (! isset($segmentData['arrival_lat']) && $arrivalAirport['lat']) {
                    $segmentData['arrival_lat'] = $arrivalAirport['lat'];
                }
                if (! isset($segmentData['arrival_lng']) && $arrivalAirport['lng']) {
                    $segmentData['arrival_lng'] = $arrivalAirport['lng'];
                }
                if (! isset($segmentData['arrival_country_code']) && $arrivalAirport['country_code']) {
                    $segmentData['arrival_country_code'] = $arrivalAirport['country_code'];
                }
            }
        }

        return $segmentData;
    }
}
