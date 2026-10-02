<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * KI-Vorlage: ein Auftrag (Prompt) fuer die KI-Suche nach Ereignissen.
 * Genau eine Vorlage ist der Standard – sie gilt fuer jede hinterlegte
 * Suche, die keine eigene Vorlage gewaehlt hat.
 */
class AiEventSearchPrompt extends Model
{
    protected $fillable = ['name', 'prompt', 'is_default', 'created_by'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function profiles(): HasMany
    {
        return $this->hasMany(AiEventSearchProfile::class, 'prompt_id');
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
