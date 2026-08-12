<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationRule extends Model
{
    use SoftDeletes;

    public const SOURCE_TRAVEL_ALERT = 'travel-alert';
    public const SOURCE_GLOBAL_TRAVEL_MONITOR = 'global-travel-monitor';

    protected $fillable = [
        'customer_id',
        'source',
        'name',
        'is_active',

        'risk_levels',
        'categories',
        'country_ids',
        'notification_template_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'risk_levels' => 'array',
        'categories' => 'array',
        'country_ids' => 'array',
    ];

    public const RISK_LEVELS = [
        'high' => 'Hoch',
        'medium' => 'Mittel',
        'low' => 'Niedrig',
        'info' => 'Information',
    ];

    /**
     * Fallback-Beschriftungen. Massgeblich sind die Event-Typen aus der
     * Datenbank (siehe categoryOptions()) - diese Liste greift nur noch, wenn
     * dort nichts zu holen ist, und liefert die Labels fuer Altbestaende.
     */
    public const CATEGORIES = [
        'environment' => 'Umweltereignisse',
        'traffic' => 'Reiseverkehr',
        'security' => 'Sicherheit',
        'entry' => 'Einreisebestimmungen',
        'health' => 'Gesundheit',
        'general' => 'Allgemein',
    ];

    /**
     * Zwei der frueher hartcodierten Schluessel weichen vom Code des
     * zugehoerigen Event-Typs ab. Bereits gespeicherte Regeln enthalten die
     * alten Schluessel, deshalb werden sie beim Laden und beim Abgleich auf
     * den Event-Typ-Code umgeschrieben.
     */
    public const LEGACY_CATEGORY_ALIASES = [
        'traffic' => 'travel',
        'security' => 'safety',
    ];

    /** @var array<string, string>|null */
    protected static ?array $categoryOptionsCache = null;

    /**
     * Auswahlliste der Kategorien: alle aktiven Event-Typen, in der im Admin
     * gepflegten Reihenfolge. Neu angelegte Event-Typen stehen dadurch ohne
     * Codeaenderung in den Benachrichtigungs-Formularen zur Verfuegung.
     *
     * @return array<string, string> code => name
     */
    public static function categoryOptions(): array
    {
        if (static::$categoryOptionsCache !== null) {
            return static::$categoryOptionsCache;
        }

        try {
            $options = EventType::query()->active()->ordered()->pluck('name', 'code')->all();
        } catch (\Throwable $e) {
            // Z. B. waehrend Migrationen, wenn die Tabelle noch nicht existiert
            $options = [];
        }

        return static::$categoryOptionsCache = $options ?: self::CATEGORIES;
    }

    /**
     * Nur fuer Tests: erzwingt ein Neuladen der Auswahlliste.
     */
    public static function flushCategoryOptionsCache(): void
    {
        static::$categoryOptionsCache = null;
    }

    /**
     * Alten Kategorie-Schluessel auf den Code des Event-Typs umschreiben.
     */
    public static function normalizeCategory(string $category): string
    {
        return self::LEGACY_CATEGORY_ALIASES[$category] ?? $category;
    }

    /**
     * @param  array<int, string>|null  $categories
     * @return array<int, string>
     */
    public static function normalizeCategories(?array $categories): array
    {
        return collect($categories ?? [])
            ->map(fn ($category) => self::normalizeCategory((string) $category))
            ->unique()
            ->values()
            ->all();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRuleRecipient::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    public function getRiskLevelLabelsAttribute(): array
    {
        return collect($this->risk_levels ?? [])
            ->map(fn ($level) => self::RISK_LEVELS[$level] ?? $level)
            ->toArray();
    }

    public function getCategoryLabelsAttribute(): array
    {
        $options = self::categoryOptions();

        // Reihenfolge der Fallbacks: Name des Event-Typs, dann die alte
        // Beschriftung - so behalten Altbestaende ihr Label, auch wenn es zu
        // ihrem Schluessel keinen Event-Typ (mehr) gibt.
        return collect($this->categories ?? [])
            ->map(function ($cat) use ($options) {
                $code = self::normalizeCategory((string) $cat);

                return $options[$code]
                    ?? $options[$cat]
                    ?? self::CATEGORIES[$cat]
                    ?? self::CATEGORIES[$code]
                    ?? $cat;
            })
            ->toArray();
    }
}
