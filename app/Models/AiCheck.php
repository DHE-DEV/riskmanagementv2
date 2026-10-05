<?php

namespace App\Models;

use App\Support\AdminV2\AiAreas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Eine KI-Pruefung: ein Prompt, der an einem Abschnitt eines Stammdaten-
 * Formulars mit den Daten dieses Abschnitts ausgefuehrt wird.
 *
 * Auf Wunsch legt sie Aufgaben an: schlaegt sie Aenderungen an einem Datensatz
 * vor, entsteht unter der Sammelaufgabe (task_parent_id) eine Unteraufgabe mit
 * Bezug auf diesen Datensatz. Eine eigene Bedingung (task_condition) ersetzt
 * diese Vorgabe.
 */
class AiCheck extends Model
{
    protected $fillable = ['name', 'description', 'area', 'section', 'prompt', 'model', 'is_active', 'task_enabled', 'task_mode', 'task_condition', 'task_parent_id', 'task_settings', 'sort_order', 'created_by'];

    /** Unteraufgaben unter einer Sammelaufgabe */
    public const TASK_MODE_PARENT = 'parent';

    /** Eine eigenstaendige Aufgabe je Datensatz */
    public const TASK_MODE_SINGLE = 'single';

    protected $attributes = [
        'task_mode' => self::TASK_MODE_PARENT,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'task_enabled' => 'boolean',
        'task_settings' => 'array',
        'sort_order' => 'integer',
    ];

    /** Wann eine Aufgabe entsteht, solange keine eigene Bedingung hinterlegt ist. */
    public const DEFAULT_TASK_CONDITION = 'Die Prüfung ergibt, dass an diesem Eintrag etwas geändert werden sollte – eine Angabe ist falsch, veraltet oder fehlt.';

    /**
     * Legt die Pruefung Aufgaben an?
     */
    public function createsTasks(): bool
    {
        return $this->exists && $this->task_enabled;
    }

    /**
     * Entsteht je Datensatz eine eigenstaendige Aufgabe – statt einer Unteraufgabe der Sammelaufgabe?
     */
    public function createsSingleTasks(): bool
    {
        return $this->task_mode === self::TASK_MODE_SINGLE;
    }

    /**
     * Hat die Pruefung eine eigene Bedingung? Sonst gilt: Aufgabe, sobald sie Aenderungen vorschlaegt.
     */
    public function hasTaskCondition(): bool
    {
        return filled($this->task_condition);
    }

    /**
     * Die Bedingung, die die KI je Datensatz beurteilt.
     */
    public function taskCondition(): string
    {
        return $this->hasTaskCondition() ? trim((string) $this->task_condition) : self::DEFAULT_TASK_CONDITION;
    }

    /**
     * Sammelaufgabe, unter der die Unteraufgaben der Pruefung entstehen.
     */
    public function taskParent(): BelongsTo
    {
        return $this->belongsTo(AdminTask::class, 'task_parent_id');
    }

    /**
     * Aufgaben, die zur Pruefung gehoeren: die Sammelaufgabe und alles, was die Pruefung angelegt hat.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(AdminTask::class, 'ai_check_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AiCheckRun::class);
    }

    /**
     * Der juengste Sammellauf.
     */
    public function latestRun(): HasOne
    {
        return $this->hasOne(AiCheckRun::class)->latestOfMany();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Pruefungen, die an diesem Abschnitt angeboten werden: die des Abschnitts
     * und die bereichsweiten (section leer). Der Abschnitt "general" (Kopf des
     * Formulars) bietet alle Pruefungen des Bereichs – auch die, die nur dort
     * gelten (section = general).
     */
    public function scopeForSection(Builder $query, string $area, string $section): Builder
    {
        return $query->where('area', $area)
            ->when($section !== AiAreas::GENERAL, fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('section')->orWhere('section', $section)))
            ->orderBy('sort_order')->orderBy('name');
    }

    public function areaLabel(): string
    {
        return AiAreas::label($this->area);
    }

    public function sectionLabel(): string
    {
        return match ($this->section) {
            null, '' => 'Alle Abschnitte',
            AiAreas::GENERAL => 'Nur gesamter Eintrag',
            default => AiAreas::sectionLabel($this->area, $this->section),
        };
    }
}
