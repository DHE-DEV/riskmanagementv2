<?php

namespace App\Services\TravelDetail;

use App\Models\TravelDetail\TdTrip;
use App\Models\TravelDetail\TdTripLocation;
use App\Models\CustomEvent;
use App\Models\DisasterEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TripProximityService
{
    private float $defaultRadiusKm;
    private float $maxRadiusKm;

    public function __construct()
    {
        $this->defaultRadiusKm = config('travel_detail.proximity.default_radius_km', 100);
        $this->maxRadiusKm = config('travel_detail.proximity.max_radius_km', 500);
    }

    /**
     * Find travelers currently near a geographic point
     */
    public function findTravelersNearPoint(
        float $lat,
        float $lng,
        ?float $radiusKm = null,
        ?Carbon $startTime = null,
        ?Carbon $endTime = null
    ): Collection {
        $radiusKm = min($radiusKm ?? $this->defaultRadiusKm, $this->maxRadiusKm);
        $startTime = $startTime ?? now();
        $endTime = $endTime ?? now();

        $query = TdTripLocation::query()
            ->withDistance($lat, $lng)
            ->withinRadius($lat, $lng, $radiusKm)
            ->activeDuring($startTime, $endTime)
            ->with(['trip' => function ($q) {
                $q->where('status', '!=', 'cancelled');
            }])
            ->orderBy('distance_km');

        $locations = $query->get();

        // Filter out locations where trip is cancelled
        return $locations->filter(fn($loc) => $loc->trip && $loc->trip->status !== 'cancelled');
    }

    /**
     * Find travelers who will be at a location within a timeframe
     */
    public function findTravelersAtLocationInTimeframe(
        float $lat,
        float $lng,
        float $radiusKm,
        Carbon $startTime,
        Carbon $endTime
    ): Collection {
        return $this->findTravelersNearPoint($lat, $lng, $radiusKm, $startTime, $endTime);
    }

    /**
     * Get trips affected by a custom event
     */
    public function getTripsAffectedByCustomEvent(CustomEvent $event, ?float $radiusKm = null): Collection
    {
        if (!$event->latitude || !$event->longitude) {
            return collect();
        }

        $radiusKm = $radiusKm ?? $this->defaultRadiusKm;

        $startTime = $event->start_date ? Carbon::parse($event->start_date) : now()->subDays(1);
        $endTime = $event->end_date ? Carbon::parse($event->end_date) : now()->addDays(7);

        $locations = $this->findTravelersNearPoint(
            $event->latitude,
            $event->longitude,
            $radiusKm,
            $startTime,
            $endTime
        );

        if (config('travel_detail.logging.enabled')) {
            Log::channel(config('travel_detail.logging.channel'))
                ->info('Proximity query for custom event', [
                    'event_id' => $event->id,
                    'radius_km' => $radiusKm,
                    'affected_trips' => $locations->pluck('trip_id')->unique()->count(),
                ]);
        }

        return $locations;
    }

    /**
     * Get trips affected by a disaster event
     */
    public function getTripsAffectedByDisasterEvent(DisasterEvent $event, ?float $radiusKm = null): Collection
    {
        if (!$event->lat || !$event->lng) {
            return collect();
        }

        // Use event's radius if available, otherwise default
        $radiusKm = $radiusKm ?? $event->radius_km ?? $this->defaultRadiusKm;

        $startTime = $event->start_time ?? now()->subDays(1);
        $endTime = $event->end_time ?? now()->addDays(7);

        return $this->findTravelersNearPoint(
            $event->lat,
            $event->lng,
            $radiusKm,
            $startTime,
            $endTime
        );
    }

    /**
     * Get trips in a specific country within a time range
     */
    public function getTripsInCountry(
        string $countryCode,
        ?Carbon $startTime = null,
        ?Carbon $endTime = null
    ): Collection {
        $startTime = $startTime ?? now();
        $endTime = $endTime ?? now();

        return TdTripLocation::query()
            ->byCountry(strtoupper($countryCode))
            ->activeDuring($startTime, $endTime)
            ->with(['trip' => function ($q) {
                $q->where('status', '!=', 'cancelled');
            }])
            ->get()
            ->filter(fn($loc) => $loc->trip && $loc->trip->status !== 'cancelled');
    }

    /**
     * Get traveler count near an event (for notification purposes)
     */
    public function getTravelerCountNearEvent(
        float $lat,
        float $lng,
        float $radiusKm,
        ?Carbon $startTime = null,
        ?Carbon $endTime = null
    ): array {
        $locations = $this->findTravelersNearPoint($lat, $lng, $radiusKm, $startTime, $endTime);

        $uniqueTrips = $locations->pluck('trip_id')->unique();

        return [
            'total_locations' => $locations->count(),
            'unique_trips' => $uniqueTrips->count(),
            'trip_ids' => $uniqueTrips->values()->toArray(),
            'by_distance' => $this->groupByDistance($locations),
        ];
    }

    /**
     * Group locations by distance bands
     */
    protected function groupByDistance(Collection $locations): array
    {
        return [
            'within_10km' => $locations->filter(fn($l) => $l->distance_km <= 10)->pluck('trip_id')->unique()->count(),
            'within_50km' => $locations->filter(fn($l) => $l->distance_km <= 50)->pluck('trip_id')->unique()->count(),
            'within_100km' => $locations->filter(fn($l) => $l->distance_km <= 100)->pluck('trip_id')->unique()->count(),
        ];
    }

    /**
     * Find all events near a trip's locations
     */
    public function findEventsNearTrip(TdTrip $trip, ?float $radiusKm = null): array
    {
        $radiusKm = $radiusKm ?? $this->defaultRadiusKm;

        $nearbyEvents = [
            'custom_events' => [],
            'disaster_events' => [],
        ];

        foreach ($trip->tripLocations as $location) {
            // Find nearby custom events
            $customEvents = CustomEvent::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('is_active', true)
                ->where(function ($q) use ($location) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $location->start_time);
                })
                ->get()
                ->filter(function ($event) use ($location, $radiusKm) {
                    $distance = $this->calculateDistance(
                        $location->lat,
                        $location->lng,
                        $event->latitude,
                        $event->longitude
                    );
                    return $distance <= $radiusKm;
                });

            foreach ($customEvents as $event) {
                if (!isset($nearbyEvents['custom_events'][$event->id])) {
                    $nearbyEvents['custom_events'][$event->id] = [
                        'event' => $event,
                        'locations' => [],
                    ];
                }
                $nearbyEvents['custom_events'][$event->id]['locations'][] = $location;
            }

            // Find nearby disaster events
            $disasterEvents = DisasterEvent::query()
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->where(function ($q) use ($location) {
                    $q->whereNull('end_time')
                        ->orWhere('end_time', '>=', $location->start_time);
                })
                ->get()
                ->filter(function ($event) use ($location, $radiusKm) {
                    $distance = $this->calculateDistance(
                        $location->lat,
                        $location->lng,
                        $event->lat,
                        $event->lng
                    );
                    $eventRadius = $event->radius_km ?? $radiusKm;
                    return $distance <= $eventRadius;
                });

            foreach ($disasterEvents as $event) {
                if (!isset($nearbyEvents['disaster_events'][$event->id])) {
                    $nearbyEvents['disaster_events'][$event->id] = [
                        'event' => $event,
                        'locations' => [],
                    ];
                }
                $nearbyEvents['disaster_events'][$event->id]['locations'][] = $location;
            }
        }

        return $nearbyEvents;
    }

    /**
     * Find travelers near a point, filtered by nationality
     *
     * @param array|string|null $nationalities ISO alpha-2 country codes (e.g., ['DE', 'AT'])
     */
    public function findTravelersByNationalityNearPoint(
        float $lat,
        float $lng,
        array|string|null $nationalities = null,
        ?float $radiusKm = null,
        ?Carbon $startTime = null,
        ?Carbon $endTime = null
    ): Collection {
        $radiusKm = min($radiusKm ?? $this->defaultRadiusKm, $this->maxRadiusKm);
        $startTime = $startTime ?? now();
        $endTime = $endTime ?? now();

        $query = TdTripLocation::query()
            ->withDistance($lat, $lng)
            ->withinRadius($lat, $lng, $radiusKm)
            ->activeDuring($startTime, $endTime)
            ->with(['trip' => function ($q) use ($nationalities) {
                $q->where('status', '!=', 'cancelled')
                    ->with(['travellers' => function ($tq) use ($nationalities) {
                        if ($nationalities) {
                            $nationalities = is_array($nationalities) ? $nationalities : [$nationalities];
                            $tq->whereIn('nationality', array_map('strtoupper', $nationalities));
                        }
                    }]);
            }])
            ->orderBy('distance_km');

        $locations = $query->get();

        // If nationality filter is set, only return locations where trip has matching travellers
        if ($nationalities) {
            return $locations->filter(function ($loc) {
                return $loc->trip &&
                    $loc->trip->status !== 'cancelled' &&
                    $loc->trip->travellers->isNotEmpty();
            });
        }

        return $locations->filter(fn($loc) => $loc->trip && $loc->trip->status !== 'cancelled');
    }

    /**
     * Get travelers in a country filtered by their nationality
     *
     * Example: Get all German (DE) and Austrian (AT) citizens currently in Thailand (TH)
     */
    public function getTravelersByNationalityInCountry(
        string $destinationCountry,
        array|string $nationalities,
        ?Carbon $startTime = null,
        ?Carbon $endTime = null
    ): Collection {
        $startTime = $startTime ?? now();
        $endTime = $endTime ?? now();
        $nationalities = is_array($nationalities) ? $nationalities : [$nationalities];

        return TdTripLocation::query()
            ->byCountry(strtoupper($destinationCountry))
            ->activeDuring($startTime, $endTime)
            ->with(['trip' => function ($q) use ($nationalities) {
                $q->where('status', '!=', 'cancelled')
                    ->with(['travellers' => function ($tq) use ($nationalities) {
                        $tq->whereIn('nationality', array_map('strtoupper', $nationalities));
                    }]);
            }])
            ->get()
            ->filter(function ($loc) {
                return $loc->trip &&
                    $loc->trip->status !== 'cancelled' &&
                    $loc->trip->travellers->isNotEmpty();
            });
    }

    /**
     * Get detailed traveler count near an event, grouped by nationality
     */
    public function getTravelerCountByNationalityNearEvent(
        float $lat,
        float $lng,
        float $radiusKm,
        ?Carbon $startTime = null,
        ?Carbon $endTime = null
    ): array {
        $locations = $this->findTravelersNearPoint($lat, $lng, $radiusKm, $startTime, $endTime);

        // Load travellers for all trips
        $tripIds = $locations->pluck('trip_id')->unique();
        $trips = TdTrip::whereIn('id', $tripIds)
            ->with('travellers')
            ->get()
            ->keyBy('id');

        $byNationality = [];
        $totalTravellers = 0;

        foreach ($locations->pluck('trip_id')->unique() as $tripId) {
            $trip = $trips->get($tripId);
            if (!$trip) continue;

            foreach ($trip->travellers as $traveller) {
                $nat = $traveller->nationality ?? 'UNKNOWN';
                $byNationality[$nat] = ($byNationality[$nat] ?? 0) + 1;
                $totalTravellers++;
            }
        }

        // Sort by count descending
        arsort($byNationality);

        return [
            'total_locations' => $locations->count(),
            'unique_trips' => $tripIds->count(),
            'total_travellers' => $totalTravellers,
            'by_nationality' => $byNationality,
            'by_distance' => $this->groupByDistance($locations),
        ];
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
