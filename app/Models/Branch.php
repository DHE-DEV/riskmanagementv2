<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends Model
{
    protected $fillable = [
        'customer_id',
        'name',
        'additional',
        'street',
        'house_number',
        'postal_code',
        'city',
        'country',
        'latitude',
        'longitude',
        'is_headquarters',
    ];

    protected $casts = [
        'is_headquarters' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
