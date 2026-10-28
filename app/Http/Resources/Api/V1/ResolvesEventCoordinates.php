<?php

namespace App\Http\Resources\Api\V1;

use App\Services\CustomEventLocationService;

/**
 * Resolves event coordinates using the same priority cascade as the map
 * (CustomEventLocationService::displayCoordinatesFor):
 * 1. City (if use_default_coordinates + city_id)
 * 2. Regional capital, then region (if use_default_coordinates + region_id)
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
            // City > regional capital > region > capital > country center
            [$lat, $lng] = app(CustomEventLocationService::class)
                ->displayCoordinatesFor($country->id, $country->pivot->region_id, $country->pivot->city_id) ?? [null, null];
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
