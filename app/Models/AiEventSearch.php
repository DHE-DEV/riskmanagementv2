<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ein Lauf der KI-Suche nach aktuellen Ereignissen.
 */
class AiEventSearch extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    /** Ein Lauf, der so lange "laeuft", ist abgebrochen (Minuten). */
    public const STALE_AFTER_MINUTES = 10;

    protected $fillable = [
        'profile_id', 'status', 'exclude_existing', 'filters', 'prompt', 'prompt_name', 'max_results', 'started_by', 'found_count', 'new_count',
        'model', 'input_tokens', 'output_tokens', 'cost', 'error', 'finished_at', 'notified_at',
    ];

    protected $casts = [
        'exclude_existing' => 'boolean',
        'filters' => 'array',
        'found_count' => 'integer',
        'new_count' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost' => 'float',
        'finished_at' => 'datetime',
        'notified_at' => 'datetime',
        'max_results' => 'integer',
    ];

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * Die hinterlegte Suche, aus der dieser Lauf stammt.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(AiEventSearchProfile::class, 'profile_id');
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(AiEventSuggestion::class, 'search_id');
    }

    /**
     * Laeuft die Suche noch? Ein haengengebliebener Lauf gilt nicht mehr als laufend.
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING
            && $this->created_at?->gt(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }

    /**
     * Wurde gezielt mit Filtern gesucht?
     */
    public function isTargeted(): bool
    {
        return ! empty($this->filters['labels'] ?? null);
    }

    /**
     * Die Eingrenzung in Worten, z. B. "Länder: Italien · Event-Typen: Streik".
     */
    public function filterSummary(): ?string
    {
        if (! $this->isTargeted()) {
            return null;
        }

        return collect($this->filters['labels'])->map(fn (array $entry) => $entry['label'].': '.$entry['value'])->implode(' · ');
    }

    public function isStale(): bool
    {
        return $this->status === self::STATUS_RUNNING && ! $this->isRunning();
    }
}
