<?php

namespace App\Services\TravelDetail;

use App\Models\TravelDetail\TdTrip;
use App\Models\TravelDetail\TdAirLeg;
use Illuminate\Support\Facades\Log;

class AirLegBuilder
{
    public function __construct(
        private CountryDeriver $countryDeriver
    ) {}

    /**
     * Build/update summaries for all air legs in a trip
     */
    public function buildForTrip(TdTrip $trip): void
    {
        foreach ($trip->airLegs as $leg) {
            $this->buildLegSummary($leg);
        }

        if (config('travel_detail.logging.enabled')) {
            Log::channel(config('travel_detail.logging.channel'))
                ->debug('Built air leg summaries', [
                    'trip_id' => $trip->id,
                    'leg_count' => $trip->airLegs->count(),
                ]);
        }
    }

    /**
     * Build summary for a single air leg
     */
    public function buildLegSummary(TdAirLeg $leg): void
    {
        $segments = $leg->segments()->orderBy('departure_time')->get();

        if ($segments->isEmpty()) {
            return;
        }

        $firstSegment = $segments->first();
        $lastSegment = $segments->last();

        // Calculate total duration
        $totalDuration = 0;
        foreach ($segments as $segment) {
            if ($segment->departure_time && $segment->arrival_time) {
                $totalDuration += $segment->departure_time->diffInMinutes($segment->arrival_time);
            }
        }

        // Also include layover times
        for ($i = 0; $i < $segments->count() - 1; $i++) {
            $current = $segments[$i];
            $next = $segments[$i + 1];

            if ($current->arrival_time && $next->departure_time) {
                $totalDuration += $current->arrival_time->diffInMinutes($next->departure_time);
            }
        }

        // Get geo data for origin/destination if needed
        $originGeo = $this->countryDeriver->getGeoDataForIata($firstSegment->departure_airport_code);
        $destGeo = $this->countryDeriver->getGeoDataForIata($lastSegment->arrival_airport_code);

        $leg->update([
            'leg_start_at' => $firstSegment->departure_time,
            'leg_end_at' => $lastSegment->arrival_time,
            'total_duration_minutes' => $totalDuration,
            'segment_count' => $segments->count(),
            'origin_airport_code' => $firstSegment->departure_airport_code,
            'origin_lat' => $firstSegment->departure_lat ?? $originGeo['lat'] ?? null,
            'origin_lng' => $firstSegment->departure_lng ?? $originGeo['lng'] ?? null,
            'origin_country_code' => $firstSegment->departure_country_code ?? $originGeo['country_code'] ?? null,
            'destination_airport_code' => $lastSegment->arrival_airport_code,
            'destination_lat' => $lastSegment->arrival_lat ?? $destGeo['lat'] ?? null,
            'destination_lng' => $lastSegment->arrival_lng ?? $destGeo['lng'] ?? null,
            'destination_country_code' => $lastSegment->arrival_country_code ?? $destGeo['country_code'] ?? null,
        ]);
    }

    /**
     * Get segment sequence for a leg
     */
    public function getSegmentSequence(TdAirLeg $leg): array
    {
        return $leg->segments()
            ->orderBy('departure_time')
            ->get()
            ->map(function ($segment, $index) {
                return [
                    'sequence' => $index + 1,
                    'segment_id' => $segment->segment_id,
                    'route' => $segment->route,
                    'departure_time' => $segment->departure_time,
                    'arrival_time' => $segment->arrival_time,
                    'flight' => $segment->flight_designator,
                ];
            })
            ->toArray();
    }

    /**
     * Get layover information for a leg
     */
    public function getLayovers(TdAirLeg $leg): array
    {
        $segments = $leg->segments()->orderBy('departure_time')->get();
        $layovers = [];

        for ($i = 0; $i < $segments->count() - 1; $i++) {
            $current = $segments[$i];
            $next = $segments[$i + 1];

            if ($current->arrival_time && $next->departure_time) {
                $layovers[] = [
                    'airport_code' => $current->arrival_airport_code,
                    'arrival_time' => $current->arrival_time,
                    'departure_time' => $next->departure_time,
                    'duration_minutes' => $current->arrival_time->diffInMinutes($next->departure_time),
                ];
            }
        }

        return $layovers;
    }
}
