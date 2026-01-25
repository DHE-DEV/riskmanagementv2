<?php

namespace App\Models\Folder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderShipService extends BaseCustomerModel
{
    protected $table = 'folder_ship_services';

    protected $fillable = [
        'itinerary_id',
        'folder_id',
        'customer_id',
        'ship_name',
        'cruise_line',
        'ship_code',
        'embarkation_date',
        'disembarkation_date',
        'nights',
        'embarkation_port',
        'embarkation_country_code',
        'embarkation_lat',
        'embarkation_lng',
        'disembarkation_port',
        'disembarkation_country_code',
        'disembarkation_lat',
        'disembarkation_lng',
        'cabin_number',
        'cabin_type',
        'cabin_category',
        'deck',
        'booking_reference',
        'total_amount',
        'currency',
        'status',
        'port_calls',
        'notes',
    ];

    protected $casts = [
        'embarkation_date' => 'date',
        'disembarkation_date' => 'date',
        'nights' => 'integer',
        'embarkation_lat' => 'decimal:8',
        'embarkation_lng' => 'decimal:8',
        'disembarkation_lat' => 'decimal:8',
        'disembarkation_lng' => 'decimal:8',
        'total_amount' => 'decimal:2',
        'port_calls' => 'array',
    ];

    /**
     * Get the itinerary that owns the ship service.
     */
    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(FolderItinerary::class, 'itinerary_id');
    }

    /**
     * Get the folder that owns the ship service.
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
     * Calculate number of nights if not set.
     */
    public function calculateNights(): void
    {
        if ($this->embarkation_date && $this->disembarkation_date) {
            $this->nights = $this->embarkation_date->diffInDays($this->disembarkation_date);
            $this->save();
        }
    }

    /**
     * Get ship identifier.
     */
    public function getShipIdentifierAttribute(): string
    {
        return $this->cruise_line ? "{$this->cruise_line} - {$this->ship_name}" : $this->ship_name;
    }
}
