<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ReverseGeocodingService
{
    private GeolocationService $geolocationService;

    public function __construct(GeolocationService $geolocationService)
    {
        $this->geolocationService = $geolocationService;
    }

    /**
     * Verwende OpenStreetMap Nominatim API für Reverse Geocoding
     */
    public function getLocationFromCoordinates(float $lat, float $lng): array
    {
        $cacheKey = "reverse_geocode_{$lat}_{$lng}";

        return Cache::remember($cacheKey, 3600, function () use ($lat, $lng) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'RiskManagementApp/1.0'
                ])->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'accept-language' => 'de,en',
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return $this->matchWithDatabase($data, $lat, $lng);
                }
            } catch (\Exception $e) {
                // Fallback zur datenbankbasierten Lösung
            }

            return $this->geolocationService->findLocationInfo($lat, $lng);
        });
    }

    /**
     * Verwende Google Geocoding API (erfordert API-Key)
     */
    public function getLocationFromGoogle(float $lat, float $lng): array
    {
        $apiKey = config('services.google.maps_api_key');

        if (! $apiKey) {
            return $this->geolocationService->findLocationInfo($lat, $lng);
        }

        $cacheKey = "google_reverse_geocode_{$lat}_{$lng}";

        return Cache::remember($cacheKey, 3600, function () use ($lat, $lng, $apiKey) {
            try {
                $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => "{$lat},{$lng}",
                    'key' => $apiKey,
                    'language' => 'de',
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return $this->matchWithGoogleData($data, $lat, $lng);
                }
            } catch (\Exception $e) {
                // Fallback zur datenbankbasierten Lösung
            }

            return $this->geolocationService->findLocationInfo($lat, $lng);
        });
    }

    /**
     * Matche API-Daten mit der Datenbank
     */
    private function matchWithDatabase(array $nominatimData, float $lat, float $lng): array
    {
        $address = $nominatimData['address'] ?? [];

        // Versuche Stadt zu finden
        $cityName = $address['city'] ?? $address['town'] ?? $address['village'] ?? null;
        $city = null;

        if ($cityName) {
            $city = City::where('name_translations', 'like', "%\"{$cityName}\"%")
                ->orWhere('name_translations', 'like', "%\"en\":\"{$cityName}\"%")
                ->orWhere('name_translations', 'like', "%\"de\":\"{$cityName}\"%")
                ->first();
        }

        // Versuche Land zu finden
        $countryName = $address['country'] ?? null;
        $country = null;

        if ($countryName) {
            $country = Country::where('name_translations', 'like', "%\"{$countryName}\"%")
                ->orWhere('iso_code', 'like', "%{$countryName}%")
                ->first();
        }

        // Fallback zur nächstgelegenen Entfernung
        if (! $city) {
            $city = $this->geolocationService->findNearestCity($lat, $lng);
        }

        if (! $country) {
            $country = $this->geolocationService->findNearestCountry($lat, $lng);
        }

        $continent = $country ? $country->continent : $this->geolocationService->findNearestContinent($lat, $lng);

        return [
            'coordinates' => [
                'lat' => $lat,
                'lng' => $lng,
            ],
            'city' => $city ? [
                'id' => $city->id,
                'name' => $city->getName(),
                'distance_km' => $city->lat && $city->lng ?
                    $this->calculateDistance($lat, $lng, $city->lat, $city->lng) : null,
                'is_capital' => $city->is_capital,
            ] : null,
            'country' => $country ? [
                'id' => $country->id,
                'name' => $country->getName(),
                'iso_code' => $country->iso_code,
                'distance_km' => $country->lat && $country->lng ?
                    $this->calculateDistance($lat, $lng, $country->lat, $country->lng) : null,
            ] : null,
            'continent' => $continent ? [
                'id' => $continent->id,
                'name' => $continent->getName(),
                'code' => $continent->code,
                'distance_km' => $continent->lat && $continent->lng ?
                    $this->calculateDistance($lat, $lng, $continent->lat, $continent->lng) : null,
            ] : null,
            'raw_data' => $nominatimData,
        ];
    }

    /**
     * Matche Google API-Daten mit der Datenbank
     */
    private function matchWithGoogleData(array $googleData, float $lat, float $lng): array
    {
        $results = $googleData['results'] ?? [];

        if (empty($results)) {
            return $this->geolocationService->findLocationInfo($lat, $lng);
        }

        $addressComponents = $results[0]['address_components'] ?? [];

        $cityName = null;
        $countryName = null;
        $countryCode = null;

        foreach ($addressComponents as $component) {
            $types = $component['types'] ?? [];

            if (in_array('locality', $types) || in_array('administrative_area_level_1', $types)) {
                $cityName = $component['long_name'];
            }

            if (in_array('country', $types)) {
                $countryName = $component['long_name'];
                $countryCode = $component['short_name'];
            }
        }

        // Matche mit Datenbank
        $city = null;
        $country = null;

        if ($cityName) {
            $city = City::where('name_translations', 'like', "%\"{$cityName}\"%")
                ->orWhere('name_translations', 'like', "%\"en\":\"{$cityName}\"%")
                ->orWhere('name_translations', 'like', "%\"de\":\"{$cityName}\"%")
                ->first();
        }

        if ($countryCode) {
            $country = Country::where('iso_code', $countryCode)
                ->orWhere('iso3_code', $countryCode)
                ->first();
        }

        // Fallback
        if (! $city) {
            $city = $this->geolocationService->findNearestCity($lat, $lng);
        }

        if (! $country) {
            $country = $this->geolocationService->findNearestCountry($lat, $lng);
        }

        $continent = $country ? $country->continent : $this->geolocationService->findNearestContinent($lat, $lng);

        return [
            'coordinates' => [
                'lat' => $lat,
                'lng' => $lng,
            ],
            'city' => $city ? [
                'id' => $city->id,
                'name' => $city->getName(),
                'distance_km' => $city->lat && $city->lng ?
                    $this->calculateDistance($lat, $lng, $city->lat, $city->lng) : null,
                'is_capital' => $city->is_capital,
            ] : null,
            'country' => $country ? [
                'id' => $country->id,
                'name' => $country->getName(),
                'iso_code' => $country->iso_code,
                'distance_km' => $country->lat && $country->lng ?
                    $this->calculateDistance($lat, $lng, $country->lat, $country->lng) : null,
            ] : null,
            'continent' => $continent ? [
                'id' => $continent->id,
                'name' => $continent->getName(),
                'code' => $continent->code,
                'distance_km' => $continent->lat && $continent->lng ?
                    $this->calculateDistance($lat, $lng, $continent->lat, $continent->lng) : null,
            ] : null,
            'raw_data' => $googleData,
        ];
    }

    /**
     * Berechne die Entfernung zwischen zwei Koordinaten in Kilometern (Haversine-Formel)
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Erdradius in Kilometern

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
