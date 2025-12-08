<?php

namespace App\Models\TravelDetail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TdStay extends Model
{
    protected $table = 'td_stays';

    protected $fillable = [
        'trip_id',
        'stay_id',
        'stay_type',
        'location_name',
        'giata_id',
        'lat',
        'lng',
        'country_code',
        'address_json',
        'check_in',
        'check_out',
        'duration_nights',
        'details_json',
        'raw_meta',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'giata_id' => 'integer',
        'duration_nights' => 'integer',
        'address_json' => 'array',
        'details_json' => 'array',
        'raw_meta' => 'array',
    ];

    /**
     * The trip this stay belongs to
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(TdTrip::class, 'trip_id');
    }

    /**
     * Scope: By GIATA ID
     */
    public function scopeByGiataId(Builder $query, int $giataId): Builder
    {
        return $query->where('giata_id', $giataId);
    }

    /**
     * Scope: By country
     */
    public function scopeByCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope: Currently staying (check_in <= now <= check_out)
     */
    public function scopeCurrentlyStaying(Builder $query): Builder
    {
        return $query->where('check_in', '<=', now())
            ->where('check_out', '>=', now());
    }

    /**
     * Calculate duration in nights
     */
    public function calculateDurationNights(): ?int
    {
        if (!$this->check_in || !$this->check_out) {
            return null;
        }

        return $this->check_in->diffInDays($this->check_out);
    }

    /**
     * Check if stay is currently active
     */
    public function isActive(): bool
    {
        return $this->check_in
            && $this->check_out
            && $this->check_in->isPast()
            && $this->check_out->isFuture();
    }

    /**
     * Check if stay has coordinates
     */
    public function hasCoordinates(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    /**
     * Get stay type label
     */
    public function getStayTypeLabelAttribute(): string
    {
        return match ($this->stay_type) {
            'hotel' => 'Hotel',
            'apartment' => 'Apartment',
            'resort' => 'Resort',
            'hostel' => 'Hostel',
            'other' => 'Sonstige',
            default => ucfirst($this->stay_type ?? 'Unknown'),
        };
    }
}
