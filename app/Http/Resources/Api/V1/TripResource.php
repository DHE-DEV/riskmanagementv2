<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'external_trip_id' => $this->external_trip_id,
            'booking_reference' => $this->booking_reference,
            'schema_version' => $this->schema_version,

            // Computed dates
            'computed_start_at' => $this->computed_start_at?->toIso8601String(),
            'computed_end_at' => $this->computed_end_at?->toIso8601String(),
            'duration_days' => $this->duration_days,

            // Countries
            'countries_visited' => $this->countries_visited,

            // Status
            'status' => $this->status,
            'is_in_progress' => $this->isInProgress(),
            'is_upcoming' => $this->isUpcoming(),

            // PDS Share Link
            'pds_share_url' => $this->pds_share_url,
            'pds_tid' => $this->pds_tid,

            // Summary
            'summary' => [
                'total_legs' => $this->whenLoaded('airLegs', fn() => $this->airLegs->count(), 0),
                'total_segments' => $this->whenLoaded('flightSegments', fn() => $this->flightSegments->count(), $this->flightSegments()->count()),
                'total_stays' => $this->whenLoaded('stays', fn() => $this->stays->count(), $this->stays()->count()),
                'total_transfers' => $this->whenLoaded('transfers', fn() => $this->transfers->count(), 0),
            ],

            // Relations (when loaded)
            'air_legs' => $this->when(
                $this->relationLoaded('airLegs'),
                fn() => $this->airLegs->map(fn($leg) => [
                    'id' => $leg->id,
                    'leg_id' => $leg->leg_id,
                    'mode' => $leg->mode,
                    'route' => $leg->route_summary,
                    'start_at' => $leg->leg_start_at?->toIso8601String(),
                    'end_at' => $leg->leg_end_at?->toIso8601String(),
                    'duration' => $leg->formatted_duration,
                    'segment_count' => $leg->segment_count,
                    'origin' => [
                        'airport_code' => $leg->origin_airport_code,
                        'country_code' => $leg->origin_country_code,
                        'coordinates' => $leg->origin_lat ? [
                            'lat' => (float) $leg->origin_lat,
                            'lng' => (float) $leg->origin_lng,
                        ] : null,
                    ],
                    'destination' => [
                        'airport_code' => $leg->destination_airport_code,
                        'country_code' => $leg->destination_country_code,
                        'coordinates' => $leg->destination_lat ? [
                            'lat' => (float) $leg->destination_lat,
                            'lng' => (float) $leg->destination_lng,
                        ] : null,
                    ],
                    'segments' => $leg->relationLoaded('segments') ? $leg->segments->map(fn($seg) => [
                        'segment_id' => $seg->segment_id,
                        'flight' => $seg->flight_designator,
                        'route' => $seg->route,
                        'departure' => [
                            'airport_code' => $seg->departure_airport_code,
                            'time' => $seg->departure_time?->toIso8601String(),
                            'terminal' => $seg->departure_terminal,
                        ],
                        'arrival' => [
                            'airport_code' => $seg->arrival_airport_code,
                            'time' => $seg->arrival_time?->toIso8601String(),
                            'terminal' => $seg->arrival_terminal,
                        ],
                        'duration' => $seg->formatted_duration,
                    ]) : null,
                ])
            ),

            'stays' => $this->when(
                $this->relationLoaded('stays'),
                fn() => $this->stays->map(fn($stay) => [
                    'id' => $stay->id,
                    'stay_id' => $stay->stay_id,
                    'type' => $stay->stay_type,
                    'location_name' => $stay->location_name,
                    'country_code' => $stay->country_code,
                    'giata_id' => $stay->giata_id,
                    'coordinates' => $stay->hasCoordinates() ? [
                        'lat' => (float) $stay->lat,
                        'lng' => (float) $stay->lng,
                    ] : null,
                    'check_in' => $stay->check_in?->toIso8601String(),
                    'check_out' => $stay->check_out?->toIso8601String(),
                    'duration_nights' => $stay->duration_nights,
                ])
            ),

            'transfers' => $this->when(
                $this->relationLoaded('transfers'),
                fn() => $this->transfers->map(fn($transfer) => [
                    'id' => $transfer->id,
                    'type' => $transfer->transfer_type,
                    'location_code' => $transfer->transfer_location_code,
                    'country_code' => $transfer->transfer_country_code,
                    'connection_time' => $transfer->formatted_connection_time,
                    'is_tight' => $transfer->is_tight_connection,
                    'from_arrival' => $transfer->from_arrival_time?->toIso8601String(),
                    'to_departure' => $transfer->to_departure_time?->toIso8601String(),
                ])
            ),

            'travellers' => $this->when(
                $this->relationLoaded('travellers'),
                fn() => $this->travellers->map(fn($traveller) => [
                    'id' => $traveller->id,
                    'external_traveller_id' => $traveller->external_traveller_id,
                    'first_name' => $traveller->first_name,
                    'last_name' => $traveller->last_name,
                    'full_name' => trim(($traveller->first_name ?? '') . ' ' . ($traveller->last_name ?? '')),
                    'nationality' => $traveller->nationality,
                    // Auto-generated travellers (from simple format) show "-" for type
                    'type' => str_starts_with($traveller->external_traveller_id ?? '', 'AUTO-')
                        ? '-'
                        : ($traveller->traveller_type ?? '-'),
                ])
            ),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
