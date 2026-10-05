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
 * Auf Wunsch legt sie Aufgaben an: trifft die Bedingung (task_condition) bei
 * einem Datensatz zu, entsteht unter der Sammelaufgabe (task_parent_id) eine
 * Unteraufgabe mit Bezug auf diesen Datensatz.
 */
class AiCheck extends Model
{
    protected $fillable = ['name', 'description', 'area', 'section', 'prompt', 'model', 'is_active', 'task_enabled', 'task_condition', 'task_parent_id', 'sort_order', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
        'task_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Legt die Pruefung Aufgaben an?
     */
    public function createsTasks(): bool
    {
        return $this->exists && $this->task_enabled && filled($this->task_condition);
    }

    /**
     * Sammelaufgabe, unter der die Unteraufgaben der Pruefung entstehen.
     */
    public function taskParent(): BelongsTo
    {
        return $this->belongsTo(AdminTask::class, 'task_parent_id');
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
