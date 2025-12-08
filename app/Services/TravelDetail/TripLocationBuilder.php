<?php

namespace App\Services\TravelDetail;

use App\Models\TravelDetail\TdTrip;
use App\Models\TravelDetail\TdTripLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TripLocationBuilder
{
    public function __construct(
        private CountryDeriver $countryDeriver
    ) {}

    /**
     * Build denormalized location timeline for a trip
     * This enables efficient geo-proximity queries
     */
    public function buildForTrip(TdTrip $trip): void
    {
        // Clear existing locations for this trip
        $trip->tripLocations()->delete();

        $locations = [];

        // Add locations from flight segments
        foreach ($trip->flightSegments as $segment) {
            // Departure location
            if ($segment->departure_time && ($segment->departure_lat || $segment->departure_airport_code)) {
                $geoData = null;
                if (!$segment->departure_lat && $segment->departure_airport_code) {
                    $geoData = $this->countryDeriver->getGeoDataForIata($segment->departure_airport_code);
                }

                $lat = $segment->departure_lat ?? $geoData['lat'] ?? null;
                $lng = $segment->departure_lng ?? $geoData['lng'] ?? null;

                if ($lat && $lng) {
                    $locations[] = [
                        'trip_id' => $trip->id,
                        'location_type' => 'departure',
                        'source_type' => 'flight_segment',
                        'source_id' => $segment->id,
                        'lat' => $lat,
                        'lng' => $lng,
                        'location_code' => $segment->departure_airport_code,
                        'location_name' => $geoData['name'] ?? null,
                        'country_code' => $segment->departure_country_code ?? $geoData['country_code'] ?? null,
                        'start_time' => $segment->departure_time->subHours(3), // Include pre-departure time
                        'end_time' => $segment->departure_time,
                        'created_at' => now(),
                    ];
                }
            }

            // Arrival location
            if ($segment->arrival_time && ($segment->arrival_lat || $segment->arrival_airport_code)) {
                $geoData = null;
                if (!$segment->arrival_lat && $segment->arrival_airport_code) {
                    $geoData = $this->countryDeriver->getGeoDataForIata($segment->arrival_airport_code);
                }

                $lat = $segment->arrival_lat ?? $geoData['lat'] ?? null;
                $lng = $segment->arrival_lng ?? $geoData['lng'] ?? null;

                if ($lat && $lng) {
                    // Find the end time (either next segment departure or some buffer)
                    $endTime = $segment->arrival_time->copy()->addHours(2);

                    $locations[] = [
                        'trip_id' => $trip->id,
                        'location_type' => 'arrival',
                        'source_type' => 'flight_segment',
                        'source_id' => $segment->id,
                        'lat' => $lat,
                        'lng' => $lng,
                        'location_code' => $segment->arrival_airport_code,
                        'location_name' => $geoData['name'] ?? null,
                        'country_code' => $segment->arrival_country_code ?? $geoData['country_code'] ?? null,
                        'start_time' => $segment->arrival_time,
                        'end_time' => $endTime,
                        'created_at' => now(),
                    ];
                }
            }
        }

        // Add locations from stays
        foreach ($trip->stays as $stay) {
            if ($stay->check_in && $stay->check_out && $stay->lat && $stay->lng) {
                $locations[] = [
                    'trip_id' => $trip->id,
                    'location_type' => 'stay',
                    'source_type' => 'stay',
                    'source_id' => $stay->id,
                    'lat' => $stay->lat,
                    'lng' => $stay->lng,
                    'location_code' => null,
                    'location_name' => $stay->location_name,
                    'country_code' => $stay->country_code,
                    'start_time' => $stay->check_in,
                    'end_time' => $stay->check_out,
                    'created_at' => now(),
                ];
            }
        }

        // Add locations from transfers
        foreach ($trip->transfers as $transfer) {
            if ($transfer->from_arrival_time && $transfer->to_departure_time &&
                $transfer->transfer_lat && $transfer->transfer_lng) {
                $locations[] = [
                    'trip_id' => $trip->id,
                    'location_type' => 'transfer',
                    'source_type' => 'transfer',
                    'source_id' => $transfer->id,
                    'lat' => $transfer->transfer_lat,
                    'lng' => $transfer->transfer_lng,
                    'location_code' => $transfer->transfer_location_code,
                    'location_name' => null,
                    'country_code' => $transfer->transfer_country_code,
                    'start_time' => $transfer->from_arrival_time,
                    'end_time' => $transfer->to_departure_time,
                    'created_at' => now(),
                ];
            }
        }

        // Bulk insert locations with POINT column
        if (!empty($locations)) {
            // Insert in chunks to handle large trips
            foreach (array_chunk($locations, 100) as $chunk) {
                foreach ($chunk as $location) {
                    DB::statement(
                        'INSERT INTO td_trip_locations
                         (trip_id, location_type, source_type, source_id, lat, lng, point,
                          location_code, location_name, country_code, start_time, end_time, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ST_SRID(POINT(?, ?), 4326), ?, ?, ?, ?, ?, ?)',
                        [
                            $location['trip_id'],
                            $location['location_type'],
                            $location['source_type'],
                            $location['source_id'],
                            $location['lat'],
                            $location['lng'],
                            $location['lng'], // POINT takes (lng, lat) order
                            $location['lat'],
                            $location['location_code'],
                            $location['location_name'],
                            $location['country_code'],
                            $location['start_time'],
                            $location['end_time'],
                            $location['created_at'],
                        ]
                    );
                }
            }
        }

        if (config('travel_detail.logging.enabled')) {
            Log::channel(config('travel_detail.logging.channel'))
                ->debug('Built trip location timeline', [
                    'trip_id' => $trip->id,
                    'location_count' => count($locations),
                ]);
        }
    }

    /**
     * Get location timeline as array
     */
    public function getLocationTimeline(TdTrip $trip): array
    {
        return $trip->tripLocations()
            ->orderBy('start_time')
            ->get()
            ->map(function ($location) {
                return [
                    'type' => $location->location_type,
                    'code' => $location->location_code,
                    'name' => $location->location_name,
                    'country' => $location->country_code,
                    'coordinates' => [
                        'lat' => (float) $location->lat,
                        'lng' => (float) $location->lng,
                    ],
                    'start' => $location->start_time->toIso8601String(),
                    'end' => $location->end_time->toIso8601String(),
                    'duration_minutes' => $location->duration_minutes,
                ];
            })
            ->toArray();
    }
}
