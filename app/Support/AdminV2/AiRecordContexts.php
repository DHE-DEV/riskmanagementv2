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
     * Bereich => Modell, Formular und Name des Parameters, ueber den das Formular seinen Datensatz bekommt.
     *
     * @var array<string, array{model: class-string<Model>, editor: class-string, parameter: string}>
     */
    protected const AREAS = [
        'continents' => ['model' => Continent::class, 'editor' => ContinentEditor::class, 'parameter' => 'continent'],
        'countries' => ['model' => Country::class, 'editor' => CountryEditor::class, 'parameter' => 'country'],
        'regions' => ['model' => Region::class, 'editor' => RegionEditor::class, 'parameter' => 'region'],
        'cities' => ['model' => City::class, 'editor' => CityEditor::class, 'parameter' => 'city'],
        'airports' => ['model' => Airport::class, 'editor' => AirportEditor::class, 'parameter' => 'airport'],
        'airport-codes' => ['model' => AirportCode::class, 'editor' => AirportCodeEditor::class, 'parameter' => 'airportCode'],
        'airlines' => ['model' => Airline::class, 'editor' => AirlineEditor::class, 'parameter' => 'airline'],
    ];

    public static function supports(string $area): bool
    {
        return isset(self::AREAS[$area]);
    }

    /**
     * Die Datensaetze, ueber die ein Sammellauf geht – ohne Papierkorb.
     */
    public static function query(string $area): Builder
    {
        return self::AREAS[$area]['model']::query();
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
