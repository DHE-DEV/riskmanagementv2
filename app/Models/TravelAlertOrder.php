<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelAlertOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
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
        'trial_expires_at' => 'date',
    ];
}
