<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Ein Feiertag eines Landes an einem konkreten Datum – Name und Kommentar
 * je Sprache. Bewegliche Feiertage stehen je Jahr einzeln; haengen
 * Regionen am Feiertag, gilt er nur dort.
 */
class CountryHoliday extends Model
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_LIBRARY = 'holidays-lib';

    protected $fillable = [
        'country_id',
        'date',
        'name_translations',
        'comment_translations',
        'is_national',
        'source',
    ];

    protected $casts = [
        'date' => 'date',
        'name_translations' => 'array',
        'comment_translations' => 'array',
        'is_national' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Regionen, in denen der Feiertag gilt – keine = landesweit.
     */
    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'country_holiday_region')->orderBy('regions.code');
    }

    public function scopeNational($query)
    {
        return $query->whereDoesntHave('regions');
    }

    public function isNational(): bool
    {
        return $this->relationLoaded('regions') ? $this->regions->isEmpty() : ! $this->regions()->exists();
    }

    public function getName(string $locale = 'de'): string
    {
        return $this->name_translations[$locale] ?? $this->name_translations['en'] ?? (string) reset($this->name_translations);
    }

    public function getComment(string $locale = 'de'): ?string
    {
        return $this->comment_translations[$locale] ?? $this->comment_translations['en'] ?? null;
    }

    public function scopeInYear($query, int $year)
    {
        return $query->whereBetween('date', [$year.'-01-01', $year.'-12-31']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'weekday' => $this->date->dayOfWeekIso,
            'name' => $this->name_translations ?: (object) [],
            'comment' => $this->comment_translations ?: (object) [],
            'is_national' => $this->isNational(),
            'regions' => $this->regions->map(fn (Region $region) => ['id' => $region->id, 'code' => $region->code, 'name' => $region->name_translations ?: (object) []])->values()->all(),
        ];
    }
}
