<?php

namespace App\Models\Folder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends BaseCustomerModel
{
    use SoftDeletes;

    protected $table = 'folder_folders';

    protected $fillable = [
        'customer_id',
        'folder_number',
        'folder_name',
        'travel_start_date',
        'travel_end_date',
        'destinations_visited',
        'primary_destination',
        'status',
        'travel_type',
        'agent_name',
        'notes',
        'total_participants',
        'total_itineraries',
        'total_value',
        'currency',
        'custom_field_1_label',
        'custom_field_1_value',
        'custom_field_2_label',
        'custom_field_2_value',
        'custom_field_3_label',
        'custom_field_3_value',
        'custom_field_4_label',
        'custom_field_4_value',
        'custom_field_5_label',
        'custom_field_5_value',
    ];

    protected $casts = [
        'destinations_visited' => 'array',
        'travel_start_date' => 'date',
        'travel_end_date' => 'date',
        'total_value' => 'decimal:2',
        'total_participants' => 'integer',
        'total_itineraries' => 'integer',
    ];

    /**
     * Get the customer that owns the folder.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the folder customer data (snapshot).
     */
    public function folderCustomer(): HasOne
    {
        return $this->hasOne(FolderCustomer::class);
    }

    /**
     * Get the participants for the folder.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(FolderParticipant::class);
    }

    /**
     * Get the itineraries for the folder.
     */
    public function itineraries(): HasMany
    {
        return $this->hasMany(FolderItinerary::class);
    }

    /**
     * Get the timeline locations for the folder.
     */
    public function timelineLocations(): HasMany
    {
        return $this->hasMany(FolderTimelineLocation::class);
    }

    /**
     * Get all flight services through itineraries.
     */
    public function flightServices(): HasMany
    {
        return $this->hasMany(FolderFlightService::class);
    }

    /**
     * Get all hotel services through itineraries.
     */
    public function hotelServices(): HasMany
    {
        return $this->hasMany(FolderHotelService::class);
    }

    /**
     * Get all ship services through itineraries.
     */
    public function shipServices(): HasMany
    {
        return $this->hasMany(FolderShipService::class);
    }

    /**
     * Get all car rental services through itineraries.
     */
    public function carRentalServices(): HasMany
    {
        return $this->hasMany(FolderCarRentalService::class);
    }

    /**
     * Generate unique folder number.
     */
    public static function generateFolderNumber(?int $customerId = null): string
    {
        $year = now()->year;
        $customerId = $customerId ?? auth('customer')->id();

        // Get the last folder number for this customer in this year with row locking
        // Note: lockForUpdate requires an active transaction from the caller
        $lastFolder = static::withoutGlobalScope('customer')
            ->where('customer_id', $customerId)
            ->where('folder_number', 'like', $year.'-'.$customerId.'-%')
            ->orderByDesc('folder_number')
            ->lockForUpdate()
            ->first();

        if ($lastFolder) {
            // Extract the sequence number and increment
            preg_match('/-(\d+)$/', $lastFolder->folder_number, $matches);
            $sequence = isset($matches[1]) ? ((int) $matches[1]) + 1 : 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%d-%s-%06d', $year, $customerId, $sequence);
    }

    /**
     * Update folder statistics.
     */
    public function updateStatistics(): void
    {
        $this->total_participants = $this->participants()->count();
        $this->total_itineraries = $this->itineraries()->count();

        // Calculate total value from all itineraries
        $this->total_value = $this->itineraries()
            ->sum('total_amount');

        $this->save();
    }
}
