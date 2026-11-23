<?php

namespace App\Models;

use App\Support\AdminV2\AiAreas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Ein Auftrag der KI-Feldpruefung: ein Abschnitt eines Eintrags, mit Websuche
 * im Hintergrund (ReviewAiFieldsJob). Mehrere Auftraege koennen gleichzeitig
 * offen sein; fertige bleiben stehen, bis sie im Formular geoeffnet wurden.
 */
class AiFieldReview extends Model
{
    use MassPrunable;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    /** Ein Lauf, der so lange "laeuft", ist abgebrochen (Minuten). */
    public const STALE_AFTER_MINUTES = 10;

    protected $fillable = [
        'user_id', 'area', 'section', 'subject_type', 'subject_id', 'title', 'url', 'model',
        'status', 'result', 'error', 'started_at', 'finished_at', 'seen_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Wartet oder laeuft noch (und ist nicht haengen geblieben).
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_QUEUED || ($this->status === self::STATUS_RUNNING && ! $this->isStale());
    }

    /**
     * Laeuft laenger, als ein Lauf dauern kann – der Worker ist abgebrochen.
     */
    public function isStale(): bool
    {
        return $this->status === self::STATUS_RUNNING && $this->started_at?->lt(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }

    public function isFinished(): bool
    {
        return ! $this->isOpen();
    }

    /**
     * Fehlermeldung eines fehlgeschlagenen oder haengen gebliebenen Laufs.
     */
    public function failureMessage(): ?string
    {
        return match (true) {
            $this->status === self::STATUS_FAILED => $this->error ?: 'Unbekannter Fehler.',
            $this->isStale() => 'Der Lauf ist abgebrochen – bitte erneut starten.',
            default => null,
        };
    }

    public function sectionLabel(): string
    {
        return AiAreas::sectionLabel($this->area, $this->section);
    }

    /**
     * Seite des Eintrags, die das Ergebnis oeffnet.
     */
    public function openUrl(): ?string
    {
        return $this->url ? $this->url.(str_contains($this->url, '?') ? '&' : '?').'ki-pruefung='.$this->id : null;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForSubject(Builder $query, string $area, ?Model $subject): void
    {
        $query->where('area', $area)
            ->where('subject_type', $subject?->getMorphClass())
            ->where('subject_id', $subject?->getKey());
    }

    /**
     * Wartende und laufende Auftraege (haengen gebliebene zaehlen nicht).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->where('status', self::STATUS_QUEUED)
            ->orWhere(fn (Builder $query) => $query->where('status', self::STATUS_RUNNING)->where('started_at', '>=', now()->subMinutes(self::STALE_AFTER_MINUTES))));
    }

    /**
     * Fertig (auch fehlgeschlagen oder abgebrochen) und noch nicht angesehen.
     *
     * @param  Builder<self>  $query
     */
    public function scopeUnseenFinished(Builder $query): void
    {
        $query->whereNull('seen_at')->whereNot(fn (Builder $query) => $query->open());
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(30));
    }
}
