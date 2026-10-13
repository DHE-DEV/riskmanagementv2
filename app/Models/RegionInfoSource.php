<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Eine Quelle der KI-Recherche zu einer Region oder einem Teilgebiet.
 * field: das Feld, bei dem die KI die Quelle angegeben hat (z. B. "climate",
 * "population", "subregions" fuer die Suche nach Teilgebieten) – leer, wenn
 * die Seite nur besucht wurde. cited: von der KI in der Antwort belegt.
 */
class RegionInfoSource extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'cited' => 'boolean',
    ];

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(RegionInfoRun::class, 'region_info_run_id');
    }
}
