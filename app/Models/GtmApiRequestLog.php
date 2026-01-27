<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GtmApiRequestLog extends Model
{
    public $timestamps = false;  // No updated_at, only created_at managed manually

    protected $fillable = [
        'customer_id',
        'token_id',
        'method',
        'endpoint',
        'query_params',
        'ip_address',
        'user_agent',
        'response_status',
        'response_time_ms',
        'created_at',
    ];

    protected $casts = [
        'query_params' => 'array',
        'response_status' => 'integer',
        'response_time_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('response_status', '<', 400);
    }

    public function scopeFailed($query)
    {
        return $query->where('response_status', '>=', 400);
    }
}
