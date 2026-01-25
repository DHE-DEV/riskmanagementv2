<?php

namespace App\Models\Folder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FolderItinerary extends BaseCustomerModel
{
    use SoftDeletes;

    protected $table = 'folder_itineraries';

    protected $fillable = [
        'folder_id',
        'customer_id',
        'booking_reference',
        'itinerary_name',
        'start_date',
        'end_date',
        'status',
        'total_amount',
        'currency',
        'payment_status',
        'provider_name',
        'provider_reference',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the folder that owns the itinerary.
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
     * Get the participants assigned to this itinerary.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            FolderParticipant::class,
            'folder_itinerary_participant',
            'itinerary_id',
            'participant_id'
        )->withTimestamps();
    }

    /**
     * Get the flight services for this itinerary.
     */
    public function flightServices(): HasMany
    {
        return $this->hasMany(FolderFlightService::class, 'itinerary_id');
    }

    /**
     * Get the hotel services for this itinerary.
     */
    public function hotelServices(): HasMany
    {
        return $this->hasMany(FolderHotelService::class, 'itinerary_id');
    }

    /**
     * Get the ship services for this itinerary.
     */
    public function shipServices(): HasMany
    {
        return $this->hasMany(FolderShipService::class, 'itinerary_id');
    }

    /**
     * Get the car rental services for this itinerary.
     */
    public function carRentalServices(): HasMany
    {
        return $this->hasMany(FolderCarRentalService::class, 'itinerary_id');
    }

    /**
     * Get the timeline locations for this itinerary.
     */
    public function timelineLocations(): HasMany
    {
        return $this->hasMany(FolderTimelineLocation::class, 'itinerary_id');
    }

    /**
     * Calculate total amount from all services.
     */
    public function calculateTotalAmount(): void
    {
        $total = 0;

        $total += $this->flightServices()->sum('total_amount');
        $total += $this->hotelServices()->sum('total_amount');
        $total += $this->shipServices()->sum('total_amount');
        $total += $this->carRentalServices()->sum('total_amount');

        $this->total_amount = $total;
        $this->save();
    }
}
