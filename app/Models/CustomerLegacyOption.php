<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLegacyOption extends Model
{
    protected $table = 'customers_legacy_options';

    protected $primaryKey = 'account_id';

    public $incrementing = false;

    protected $casts = [
        'live_from' => 'date',
        'end_of_use' => 'date',
        'providers' => 'array',
        'cooperations' => 'array',
        'show_visa_service' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'account_id');
    }
}
