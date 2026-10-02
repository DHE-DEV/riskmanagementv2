<?php

namespace App\Livewire\AdminV2\MasterData\Countries;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsCoordinates;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\RunsAiAssistant;
use App\Models\Continent;
use App\Models\Country;
use App\Support\AdminV2\CountryRiskProfile;
use App\Support\AdminV2\MasterData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Stammdaten > Laender: ein Land anlegen oder bearbeiten – Grunddaten,
 * Koordinaten und Risikoprofil; dazu, was am Land haengt (Regionen, Staedte,
 * Flughaefen) und die Laendergrenze zum Nachsehen.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsCoordinates, EditsMasterData, RunsAiAssistant;

    /** So viele Eintraege zeigt die Seitenspalte je Liste. */
    public const RELATED_LIMIT = 12;

    public string $nameDe = '';

    public string $nameEn = '';

    /** @var array<int, array{code: string, name: string}> weitere Sprachen neben Deutsch und Englisch */
    public array $extraNames = [];

    public string $isoCode = '';

    public string $iso3Code = '';

    public string $continentId = '';

    public bool $isEuMember = false;

    public bool $isSchengenMember = false;

    public string $currencyCode = '';

    public string $currencyName = '';

    public string $currencySymbol = '';

    public string $phonePrefix = '';

    public string $timezone = '';

    public string $population = '';

    public string $areaKm2 = '';

    /** @var array<string, array<string, mixed>> Formularwerte, siehe CountryRiskProfile */
    public array $riskProfile = [];

    public function mount(?int $country = null): void
    {
        if ($country === null) {
            $this->riskProfile = CountryRiskProfile::toForm(null);
            $continent = (int) request()->query('continent');
            $this->continentId = $continent && Continent::whereKey($continent)->exists() ? (string) $continent : '';

            return;
        }

        $record = Country::withTrashed()->findOrFail($country);

        $this->recordId = $record->id;

        // Schluessel mit Leerzeichen ("de ") stammen aus Altdaten und zaehlen zur jeweiligen Sprache.
        $names = [];
        foreach ($record->name_translations ?? [] as $language => $name) {
            $names[mb_strtolower(trim((string) $language))] ??= (string) $name;
        }

        $this->nameDe = $names['de'] ?? '';
        $this->nameEn = $names['en'] ?? '';
        $this->extraNames = collect($names)->except(['de', 'en'])
            ->map(fn (string $name, string $language) => ['code' => $language, 'name' => $name])
            ->values()->all();

        $this->isoCode = (string) $record->iso_code;
        $this->iso3Code = (string) $record->iso3_code;
        $this->continentId = (string) $record->continent_id;
        $this->isEuMember = (bool) $record->is_eu_member;
        $this->isSchengenMember = (bool) $record->is_schengen_member;
        $this->currencyCode = (string) $record->currency_code;
        $this->currencyName = (string) $record->currency_name;
        $this->currencySymbol = (string) $record->currency_symbol;
        $this->phonePrefix = (string) $record->phone_prefix;
        $this->timezone = (string) $record->timezone;
        $this->population = (string) $record->population;
        $this->areaKm2 = $record->area_km2 === null ? '' : rtrim(rtrim(number_format((float) $record->area_km2, 2, '.', ''), '0'), '.');
        $this->riskProfile = CountryRiskProfile::toForm($record->risk_profile);
        $this->fillCoordinates($record);
    }

    protected function masterDataModel(): string
    {
        return Country::class;
    }

    protected function routeBase(): string
    {
        return 'adminv2.master-data.countries';
    }

    #[Computed]
    public function continents(): Collection
    {
        return Continent::query()->ordered()->get();
    }

    public function addName(): void
    {
        $this->extraNames[] = ['code' => '', 'name' => ''];
    }

    public function removeName(int $index): void
    {
        unset($this->extraNames[$index]);
        $this->extraNames = array_values($this->extraNames);
    }

    /**
     * Gesamt-Risiko aus den gerade eingetragenen Stufen – wie
     * Country::overall_risk_level die hoechste der drei Hauptstufen.
     */
    #[Computed]
    public function overallRisk(): ?int
    {
        $levels = array_filter([
            (int) ($this->riskProfile['security']['overall_risk_level'] ?? 0),
            (int) ($this->riskProfile['health']['health_risk_level'] ?? 0),
            (int) ($this->riskProfile['natural_hazards']['natural_hazard_level'] ?? 0),
        ]);

        return $levels ? max($levels) : null;
    }

    /**
     * Was am Land haengt: Anzahl und die ersten Eintraege fuer die Seitenspalte.
     *
     * @return array{regions: array{count: int, items: Collection}, cities: array{count: int, items: Collection}, airports: array{count: int, items: Collection}, events: int}|null
     */
    #[Computed]
    public function related(): ?array
    {
        $country = $this->record;

        if (! $country) {
            return null;
        }

        return [
            'regions' => [
                'count' => $country->regions()->count(),
                'items' => $country->regions()->orderByRaw(MasterData::nameSql('regions'))->limit(self::RELATED_LIMIT)->get(),
            ],
            'cities' => [
                'count' => $country->cities()->count(),
                // Hauptstadt zuerst, dann die groessten Staedte.
                'items' => $country->cities()->orderByDesc('is_capital')->orderByDesc('population')->limit(self::RELATED_LIMIT)->get(),
            ],
            'airports' => [
                'count' => $country->airports()->count(),
                'items' => $country->airports()->orderBy('name')->limit(self::RELATED_LIMIT)->get(['id', 'name', 'iata_code', 'icao_code']),
            ],
            'events' => DB::table('country_custom_event')->where('country_id', $country->id)->distinct()->count('custom_event_id'),
        ];
    }

    /**
     * Die Laendergrenze – reine Anzeige; geschrieben wird sie ausschliesslich
     * vom Befehl countries:import-boundaries.
     *
     * @return array<string, string>|null
     */
    #[Computed]
    public function boundary(): ?array
    {
        $boundary = $this->record?->boundary;

        if (! $boundary) {
            return null;
        }

        $stats = $boundary->spatialStats();
        $number = fn (float|int $value, int $decimals = 0) => number_format($value, $decimals, ',', '.');
        $rows = [];

        $rows['Quelle'] = (string) $boundary->source;
        $rows['Bezeichnung in der Quelle'] = (string) $boundary->name;
        $rows['Codes in der Quelle'] = implode(' / ', array_filter([$boundary->iso_a2, $boundary->iso_a3]));

        if ($boundary->source_features > 1) {
            $rows['Quell-Einheiten'] = $boundary->source_features.' zusammengefasst (z. B. Landesteile oder Außengebiete)';
        }

        if ($stats['parts'] !== null) {
            $rows['Teilpolygone'] = $number($stats['parts']);
        }

        if ($stats['area_km2'] !== null) {
            $area = $number($stats['area_km2']).' km²';

            // Aus der Geometrie gerechnet – zum Vergleich mit dem Feld "Fläche".
            if ($this->record->area_km2 > 0) {
                $deviation = (($stats['area_km2'] - $this->record->area_km2) / $this->record->area_km2) * 100;
                $area .= ' (Stammdaten: '.$number((float) $this->record->area_km2).' km², Abweichung '.($deviation >= 0 ? '+' : '').$number($deviation, 1).' %)';
            }

            $rows['Fläche aus dem Polygon'] = $area;
        }

        if ($boundary->min_lat !== null) {
            $rows['Bounding-Box'] = $number($boundary->min_lat, 4).' bis '.$number($boundary->max_lat, 4).' Breite, '
                .$number($boundary->min_lng, 4).' bis '.$number($boundary->max_lng, 4).' Länge';
        }

        if ($stats['is_valid'] !== null) {
            $rows['Geometrie'] = $stats['is_valid'] ? 'gültig' : 'ungültig';
        }

        if ($stats['bytes'] !== null) {
            $rows['Datengröße'] = $number($stats['bytes'] / 1024, 1).' KB';
        }

        $rows['Stand'] = (string) $boundary->updated_at?->format('d.m.Y H:i');

        return array_filter($rows, fn (string $value) => $value !== '');
    }

    public function save(bool $another = false): void
    {
        $this->isoCode = mb_strtoupper(trim($this->isoCode));
        $this->iso3Code = mb_strtoupper(trim($this->iso3Code));
        $this->currencyCode = mb_strtoupper(trim($this->currencyCode));
        $this->population = str_replace(['.', ' '], '', trim($this->population));
        $this->areaKm2 = str_replace(',', '.', trim($this->areaKm2));
        $this->extraNames = array_values(array_filter(
            array_map(fn (array $row) => ['code' => mb_strtolower(trim((string) ($row['code'] ?? ''))), 'name' => trim((string) ($row['name'] ?? ''))], $this->extraNames),
            fn (array $row) => $row['code'] !== '' || $row['name'] !== '',
        ));
        $this->normalizeCoordinates();

        // Unveraenderte Codes werden nicht erneut auf Eindeutigkeit geprueft.
        $record = $this->record;
        $unique = fn (string $column, string $value) => ! $record || mb_strtoupper((string) $record->{$column}) !== $value
            ? Rule::unique('countries', $column)->ignore($this->recordId)
            : null;

        $this->validate([
            'nameDe' => ['required', 'string', 'max:255'],
            'nameEn' => ['required', 'string', 'max:255'],
            'extraNames.*.code' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[a-z0-9]{2,4})?$/', 'distinct', Rule::notIn(['de', 'en'])],
            'extraNames.*.name' => ['required', 'string', 'max:255'],
            'isoCode' => array_filter(['required', 'alpha', 'size:2', $unique('iso_code', $this->isoCode)]),
            'iso3Code' => array_filter(['required', 'alpha', 'size:3', $unique('iso3_code', $this->iso3Code)]),
            'continentId' => ['required', Rule::exists('continents', 'id')->whereNull('deleted_at')],
            'currencyCode' => ['nullable', 'alpha', 'size:3'],
            'currencyName' => ['nullable', 'string', 'max:255'],
            'currencySymbol' => ['nullable', 'string', 'max:5'],
            'phonePrefix' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'population' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'areaKm2' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'riskProfile.entry.passport_validity_months' => ['nullable', 'integer', 'min:0', 'max:120'],
        ] + $this->coordinateRules(), [
            'nameDe.required' => 'Bitte den deutschen Namen angeben.',
            'nameEn.required' => 'Bitte den englischen Namen angeben.',
            'extraNames.*.code.required' => 'Bitte das Sprachkürzel angeben, z. B. fr.',
            'extraNames.*.code.regex' => 'Das Sprachkürzel besteht aus 2–3 Buchstaben, z. B. fr oder pt-br.',
            'extraNames.*.code.distinct' => 'Diese Sprache ist doppelt eingetragen.',
            'extraNames.*.code.not_in' => 'Deutsch und Englisch stehen in den Feldern oben.',
            'extraNames.*.name.required' => 'Bitte den Namen in dieser Sprache angeben.',
            'isoCode.required' => 'Bitte den ISO-Code mit 2 Buchstaben angeben.',
            'isoCode.alpha' => 'Der ISO-Code besteht aus 2 Buchstaben.',
            'isoCode.size' => 'Der ISO-Code besteht aus 2 Buchstaben.',
            'isoCode.unique' => 'Diesen ISO-Code trägt bereits ein anderes Land (auch der Papierkorb zählt).',
            'iso3Code.required' => 'Bitte den ISO-Code mit 3 Buchstaben angeben.',
            'iso3Code.alpha' => 'Der ISO3-Code besteht aus 3 Buchstaben.',
            'iso3Code.size' => 'Der ISO3-Code besteht aus 3 Buchstaben.',
            'iso3Code.unique' => 'Diesen ISO3-Code trägt bereits ein anderes Land (auch der Papierkorb zählt).',
            'continentId.required' => 'Bitte einen Kontinent wählen.',
            'continentId.exists' => 'Bitte einen Kontinent wählen.',
            'currencyCode.alpha' => 'Der Währungscode besteht aus 3 Buchstaben, z. B. EUR.',
            'currencyCode.size' => 'Der Währungscode besteht aus 3 Buchstaben, z. B. EUR.',
            'currencySymbol.max' => 'Das Währungssymbol darf höchstens 5 Zeichen haben.',
            'phonePrefix.max' => 'Die Vorwahl darf höchstens 10 Zeichen haben.',
            'population.integer' => 'Die Bevölkerung muss eine ganze Zahl sein.',
            'areaKm2.numeric' => 'Die Fläche muss eine Zahl sein.',
            'riskProfile.entry.passport_validity_months.integer' => 'Die Gültigkeit des Reisepasses ist eine ganze Zahl von Monaten.',
        ] + $this->coordinateMessages());

        $record ??= new Country;
        $created = ! $record->exists;

        $names = ['de' => trim($this->nameDe), 'en' => trim($this->nameEn)];
        foreach ($this->extraNames as $row) {
            $names[$row['code']] = $row['name'];
        }

        $record->fill([
            'name_translations' => $names,
            'iso_code' => $this->isoCode,
            'iso3_code' => $this->iso3Code,
            'continent_id' => (int) $this->continentId,
            'is_eu_member' => $this->isEuMember,
            'is_schengen_member' => $this->isSchengenMember,
            'currency_code' => $this->currencyCode ?: null,
            'currency_name' => trim($this->currencyName) ?: null,
            'currency_symbol' => trim($this->currencySymbol) ?: null,
            'phone_prefix' => trim($this->phonePrefix) ?: null,
            'timezone' => trim($this->timezone) ?: null,
            'population' => $this->population === '' ? null : (int) $this->population,
            'area_km2' => $this->areaKm2 === '' ? null : (float) $this->areaKm2,
            'risk_profile' => CountryRiskProfile::fromForm($this->riskProfile, $record->risk_profile),
        ] + $this->coordinateValues())->save();

        unset($this->related, $this->boundary, $this->overallRisk);

        $this->finishSave($record, $created, $another);
    }

    protected function aiModelType(): string
    {
        return 'Country';
    }

    protected function aiPlaceholderData(): array
    {
        $country = $this->record;

        return [
            'name' => $country->getName('de'),
            'name_en' => $country->getName('en'),
            'iso_code' => $country->iso_code,
            'iso3_code' => $country->iso3_code,
            'continent' => $country->continent?->getName('de') ?? 'N/A',
            'is_eu_member' => $country->is_eu_member ? 'Ja' : 'Nein',
            'is_schengen_member' => $country->is_schengen_member ? 'Ja' : 'Nein',
            'currency_code' => $country->currency_code ?? 'N/A',
            'currency_name' => $country->currency_name ?? 'N/A',
            'phone_prefix' => $country->phone_prefix ?? 'N/A',
            'population' => $country->population ?? 'N/A',
            'area_km2' => $country->area_km2 ?? 'N/A',
        ];
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.countries.editor')
            ->title($this->record ? $this->record->getName('de') : 'Neues Land');
    }
}
