<?php

namespace App\Models\TravelDetail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TdImportLog extends Model
{
    protected $table = 'td_import_logs';

    public $timestamps = false;

    protected $fillable = [
        'provider_id',
        'external_trip_id',
        'action',
        'status',
        'error_message',
        'error_details',
        'request_payload',
        'response_payload',
        'processing_time_ms',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'error_details' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'processing_time_ms' => 'integer',
    ];

    /**
     * Scope: By provider
     */
    public function scopeByProvider(Builder $query, string $providerId): Builder
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope: Successful imports
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope: Failed imports
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Errors only
     */
    public function scopeErrors(Builder $query): Builder
    {
        return $query->where('action', 'error');
    }

    /**
     * Scope: Recent (last N days)
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if this log entry represents a success
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if this log entry represents a failure
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get action label
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'create' => 'Erstellt',
            'update' => 'Aktualisiert',
            'error' => 'Fehler',
            default => ucfirst($this->action ?? 'Unknown'),
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'success' => 'Erfolgreich',
            'failed' => 'Fehlgeschlagen',
            'partial' => 'Teilweise',
            default => ucfirst($this->status ?? 'Unknown'),
        };
    }

    /**
     * Get formatted processing time
     */
    public function getFormattedProcessingTimeAttribute(): ?string
    {
        if (!$this->processing_time_ms) {
            return null;
        }

        if ($this->processing_time_ms < 1000) {
            return "{$this->processing_time_ms}ms";
        }

        $seconds = round($this->processing_time_ms / 1000, 2);
        return "{$seconds}s";
    }

    /**
     * Create a success log entry
     */
    public static function logSuccess(
        string $providerId,
        string $externalTripId,
        string $action,
        int $processingTimeMs,
        ?array $responsePayload = null
    ): self {
        return self::create([
            'provider_id' => $providerId,
            'external_trip_id' => $externalTripId,
            'action' => $action,
            'status' => 'success',
            'processing_time_ms' => $processingTimeMs,
            'response_payload' => $responsePayload,
            'created_at' => now(),
        ]);
    }

    /**
     * Create a failure log entry
     */
    public static function logFailure(
        string $providerId,
        ?string $externalTripId,
        string $errorMessage,
        ?array $errorDetails = null,
        ?array $requestPayload = null,
        ?int $processingTimeMs = null
    ): self {
        return self::create([
            'provider_id' => $providerId,
            'external_trip_id' => $externalTripId,
            'action' => 'error',
            'status' => 'failed',
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'request_payload' => $requestPayload,
            'processing_time_ms' => $processingTimeMs,
            'created_at' => now(),
        ]);
    }
}
