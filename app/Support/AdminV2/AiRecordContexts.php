<?php

namespace App\Support\AdminV2;

use App\Livewire\AdminV2\MasterData\Airlines\Editor as AirlineEditor;
use App\Livewire\AdminV2\MasterData\AirportCodes\Editor as AirportCodeEditor;
use App\Livewire\AdminV2\MasterData\Airports\Editor as AirportEditor;
use App\Livewire\AdminV2\MasterData\Cities\Editor as CityEditor;
use App\Livewire\AdminV2\MasterData\Continents\Editor as ContinentEditor;
use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Livewire\AdminV2\MasterData\Regions\Editor as RegionEditor;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\AirportCode;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use function Livewire\trigger;

/**
 * Die Angaben eines gespeicherten Datensatzes zu den Platzhaltern seines
 * Bereichs (siehe AiAreas) – fuer den Sammellauf einer KI-Pruefung, der ohne
 * geoeffnetes Formular auskommt.
 *
 * Die Angaben kommen aus dem Formular des Bereichs selbst: es wird mit dem
 * Datensatz befuellt (mount), aber nicht angezeigt. So bekommt die KI im
 * Sammellauf genau das, was sie auch im KI-Fenster des Eintrags bekaeme, und
 * ein neues Feld muss nur an einer Stelle ergaenzt werden.
 *
 * Das Formular prueft den Admin-Zugang – beim Aufruf muss ein Admin angemeldet
 * sein (siehe AiCheckBatchService).
 */
class AiRecordContexts
{
    /**
     * Bereich => Modell, Formular und Name des Parameters, ueber den das Formular
     * seinen Datensatz bekommt. "filters" nennt, wonach sich ein Sammellauf
     * eingrenzen laesst: Eingrenzung => Spalte.
     *
     * @var array<string, array{model: class-string<Model>, editor: class-string, parameter: string, filters: array<string, string>}>
     */
    protected const AREAS = [
        'continents' => ['model' => Continent::class, 'editor' => ContinentEditor::class, 'parameter' => 'continent', 'filters' => []],
        'countries' => ['model' => Country::class, 'editor' => CountryEditor::class, 'parameter' => 'country', 'filters' => ['continent' => 'continent_id']],
        'regions' => ['model' => Region::class, 'editor' => RegionEditor::class, 'parameter' => 'region', 'filters' => ['country' => 'country_id']],
        'cities' => ['model' => City::class, 'editor' => CityEditor::class, 'parameter' => 'city', 'filters' => ['country' => 'country_id', 'capital' => 'is_capital']],
        'airports' => ['model' => Airport::class, 'editor' => AirportEditor::class, 'parameter' => 'airport', 'filters' => ['active' => 'is_active', 'country' => 'country_id', 'type' => 'type']],
        'airport-codes' => ['model' => AirportCode::class, 'editor' => AirportCodeEditor::class, 'parameter' => 'airportCode', 'filters' => ['scheduled' => 'scheduled_service', 'type' => 'type', 'country' => 'country_id', 'active' => 'is_active']],
        'airlines' => ['model' => Airline::class, 'editor' => AirlineEditor::class, 'parameter' => 'airline', 'filters' => ['active' => 'is_active', 'country' => 'home_country_id']],
    ];

    /** Bezeichnungen der Ja/Nein-Eingrenzungen. */
    protected const SWITCHES = [
        'active' => 'Nur aktive Einträge',
        'scheduled' => 'Nur mit Linienverkehr',
        'capital' => 'Nur Hauptstädte',
    ];

    public static function supports(string $area): bool
    {
        return isset(self::AREAS[$area]);
    }

    /**
     * Wonach sich ein Sammellauf in diesem Bereich eingrenzen laesst.
     *
     * @return array<string, array{label: string, kind: string, options?: array<string, string>}>
     */
    public static function filters(string $area): array
    {
        $filters = [];

        foreach (array_keys(self::AREAS[$area]['filters'] ?? []) as $key) {
            $filters[$key] = match ($key) {
                'country' => ['label' => $area === 'airlines' ? 'Heimatland' : 'Land', 'kind' => 'country'],
                'continent' => ['label' => 'Kontinent', 'kind' => 'continent'],
                'type' => ['label' => 'Typ', 'kind' => 'select', 'options' => self::AREAS[$area]['model']::getTypeOptions()],
                default => ['label' => self::SWITCHES[$key], 'kind' => 'switch'],
            };
        }

        return $filters;
    }

    /**
     * Nur Eingrenzungen, die es in diesem Bereich gibt und die etwas eingrenzen.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, bool|int|string>
     */
    public static function sanitizeFilters(string $area, array $filters): array
    {
        $clean = [];

        foreach (self::filters($area) as $key => $definition) {
            $value = $filters[$key] ?? null;

            $value = match ($definition['kind']) {
                'switch' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ?: null,
                'select' => is_string($value) && isset($definition['options'][$value]) ? $value : null,
                default => (int) $value ?: null,
            };

            if ($value !== null) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * Die Eingrenzung in Worten, z. B. ["Nur aktive Einträge", "Land: Deutschland"].
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    public static function describeFilters(string $area, array $filters): array
    {
        $definitions = self::filters($area);
        $parts = [];

        foreach (self::sanitizeFilters($area, $filters) as $key => $value) {
            $parts[] = match ($definitions[$key]['kind']) {
                'switch' => $definitions[$key]['label'],
                'select' => $definitions[$key]['label'].': '.$definitions[$key]['options'][$value],
                'country' => $definitions[$key]['label'].': '.(Country::withTrashed()->find($value)?->getName('de') ?? '#'.$value),
                'continent' => $definitions[$key]['label'].': '.(Continent::withTrashed()->find($value)?->getName('de') ?? '#'.$value),
            };
        }

        return $parts;
    }

    /**
     * Die Datensaetze, ueber die ein Sammellauf geht – ohne Papierkorb, auf Wunsch eingegrenzt.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function query(string $area, array $filters = []): Builder
    {
        $query = self::AREAS[$area]['model']::query();
        $columns = self::AREAS[$area]['filters'];

        foreach (self::sanitizeFilters($area, $filters) as $key => $value) {
            $column = $query->qualifyColumn($columns[$key]);

            match (true) {
                $key === 'scheduled' => $query->where($column, 'yes'),
                // Flughafen-Codes kennen ihr Land oft nur als ISO-Code.
                $key === 'country' && $area === 'airport-codes' => $query->where(fn (Builder $query) => $query
                    ->where($column, $value)
                    ->orWhere('iso_country', (string) Country::withTrashed()->find($value)?->iso_code)),
                default => $query->where($column, $value),
            };
        }

        return $query;
    }

    /**
     * @return array<string, mixed> Platzhalter-Schluessel => Wert
     */
    public static function context(string $area, Model $record): array
    {
        $definition = self::AREAS[$area];

        $editor = app('livewire')->new($definition['editor']);

        // Wie beim Oeffnen der Seite: Zugang pruefen und das Formular mit dem Datensatz fuellen.
        trigger('mount', $editor, [$definition['parameter'] => $record->getKey()], null, null);

        return (fn () => $this->aiContext())->call($editor);
    }
}
