<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sammellauf einer KI-Pruefung: sie wird fuer alle Datensaetze ihres Bereichs
 * ausgefuehrt, stueckweise – der Stand steht hier.
 */
class AiCheckRun extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_FINISHED = 'finished';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    protected $fillable = ['ai_check_id', 'started_by', 'status', 'filters', 'total', 'processed', 'matched', 'created', 'failed', 'last_record_id', 'total_tokens', 'cost', 'error', 'finished_at'];

    protected $casts = [
        'filters' => 'array',
        'total' => 'integer',
        'processed' => 'integer',
        'matched' => 'integer',
        'created' => 'integer',
        'failed' => 'integer',
        'last_record_id' => 'integer',
        'total_tokens' => 'integer',
        'cost' => 'float',
        'finished_at' => 'datetime',
    ];

    public function check(): BelongsTo
    {
        return $this->belongsTo(AiCheck::class, 'ai_check_id');
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }
}
