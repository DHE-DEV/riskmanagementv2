<?php

namespace App\Models\TravelDetail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdTransfer extends Model
{
    protected $table = 'td_transfers';

    protected $fillable = [
        'trip_id',
        'from_segment_type',
        'from_segment_id',
        'to_segment_type',
        'to_segment_id',
        'transfer_location_code',
        'transfer_lat',
        'transfer_lng',
        'transfer_country_code',
        'connection_time_minutes',
        'from_arrival_time',
        'to_departure_time',
        'transfer_type',
        'is_tight_connection',
    ];

    protected $casts = [
        'from_arrival_time' => 'datetime',
        'to_departure_time' => 'datetime',
        'transfer_lat' => 'decimal:8',
        'transfer_lng' => 'decimal:8',
        'connection_time_minutes' => 'integer',
        'is_tight_connection' => 'boolean',
    ];

    /**
     * The trip this transfer belongs to
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(TdTrip::class, 'trip_id');
    }

    /**
     * Get the "from" segment (flight or stay)
     */
    public function getFromSegment(): ?Model
    {
        return match ($this->from_segment_type) {
            'flight' => TdFlightSegment::find($this->from_segment_id),
            'stay' => TdStay::find($this->from_segment_id),
            default => null,
        };
    }

    /**
     * Get the "to" segment (flight or stay)
     */
    public function getToSegment(): ?Model
    {
        return match ($this->to_segment_type) {
            'flight' => TdFlightSegment::find($this->to_segment_id),
            'stay' => TdStay::find($this->to_segment_id),
            default => null,
        };
    }

    /**
     * Get formatted connection time
     */
    public function getFormattedConnectionTimeAttribute(): ?string
    {
        if (!$this->connection_time_minutes) {
            return null;
        }

        $hours = floor($this->connection_time_minutes / 60);
        $minutes = $this->connection_time_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }

    /**
     * Get transfer type label
     */
    public function getTransferTypeLabelAttribute(): string
    {
        return match ($this->transfer_type) {
            'airport' => 'Flughafen-Umstieg',
            'city' => 'Stadt-Transfer',
            'same_location' => 'Gleicher Ort',
            default => ucfirst($this->transfer_type ?? 'Unknown'),
        };
    }

    /**
     * Check if this is an airport transfer
     */
    public function isAirportTransfer(): bool
    {
        return $this->transfer_type === 'airport';
    }

    /**
     * Calculate connection time from arrival/departure times
     */
    public function calculateConnectionTime(): ?int
    {
        if (!$this->from_arrival_time || !$this->to_departure_time) {
            return null;
        }

        return $this->from_arrival_time->diffInMinutes($this->to_departure_time);
    }
}
