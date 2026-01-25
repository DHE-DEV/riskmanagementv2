<?php

namespace App\Models\Folder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderHotelService extends BaseCustomerModel
{
    protected $table = 'folder_hotel_services';

    protected $fillable = [
        'itinerary_id',
        'folder_id',
        'customer_id',
        'hotel_name',
        'hotel_code',
        'hotel_code_type',
        'street',
        'postal_code',
        'city',
        'country_code',
        'lat',
        'lng',
        'check_in_date',
        'check_out_date',
        'nights',
        'room_type',
        'room_count',
        'board_type',
        'booking_reference',
        'total_amount',
        'currency',
        'status',
        'notes',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'nights' => 'integer',
        'room_count' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the itinerary that owns the hotel service.
     */
    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(FolderItinerary::class, 'itinerary_id');
    }

    /**
     * Get the folder that owns the hotel service.
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
        if ($this->check_in_date && $this->check_out_date) {
            $this->nights = $this->check_in_date->diffInDays($this->check_out_date);
            $this->save();
        }
    }

    /**
     * Get full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->postal_code,
            $this->city,
            $this->country_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Update spatial point when coordinates change.
     * Note: SRID 4326 (WGS 84) expects POINT(latitude longitude) axis order
     */
    protected static function booted(): void
    {
        parent::booted();

        static::saving(function ($model) {
            if ($model->lat && $model->lng) {
                $model->setAttribute('point', \DB::raw("ST_GeomFromText('POINT({$model->lat} {$model->lng})', 4326)"));
            } else {
                // Set point to NULL if no coordinates
                $model->setAttribute('point', null);
            }
        });
    }
}
