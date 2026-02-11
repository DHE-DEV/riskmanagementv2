<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiClientRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'api_client_id',
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

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    public function scopeForClient($query, int $apiClientId)
    {
        return $query->where('api_client_id', $apiClientId);
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
