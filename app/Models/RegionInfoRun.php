<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein KI-Lauf der Regionsinfos (siehe Migration create_region_info_runs_table).
 */
class RegionInfoRun extends Model
{
    public const KIND_SUGGEST = 'suggest';

    public const KIND_FILL = 'fill';

    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /** Abgeschlossene Laeufe der Liste, die ausgeblendet wurden */
    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = ['kind', 'region_id', 'status', 'pending', 'total', 'done', 'failed', 'overwrite', 'result', 'error', 'started_by', 'finished_at'];

    protected $casts = [
        'pending' => 'array',
        'failed' => 'array',
        'result' => 'array',
        'overwrite' => 'boolean',
        'finished_at' => 'datetime',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }
}
