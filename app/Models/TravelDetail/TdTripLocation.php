<?php

namespace App\Models\TravelDetail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TdTripLocation extends Model
{
    protected $table = 'td_trip_locations';

    public $timestamps = false;

    protected $fillable = [
        'trip_id',
        'location_type',
        'source_type',
        'source_id',
        'lat',
        'lng',
        'location_code',
        'location_name',
        'country_code',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * The trip this location belongs to
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(TdTrip::class, 'trip_id');
    }

    /**
     * Get the source record (flight segment, stay, or transfer)
     */
    public function getSource(): ?Model
    {
        return match ($this->source_type) {
            'flight_segment' => TdFlightSegment::find($this->source_id),
            'stay' => TdStay::find($this->source_id),
            'transfer' => TdTransfer::find($this->source_id),
            default => null,
        };
    }

    /**
     * Scope: Find locations within radius of a point
     * Uses ST_Distance_Sphere for accurate distance calculation
     */
    public function scopeWithinRadius(Builder $query, float $lat, float $lng, float $radiusKm): Builder
    {
        $radiusMeters = $radiusKm * 1000;

        // Check if using MySQL with spatial support
        if (DB::connection()->getDriverName() === 'mysql') {
            return $query->whereRaw(
                'ST_Distance_Sphere(point, ST_SRID(POINT(?, ?), 4326)) <= ?',
                [$lng, $lat, $radiusMeters]
            );
        }

        // Fallback: Haversine formula approximation for other databases
        return $query->whereRaw(
            '(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) <= ?',
            [$lat, $lng, $lat, $radiusKm]
        );
    }

    /**
     * Scope: Add distance calculation
     */
    public function scopeWithDistance(Builder $query, float $lat, float $lng): Builder
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            return $query->selectRaw(
                '*, ST_Distance_Sphere(point, ST_SRID(POINT(?, ?), 4326)) / 1000 as distance_km',
                [$lng, $lat]
            );
        }

        // Fallback: Haversine formula
        return $query->selectRaw(
            '*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) as distance_km',
            [$lat, $lng, $lat]
        );
    }

    /**
     * Scope: Active during time range
     */
    public function scopeActiveDuring(Builder $query, $startTime, $endTime): Builder
    {
        return $query->where('start_time', '<=', $endTime)
            ->where('end_time', '>=', $startTime);
    }

    /**
     * Scope: By country
     */
    public function scopeByCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope: By location type
     */
    public function scopeByLocationType(Builder $query, string $type): Builder
    {
        return $query->where('location_type', $type);
    }

    /**
     * Get location type label
     */
    public function getLocationTypeLabelAttribute(): string
    {
        return match ($this->location_type) {
            'departure' => 'Abflug',
            'arrival' => 'Ankunft',
            'stay' => 'Aufenthalt',
            'transfer' => 'Umstieg',
            default => ucfirst($this->location_type ?? 'Unknown'),
        };
    }

    /**
     * Get duration at location in minutes
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Boot method to set POINT column when creating
     */
    protected static function booted(): void
    {
        static::creating(function (TdTripLocation $location) {
            // The POINT column will be set via raw SQL after insert
            // because Laravel doesn't natively support POINT types
        });

        static::created(function (TdTripLocation $location) {
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement(
                    'UPDATE td_trip_locations SET point = ST_SRID(POINT(?, ?), 4326) WHERE id = ?',
                    [$location->lng, $location->lat, $location->id]
                );
            }
        });
    }
}
