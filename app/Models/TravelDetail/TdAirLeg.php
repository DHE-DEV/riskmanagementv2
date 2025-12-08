<?php

namespace App\Models\TravelDetail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TdAirLeg extends Model
{
    protected $table = 'td_air_legs';

    protected $fillable = [
        'trip_id',
        'leg_id',
        'mode',
        'leg_start_at',
        'leg_end_at',
        'total_duration_minutes',
        'segment_count',
        'origin_airport_code',
        'origin_lat',
        'origin_lng',
        'origin_country_code',
        'destination_airport_code',
        'destination_lat',
        'destination_lng',
        'destination_country_code',
    ];

    protected $casts = [
        'leg_start_at' => 'datetime',
        'leg_end_at' => 'datetime',
        'total_duration_minutes' => 'integer',
        'segment_count' => 'integer',
        'origin_lat' => 'decimal:8',
        'origin_lng' => 'decimal:8',
        'destination_lat' => 'decimal:8',
        'destination_lng' => 'decimal:8',
    ];

    /**
     * The trip this leg belongs to
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(TdTrip::class, 'trip_id');
    }

    /**
     * Flight segments in this leg
     */
    public function segments(): HasMany
    {
        return $this->hasMany(TdFlightSegment::class, 'air_leg_id')
            ->orderBy('sequence_in_leg');
    }

    /**
     * Get formatted duration (e.g., "12h 30m")
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->total_duration_minutes) {
            return null;
        }

        $hours = floor($this->total_duration_minutes / 60);
        $minutes = $this->total_duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }

    /**
     * Get route summary (e.g., "FRA → JFK")
     */
    public function getRouteSummaryAttribute(): string
    {
        $origin = $this->origin_airport_code ?? '?';
        $destination = $this->destination_airport_code ?? '?';

        return "{$origin} → {$destination}";
    }

    /**
     * Check if leg has transfers
     */
    public function hasTransfers(): bool
    {
        return $this->segment_count > 1;
    }
}
