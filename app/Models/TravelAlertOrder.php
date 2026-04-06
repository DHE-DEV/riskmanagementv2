<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelAlertOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_type',
        'business_type',
        'company',
        'first_name',
        'last_name',
        'email',
        'phone',
        'street',
        'postal_code',
        'city',
        'country',
        'existing_billing',
        'remarks',
        'trial_expires_at',
    ];

    protected $casts = [
        'business_type' => 'array',
        'trial_expires_at' => 'date',
    ];
}
