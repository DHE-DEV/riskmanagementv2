<?php

namespace App\Models\Folder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderCarRentalService extends BaseCustomerModel
{
    protected $table = 'folder_car_rental_services';

    protected $fillable = [
        'itinerary_id',
        'folder_id',
        'customer_id',
        'rental_company',
        'booking_reference',
        'pickup_location',
        'pickup_country_code',
        'pickup_lat',
        'pickup_lng',
        'pickup_datetime',
        'return_location',
        'return_country_code',
        'return_lat',
        'return_lng',
        'return_datetime',
        'vehicle_category',
        'vehicle_type',
        'vehicle_make_model',
        'transmission',
        'fuel_type',
        'rental_days',
        'total_amount',
        'currency',
        'insurance_options',
        'extras',
        'status',
        'notes',
    ];

    protected $casts = [
        'pickup_lat' => 'decimal:8',
        'pickup_lng' => 'decimal:8',
        'return_lat' => 'decimal:8',
        'return_lng' => 'decimal:8',
        'pickup_datetime' => 'datetime',
        'return_datetime' => 'datetime',
        'rental_days' => 'integer',
        'total_amount' => 'decimal:2',
        'insurance_options' => 'array',
        'extras' => 'array',
    ];

    /**
     * Get the itinerary that owns the car rental service.
     */
    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(FolderItinerary::class, 'itinerary_id');
    }

    /**
     * Get the folder that owns the car rental service.
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
     * Calculate rental days if not set.
     */
    public function calculateRentalDays(): void
    {
        if ($this->pickup_datetime && $this->return_datetime) {
            $this->rental_days = $this->pickup_datetime->diffInDays($this->return_datetime);
            $this->save();
        }
    }

    /**
     * Check if it's a one-way rental.
     */
    public function getIsOneWayAttribute(): bool
    {
        return $this->pickup_location !== $this->return_location;
    }

    /**
     * Get vehicle description.
     */
    public function getVehicleDescriptionAttribute(): string
    {
        $parts = array_filter([
            $this->vehicle_make_model,
            $this->vehicle_type,
            $this->transmission,
        ]);

        return implode(' - ', $parts);
    }
}
