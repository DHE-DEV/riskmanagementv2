<?php

namespace App\Models\TravelDetail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdFlightSegment extends Model
{
    protected $table = 'td_flight_segments';

    protected $fillable = [
        'air_leg_id',
        'trip_id',
        'segment_id',
        'sequence_in_leg',
        'departure_airport_code',
        'departure_lat',
        'departure_lng',
        'departure_country_code',
        'departure_time',
        'departure_terminal',
        'arrival_airport_code',
        'arrival_lat',
        'arrival_lng',
        'arrival_country_code',
        'arrival_time',
        'arrival_terminal',
        'marketing_airline_code',
        'flight_number',
        'operating_airline_code',
        'transfer_role_hint',
        'duration_minutes',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'departure_lat' => 'decimal:8',
        'departure_lng' => 'decimal:8',
        'arrival_lat' => 'decimal:8',
        'arrival_lng' => 'decimal:8',
        'sequence_in_leg' => 'integer',
        'duration_minutes' => 'integer',
    ];

    /**
     * The air leg this segment belongs to
     */
    public function airLeg(): BelongsTo
    {
        return $this->belongsTo(TdAirLeg::class, 'air_leg_id');
    }

    /**
     * The trip this segment belongs to
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(TdTrip::class, 'trip_id');
    }

    /**
     * Get flight designator (e.g., "LH 123")
     */
    public function getFlightDesignatorAttribute(): ?string
    {
        if (!$this->marketing_airline_code || !$this->flight_number) {
            return null;
        }

        return "{$this->marketing_airline_code} {$this->flight_number}";
    }

    /**
     * Get route (e.g., "FRA → JFK")
     */
    public function getRouteAttribute(): string
    {
        return "{$this->departure_airport_code} → {$this->arrival_airport_code}";
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration_minutes) {
            return null;
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }

    /**
     * Check if this is a transfer-in segment
     */
    public function isTransferIn(): bool
    {
        return $this->transfer_role_hint === 'in';
    }

    /**
     * Check if this is a transfer-out segment
     */
    public function isTransferOut(): bool
    {
        return $this->transfer_role_hint === 'out';
    }

    /**
     * Calculate duration from departure/arrival times
     */
    public function calculateDuration(): ?int
    {
        if (!$this->departure_time || !$this->arrival_time) {
            return null;
        }

        return $this->departure_time->diffInMinutes($this->arrival_time);
    }
}
