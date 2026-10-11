<?php

namespace App\Models;

use App\Support\AdminV2\SubregionInfo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Teilgebiet einer Region: Insel, Inselgruppe oder Reisegebiet (siehe
 * SubregionInfo::KINDS). Die Infos in "info" haben den Aufbau der Regionsinfos.
 */
class Subregion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'region_id',
        'country_id',
        'kind',
        'name_translations',
        'code',
        'lat',
        'lng',
        'info',
        'sort_order',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'info' => 'array',
        'lat' => 'decimal:6',
        'lng' => 'decimal:6',
        'sort_order' => 'integer',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function sights(): HasMany
    {
        return $this->hasMany(Sight::class);
    }

    public function getName(string $language = 'de'): string
    {
        $names = (array) $this->name_translations;

        return (string) ($names[$language] ?? $names['de'] ?? $names['en'] ?? reset($names) ?: '');
    }

    public function kindLabel(): string
    {
        return SubregionInfo::kindLabel($this->kind);
    }
}
