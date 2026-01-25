<?php

namespace App\Models\Folder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FolderParticipant extends BaseCustomerModel
{
    protected $table = 'folder_participants';

    protected $fillable = [
        'folder_id',
        'customer_id',
        'salutation',
        'title',
        'first_name',
        'last_name',
        'birth_date',
        'nationality',
        'passport_number',
        'passport_issue_date',
        'passport_expiry_date',
        'passport_issuing_country',
        'email',
        'phone',
        'dietary_requirements',
        'medical_conditions',
        'notes',
        'is_main_contact',
        'participant_type',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'passport_issue_date' => 'date',
        'passport_expiry_date' => 'date',
        'is_main_contact' => 'boolean',
    ];

    /**
     * Get the folder that owns the participant.
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
     * Get the itineraries this participant is assigned to.
     */
    public function itineraries(): BelongsToMany
    {
        return $this->belongsToMany(
            FolderItinerary::class,
            'folder_itinerary_participant',
            'participant_id',
            'itinerary_id'
        )->withTimestamps();
    }

    /**
     * Get full name.
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->title,
            $this->first_name,
            $this->last_name,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Check if passport is expired.
     */
    public function getIsPassportExpiredAttribute(): bool
    {
        if (! $this->passport_expiry_date) {
            return false;
        }

        return $this->passport_expiry_date->isPast();
    }

    /**
     * Check if passport expires soon (within 6 months).
     */
    public function getIsPassportExpiringSoonAttribute(): bool
    {
        if (! $this->passport_expiry_date) {
            return false;
        }

        return $this->passport_expiry_date->isBetween(now(), now()->addMonths(6));
    }
}
