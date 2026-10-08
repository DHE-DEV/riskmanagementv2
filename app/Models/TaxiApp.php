<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Eine Taxi- bzw. Mobilitaets-App (Uber, Bolt, FreeNow, …): Logo, Name,
 * Beschreibung je Sprache und die Wege zu Website und App-Stores. Welche Apps
 * in einem Land verbreitet sind, haengt am Land.
 */
class TaxiApp extends Model
{
    protected $fillable = [
        'name',
        'description_translations',
        'logo_url',
        'website_url',
        'app_store_url',
        'play_store_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'description_translations' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_taxi_app');
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
     * Darstellung fuer eine API.
     *
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
            'app_store_url' => $this->app_store_url,
            'play_store_url' => $this->play_store_url,
        ];
    }
}
