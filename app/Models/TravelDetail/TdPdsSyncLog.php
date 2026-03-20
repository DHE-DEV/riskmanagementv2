<?php

namespace App\Models\TravelDetail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Customer;

class TdPdsSyncLog extends Model
{
    protected $table = 'td_pds_sync_log';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'status',
        'trips_fetched',
        'trips_created',
        'trips_updated',
        'trips_unchanged',
        'trips_total_api',
        'pages_fetched',
        'error_message',
        'duration_ms',
        'started_at',
        'completed_at',
        'created_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public static function start(int $customerId): self
    {
        return self::create([
            'customer_id' => $customerId,
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
        ]);
    }

    public function markCompleted(array $stats): void
    {
        $this->update([
            'status' => 'success',
            'trips_fetched' => $stats['fetched'] ?? 0,
            'trips_created' => $stats['created'] ?? 0,
            'trips_updated' => $stats['updated'] ?? 0,
            'trips_unchanged' => $stats['unchanged'] ?? 0,
            'trips_total_api' => $stats['total_api'] ?? null,
            'pages_fetched' => $stats['pages'] ?? 0,
            'duration_ms' => round((microtime(true) - $this->started_at->timestamp) * 1000),
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $error, array $stats = []): void
    {
        $this->update([
            'status' => empty($stats['created'] ?? 0) && empty($stats['updated'] ?? 0) ? 'failed' : 'partial',
            'trips_fetched' => $stats['fetched'] ?? $this->trips_fetched,
            'trips_created' => $stats['created'] ?? $this->trips_created,
            'trips_updated' => $stats['updated'] ?? $this->trips_updated,
            'trips_unchanged' => $stats['unchanged'] ?? $this->trips_unchanged,
            'trips_total_api' => $stats['total_api'] ?? $this->trips_total_api,
            'pages_fetched' => $stats['pages'] ?? $this->pages_fetched,
            'error_message' => $error,
            'duration_ms' => round((microtime(true) - $this->started_at->timestamp) * 1000),
            'completed_at' => now(),
        ]);
    }
}
