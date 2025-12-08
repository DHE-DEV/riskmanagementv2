<?php

namespace App\Services\TravelDetail;

use App\Models\TravelDetail\TdTrip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TripRangeCalculator
{
    /**
     * Calculate the overall trip start and end dates
     */
    public function calculateForTrip(TdTrip $trip): void
    {
        $earliestStart = null;
        $latestEnd = null;

        // Check flight segments
        foreach ($trip->flightSegments as $segment) {
            if ($segment->departure_time) {
                if (!$earliestStart || $segment->departure_time->lt($earliestStart)) {
                    $earliestStart = $segment->departure_time;
                }
            }
            if ($segment->arrival_time) {
                if (!$latestEnd || $segment->arrival_time->gt($latestEnd)) {
                    $latestEnd = $segment->arrival_time;
                }
            }
        }

        // Check stays
        foreach ($trip->stays as $stay) {
            if ($stay->check_in) {
                if (!$earliestStart || $stay->check_in->lt($earliestStart)) {
                    $earliestStart = $stay->check_in;
                }
            }
            if ($stay->check_out) {
                if (!$latestEnd || $stay->check_out->gt($latestEnd)) {
                    $latestEnd = $stay->check_out;
                }
            }
        }

        // Update trip with calculated range
        $trip->update([
            'computed_start_at' => $earliestStart,
            'computed_end_at' => $latestEnd,
        ]);

        // Update trip status based on dates
        $this->updateTripStatus($trip);

        if (config('travel_detail.logging.enabled')) {
            Log::channel(config('travel_detail.logging.channel'))
                ->debug('Calculated trip range', [
                    'trip_id' => $trip->id,
                    'start' => $earliestStart?->toIso8601String(),
                    'end' => $latestEnd?->toIso8601String(),
                    'status' => $trip->status,
                ]);
        }
    }

    /**
     * Update trip status based on computed dates
     */
    protected function updateTripStatus(TdTrip $trip): void
    {
        if ($trip->status === 'cancelled') {
            return; // Don't change cancelled trips
        }

        if ($trip->computed_end_at && $trip->computed_end_at->isPast()) {
            $trip->update(['status' => 'completed']);
        } elseif ($trip->computed_start_at) {
            $trip->update(['status' => 'active']);
        }
    }

    /**
     * Get trip duration in days
     */
    public function getDurationDays(TdTrip $trip): ?int
    {
        if (!$trip->computed_start_at || !$trip->computed_end_at) {
            return null;
        }

        return $trip->computed_start_at->diffInDays($trip->computed_end_at);
    }

    /**
     * Get trip duration in hours
     */
    public function getDurationHours(TdTrip $trip): ?int
    {
        if (!$trip->computed_start_at || !$trip->computed_end_at) {
            return null;
        }

        return $trip->computed_start_at->diffInHours($trip->computed_end_at);
    }

    /**
     * Check if a trip overlaps with a given time range
     */
    public function overlapsWithRange(TdTrip $trip, Carbon $start, Carbon $end): bool
    {
        if (!$trip->computed_start_at || !$trip->computed_end_at) {
            return false;
        }

        return $trip->computed_start_at->lte($end) && $trip->computed_end_at->gte($start);
    }

    /**
     * Check if a trip is active at a given point in time
     */
    public function isActiveAt(TdTrip $trip, Carbon $dateTime): bool
    {
        if (!$trip->computed_start_at || !$trip->computed_end_at) {
            return false;
        }

        return $trip->computed_start_at->lte($dateTime) && $trip->computed_end_at->gte($dateTime);
    }
}
