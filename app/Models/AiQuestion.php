<?php

namespace App\Models;

use App\Support\AdminV2\AiAreas;
use App\Support\AiProviders;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Eine Anfrage an die KI zu einem Eintrag – beantwortet im Hintergrund
 * (AskAiQuestionJob) von ChatGPT, Claude oder Gemini. Zwei Arten:
 *
 * - freie Frage zu einem Abschnitt (field leer): die Antwort steht unter der Frage
 * - Feldrecherche (field gesetzt): die KI liefert einen Wert fuer genau dieses
 *   Feld samt Begruendung; wer ihn uebernimmt, wird mit altem und neuem Wert
 *   festgehalten.
 *
 * Das Protokoll bleibt dauerhaft: was gesendet wurde (prompt), was die KI
 * vorgeschlagen hat, mit welchen Quellen – und was daraus wurde.
 */
class AiQuestion extends Model
{
    /** @use HasFactory<\Database\Factories\AiQuestionFactory> */
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    /** Ein Lauf, der so lange "laeuft", ist abgebrochen (Minuten). */
    public const STALE_AFTER_MINUTES = 10;

    protected $fillable = [
        'user_id', 'area', 'section', 'field', 'ai_field_prompt_id', 'subject_type', 'subject_id', 'title', 'provider', 'model',
        'question', 'prompt', 'status', 'answer', 'value', 'note', 'previous_value', 'applied_value', 'sources', 'usage', 'error',
        'started_at', 'finished_at', 'applied_at', 'applied_by', 'saved_at',
    ];

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'usage' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'applied_at' => 'datetime',
            'saved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Die Vorlage, mit der gefragt wurde (der damals gesendete Text steht in question). */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AiFieldPrompt::class, 'ai_field_prompt_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
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
        return $this->status === self::STATUS_RUNNING && (bool) $this->started_at?->lt(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }

    /**
     * Fehlermeldung eines fehlgeschlagenen oder haengen gebliebenen Laufs.
     */
    public function failureMessage(): ?string
    {
        return match (true) {
            $this->status === self::STATUS_FAILED => $this->error ?: 'Unbekannter Fehler.',
            $this->isStale() => 'Der Lauf ist abgebrochen – bitte erneut fragen.',
            default => null,
        };
    }

    /**
     * Wurde der Vorschlag ins Formular uebernommen?
     */
    public function isApplied(): bool
    {
        return $this->applied_at !== null;
    }

    /**
     * Kosten in US-Dollar laut Preisliste, sofern bekannt.
     */
    public function cost(): ?float
    {
        $cost = $this->usage['cost'] ?? null;

        return is_numeric($cost) ? (float) $cost : null;
    }

    public function providerLabel(): string
    {
        return AiProviders::label($this->provider);
    }

    public function sectionLabel(): string
    {
        return AiAreas::sectionLabel($this->area, $this->section);
    }

    /**
     * Die Antwort als HTML (Markdown der KI, ohne eingebettetes HTML).
     */
    public function answerHtml(): string
    {
        return Str::markdown((string) $this->answer, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForSection(Builder $query, string $area, string $section, ?Model $subject): void
    {
        $query->where('area', $area)
            ->where('section', $section)
            ->where('subject_type', $subject?->getMorphClass())
            ->where('subject_id', $subject?->getKey());
    }

    /**
     * Wartende und laufende Fragen (haengen gebliebene zaehlen nicht).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->where('status', self::STATUS_QUEUED)
            ->orWhere(fn (Builder $query) => $query->where('status', self::STATUS_RUNNING)->where('started_at', '>=', now()->subMinutes(self::STALE_AFTER_MINUTES))));
    }
}
