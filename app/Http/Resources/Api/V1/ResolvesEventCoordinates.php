<?php

namespace App\Http\Resources\Api\V1;

use App\Models\City;
use App\Models\Region;

/**
 * Resolves event coordinates using the same 4-level priority cascade as the map:
 * 1. City (if use_default_coordinates + city_id)
 * 2. Region (if use_default_coordinates + region_id)
 * 3. Capital city
 * 4. Country center
 * Custom pivot coordinates when use_default_coordinates = false.
 * Final fallback: event-level coordinates.
 */
trait ResolvesEventCoordinates
{
    private function getCoordinatesForCountry($country): array
    {
        if (!$country) {
            return [
                'latitude' => $this->latitude ? (float) $this->latitude : null,
                'longitude' => $this->longitude ? (float) $this->longitude : null,
            ];
        }

        $lat = null;
        $lng = null;

        if ($country->pivot && $country->pivot->use_default_coordinates) {
            // 1. City coordinates
            if ($country->pivot->city_id) {
                $city = City::find($country->pivot->city_id);
                if ($city && $city->lat && $city->lng) {
                    $lat = $city->lat;
                    $lng = $city->lng;
                }
            }

            // 2. Region coordinates
            if (!$lat && !$lng && $country->pivot->region_id) {
                $region = Region::find($country->pivot->region_id);
                if ($region && $region->lat && $region->lng) {
                    $lat = $region->lat;
                    $lng = $region->lng;
                }
            }

            // 3. Capital city coordinates
            if (!$lat && !$lng && $country->capital && $country->capital->lat && $country->capital->lng) {
                $lat = $country->capital->lat;
                $lng = $country->capital->lng;
            }

            // 4. Country center
            if (!$lat && !$lng) {
                $lat = $country->lat;
                $lng = $country->lng;
            }
        } elseif ($country->pivot) {
            // Custom pivot coordinates
            $lat = $country->pivot->latitude;
            $lng = $country->pivot->longitude;
        }

        // Final fallback: event-level coordinates
        if (!$lat && !$lng && $this->latitude && $this->longitude) {
            $lat = $this->latitude;
            $lng = $this->longitude;
        }

        return [
            'latitude' => $lat ? (float) $lat : null,
            'longitude' => $lng ? (float) $lng : null,
        ];
    }
}
