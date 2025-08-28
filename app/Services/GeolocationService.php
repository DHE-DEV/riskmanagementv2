<?php

namespace App\Services;

use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use Illuminate\Support\Collection;

class GeolocationService
{
    /**
     * Finde die nächstgelegene Stadt zu den gegebenen Koordinaten
     */
    public function findNearestCity(float $lat, float $lng, int $maxDistanceKm = 50): ?City
    {
        $cities = City::with(['country.continent'])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        $nearestCity = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($cities as $city) {
            $distance = $this->calculateDistance($lat, $lng, $city->lat, $city->lng);

            if ($distance <= $maxDistanceKm && $distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestCity = $city;
            }
        }

        return $nearestCity;
    }

    /**
     * Finde das nächstgelegene Land zu den gegebenen Koordinaten
     */
    public function findNearestCountry(float $lat, float $lng, int $maxDistanceKm = 2000): ?Country
    {
        $countries = Country::with('continent')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        $nearestCountry = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($countries as $country) {
            $distance = $this->calculateDistance($lat, $lng, $country->lat, $country->lng);

            if ($distance <= $maxDistanceKm && $distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestCountry = $country;
            }
        }

        return $nearestCountry;
    }

    /**
     * Finde den nächstgelegenen Kontinent zu den gegebenen Koordinaten
     */
    public function findNearestContinent(float $lat, float $lng): ?Continent
    {
        $continents = Continent::whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        $nearestContinent = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($continents as $continent) {
            $distance = $this->calculateDistance($lat, $lng, $continent->lat, $continent->lng);

            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestContinent = $continent;
            }
        }

        return $nearestContinent;
    }

    /**
     * Finde alle geografischen Informationen für gegebene Koordinaten
     */
    public function findLocationInfo(float $lat, float $lng): array
    {
        $city = $this->findNearestCity($lat, $lng);
        $country = $this->findNearestCountry($lat, $lng);
        $continent = $this->findNearestContinent($lat, $lng);

        return [
            'coordinates' => [
                'lat' => $lat,
                'lng' => $lng,
            ],
            'city' => $city ? [
                'id' => $city->id,
                'name' => $city->getName(),
                'distance_km' => $this->calculateDistance($lat, $lng, $city->lat, $city->lng),
                'is_capital' => $city->is_capital,
            ] : null,
            'country' => $country ? [
                'id' => $country->id,
                'name' => $country->getName(),
                'iso_code' => $country->iso_code,
                'distance_km' => $this->calculateDistance($lat, $lng, $country->lat, $country->lng),
            ] : null,
            'continent' => $continent ? [
                'id' => $continent->id,
                'name' => $continent->getName(),
                'code' => $continent->code,
                'distance_km' => $this->calculateDistance($lat, $lng, $continent->lat, $continent->lng),
            ] : null,
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

    /**
     * Finde Städte innerhalb eines bestimmten Radius
     */
    public function findCitiesInRadius(float $lat, float $lng, int $radiusKm): Collection
    {
        $cities = City::with(['country.continent'])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        return $cities->filter(function ($city) use ($lat, $lng, $radiusKm) {
            $distance = $this->calculateDistance($lat, $lng, $city->lat, $city->lng);

            return $distance <= $radiusKm;
        })->map(function ($city) use ($lat, $lng) {
            $city->distance_km = $this->calculateDistance($lat, $lng, $city->lat, $city->lng);

            return $city;
        })->sortBy('distance_km');
    }
}
