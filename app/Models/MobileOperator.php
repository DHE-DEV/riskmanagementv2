<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Ein Mobilfunkanbieter (Telekom, Vodafone, Orange, …): Logo, Name,
 * Beschreibung je Sprache, Website und Prepaid-Seite. In welchen Laendern
 * er verbreitet ist, haengt am Land.
 */
class MobileOperator extends Model
{
    protected $fillable = [
        'name',
        'description_translations',
        'logo_url',
        'website_url',
        'prepaid_url',
        'offers_esim',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'description_translations' => 'array',
        'offers_esim' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_mobile_operator');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function description(string $locale = 'de'): ?string
    {
        return $this->description_translations[$locale] ?? $this->description_translations['en'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description_translations ?: (object) [],
            'logo_url' => $this->logo_url,
            'website_url' => $this->website_url,
            'prepaid_url' => $this->prepaid_url,
            'offers_esim' => $this->offers_esim,
        ];
    }
}
