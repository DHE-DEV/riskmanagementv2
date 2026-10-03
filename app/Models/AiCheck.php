<?php

namespace App\Models;

use App\Support\AdminV2\AiAreas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eine KI-Pruefung: ein Prompt, der an einem Abschnitt eines Stammdaten-
 * Formulars mit den Daten dieses Abschnitts ausgefuehrt wird.
 */
class AiCheck extends Model
{
    protected $fillable = ['name', 'description', 'area', 'section', 'prompt', 'model', 'is_active', 'sort_order', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

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
