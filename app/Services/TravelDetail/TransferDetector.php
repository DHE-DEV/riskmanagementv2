<?php

namespace App\Services\TravelDetail;

use App\Models\TravelDetail\TdTrip;
use App\Models\TravelDetail\TdTransfer;
use App\Models\TravelDetail\TdFlightSegment;
use Illuminate\Support\Facades\Log;

class TransferDetector
{
    private int $minConnectionMinutes;
    private int $maxConnectionMinutes;
    private int $tightConnectionMinutes;

    public function __construct(
        private CountryDeriver $countryDeriver
    ) {
        $this->minConnectionMinutes = config('travel_detail.transfers.min_connection_minutes', 30);
        $this->maxConnectionMinutes = config('travel_detail.transfers.max_connection_minutes', 2880);
        $this->tightConnectionMinutes = config('travel_detail.transfers.tight_connection_minutes', 60);
    }

    /**
     * Detect all transfers within a trip
     */
    public function detectForTrip(TdTrip $trip): void
    {
        // Clear existing transfers for this trip
        $trip->transfers()->delete();

        $transfers = [];

        // Detect transfers within air legs
        foreach ($trip->airLegs as $leg) {
            $legTransfers = $this->detectTransfersInLeg($trip, $leg->segments()->orderBy('departure_time')->get());
            $transfers = array_merge($transfers, $legTransfers);
        }

        // Detect transfers between different elements (stay to flight, flight to stay, etc.)
        $interElementTransfers = $this->detectInterElementTransfers($trip);
        $transfers = array_merge($transfers, $interElementTransfers);

        // Bulk insert transfers
        if (!empty($transfers)) {
            TdTransfer::insert($transfers);
        }

        if (config('travel_detail.logging.enabled')) {
            Log::channel(config('travel_detail.logging.channel'))
                ->debug('Detected transfers', [
                    'trip_id' => $trip->id,
                    'transfer_count' => count($transfers),
                ]);
        }
    }

    /**
     * Detect transfers between segments in a leg
     */
    protected function detectTransfersInLeg(TdTrip $trip, $segments): array
    {
        $transfers = [];

        for ($i = 0; $i < $segments->count() - 1; $i++) {
            /** @var TdFlightSegment $current */
            $current = $segments[$i];
            /** @var TdFlightSegment $next */
            $next = $segments[$i + 1];

            // Check if this is a valid transfer
            if (!$this->isValidTransfer($current, $next)) {
                continue;
            }

            $connectionMinutes = $current->arrival_time->diffInMinutes($next->departure_time);

            // Get geo data for transfer location
            $geoData = $this->countryDeriver->getGeoDataForIata($current->arrival_airport_code);

            $transfers[] = [
                'trip_id' => $trip->id,
                'from_segment_type' => 'flight',
                'from_segment_id' => $current->id,
                'to_segment_type' => 'flight',
                'to_segment_id' => $next->id,
                'transfer_location_code' => $current->arrival_airport_code,
                'transfer_lat' => $current->arrival_lat ?? $geoData['lat'] ?? null,
                'transfer_lng' => $current->arrival_lng ?? $geoData['lng'] ?? null,
                'transfer_country_code' => $current->arrival_country_code ?? $geoData['country_code'] ?? null,
                'connection_time_minutes' => $connectionMinutes,
                'from_arrival_time' => $current->arrival_time,
                'to_departure_time' => $next->departure_time,
                'transfer_type' => 'airport',
                'is_tight_connection' => $connectionMinutes <= $this->tightConnectionMinutes,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Update segment transfer role hints
            $current->update(['transfer_role_hint' => 'out']);
            $next->update(['transfer_role_hint' => 'in']);
        }

        return $transfers;
    }

    /**
     * Check if two segments form a valid transfer
     */
    protected function isValidTransfer(TdFlightSegment $from, TdFlightSegment $to): bool
    {
        // Must have valid times
        if (!$from->arrival_time || !$to->departure_time) {
            return false;
        }

        // Arrival airport must match departure airport
        if ($from->arrival_airport_code !== $to->departure_airport_code) {
            return false;
        }

        // Departure must be after arrival
        if ($to->departure_time->lt($from->arrival_time)) {
            return false;
        }

        // Check connection time is within valid range
        $connectionMinutes = $from->arrival_time->diffInMinutes($to->departure_time);

        if ($connectionMinutes < $this->minConnectionMinutes) {
            return false; // Too short, not a realistic transfer
        }

        if ($connectionMinutes > $this->maxConnectionMinutes) {
            return false; // Too long, probably separate journeys
        }

        return true;
    }

    /**
     * Detect transfers between different itinerary elements
     * (e.g., flight to hotel, hotel to flight)
     */
    protected function detectInterElementTransfers(TdTrip $trip): array
    {
        $transfers = [];

        // Get all segments and stays sorted by time
        $segments = $trip->flightSegments()->orderBy('arrival_time')->get();
        $stays = $trip->stays()->orderBy('check_in')->get();

        // Check flights ending near stay start
        foreach ($segments as $segment) {
            foreach ($stays as $stay) {
                // Flight arrival to hotel check-in
                if ($segment->arrival_time && $stay->check_in) {
                    $gapMinutes = $segment->arrival_time->diffInMinutes($stay->check_in);

                    // If arrival is before check-in and within reasonable time (0-24 hours)
                    if ($segment->arrival_time->lte($stay->check_in) && $gapMinutes <= 1440) {
                        // Check if same location (could be refined with geo proximity)
                        $transfers[] = [
                            'trip_id' => $trip->id,
                            'from_segment_type' => 'flight',
                            'from_segment_id' => $segment->id,
                            'to_segment_type' => 'stay',
                            'to_segment_id' => $stay->id,
                            'transfer_location_code' => $segment->arrival_airport_code,
                            'transfer_lat' => $segment->arrival_lat,
                            'transfer_lng' => $segment->arrival_lng,
                            'transfer_country_code' => $segment->arrival_country_code,
                            'connection_time_minutes' => $gapMinutes,
                            'from_arrival_time' => $segment->arrival_time,
                            'to_departure_time' => $stay->check_in,
                            'transfer_type' => 'city',
                            'is_tight_connection' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        // Check stays ending near flight start
        foreach ($stays as $stay) {
            foreach ($segments as $segment) {
                // Hotel check-out to flight departure
                if ($stay->check_out && $segment->departure_time) {
                    $gapMinutes = $stay->check_out->diffInMinutes($segment->departure_time);

                    // If check-out is before departure and within reasonable time
                    if ($stay->check_out->lte($segment->departure_time) && $gapMinutes <= 1440) {
                        $transfers[] = [
                            'trip_id' => $trip->id,
                            'from_segment_type' => 'stay',
                            'from_segment_id' => $stay->id,
                            'to_segment_type' => 'flight',
                            'to_segment_id' => $segment->id,
                            'transfer_location_code' => $segment->departure_airport_code,
                            'transfer_lat' => $stay->lat,
                            'transfer_lng' => $stay->lng,
                            'transfer_country_code' => $stay->country_code,
                            'connection_time_minutes' => $gapMinutes,
                            'from_arrival_time' => $stay->check_out,
                            'to_departure_time' => $segment->departure_time,
                            'transfer_type' => 'city',
                            'is_tight_connection' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        return $transfers;
    }
}
