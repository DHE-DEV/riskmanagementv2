<?php

namespace App\Models\TravelDetail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class TdTrip extends Model
{
    use SoftDeletes;

    protected $table = 'td_trips';

    protected $fillable = [
        'provider_id',
        'external_trip_id',
        'provider_name',
        'provider_sent_at',
        'booking_reference',
        'schema_version',
        'computed_start_at',
        'computed_end_at',
        'countries_visited',
        'status',
        'is_archived',
        'pds_share_url',
        'pds_tid',
        'pds_share_created_at',
        'raw_payload',
    ];

    protected $casts = [
        'provider_sent_at' => 'datetime',
        'computed_start_at' => 'datetime',
        'computed_end_at' => 'datetime',
        'pds_share_created_at' => 'datetime',
        'countries_visited' => 'array',
        'raw_payload' => 'array',
        'is_archived' => 'boolean',
    ];

    /**
     * Air legs for this trip (sorted by start date)
     */
    public function airLegs(): HasMany
    {
        return $this->hasMany(TdAirLeg::class, 'trip_id')->orderBy('leg_start_at');
    }

    /**
     * Flight segments for this trip (through air legs)
     */
    public function flightSegments(): HasMany
    {
        return $this->hasMany(TdFlightSegment::class, 'trip_id');
    }

    /**
     * Stays (hotels, apartments, etc.) for this trip (sorted by check-in date)
     */
    public function stays(): HasMany
    {
        return $this->hasMany(TdStay::class, 'trip_id')->orderBy('check_in');
    }

    /**
     * Travellers on this trip
     */
    public function travellers(): HasMany
    {
        return $this->hasMany(TdTraveller::class, 'trip_id');
    }

    /**
     * Transfers between segments/stays
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(TdTransfer::class, 'trip_id');
    }

    /**
     * Denormalized location timeline for geo queries
     */
    public function tripLocations(): HasMany
    {
        return $this->hasMany(TdTripLocation::class, 'trip_id');
    }

    /**
     * PDS share links for this trip
     */
    public function pdsShareLinks(): HasMany
    {
        return $this->hasMany(TdPdsShareLink::class, 'trip_id');
    }

    /**
     * Scope: Active trips only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Completed trips only
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Currently traveling (trip in progress)
     */
    public function scopeCurrentlyTraveling(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('computed_start_at', '<=', now())
            ->where('computed_end_at', '>=', now());
    }

    /**
     * Scope: Upcoming trips
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('computed_start_at', '>', now());
    }

    /**
     * Scope: Archivable trips (completed and past archival threshold)
     */
    public function scopeArchivable(Builder $query, int $daysAfterCompletion = 30): Builder
    {
        return $query->where('status', 'completed')
            ->where('is_archived', false)
            ->where('computed_end_at', '<', now()->subDays($daysAfterCompletion));
    }

    /**
     * Scope: Not archived
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: By provider
     */
    public function scopeByProvider(Builder $query, string $providerId): Builder
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Check if trip is currently in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'active'
            && $this->computed_start_at
            && $this->computed_end_at
            && $this->computed_start_at->isPast()
            && $this->computed_end_at->isFuture();
    }

    /**
     * Check if trip is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->status === 'active'
            && $this->computed_start_at
            && $this->computed_start_at->isFuture();
    }

    /**
     * Check if trip has ended
     */
    public function hasEnded(): bool
    {
        return $this->computed_end_at && $this->computed_end_at->isPast();
    }

    /**
     * Get trip duration in days
     */
    public function getDurationDaysAttribute(): ?int
    {
        if (!$this->computed_start_at || !$this->computed_end_at) {
            return null;
        }

        return $this->computed_start_at->diffInDays($this->computed_end_at);
    }

    /**
     * Get total segment count
     */
    public function getTotalSegmentsAttribute(): int
    {
        return $this->flightSegments()->count();
    }

    /**
     * Get total stay count
     */
    public function getTotalStaysAttribute(): int
    {
        return $this->stays()->count();
    }

    /**
     * Get chronological timeline of all locations
     * Returns a sorted collection of location events (flights combined into single rows)
     */
    public function getChronologicalTimeline(): \Illuminate\Support\Collection
    {
        // Preload flight segments
        $flightSegments = $this->flightSegments()
            ->orderBy('departure_time')
            ->get();

        // Get all flight segment IDs
        $flightSegmentIds = $flightSegments->pluck('id')->toArray();

        // Preload transfers - only flight-to-flight transfers (not flight-to-hotel)
        // Key by from_segment_id (the arriving flight)
        $transfers = $this->transfers()
            ->whereIn('to_segment_id', $flightSegmentIds)
            ->get()
            ->groupBy('from_segment_id');

        // Preload stays
        $stays = $this->stays()
            ->orderBy('check_in')
            ->get();

        // Collect all airport codes and load their names
        $airportCodes = $flightSegments->pluck('departure_airport_code')
            ->merge($flightSegments->pluck('arrival_airport_code'))
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        $airportNames = \App\Models\AirportCode::whereIn('iata_code', $airportCodes)
            ->pluck('name', 'iata_code')
            ->toArray();

        $timeline = collect();

        // Add flights as single rows (departure + arrival combined)
        foreach ($flightSegments as $segment) {
            $transferInfo = null;
            if (isset($transfers[$segment->id])) {
                $transfer = $transfers[$segment->id]->first();
                if ($transfer && $transfer->connection_time_minutes) {
                    $transferInfo = [
                        'connection_time' => $transfer->connection_time_minutes,
                        'formatted_time' => $this->formatDuration($transfer->connection_time_minutes),
                        'location' => $transfer->transfer_location_code,
                    ];
                }
            }

            $timeline->push([
                'type' => 'flight',
                'type_label' => 'Flug',
                'departure_code' => $segment->departure_airport_code,
                'arrival_code' => $segment->arrival_airport_code,
                'departure_name' => $airportNames[$segment->departure_airport_code] ?? null,
                'arrival_name' => $airportNames[$segment->arrival_airport_code] ?? null,
                'departure_time' => $segment->departure_time,
                'arrival_time' => $segment->arrival_time,
                'departure_country' => $segment->departure_country_code,
                'arrival_country' => $segment->arrival_country_code,
                'transfer_info' => $transferInfo,
                'duration_minutes' => $segment->duration_minutes,
                'formatted_duration' => $this->formatDuration($segment->duration_minutes),
                'sort_time' => $segment->departure_time,
            ]);
        }

        // Add stays
        foreach ($stays as $stay) {
            // Calculate nights by comparing dates only (without time)
            $nights = null;
            if ($stay->check_in && $stay->check_out) {
                $nights = $stay->check_in->startOfDay()->diffInDays($stay->check_out->startOfDay());
            }

            $timeline->push([
                'type' => 'stay',
                'type_label' => 'Hotel',
                'location_name' => $stay->location_name,
                'location_code' => null,
                'country_code' => $stay->country_code,
                'check_in' => $stay->check_in,
                'check_out' => $stay->check_out,
                'transfer_info' => null,
                'nights' => $nights,
                'formatted_duration' => $nights !== null ? ($nights == 1 ? '1 Nacht' : "{$nights} Nächte") : '-',
                'sort_time' => $stay->check_in,
            ]);
        }

        // Sort by time
        return $timeline->sortBy('sort_time')->values();
    }

    /**
     * Format duration in minutes to human readable string
     */
    protected function formatDuration(?int $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }

        if ($minutes < 60) {
            return "{$minutes} Min";
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours < 24) {
            return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($remainingHours > 0) {
            return "{$days} Tag(e), {$remainingHours}h";
        }

        return "{$days} Tag(e)";
    }
}
