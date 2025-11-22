<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SsoLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'request_id',
        'customer_id',
        'step',
        'version_idp',
        'version_sp',
        'status',
        'jwt_payload',
        'jwt_token',
        'ott',
        'agent_id',
        'service1_customer_id',
        'request_data',
        'response_data',
        'error_message',
        'error_trace',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'duration_ms',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'jwt_payload' => 'array',
        'request_data' => 'array',
        'response_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the customer that this log entry belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope a query to filter by request ID.
     */
    public function scopeByRequestId(Builder $query, string $requestId): Builder
    {
        return $query->where('request_id', $requestId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to get recent logs.
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to filter by step.
     */
    public function scopeByStep(Builder $query, string $step): Builder
    {
        return $query->where('step', $step);
    }

    /**
     * Scope a query to get only error logs.
     */
    public function scopeErrors(Builder $query): Builder
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope a query to get only success logs.
     */
    public function scopeSuccess(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    /**
     * Get all logs for a specific request ID ordered by creation time.
     */
    public static function getRequestHistory(string $requestId): \Illuminate\Database\Eloquent\Collection
    {
        return static::byRequestId($requestId)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
