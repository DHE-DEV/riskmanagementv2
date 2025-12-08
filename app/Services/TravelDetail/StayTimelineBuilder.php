<?php

namespace App\Services\TravelDetail;

use App\Models\TravelDetail\TdTrip;
use App\Models\TravelDetail\TdStay;
use Illuminate\Support\Facades\Log;

class StayTimelineBuilder
{
    /**
     * Build/update stay durations for a trip
     */
    public function buildForTrip(TdTrip $trip): void
    {
        foreach ($trip->stays as $stay) {
            $this->calculateStayDuration($stay);
        }

        if (config('travel_detail.logging.enabled')) {
            Log::channel(config('travel_detail.logging.channel'))
                ->debug('Built stay timeline', [
                    'trip_id' => $trip->id,
                    'stay_count' => $trip->stays->count(),
                ]);
        }
    }

    /**
     * Calculate and update duration for a single stay
     */
    public function calculateStayDuration(TdStay $stay): void
    {
        if (!$stay->check_in || !$stay->check_out) {
            return;
        }

        $durationNights = $stay->check_in->diffInDays($stay->check_out);

        $stay->update([
            'duration_nights' => $durationNights,
        ]);
    }

    /**
     * Get stay timeline for a trip
     */
    public function getStayTimeline(TdTrip $trip): array
    {
        return $trip->stays()
            ->orderBy('check_in')
            ->get()
            ->map(function ($stay) {
                return [
                    'stay_id' => $stay->stay_id,
                    'type' => $stay->stay_type,
                    'location_name' => $stay->location_name,
                    'country_code' => $stay->country_code,
                    'check_in' => $stay->check_in,
                    'check_out' => $stay->check_out,
                    'duration_nights' => $stay->duration_nights,
                    'coordinates' => $stay->hasCoordinates() ? [
                        'lat' => $stay->lat,
                        'lng' => $stay->lng,
                    ] : null,
                ];
            })
            ->toArray();
    }

    /**
     * Get total nights for a trip
     */
    public function getTotalNights(TdTrip $trip): int
    {
        return $trip->stays()->sum('duration_nights') ?? 0;
    }

    /**
     * Get stays by country for a trip
     */
    public function getStaysByCountry(TdTrip $trip): array
    {
        return $trip->stays()
            ->selectRaw('country_code, COUNT(*) as stay_count, SUM(duration_nights) as total_nights')
            ->groupBy('country_code')
            ->get()
            ->mapWithKeys(function ($row) {
                return [$row->country_code => [
                    'stay_count' => $row->stay_count,
                    'total_nights' => $row->total_nights,
                ]];
            })
            ->toArray();
    }

    /**
     * Check for overlapping stays
     */
    public function findOverlappingStays(TdTrip $trip): array
    {
        $stays = $trip->stays()->orderBy('check_in')->get();
        $overlaps = [];

        for ($i = 0; $i < $stays->count(); $i++) {
            for ($j = $i + 1; $j < $stays->count(); $j++) {
                $stayA = $stays[$i];
                $stayB = $stays[$j];

                // Check if stays overlap
                if ($stayA->check_in && $stayA->check_out &&
                    $stayB->check_in && $stayB->check_out) {
                    if ($stayA->check_in->lt($stayB->check_out) &&
                        $stayB->check_in->lt($stayA->check_out)) {
                        $overlaps[] = [
                            'stay_a' => $stayA->stay_id,
                            'stay_b' => $stayB->stay_id,
                            'overlap_start' => max($stayA->check_in, $stayB->check_in),
                            'overlap_end' => min($stayA->check_out, $stayB->check_out),
                        ];
                    }
                }
            }
        }

        return $overlaps;
    }
}
