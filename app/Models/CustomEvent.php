<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class CustomEvent extends Model implements Feedable
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'version',
        'version_group_uuid',
        'version_parent_id',
        'superseded_by_id',
        'superseded_at',
        'activated_at',
        'version_note',
        'title',
        'title_translations',
        'description',
        'event_type',
        'event_type_id',
        'event_category_id',
        'data_source',
        'data_source_id',
        'country_id',
        'latitude',
        'longitude',
        'is_nationwide',
        'marker_color',
        'marker_icon',
        'icon_color',
        'marker_size',
        'popup_content',
        'popup_content_translations',
        'source',
        'source_show_frontend',
        'source_link_text',
        'source_link_url',
        'source_links',
        'start_date',
        'end_date',
        'is_active',
        'archived',
        'archived_at',
        'priority',
        'severity',
        'category',
        'tags',
        'created_by',
        'updated_by',
        'selected_display_event_type_id',
        'api_client_id',
        'review_status',
        'reviewed_at',
        'reviewed_by',
        'customer_id',
        'visible_community',
        'community_start_date',
        'community_end_date',
        'visible_organization',
        'organization_start_date',
        'organization_end_date',
    ];

    protected $casts = [
        'version' => 'integer',
        'superseded_at' => 'datetime',
        'activated_at' => 'datetime',
        'title_translations' => 'array',
        'popup_content_translations' => 'array',
        'latitude' => 'decimal:16',
        'longitude' => 'decimal:16',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'archived' => 'boolean',
        'is_nationwide' => 'boolean',
        'source_show_frontend' => 'boolean',
        'source_links' => 'array',
        'archived_at' => 'datetime',
        'tags' => 'array',
        'reviewed_at' => 'datetime',
        'visible_community' => 'boolean',
        'community_start_date' => 'date',
        'community_end_date' => 'date',
        'visible_organization' => 'boolean',
        'organization_start_date' => 'date',
        'organization_end_date' => 'date',
    ];

    protected $attributes = [
        'marker_color' => '#FF0000',
        'marker_icon' => 'fa-map-marker',
        'icon_color' => '#FFFFFF',
        'marker_size' => 'medium',
        'is_active' => true,
        'priority' => 'medium',
        'severity' => 'medium',
    ];

    /**
     * Configured event translation locales (z. B. ['de', 'en', 'nl']).
     */
    public static function translationLocales(): array
    {
        $raw = (string) config('app.event_languages', 'de,en,nl');

        $locales = array_values(array_filter(array_map(
            fn ($code) => strtolower(trim($code)),
            explode(',', $raw)
        )));

        // Ausgangssprache immer enthalten und an erster Stelle.
        $source = static::sourceLocale();
        $locales = array_values(array_unique(array_merge([$source], $locales)));

        return $locales;
    }

    /**
     * Ausgangssprache, aus der übersetzt wird und auf die zurückgefallen wird.
     */
    public static function sourceLocale(): string
    {
        return strtolower((string) config('app.event_source_language', 'de'));
    }

    /**
     * Anzeigename für ein Sprachkürzel, optional mit Flaggen-Emoji.
     * Fallback: Großbuchstaben des Codes.
     */
    public static function localeLabel(string $locale, bool $withFlag = true): string
    {
        $locale = strtolower(trim($locale));

        $flags = [
            'de' => '🇩🇪', 'en' => '🇬🇧', 'nl' => '🇳🇱', 'fr' => '🇫🇷',
            'es' => '🇪🇸', 'it' => '🇮🇹', 'pt' => '🇵🇹', 'pl' => '🇵🇱',
        ];
        $names = [
            'de' => 'Deutsch', 'en' => 'English', 'nl' => 'Nederlands', 'fr' => 'Français',
            'es' => 'Español', 'it' => 'Italiano', 'pt' => 'Português', 'pl' => 'Polski',
        ];

        $name = $names[$locale] ?? strtoupper($locale);

        if ($withFlag && isset($flags[$locale])) {
            return $flags[$locale] . ' ' . $name;
        }

        return $name;
    }

    /**
     * Nur das Flaggen-Emoji für ein Sprachkürzel (leer, wenn unbekannt).
     */
    public static function localeFlag(string $locale): string
    {
        return [
            'de' => '🇩🇪', 'en' => '🇬🇧', 'nl' => '🇳🇱', 'fr' => '🇫🇷',
            'es' => '🇪🇸', 'it' => '🇮🇹', 'pt' => '🇵🇹', 'pl' => '🇵🇱',
        ][strtolower(trim($locale))] ?? '🌐';
    }

    /**
     * Übersetzten Wert für eine Sprache auflösen (mit Fallback-Kette:
     * gewünschte Sprache -> Ausgangssprache -> erster vorhandener Wert -> Rohwert).
     */
    protected function resolveTranslation(?array $translations, ?string $fallback, ?string $locale = null): ?string
    {
        $locale = strtolower($locale ?: app()->getLocale());
        $source = static::sourceLocale();

        if (is_array($translations)) {
            if (! empty($translations[$locale])) {
                return $translations[$locale];
            }
            if (! empty($translations[$source])) {
                return $translations[$source];
            }
            foreach ($translations as $value) {
                if (! empty($value)) {
                    return $value;
                }
            }
        }

        return $fallback;
    }

    /**
     * Titel in der aktuellen (oder angegebenen) Sprache.
     */
    public function getTitle(?string $locale = null): ?string
    {
        return $this->resolveTranslation($this->title_translations, $this->attributes['title'] ?? null, $locale);
    }

    /**
     * Beschreibung (popup_content) in der aktuellen (oder angegebenen) Sprache.
     */
    public function getPopupContent(?string $locale = null): ?string
    {
        return $this->resolveTranslation($this->popup_content_translations, $this->attributes['popup_content'] ?? null, $locale);
    }

    /**
     * Locale-bewusster Accessor: $event->title liefert die Übersetzung
     * passend zur aktiven App-Locale (Fallback auf Ausgangssprache/Rohwert).
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->resolveTranslation($this->title_translations, $value),
        );
    }

    /**
     * Locale-bewusster Accessor für die Beschreibung (popup_content).
     */
    protected function popupContent(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->resolveTranslation($this->popup_content_translations, $value),
        );
    }

    /**
     * Hält die JSON-Übersetzungen und die Legacy-Spalten konsistent:
     * - sind Übersetzungen gesetzt, spiegelt die Spalte die Ausgangssprache;
     * - wird nur die Spalte beschrieben (Import/API), wandert ihr Wert in die JSON.
     */
    protected function syncTranslationColumns(): void
    {
        $source = static::sourceLocale();

        foreach ([
            'title' => 'title_translations',
            'popup_content' => 'popup_content_translations',
        ] as $column => $jsonColumn) {
            $translations = $this->{$jsonColumn};
            $translations = is_array($translations) ? $translations : [];
            $rawColumn = $this->attributes[$column] ?? null;

            if (array_key_exists($source, $translations) && filled($translations[$source])) {
                // Übersetzungen sind führend -> Spalte mit Ausgangssprache spiegeln.
                $this->attributes[$column] = $translations[$source];
            } elseif (filled($rawColumn)) {
                // Legacy-/Direktschreibzugriff -> Ausgangssprache in die JSON übernehmen.
                $translations[$source] = $rawColumn;
                $this->{$jsonColumn} = $translations;
            }
        }
    }

    /**
     * Alle Quellen-Links in einheitlicher Form.
     * Faellt auf die alten Einzelspalten zurueck, solange source_links leer ist.
     *
     * @return array<int, array{show_frontend: bool, link_text: ?string, link_url: ?string}>
     */
    public function normalizedSourceLinks(): array
    {
        $links = is_array($this->source_links) ? $this->source_links : [];

        if (empty($links) && (filled($this->source_link_text) || filled($this->source_link_url))) {
            $links = [[
                'show_frontend' => $this->source_show_frontend ?? true,
                'link_text' => $this->source_link_text,
                'link_url' => $this->source_link_url,
            ]];
        }

        return collect($links)
            ->filter(fn ($link) => is_array($link))
            ->map(fn (array $link) => [
                'show_frontend' => (bool) ($link['show_frontend'] ?? true),
                'link_text' => $link['link_text'] ?? null,
                'link_url' => $link['link_url'] ?? null,
            ])
            ->filter(fn (array $link) => filled($link['link_text']) || filled($link['link_url']))
            ->values()
            ->all();
    }

    /**
     * Nur die Links, die im Frontend ausgegeben werden duerfen.
     *
     * @return array<int, array{show_frontend: bool, link_text: ?string, link_url: ?string}>
     */
    public function visibleSourceLinks(): array
    {
        return collect($this->normalizedSourceLinks())
            ->filter(fn (array $link) => $link['show_frontend'])
            ->values()
            ->all();
    }

    /**
     * Alte Einzelspalten aus dem ersten Listeneintrag weiterpflegen, damit
     * bestehende API-Konsumenten unveraendert weiterlaufen.
     */
    protected function syncLegacySourceColumns(): void
    {
        if (! array_key_exists('source_links', $this->attributes)) {
            return;
        }

        $first = $this->normalizedSourceLinks()[0] ?? null;

        $this->attributes['source_show_frontend'] = $first ? (bool) $first['show_frontend'] : true;
        $this->attributes['source_link_text'] = $first['link_text'] ?? null;
        $this->attributes['source_link_url'] = $first['link_url'] ?? null;
    }

    /**
     * Prozessweiter Cache fuer Region-/City-Lookups der Standort-Datensaetze.
     *
     * @var array<string, array<int, mixed>>
     */
    protected static array $locationLookupCache = ['region' => [], 'city' => []];

    protected static function lookupRegion(?int $id): ?Region
    {
        if (! $id) {
            return null;
        }

        return static::$locationLookupCache['region'][$id] ??= Region::find($id);
    }

    protected static function lookupCity(?int $id): ?City
    {
        if (! $id) {
            return null;
        }

        return static::$locationLookupCache['city'][$id] ??= City::find($id);
    }

    /**
     * Alle Standort-Datensaetze des Events - eine Zeile je Pivot-Eintrag.
     * Pro Land sind beliebig viele Datensaetze moeglich (Region, Stadt, Koordinaten, Notiz).
     *
     * Koordinaten-Kaskade bei "Standard-Koordinaten": Stadt > Region > Hauptstadt > Land.
     * Sonst die im Datensatz hinterlegten Koordinaten.
     *
     * @return array<int, array<string, mixed>>
     */
    public function locationRecords(?string $locale = 'de'): array
    {
        $this->loadMissing('countries');

        return $this->countries->map(function (Country $country) use ($locale) {
            $pivot = $country->pivot;

            $region = static::lookupRegion($pivot?->region_id ? (int) $pivot->region_id : null);
            $city = static::lookupCity($pivot?->city_id ? (int) $pivot->city_id : null);

            [$lat, $lng] = $this->resolveLocationCoordinates($country, $region, $city);

            $countryName = $country->getName($locale);
            $labelParts = array_values(array_filter([
                $countryName,
                $region?->getName($locale),
                $city?->getName($locale),
            ]));

            return [
                'pivot_id' => $pivot?->id,
                'country_id' => $country->id,
                'country_name' => $countryName,
                'iso_code' => $country->iso_code,
                'region_id' => $region?->id,
                'region_name' => $region?->getName($locale),
                'city_id' => $city?->id,
                'city_name' => $city?->getName($locale),
                'latitude' => $lat,
                'longitude' => $lng,
                'location_note' => $pivot?->location_note,
                'use_default_coordinates' => (bool) ($pivot?->use_default_coordinates),
                // Anzeigefertige Bezeichnung, z.B. "Spanien - Katalonien - Barcelona"
                'label' => implode(' – ', $labelParts),
            ];
        })->all();
    }

    /**
     * Koordinaten eines einzelnen Standort-Datensatzes aufloesen.
     *
     * @return array{0: ?float, 1: ?float}
     */
    protected function resolveLocationCoordinates(Country $country, ?Region $region, ?City $city): array
    {
        $pivot = $country->pivot;

        if ($pivot && ! $pivot->use_default_coordinates) {
            if ($pivot->latitude && $pivot->longitude) {
                return [(float) $pivot->latitude, (float) $pivot->longitude];
            }
        } else {
            // Standard-Koordinaten: Stadt > Region > Hauptstadt > Land
            if ($city && $city->lat && $city->lng) {
                return [(float) $city->lat, (float) $city->lng];
            }

            if ($region && $region->lat && $region->lng) {
                return [(float) $region->lat, (float) $region->lng];
            }

            if ($country->capital && $country->capital->lat && $country->capital->lng) {
                return [(float) $country->capital->lat, (float) $country->capital->lng];
            }

            if ($country->lat && $country->lng) {
                return [(float) $country->lat, (float) $country->lng];
            }
        }

        // Letzter Fallback: Event-Koordinaten
        if ($this->latitude && $this->longitude) {
            return [(float) $this->latitude, (float) $this->longitude];
        }

        return [null, null];
    }

    /**
     * Kurze Textliste aller Standorte - fuer Benachrichtigungen und Suchergebnisse.
     * z.B. "Spanien – Katalonien – Barcelona, Frankreich – Paris"
     */
    public function locationSummary(?string $locale = 'de'): string
    {
        return collect($this->locationRecords($locale))
            ->pluck('label')
            ->filter()
            ->unique()
            ->implode(', ');
    }

    /**
     * Gehoert das Land zu diesem Ereignis? Beruecksichtigt die Mehrfachzuordnung
     * (countries) und die alte Einzelspalte country_id.
     */
    public function coversCountry(int $countryId): bool
    {
        if ((int) $this->country_id === $countryId) {
            return true;
        }

        $this->loadMissing('countries');

        return $this->countries->contains('id', $countryId);
    }

    /**
     * Alle Laender-IDs des Ereignisses (Mehrfachzuordnung plus Einzelspalte).
     *
     * @return array<int, int>
     */
    public function coveredCountryIds(): array
    {
        $this->loadMissing('countries');

        $ids = $this->countries->pluck('id')->all();

        if ($this->country_id) {
            $ids[] = (int) $this->country_id;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    // ------------------------------------------------------------------
    // Versionierung
    //
    // Ereignisse werden nie ueberschrieben: eine Aenderung entsteht als neue
    // Zeile mit derselben version_group_uuid und der naechsten Versionsnummer.
    // Wird die neue Version aktiviert, deaktiviert sie ihre Vorgaenger und
    // markiert sie als abgeloest - lesbar bleiben sie dauerhaft.
    // ------------------------------------------------------------------

    /**
     * Alle Versionen desselben Ereignisses - inklusive dieser hier.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(static::class, 'version_group_uuid', 'version_group_uuid')
            ->orderByDesc('version');
    }

    /**
     * Version, aus der diese hier dupliziert wurde.
     */
    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(static::class, 'version_parent_id');
    }

    /**
     * Version, die diese hier abgeloest hat.
     */
    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(static::class, 'superseded_by_id');
    }

    /**
     * Ist das die aktuell gueltige Version der Gruppe?
     */
    public function isCurrentVersion(): bool
    {
        return $this->superseded_by_id === null;
    }

    /**
     * Wurde diese Version jemals aktiv geschaltet? Nur solche Versionen
     * duerfen Kunden in der Historie sehen - Entwuerfe bleiben intern.
     */
    public function isPublishedVersion(): bool
    {
        return $this->activated_at !== null;
    }

    /**
     * Scope: nur die jeweils aktuellste Version je Gruppe.
     */
    public function scopeCurrentVersion($query)
    {
        return $query->whereNull('superseded_by_id');
    }

    /**
     * Scope: nur abgeloeste (historische) Versionen.
     */
    public function scopeSupersededVersion($query)
    {
        return $query->whereNotNull('superseded_by_id');
    }

    /**
     * Scope: nur Versionen, die mindestens einmal veroeffentlicht waren.
     */
    public function scopePublishedVersion($query)
    {
        return $query->whereNotNull('activated_at');
    }

    /**
     * Historie der Gruppe fuer die Anzeige - neueste Version zuerst.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public function versionHistory(bool $onlyPublished = false): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::query()
            ->where('version_group_uuid', $this->version_group_uuid ?: $this->uuid)
            ->orderByDesc('version')
            ->orderByDesc('id');

        if ($onlyPublished) {
            $query->publishedVersion();
        }

        return $query->get();
    }

    /**
     * Die aktuell gueltige Version der Gruppe - fuer Zugriffe, die eine
     * feste Kennung (z. B. eine UUID aus der API) mitbringen und trotzdem
     * immer den aktuellen Stand treffen sollen.
     */
    public function resolveCurrentVersion(): static
    {
        if ($this->isCurrentVersion() || ! $this->version_group_uuid) {
            return $this;
        }

        return static::query()
            ->where('version_group_uuid', $this->version_group_uuid)
            ->currentVersion()
            ->orderByDesc('version')
            ->first() ?? $this;
    }

    /**
     * Naechste freie Versionsnummer der Gruppe.
     */
    public function nextVersionNumber(): int
    {
        return (int) static::withTrashed()
            ->where('version_group_uuid', $this->version_group_uuid)
            ->max('version') + 1;
    }

    /**
     * Diese Version aktivieren: sie wird gueltig, alle Vorgaenger werden
     * deaktiviert und als abgeloest gekennzeichnet.
     */
    public function activateVersion(?int $userId = null): void
    {
        $this->forceFill([
            'is_active' => true,
            'superseded_by_id' => null,
            'superseded_at' => null,
        ]);

        if ($userId) {
            $this->updated_by = $userId;
        }

        // activated_at und das Abloesen der Vorgaenger uebernehmen die
        // Model-Hooks, damit auch ein einfaches Umlegen des "Aktiv"-Schalters
        // im Formular denselben Weg nimmt.
        $this->save();
    }

    /**
     * Alle uebrigen Versionen der Gruppe deaktivieren und als abgeloest markieren.
     */
    public function supersedeOtherVersions(): void
    {
        if (! $this->version_group_uuid || ! $this->is_active) {
            return;
        }

        $version = (int) $this->version;
        $id = $this->getKey();

        static::query()
            ->where('version_group_uuid', $this->version_group_uuid)
            ->whereKeyNot($id)
            ->where(function ($query) use ($version) {
                // Aktive Vorgaenger ebenso wie aeltere, noch nicht abgeloeste
                // Entwuerfe - danach bleibt genau eine gueltige Version uebrig.
                $query->where('is_active', true)
                    ->orWhere(function ($sub) use ($version) {
                        $sub->where('version', '<', $version)
                            ->whereNull('superseded_by_id');
                    });
            })
            ->update([
                'is_active' => false,
                'superseded_by_id' => $id,
                'superseded_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Country relation (single - for backward compatibility).
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Countries relation (many-to-many).
     */
    public function countries()
    {
        // 'id' im Pivot, damit einzelne Standort-Datensaetze adressierbar sind -
        // pro Land sind mehrere Zeilen erlaubt.
        return $this->belongsToMany(Country::class, 'country_custom_event')
            ->withPivot(['id', 'latitude', 'longitude', 'location_note', 'use_default_coordinates', 'region_id', 'city_id'])
            ->withTimestamps();
    }

    /**
     * Regions relation (many-to-many).
     */
    public function regions()
    {
        return $this->belongsToMany(Region::class, 'custom_event_region')
            ->withPivot(['latitude', 'longitude', 'location_note', 'use_default_coordinates'])
            ->withTimestamps();
    }

    /**
     * Cities relation (many-to-many).
     */
    public function cities()
    {
        return $this->belongsToMany(City::class, 'city_custom_event')
            ->withPivot(['latitude', 'longitude', 'location_note', 'use_default_coordinates'])
            ->withTimestamps();
    }

    /**
     * Event type relation (single - for backward compatibility).
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Event types relation (many-to-many).
     */
    public function eventTypes()
    {
        return $this->belongsToMany(EventType::class, 'custom_event_event_type')
            ->withTimestamps();
    }

    public function orgNodes()
    {
        return $this->belongsToMany(OrgNode::class, 'custom_event_org_node')->withPivot('start_date', 'end_date');
    }

    /**
     * Event category relation.
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * Get the user who created this event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this event.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Customer relation.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * API Client relation.
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * Reviewer relation.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope: pending review.
     */
    public function scopePendingReview($query)
    {
        return $query->where('review_status', 'pending_review');
    }

    /**
     * Scope: approved.
     */
    public function scopeApproved($query)
    {
        return $query->where('review_status', 'approved');
    }

    /**
     * Approve this event.
     */
    public function approve(int $userId): void
    {
        $this->update([
            'review_status' => 'approved',
            'is_active' => true,
            'reviewed_at' => now(),
            'reviewed_by' => $userId,
        ]);
    }

    /**
     * Reject this event.
     */
    public function reject(int $userId): void
    {
        $this->update([
            'review_status' => 'rejected',
            'is_active' => false,
            'reviewed_at' => now(),
            'reviewed_by' => $userId,
        ]);
    }

    /**
     * Get the correct event type code, resolving 'general' from EventType relationship
     */
    public function getCorrectEventType()
    {
        // Wenn event_type 'general' ist, hole den korrekten Code aus der Beziehung
        if ($this->attributes['event_type'] === 'general' && $this->event_type_id) {
            $eventType = EventType::find($this->event_type_id);
            return $eventType && $eventType->code ? $eventType->code : 'other';
        }

        return $this->attributes['event_type'] ?: 'other';
    }

    /**
     * Get the display icon based on settings and selected event types
     * Returns always a string (never an array)
     */
    public function getDisplayIcon()
    {
        $settings = EventDisplaySetting::current();

        // Wenn mehrere Event-Typen ausgewählt sind
        if ($this->eventTypes && $this->eventTypes->count() > 1) {
            // Strategie: manual_select - Verwende manuell ausgewähltes Icon
            if ($settings->shouldShowManualSelection() && $this->selected_display_event_type_id) {
                $selectedEventType = EventType::find($this->selected_display_event_type_id);
                if ($selectedEventType && $selectedEventType->icon) {
                    return $selectedEventType->icon;
                }
            }

            // Strategie: multi_event_type - Verwende spezielles Multi-Event Icon
            if ($settings->shouldUseMultiEventType() && $settings->multi_event_type_id) {
                $multiEventType = EventType::find($settings->multi_event_type_id);
                if ($multiEventType && $multiEventType->icon) {
                    return $multiEventType->icon;
                }
            }

            // Strategie: show_all - Verwende erstes Icon (alle Icons in getAllIcons())
            if ($settings->shouldShowAllIcons()) {
                $firstIcon = $this->eventTypes->first()->icon;
                if ($firstIcon) {
                    return $firstIcon;
                }
            }
        }

        // Standard: Erstes Icon oder Fallback
        if ($this->eventTypes && $this->eventTypes->isNotEmpty()) {
            $firstIcon = $this->eventTypes->first()->icon;
            if ($firstIcon) {
                return $firstIcon;
            }
        }

        // Legacy: Single event type
        if ($this->eventType && $this->eventType->icon) {
            return $this->eventType->icon;
        }

        // Fallback auf marker_icon
        return $this->marker_icon ?? 'fa-map-marker';
    }

    /**
     * Get all icons for multi-event display (for show_all strategy)
     */
    public function getAllIcons(): array
    {
        if ($this->eventTypes && $this->eventTypes->isNotEmpty()) {
            return $this->eventTypes->map(function ($eventType) {
                return [
                    'icon' => $eventType->icon ?? 'fa-map-marker',
                    'color' => $eventType->color ?? '#FF0000',
                    'name' => $eventType->name,
                ];
            })->toArray();
        }

        return [[
            'icon' => $this->marker_icon ?? 'fa-map-marker',
            'color' => $this->marker_color ?? '#FF0000',
            'name' => $this->eventType?->name ?? 'Standard',
        ]];
    }

    /**
     * Set the event_type automatically when event_type_id is set
     */
    public function setEventTypeIdAttribute($value)
    {
        $this->attributes['event_type_id'] = $value;
        
        // Automatisch event_type aus EventType-Beziehung setzen
        if ($value) {
            $eventType = EventType::find($value);
            if ($eventType && $eventType->code) {
                $this->attributes['event_type'] = $eventType->code;
            }
        }
    }

    /**
     * Get the labels for this event.
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'custom_event_label')
            ->withTimestamps();
    }

    /**
     * Scope: exclude customer-owned events (only global/admin events).
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('customer_id');
    }

    /**
     * Scope: only events belonging to a specific customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope a query to only include active events.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include non-archived events.
     */
    public function scopeNotArchived($query)
    {
        return $query->where('archived', false);
    }

    /**
     * Scope a query to only include archived events.
     */
    public function scopeArchived($query)
    {
        return $query->where('archived', true);
    }

    /**
     * Scope a query to include visible events (active and not expired archived).
     * Archived events are visible for 1 year after their end_date.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                // Nicht-archivierte Events
                $q->where('archived', false)
                  // Oder archivierte Events, die noch nicht abgelaufen sind
                  ->orWhere(function ($subQ) {
                      $subQ->where('archived', true)
                           ->where(function ($dateQ) {
                               // Events mit Enddatum: 1 Jahr nach Enddatum noch sichtbar
                               $dateQ->whereNotNull('end_date')
                                     ->where('end_date', '>=', now()->subYear())
                               // Events ohne Enddatum: 1 Jahr nach Archivierungsdatum noch sichtbar
                               ->orWhere(function ($archQ) {
                                   $archQ->whereNull('end_date')
                                         ->whereNotNull('archived_at')
                                         ->where('archived_at', '>=', now()->subYear());
                               });
                           });
                  });
            });
    }

    /**
     * Scope a query to only include events within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($subQ) use ($startDate, $endDate) {
                  $subQ->where('start_date', '<=', $startDate)
                       ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Scope a query to only include events by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include events by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the clicks for this event.
     */
    public function clicks()
    {
        return $this->hasMany(EventClick::class);
    }

    /**
     * Get click statistics for this event.
     */
    public function getClickStatistics()
    {
        return [
            'total' => $this->clicks()->count(),
            'list' => $this->clicks()->byType('list')->count(),
            'map_marker' => $this->clicks()->byType('map_marker')->count(),
            'details_button' => $this->clicks()->byType('details_button')->count(),
            'today' => $this->clicks()->whereDate('clicked_at', today())->count(),
            'this_week' => $this->clicks()->whereBetween('clicked_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => $this->clicks()->whereMonth('clicked_at', now()->month)->whereYear('clicked_at', now()->year)->count(),
        ];
    }

    /**
     * Get the marker size options.
     */
    public static function getMarkerSizeOptions(): array
    {
        return [
            'small' => 'Klein',
            'medium' => 'Mittel',
            'large' => 'Groß',
        ];
    }

    /**
     * Get the priority options.
     */
    public static function getPriorityOptions(): array
    {
        return [
            'info' => 'Information',
            'low' => 'Niedrig',
            'medium' => 'Mittel',
            'high' => 'Hoch',
        ];
    }

    /**
     * Get the event type options.
     */
    public static function getEventTypeOptions(): array
    {
        return EventType::active()
            ->ordered()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get legacy event type options for backward compatibility.
     */
    public static function getLegacyEventTypeOptions(): array
    {
        return [
            'earthquake' => 'Erdbeben',
            'hurricane' => 'Hurrikan',
            'flood' => 'Überschwemmung',
            'wildfire' => 'Waldbrand',
            'volcano' => 'Vulkan',
            'drought' => 'Dürre',
            'exercise' => 'Übung',
            'other' => 'Sonstiges',
        ];
    }

    /**
     * Get the severity options.
     */
    public static function getSeverityOptions(): array
    {
        return [
            'low' => 'Niedrig',
            'medium' => 'Mittel',
            'high' => 'Hoch',
        ];
    }

    /**
     * Archive the event.
     */
    public function archive(): void
    {
        $this->update([
            'archived' => true,
            'archived_at' => now(),
        ]);
    }

    /**
     * Unarchive the event.
     */
    public function unarchive(): void
    {
        $this->update([
            'archived' => false,
            'archived_at' => null,
        ]);
    }

    /**
     * Check if the event is still visible (considering archive rules).
     */
    public function isVisible(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->archived) {
            return true;
        }

        // For archived events, check if they're still within the 1-year visibility period
        $referenceDate = $this->end_date ?: $this->archived_at;

        if (!$referenceDate) {
            return false;
        }

        return $referenceDate->gte(now()->subYear());
    }

    /**
     * Get all active events for the main feed.
     */
    public static function getFeedItems()
    {
        return static::active()
            ->notArchived()
            ->global()
            ->with(['country', 'eventTypes', 'creator'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get high-priority events for the critical feed.
     */
    public static function getCriticalFeedItems()
    {
        return static::active()
            ->notArchived()
            ->global()
            ->whereIn('priority', ['high'])
            ->with(['country', 'eventTypes', 'creator'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Convert the event to a FeedItem.
     */
    public function toFeedItem(): FeedItem
    {
        return FeedItem::create()
            ->id($this->id)
            ->title($this->getFeedTitle())
            ->summary($this->getFeedSummary())
            ->updated($this->updated_at)
            ->link(url("/events/{$this->id}"))
            ->authorName($this->creator?->name ?? 'System')
            ->category($this->getFeedCategories());
    }

    /**
     * Get formatted title with country name for feed.
     */
    public function getFeedTitle(): string
    {
        $title = $this->title;

        // Add country name if available
        if ($this->country) {
            $title .= ' - ' . $this->country->name;
        } elseif ($this->countries && $this->countries->count() > 0) {
            $countryNames = $this->countries->pluck('name')->take(3)->implode(', ');
            if ($this->countries->count() > 3) {
                $countryNames .= ' +' . ($this->countries->count() - 3);
            }
            $title .= ' - ' . $countryNames;
        }

        return $title;
    }

    /**
     * Get sanitized description suitable for feeds.
     */
    public function getFeedSummary(): string
    {
        if (empty($this->description)) {
            return 'No description available.';
        }

        // Strip HTML tags and decode entities
        $summary = strip_tags($this->description);
        $summary = html_entity_decode($summary, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Limit length to 500 characters
        if (strlen($summary) > 500) {
            $summary = substr($summary, 0, 497) . '...';
        }

        return $summary;
    }

    /**
     * Get array of event type names for feed categories.
     */
    public function getFeedCategories(): string
    {
        $categories = [];

        // Add event types from many-to-many relationship
        if ($this->eventTypes && $this->eventTypes->isNotEmpty()) {
            $categories = $this->eventTypes->pluck('name')->toArray();
        }
        // Fallback to single event type
        elseif ($this->eventType) {
            $categories[] = $this->eventType->name;
        }

        // Add priority as category
        if ($this->priority) {
            $categories[] = ucfirst($this->priority) . ' Priority';
        }

        return implode(', ', $categories);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->uuid)) {
                $event->uuid = Str::uuid();
            }

            // Ein neu angelegtes Ereignis eroeffnet seine eigene Versionsgruppe.
            if (empty($event->version_group_uuid)) {
                $event->version_group_uuid = $event->uuid;
            }

            if (empty($event->version)) {
                $event->version = 1;
            }

            if ($event->is_active && empty($event->activated_at)) {
                $event->activated_at = now();
            }
        });

        // Aktivierungszeitpunkt festhalten - der Benachrichtigungslauf haengt
        // daran, damit auch spaeter freigeschaltete Versionen noch versendet werden.
        static::updating(function (CustomEvent $event) {
            if ($event->isDirty('is_active') && $event->is_active) {
                $event->activated_at = now();
            }
        });

        // Nach dem Speichern die Vorgaengerversionen abloesen, damit ein
        // Ereignis nie doppelt erscheint.
        static::saved(function (CustomEvent $event) {
            if (! $event->is_active) {
                return;
            }

            if ($event->wasRecentlyCreated || $event->wasChanged(['is_active', 'activated_at'])) {
                $event->supersedeOtherVersions();
            }
        });

        // Übersetzungs-JSON und Legacy-Spalten (title/popup_content) synchron halten.
        static::saving(function (CustomEvent $event) {
            $event->syncTranslationColumns();
            $event->syncLegacySourceColumns();
        });

        // Automatically set archived_at when archiving
        static::updating(function ($event) {
            if ($event->isDirty('archived')) {
                if ($event->archived && !$event->archived_at) {
                    $event->archived_at = now();
                } elseif (!$event->archived) {
                    $event->archived_at = null;
                }
            }
        });
    }
}
