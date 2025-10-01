<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiPrompt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'model_type',
        'prompt_template',
        'category',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope für aktive Prompts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für bestimmten Model-Typ
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope für bestimmte Kategorie
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope für Sortierung
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Ersetzt Platzhalter im Prompt-Template mit tatsächlichen Daten
     */
    public function fillPlaceholders(array $data): string
    {
        $prompt = $this->prompt_template;

        foreach ($data as $key => $value) {
            // Konvertiere Arrays/Objekte zu JSON-String
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            // Ersetze Platzhalter wie {name}, {iso_code}, etc.
            $prompt = str_replace('{' . $key . '}', $value ?? '', $prompt);
        }

        return $prompt;
    }
}
