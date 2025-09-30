<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Continent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_translations',
        'code',
        'sort_order',
        'description',
        'keywords',
        'lat',
        'lng',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'sort_order' => 'integer',
        'lat' => 'decimal:6',
        'lng' => 'decimal:6',
        'keywords' => 'array',
    ];

    /**
     * Get the countries for this continent.
     */
    public function countries(): HasMany
    {
        return $this->hasMany(Country::class);
    }

    /**
     * Get the name in a specific language.
     */
    public function getName(string $language = 'de'): string
    {
        $translations = $this->name_translations ?? [];
        return $translations[$language] ?? $translations['en'] ?? $this->code ?? 'Unknown';
    }

    /**
     * Scope a query to order continents by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope a query to search continents by name.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Get the continent code options.
     */
    public static function getCodeOptions(): array
    {
        return [
            'AF' => 'Afrika',
            'AN' => 'Antarktis',
            'AS' => 'Asien',
            'EU' => 'Europa',
            'NA' => 'Nordamerika',
            'OC' => 'Ozeanien',
            'SA' => 'Südamerika',
        ];
    }
}
