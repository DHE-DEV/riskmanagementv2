<?php

namespace App\Support\AdminV2;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\AirportCode;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\MasterDataChange;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Die Stammdaten-Bereiche des Admin-Bereichs – eine Liste fuer Navigation,
 * Routen und Seiten. "routes" nennt den Routen-Namen eines umgezogenen
 * Bereichs; solange er fehlt, zeigt der Bereich einen Hinweis und "legacy"
 * den Weg zu seiner Seite im bisherigen Admin.
 */
class MasterData
{
    /** Richtung einer Verknuepfung Airline <-> Flughafen */
    public const LINK_DIRECTIONS = [
        'both' => 'Abflug und Ankunft',
        'from' => 'Abflug',
        'to' => 'Ankunft',
    ];

    /**
     * @return array<string, array{label: string, icon: string, description: string, legacy: ?string, routes?: string}>
     */
    public static function sections(): array
    {
        return [
            'continents' => [
                'label' => 'Kontinente',
                'icon' => 'globe-europe-africa',
                'description' => 'Die Kontinente, denen Länder zugeordnet sind.',
                'legacy' => '/admin/continents',
                'routes' => 'adminv2.master-data.continents',
            ],
            'countries' => [
                'label' => 'Länder',
                'icon' => 'flag',
                'description' => 'Länder mit ISO-Codes, Übersetzungen, Koordinaten und Risikoprofil.',
                'legacy' => '/admin/countries',
                'routes' => 'adminv2.master-data.countries',
            ],
            'regions' => [
                'label' => 'Regionen',
                'icon' => 'map',
                'description' => 'Regionen und Bundesstaaten der Länder.',
                'legacy' => '/admin/regions',
                'routes' => 'adminv2.master-data.regions',
            ],
            'cities' => [
                'label' => 'Städte',
                'icon' => 'building-office-2',
                'description' => 'Städte mit Zuordnung zu Land und Region.',
                'legacy' => '/admin/cities',
                'routes' => 'adminv2.master-data.cities',
            ],
            'sights' => [
                'label' => 'Sehenswürdigkeiten',
                'icon' => 'camera',
                'description' => 'Sehenswürdigkeiten und Unternehmungen je Land, Region und Stadt.',
                'legacy' => null,
                'routes' => 'adminv2.master-data.sights',
            ],
            'airports' => [
                'label' => 'Flughäfen',
                'icon' => 'paper-airplane',
                'description' => 'Die gepflegten Flughäfen mit Lage, Lounges, Mobilität, Hotels und Airlines.',
                'legacy' => '/admin/airports',
                'routes' => 'adminv2.master-data.airports',
            ],
            'airport-codes' => [
                'label' => 'Flughafen-Codes',
                'icon' => 'hashtag',
                'description' => 'Das vollständige Verzeichnis aller Flugplätze mit IATA-, ICAO- und weiteren Codes.',
                'legacy' => '/admin/airport-codes',
                'routes' => 'adminv2.master-data.airport-codes',
            ],
            'airlines' => [
                'label' => 'Airlines',
                'icon' => 'ticket',
                'description' => 'Fluggesellschaften mit Codes, Gepäckregeln, Haustiermitnahme und Direktverbindungen.',
                'legacy' => '/admin/airlines',
                'routes' => 'adminv2.master-data.airlines',
            ],
        ];
    }

    /**
     * Bereiche, die noch nicht umgezogen sind und nur den Hinweis zeigen.
     *
     * @return array<int, string>
     */
    public static function placeholderKeys(): array
    {
        return array_keys(array_filter(self::sections(), fn (array $section) => ! isset($section['routes'])));
    }

    public static function url(string $key): string
    {
        $routes = self::sections()[$key]['routes'] ?? null;

        return $routes ? route($routes.'.index') : route('adminv2.master-data.section', $key);
    }

    /**
     * Gehoert die aufgerufene Seite zu diesem Bereich?
     */
    public static function isCurrent(string $key): bool
    {
        $routes = self::sections()[$key]['routes'] ?? null;

        return $routes
            ? request()->routeIs($routes.'.*')
            : request()->routeIs('adminv2.master-data.section') && request()->route('section') === $key;
    }

    /**
     * Der deutsche Name als SQL-Ausdruck. Die Sortierung nach unicode_ci
     * ordnet Umlaute richtig ein und macht die Suche unabhaengig von
     * Gross-/Kleinschreibung.
     */
    public static function nameSql(string $table, string $language = 'de'): string
    {
        $language = $language === 'en' ? 'en' : 'de';

        return "JSON_UNQUOTE(JSON_EXTRACT(`{$table}`.`name_translations`, '$.{$language}')) COLLATE utf8mb4_unicode_ci";
    }

    /**
     * Sucht im deutschen und englischen Namen sowie in den genannten Spalten.
     *
     * @param  array<int, string>  $columns
     */
    public static function search(Builder $query, string $term, array $columns = []): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $table = $query->getModel()->getTable();
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $query) use ($table, $like, $columns) {
            $query->whereRaw(self::nameSql($table, 'de').' LIKE ?', [$like])
                ->orWhereRaw(self::nameSql($table, 'en').' LIKE ?', [$like]);

            foreach ($columns as $column) {
                $query->orWhere($table.'.'.$column, 'like', $like);
            }
        });
    }

    /**
     * Aenderung im Protokoll festhalten – fuer die Statistik je Mitarbeiter.
     *
     * @param  array<int, string>  $changes  geaenderte Felder bzw. Bereiche
     */
    public static function logChange(Model $record, string $action, array $changes = []): void
    {
        MasterDataChange::create([
            'model_type' => $record->getMorphClass(),
            'model_id' => $record->getKey(),
            'user_id' => auth('web')->id(),
            'action' => $action,
            'changes' => $changes ?: null,
            'created_at' => now(),
        ]);
    }

    /**
     * Bezeichnung eines Eintrags fuer Rueckmeldungen und Rueckfragen.
     */
    public static function recordLabel(Model $record): string
    {
        return match (true) {
            $record instanceof Country => $record->getName('de').' ('.$record->iso_code.')',
            $record instanceof Airport, $record instanceof Airline => trim($record->name.' '.($record->iata_code ? '('.$record->iata_code.')' : '')),
            $record instanceof AirportCode => trim($record->name.' '.(($code = $record->iata_code ?: $record->icao_code ?: $record->ident) ? '('.$code.')' : '')),
            default => $record->getName('de'),
        };
    }

    /**
     * Was an einem Eintrag haengt: Bezeichnung => Anzahl. Solange hier etwas
     * steht, laesst sich der Eintrag nicht endgueltig loeschen.
     *
     * @return array<string, int>
     */
    public static function dependents(Model $record): array
    {
        $counts = match (true) {
            $record instanceof Continent => [
                'Länder' => $record->countries()->withTrashed()->count(),
            ],
            $record instanceof Country => [
                'Regionen' => $record->regions()->withTrashed()->count(),
                'Städte' => $record->cities()->withTrashed()->count(),
                'Sehenswürdigkeiten' => $record->sights()->withTrashed()->count(),
                'Flughäfen' => $record->airports()->withTrashed()->count(),
                'Ereignisse' => DB::table('country_custom_event')->where('country_id', $record->id)->distinct()->count('custom_event_id'),
                'Katastrophen-Ereignisse' => $record->disasterEvents()->count(),
                'Kreuzfahrthäfen' => DB::table('tourism_cruise_ports')->where('country_id', $record->id)->count(),
            ],
            $record instanceof Region => [
                'Städte' => $record->cities()->withTrashed()->count(),
                'Sehenswürdigkeiten' => $record->sights()->withTrashed()->count(),
                'Ereignisse' => DB::table('custom_event_region')->where('region_id', $record->id)->distinct()->count('custom_event_id')
                    + DB::table('country_custom_event')->where('region_id', $record->id)->distinct()->count('custom_event_id'),
                'Katastrophen-Ereignisse' => $record->disasterEvents()->count(),
            ],
            $record instanceof City => [
                'Flughäfen' => $record->airports()->withTrashed()->count(),
                'Sehenswürdigkeiten' => $record->sights()->withTrashed()->count(),
                'Ereignisse' => DB::table('city_custom_event')->where('city_id', $record->id)->distinct()->count('custom_event_id')
                    + DB::table('country_custom_event')->where('city_id', $record->id)->distinct()->count('custom_event_id'),
                'Katastrophen-Ereignisse' => $record->disasterEvents()->count(),
            ],
            // Die Verknuepfungen zu Airlines haengen nicht am Eintrag – sie fallen mit ihm weg.
            $record instanceof AirportCode => [
                'Flugsegmente in Reisen' => DB::table('folder_flight_segments')
                    ->where('departure_airport_id', $record->id)->orWhere('arrival_airport_id', $record->id)->count(),
            ],
            default => [],
        };

        return array_filter($counts);
    }
}
