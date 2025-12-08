<?php

namespace App\Models\TravelDetail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdTraveller extends Model
{
    protected $table = 'td_travellers';

    protected $fillable = [
        'trip_id',
        'external_traveller_id',
        'traveller_type',
        'first_name',
        'last_name',
        'salutation',
        'date_of_birth',
        'nationality',
        'email',
        'phone',
        'passport_country',
        'meta',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'meta' => 'array',
    ];

    /**
     * Get the trip this traveller belongs to
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(TdTrip::class, 'trip_id');
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): ?string
    {
        if (!$this->first_name && !$this->last_name) {
            return null;
        }

        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get formatted traveller type
     */
    public function getTravellerTypeLabelAttribute(): string
    {
        return match ($this->traveller_type) {
            'adult' => 'Erwachsener',
            'child' => 'Kind',
            'infant' => 'Kleinkind',
            default => $this->traveller_type,
        };
    }

    /**
     * Scope: Filter by nationality
     */
    public function scopeWithNationality($query, string|array $nationality)
    {
        if (is_array($nationality)) {
            return $query->whereIn('nationality', $nationality);
        }
        return $query->where('nationality', $nationality);
    }

    /**
     * Scope: Filter by passport country
     */
    public function scopeWithPassport($query, string|array $passportCountry)
    {
        if (is_array($passportCountry)) {
            return $query->whereIn('passport_country', $passportCountry);
        }
        return $query->where('passport_country', $passportCountry);
    }
}
