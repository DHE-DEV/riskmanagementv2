<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sehenswuerdigkeit oder Unternehmung eines Landes, optional einer Region und Stadt.
 */
class Sight extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_translations',
        'country_id',
        'region_id',
        'city_id',
        'category',
        'is_highlight',
        'sort_order',
        'lat',
        'lng',
        'address',
        'website_url',
        'ticket_url',
        'info',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'info' => 'array',
        'is_highlight' => 'boolean',
        'sort_order' => 'integer',
        'lat' => 'decimal:6',
        'lng' => 'decimal:6',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function getName(string $language = 'de'): string
    {
        $names = (array) $this->name_translations;

        return (string) ($names[$language] ?? $names['de'] ?? $names['en'] ?? reset($names) ?: '');
    }
}
