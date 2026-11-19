<?php

namespace App\Models;

use App\Support\AiProviders;
use App\Support\AiSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * KI-Vorlage: ein Auftrag (Prompt) fuer die KI-Suche nach Ereignissen.
 * Genau eine Vorlage ist der Standard – sie gilt fuer jede hinterlegte
 * Suche, die keine eigene Vorlage gewaehlt hat. Ein eigenes Modell ist
 * moeglich; ohne gilt das Standardmodell unter System > KI.
 */
class AiEventSearchPrompt extends Model
{
    protected $fillable = ['name', 'prompt', 'model', 'is_default', 'created_by'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function profiles(): HasMany
    {
        return $this->hasMany(AiEventSearchProfile::class, 'prompt_id');
    }

    /**
     * Das Modell, mit dem diese Vorlage sucht: ihr eigenes, sonst das Standardmodell.
     */
    public function effectiveModel(): string
    {
        return trim((string) $this->model) ?: AiSettings::model();
    }

    /**
     * Modell und Anbieter zum Anzeigen, z. B. "claude-opus-5-5 (Anthropic (Claude))".
     */
    public function modelLabel(): string
    {
        $model = $this->effectiveModel();

        return $model.' ('.AiProviders::label(AiProviders::forModel($model)).')'.(blank($this->model) ? ' – Standardmodell' : '');
    }

    /**
     * Die Standard-Vorlage.
     */
    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->orderBy('id')->first()
            ?? static::query()->orderBy('id')->first();
    }

    /**
     * Diese Vorlage zum Standard machen – es gibt immer genau einen.
     */
    public function makeDefault(): void
    {
        DB::transaction(function () {
            static::query()->whereKeyNot($this->id)->update(['is_default' => false]);
            $this->forceFill(['is_default' => true])->save();
        });
    }
}
