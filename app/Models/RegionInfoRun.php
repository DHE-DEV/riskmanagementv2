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

    /** Sehenswuerdigkeiten vieler Regionen (oder einer aus dem Editor) per KI anlegen */
    public const KIND_SIGHTS = 'sights';

    /** Sehenswuerdigkeiten einer Region aus ihrem Editor heraus */
    public const KIND_SIGHTS_ONE = 'sights_one';

    /** Luecken der Regionen und Teilgebiete eines oder mehrerer Laender fuellen (regions:fill-missing) */
    public const KIND_FILL_MISSING = 'fill_missing';

    public function isSights(): bool
    {
        return in_array($this->kind, [self::KIND_SIGHTS, self::KIND_SIGHTS_ONE], true);
    }

    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /** Abgeschlossene Laeufe der Liste, die ausgeblendet wurden */
    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = ['kind', 'region_id', 'subregion_id', 'status', 'pending', 'total', 'done', 'failed', 'overwrite', 'result', 'error', 'started_by', 'finished_at'];

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

    /** Gesetzt, wenn der Lauf einem Teilgebiet statt der Region gilt */
    public function subregion(): BelongsTo
    {
        return $this->belongsTo(Subregion::class);
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }
}
