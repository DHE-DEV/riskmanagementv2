<?php

namespace App\Models\Folder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FolderFlightService extends BaseCustomerModel
{
    protected $table = 'folder_flight_services';

    protected $fillable = [
        'itinerary_id',
        'folder_id',
        'customer_id',
        'booking_reference',
        'service_type',
        'departure_time',
        'arrival_time',
        'origin_airport_code',
        'destination_airport_code',
        'origin_country_code',
        'destination_country_code',
        'total_amount',
        'currency',
        'airline_pnr',
        'ticket_numbers',
        'status',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'total_amount' => 'decimal:2',
        'ticket_numbers' => 'array',
    ];

    /**
     * Get the itinerary that owns the flight service.
     */
    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(FolderItinerary::class, 'itinerary_id');
    }

    /**
     * Get the folder that owns the flight service.
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
     * Get the flight segments.
     */
    public function segments(): HasMany
    {
        return $this->hasMany(FolderFlightSegment::class, 'flight_service_id');
    }

    /**
     * Calculate total duration in minutes.
     */
    public function getTotalDurationAttribute(): ?int
    {
        if (! $this->departure_time || ! $this->arrival_time) {
            return null;
        }

        return $this->departure_time->diffInMinutes($this->arrival_time);
    }
}
