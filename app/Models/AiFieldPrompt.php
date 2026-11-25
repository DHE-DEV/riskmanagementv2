<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gespeicherter Prompt der KI-Feldrecherche fuer ein Feld eines Bereichs, z. B.
 * "Kriminalitaetsniveau" bei den Laendern. Platzhalter wie {name} werden beim
 * Senden mit den Werten des jeweiligen Eintrags gefuellt – so gilt eine einmal
 * gefundene Vorlage fuer alle Laender. Je Feld ist hoechstens eine Standard.
 */
class AiFieldPrompt extends Model
{
    /** @use HasFactory<\Database\Factories\AiFieldPromptFactory> */
    use HasFactory;

    protected $fillable = ['area', 'field', 'name', 'prompt', 'provider', 'model', 'is_default', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AiQuestion::class);
    }

    /**
     * Vorlagen eines Feldes, die Standardvorlage zuerst.
     *
     * @param  Builder<self>  $query
     */
    public function scopeForField(Builder $query, string $area, string $field): void
    {
        $query->where('area', $area)->where('field', $field)->orderByDesc('is_default')->orderBy('name');
    }

    /**
     * Diese Vorlage zur Standardvorlage ihres Feldes machen.
     */
    public function makeDefault(): void
    {
        static::query()->where('area', $this->area)->where('field', $this->field)->whereKeyNot($this->getKey())->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
