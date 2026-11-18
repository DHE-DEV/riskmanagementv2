<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'name_translations',
        'description',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Name in der gewünschten Sprache. Die Spalte name ist die Ausgangssprache (Deutsch);
     * fehlt eine Übersetzung, kommt sie als Rückfall.
     */
    public function getName(?string $language = null): string
    {
        $language = strtolower($language ?: app()->getLocale());

        if ($language !== CustomEvent::sourceLocale() && filled($this->name_translations[$language] ?? null)) {
            return $this->name_translations[$language];
        }

        return (string) $this->name;
    }

    /**
     * Ereignisse, die diesem Typ zugeordnet sind (Mehrfachzuordnung).
     */
    public function events(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(CustomEvent::class, 'custom_event_event_type');
    }

    /**
     * Get custom events of this type.
     */
    public function customEvents(): HasMany
    {
        return $this->hasMany(CustomEvent::class);
    }

    /**
     * Get event categories of this type.
     */
    public function eventCategories(): HasMany
    {
        return $this->hasMany(EventCategory::class);
    }

    /**
     * Scope for active event types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered event types.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
