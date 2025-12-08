<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomEvent;
use App\Services\TravelDetail\TripProximityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProximityController extends Controller
{
    public function __construct(
        private TripProximityService $proximityService
    ) {}

    /**
     * Find travelers near a geographic point.
     *
     * POST /api/v1/proximity/near-event
     */
    public function nearEvent(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
        ]);

        $locations = $this->proximityService->findTravelersNearPoint(
            $request->lat,
            $request->lng,
            $request->radius_km,
            $request->start_time ? Carbon::parse($request->start_time) : null,
            $request->end_time ? Carbon::parse($request->end_time) : null
        );

        $uniqueTrips = $locations->pluck('trip')->unique('id');

        return response()->json([
            'success' => true,
            'data' => [
                'query' => [
                    'lat' => $request->lat,
                    'lng' => $request->lng,
                    'radius_km' => $request->radius_km ?? config('travel_detail.proximity.default_radius_km'),
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                ],
                'results' => [
                    'total_locations' => $locations->count(),
                    'unique_trips' => $uniqueTrips->count(),
                ],
                'trips' => $uniqueTrips->map(fn($trip) => [
                    'id' => $trip->id,
                    'provider_id' => $trip->provider_id,
                    'external_trip_id' => $trip->external_trip_id,
                    'booking_reference' => $trip->booking_reference,
                    'computed_start_at' => $trip->computed_start_at?->toIso8601String(),
                    'computed_end_at' => $trip->computed_end_at?->toIso8601String(),
                    'status' => $trip->status,
                ])->values(),
                'locations' => $locations->map(fn($loc) => [
                    'trip_id' => $loc->trip_id,
                    'location_type' => $loc->location_type,
                    'location_code' => $loc->location_code,
                    'location_name' => $loc->location_name,
                    'country_code' => $loc->country_code,
                    'distance_km' => round($loc->distance_km, 2),
                    'start_time' => $loc->start_time?->toIso8601String(),
                    'end_time' => $loc->end_time?->toIso8601String(),
                ])->values(),
            ],
        ]);
    }

    /**
     * Find travelers at a location within a specific timeframe.
     *
     * POST /api/v1/proximity/at-location
     */
    public function atLocation(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['required', 'numeric', 'min:1', 'max:500'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ]);

        $locations = $this->proximityService->findTravelersAtLocationInTimeframe(
            $request->lat,
            $request->lng,
            $request->radius_km,
            Carbon::parse($request->start_time),
            Carbon::parse($request->end_time)
        );

        $stats = $this->proximityService->getTravelerCountNearEvent(
            $request->lat,
            $request->lng,
            $request->radius_km,
            Carbon::parse($request->start_time),
            Carbon::parse($request->end_time)
        );

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $stats,
                'trip_ids' => $locations->pluck('trip_id')->unique()->values(),
            ],
        ]);
    }

    /**
     * Find travelers affected by a specific event.
     *
     * POST /api/v1/proximity/affected-by-event/{event}
     */
    public function affectedByEvent(CustomEvent $event, Request $request): JsonResponse
    {
        $request->validate([
            'radius_km' => ['nullable', 'numeric', 'min:1', 'max:500'],
        ]);

        if (!$event->latitude || !$event->longitude) {
            return response()->json([
                'success' => false,
                'message' => 'Event has no coordinates',
            ], 422);
        }

        $locations = $this->proximityService->getTripsAffectedByCustomEvent(
            $event,
            $request->radius_km
        );

        $uniqueTrips = $locations->pluck('trip')->unique('id');

        return response()->json([
            'success' => true,
            'data' => [
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'coordinates' => [
                        'lat' => $event->latitude,
                        'lng' => $event->longitude,
                    ],
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                ],
                'results' => [
                    'total_affected_trips' => $uniqueTrips->count(),
                    'total_locations' => $locations->count(),
                ],
                'affected_trips' => $uniqueTrips->map(fn($trip) => [
                    'id' => $trip->id,
                    'provider_id' => $trip->provider_id,
                    'external_trip_id' => $trip->external_trip_id,
                    'booking_reference' => $trip->booking_reference,
                    'status' => $trip->status,
                ])->values(),
            ],
        ]);
    }

    /**
     * Find trips in a specific country.
     *
     * POST /api/v1/proximity/trips-in-country
     */
    public function tripsInCountry(Request $request): JsonResponse
    {
        $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
        ]);

        $locations = $this->proximityService->getTripsInCountry(
            $request->country_code,
            $request->start_time ? Carbon::parse($request->start_time) : null,
            $request->end_time ? Carbon::parse($request->end_time) : null
        );

        $uniqueTrips = $locations->pluck('trip')->unique('id');

        return response()->json([
            'success' => true,
            'data' => [
                'country_code' => strtoupper($request->country_code),
                'results' => [
                    'total_trips' => $uniqueTrips->count(),
                    'total_locations' => $locations->count(),
                ],
                'trips' => $uniqueTrips->map(fn($trip) => [
                    'id' => $trip->id,
                    'provider_id' => $trip->provider_id,
                    'external_trip_id' => $trip->external_trip_id,
                    'computed_start_at' => $trip->computed_start_at?->toIso8601String(),
                    'computed_end_at' => $trip->computed_end_at?->toIso8601String(),
                    'status' => $trip->status,
                ])->values(),
            ],
        ]);
    }
}
