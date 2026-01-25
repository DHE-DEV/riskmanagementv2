<?php

namespace App\Models\Folder;

use App\Models\AirportCode;
use App\Models\Country;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderFlightSegment extends BaseCustomerModel
{
    protected $table = 'folder_flight_segments';

    protected $fillable = [
        'flight_service_id',
        'folder_id',
        'customer_id',
        'segment_number',
        'departure_airport_code',
        'departure_airport_id',
        'departure_country_id',
        'departure_lat',
        'departure_lng',
        'departure_country_code',
        'departure_time',
        'departure_terminal',
        'arrival_airport_code',
        'arrival_airport_id',
        'arrival_country_id',
        'arrival_lat',
        'arrival_lng',
        'arrival_country_code',
        'arrival_time',
        'arrival_terminal',
        'airline_code',
        'flight_number',
        'aircraft_type',
        'duration_minutes',
        'booking_class',
        'cabin_class',
    ];

    protected $casts = [
        'departure_lat' => 'decimal:8',
        'departure_lng' => 'decimal:8',
        'arrival_lat' => 'decimal:8',
        'arrival_lng' => 'decimal:8',
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'segment_number' => 'integer',
        'duration_minutes' => 'integer',
    ];

    /**
     * Get the flight service that owns the segment.
     */
    public function flightService(): BelongsTo
    {
        return $this->belongsTo(FolderFlightService::class, 'flight_service_id');
    }

    /**
     * Get the folder that owns the segment.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the departure airport.
     */
    public function departureAirport(): BelongsTo
    {
        return $this->belongsTo(AirportCode::class, 'departure_airport_id');
    }

    /**
     * Get the arrival airport.
     */
    public function arrivalAirport(): BelongsTo
    {
        return $this->belongsTo(AirportCode::class, 'arrival_airport_id');
    }

    /**
     * Get the departure country.
     */
    public function departureCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'departure_country_id');
    }

    /**
     * Get the arrival country.
     */
    public function arrivalCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'arrival_country_id');
    }

    /**
     * Get flight identifier (e.g., "LH401").
     */
    public function getFlightIdentifierAttribute(): ?string
    {
        if (! $this->airline_code || ! $this->flight_number) {
            return null;
        }

        return $this->airline_code.$this->flight_number;
    }

    /**
     * Calculate duration if not set.
     */
    public function calculateDuration(): void
    {
        if ($this->departure_time && $this->arrival_time) {
            $this->duration_minutes = $this->departure_time->diffInMinutes($this->arrival_time);
            $this->save();
        }
    }
}
