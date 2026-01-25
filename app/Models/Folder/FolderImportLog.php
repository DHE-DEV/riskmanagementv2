<?php

namespace App\Models\Folder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderImportLog extends BaseCustomerModel
{
    protected $table = 'folder_import_logs';

    protected $fillable = [
        'customer_id',
        'folder_id',
        'import_source',
        'provider_name',
        'status',
        'source_data',
        'mapping_config',
        'error_message',
        'records_imported',
        'records_failed',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'source_data' => 'array',
        'mapping_config' => 'array',
        'records_imported' => 'integer',
        'records_failed' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the customer that owns the import log.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the folder created from this import.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Mark import as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark import as completed.
     */
    public function markAsCompleted(int $imported, int $failed = 0): void
    {
        $this->update([
            'status' => 'completed',
            'records_imported' => $imported,
            'records_failed' => $failed,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark import as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get duration in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }
}
