<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'iso_code',
        'iso3_code',
        'name_translations',
        'is_eu_member',
        'is_schengen_member',
        'currency_code',
        'currency_name',
        'currency_symbol',
        'phone_prefix',
        'languages',
        'timezone',
        'risk_factors',
        'travel_advisories',
        'climate_zones',
        'risk_profile',
        'population',
        'area_km2',
        'lat',
        'lng',
        'continent_id',
        'territory_type',
        'parent_country_id',
        'driving_side',
        'travel_info',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'is_eu_member' => 'boolean',
        'is_schengen_member' => 'boolean',
        'languages' => 'array',
        'risk_factors' => 'array',
        'travel_advisories' => 'array',
        'climate_zones' => 'array',
        'risk_profile' => 'array',
        'population' => 'integer',
        'area_km2' => 'decimal:2',
        'lat' => 'decimal:6',
        'lng' => 'decimal:6',
        'travel_info' => 'array',
    ];

    /**
     * Get the continent for this country.
     */
    public function continent(): BelongsTo
    {
        return $this->belongsTo(Continent::class);
    }

    /**
     * Mutterland eines abhaengigen Gebiets (z. B. Frankreich fuer Martinique).
     */
    public function parentCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'parent_country_id');
    }

    /**
     * Abhaengige Gebiete dieses Landes.
     */
    public function territories(): HasMany
    {
        return $this->hasMany(Country::class, 'parent_country_id');
    }

    /**
     * Bilder des Landes – Titelbild zuerst, dann die Galerie in ihrer Reihenfolge.
     */
    public function images(): HasMany
    {
        return $this->hasMany(CountryImage::class)
            ->orderByRaw("kind = 'hero' desc")
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Taxi- und Mobilitaets-Apps, die im Land verbreitet sind.
     */
    public function taxiApps()
    {
        return $this->belongsToMany(TaxiApp::class, 'country_taxi_app')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Mobilfunkanbieter, die im Land verbreitet sind.
     */
    public function mobileOperators()
    {
        return $this->belongsToMany(MobileOperator::class, 'country_mobile_operator')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Feiertage des Landes, nach Datum.
     */
    public function holidays(): HasMany
    {
        return $this->hasMany(CountryHoliday::class)->orderBy('date');
    }

    public function heroImage(): HasOne
    {
        return $this->hasOne(CountryImage::class)->where('kind', CountryImage::KIND_HERO);
    }

    /**
     * Flagge als SVG – die Flaggen liegen nicht im System, sondern kommen
     * ueber den ISO-Code von flagcdn.com.
     */
    public function getFlagUrlAttribute(): ?string
    {
        return $this->iso_code ? 'https://flagcdn.com/'.strtolower($this->iso_code).'.svg' : null;
    }

    /**
     * Flagge als Emoji aus den beiden Regional-Indicator-Zeichen des ISO-Codes.
     */
    public function getFlagEmojiAttribute(): ?string
    {
        $code = strtoupper((string) $this->iso_code);

        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return null;
        }

        return implode('', array_map(fn (string $letter) => mb_chr(0x1F1E6 + ord($letter) - ord('A')), str_split($code)));
    }

    /**
     * Titelbild fuer Apps und Feeds: das hochgeladene Titelbild, sonst das
     * bisherige Standardbild unter public/images/countries, sonst nichts.
     */
    public function getHeroImageUrlAttribute(): ?string
    {
        if ($hero = $this->heroImage) {
            return $hero->url();
        }

        $file = 'images/countries/'.strtolower((string) $this->iso_code).'.jpg';

        return $this->iso_code && file_exists(public_path($file)) ? asset($file) : null;
    }

    /**
     * Hinterlegte Landesgrenze (Polygon aus Natural Earth).
     * Read-only - die Daten kommen ausschliesslich aus countries:import-boundaries.
     */
    public function boundary(): HasOne
    {
        return $this->hasOne(CountryBoundary::class);
    }

    /**
     * Get the regions for this country.
     */
    public function sights(): HasMany
    {
        return $this->hasMany(Sight::class);
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    /**
     * Teilgebiete der Regionen (Inseln, Inselgruppen, Reisegebiete).
     */
    public function subregions(): HasMany
    {
        return $this->hasMany(Subregion::class);
    }

    /**
     * Get the cities for this country.
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Hauptstadt des Landes. Aus Altdaten sind manchmal mehrere Staedte als
     * Hauptstadt markiert, teils sogar Staedte eines anderen Landes – Vorrang
     * hat die Stadt mit Region, die zugleich Regionshauptstadt ist, dann die
     * zuerst angelegte. So ist das Ergebnis immer dasselbe.
     */
    public function capital(): HasOne
    {
        return $this->hasOne(City::class)
            ->where('is_capital', true)
            ->orderByRaw('region_id is null')
            ->orderByDesc('is_regional_capital')
            ->orderBy('id');
    }

    /**
     * Alle als Hauptstadt markierten Staedte, in derselben Reihenfolge wie
     * {@see capital()} – mehr als eine deutet auf Altdaten hin.
     */
    public function capitals(): HasMany
    {
        return $this->hasMany(City::class)
            ->where('is_capital', true)
            ->orderByRaw('region_id is null')
            ->orderByDesc('is_regional_capital')
            ->orderBy('id');
    }

    /**
     * Ob ein Punkt innerhalb der Landesgrenze liegt (Rechteck um die Grenze mit
     * etwas Spielraum). Ohne hinterlegte Grenze gilt jeder Punkt als passend.
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        $boundary = $this->boundary;

        if (! $boundary || $boundary->min_lat === null || $boundary->min_lng === null) {
            return true;
        }

        $margin = 0.3;

        return $lat >= $boundary->min_lat - $margin && $lat <= $boundary->max_lat + $margin
            && $lng >= $boundary->min_lng - $margin && $lng <= $boundary->max_lng + $margin;
    }

    /**
     * Koordinaten der Hauptstadt – dorthin setzt die Karte das Land und seine
     * landesweiten Ereignisse. null ohne Hauptstadt, ohne ihre Koordinaten oder
     * wenn sie ausserhalb der Landesgrenze liegt (falsch zugeordnete Altdaten).
     *
     * @return array{0: float, 1: float}|null
     */
    public function capitalCoordinates(): ?array
    {
        $capital = $this->capital;

        if (! $capital || $capital->lat === null || $capital->lng === null) {
            return null;
        }

        $lat = (float) $capital->lat;
        $lng = (float) $capital->lng;

        return $this->containsPoint($lat, $lng) ? [$lat, $lng] : null;
    }

    /**
     * Setzt die Koordinaten des Landes auf die seiner Hauptstadt. Ohne
     * verwendbare Hauptstadt bleiben sie unveraendert.
     */
    public function syncCoordinatesFromCapital(): bool
    {
        $this->unsetRelation('capital');
        $coordinates = $this->capitalCoordinates();

        if ($coordinates === null) {
            return false;
        }

        $this->lat = $coordinates[0];
        $this->lng = $coordinates[1];

        if (! $this->isDirty(['lat', 'lng'])) {
            return false;
        }

        return $this->save();
    }

    /**
     * Fluggesellschaften mit Sitz in diesem Land.
     */
    public function airlines(): HasMany
    {
        return $this->hasMany(Airline::class, 'home_country_id');
    }

    /**
     * Get the airports for this country.
     */
    public function airports(): HasMany
    {
        return $this->hasMany(Airport::class);
    }

    /**
     * Get the disaster events for this country.
     */
    public function disasterEvents(): HasMany
    {
        return $this->hasMany(DisasterEvent::class);
    }

    /**
     * Get the custom events for this country (many-to-many).
     */
    public function customEvents()
    {
        return $this->belongsToMany(CustomEvent::class, 'country_custom_event')
            ->withPivot(['latitude', 'longitude', 'location_note', 'use_default_coordinates'])
            ->withTimestamps();
    }

    /**
     * Get the name in a specific language.
     */
    public function getName(string $language = 'de'): string
    {
        $translations = $this->name_translations ?? [];

        return $translations[$language] ?? $translations['en'] ?? $this->iso_code ?? 'Unknown';
    }

    /**
     * Scope a query to only include EU member countries.
     */
    public function scopeEuMembers($query)
    {
        return $query->where('is_eu_member', true);
    }

    /**
     * Scope a query to search countries by name or code.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('iso_code', 'like', "%{$search}%")
                ->orWhere('iso3_code', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to only include countries by continent.
     */
    public function scopeByContinent($query, $continentId)
    {
        return $query->where('continent_id', $continentId);
    }

    /**
     * Get the overall risk level (max of security, health, natural hazards).
     */
    public function getOverallRiskLevelAttribute(): ?int
    {
        $profile = $this->risk_profile;
        if (! $profile) {
            return null;
        }

        $levels = array_filter([
            $profile['security']['overall_risk_level'] ?? null,
            $profile['health']['health_risk_level'] ?? null,
            $profile['natural_hazards']['natural_hazard_level'] ?? null,
        ]);

        return $levels ? (int) max($levels) : null;
    }

    /**
     * Check if the country has a risk profile.
     */
    public function getHasRiskProfileAttribute(): bool
    {
        return ! empty($this->risk_profile);
    }

    /**
     * Get the label for a risk level.
     */
    public static function getRiskLevelLabel(?int $level): string
    {
        return match ($level) {
            1 => 'Sehr niedrig',
            2 => 'Niedrig',
            3 => 'Mittel',
            4 => 'Hoch',
            5 => 'Sehr hoch',
            default => 'Nicht bewertet',
        };
    }

    /**
     * Get the Filament color for a risk level.
     */
    public static function getRiskLevelColor(?int $level): string
    {
        return match ($level) {
            1 => 'success',
            2 => 'info',
            3 => 'warning',
            4, 5 => 'danger',
            default => 'gray',
        };
    }
}
