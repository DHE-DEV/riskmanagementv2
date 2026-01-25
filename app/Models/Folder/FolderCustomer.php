<?php

namespace App\Models\Folder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderCustomer extends BaseCustomerModel
{
    protected $table = 'folder_customers';

    protected $fillable = [
        'folder_id',
        'customer_id',
        'salutation',
        'title',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'street',
        'house_number',
        'postal_code',
        'city',
        'country_code',
        'notes',
        'birth_date',
        'nationality',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Get the folder that owns the customer data.
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
     * Get full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->house_number,
            $this->postal_code,
            $this->city,
        ]);

        return implode(', ', $parts);
    }
}
