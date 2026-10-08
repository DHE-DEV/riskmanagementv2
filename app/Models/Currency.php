<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Eine Waehrung nach ISO 4217 (Code, Nummer, Name je Sprache, Symbol).
 */
class Currency extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'numeric_code',
        'name_translations',
        'symbol',
        'minor_unit',
        'is_active',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'numeric_code' => 'integer',
        'minor_unit' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getName(string $locale = 'de'): string
    {
        return $this->name_translations[$locale] ?? $this->name_translations['en'] ?? $this->code;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Optionen fuer Auswahlfelder: [['value' => 'EUR', 'label' => 'Euro (EUR)', 'code' => 'EUR'], …],
     * alphabetisch nach Name in der gewuenschten Sprache.
     *
     * @return array<int, array{value: string, label: string, code: string}>
     */
    public static function options(string $locale = 'de'): array
    {
        return static::query()->active()->get()
            ->map(fn (Currency $currency) => [
                'value' => $currency->code,
                'label' => $currency->getName($locale).' ('.$currency->code.')'.($currency->symbol && $currency->symbol !== $currency->code ? ' '.$currency->symbol : ''),
                'code' => $currency->code,
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Codes aller aktiven Waehrungen – fuer Validierungen.
     *
     * @return Collection<int, string>
     */
    public static function codes(): Collection
    {
        return static::query()->active()->pluck('code');
    }
}
