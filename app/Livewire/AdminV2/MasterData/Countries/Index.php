<?php

namespace App\Livewire\AdminV2\MasterData\Countries;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\ManagesMasterDataList;
use App\Models\Continent;
use App\Models\Country;
use App\Support\AdminV2\MasterData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Stammdaten > Laender: Liste mit Suche, Filtern, Sortierung und Papierkorb.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Länder')]
class Index extends Component
{
    use AuthorizesAdminV2, ManagesMasterDataList;

    /**
     * Gesamt-Risiko wie Country::overall_risk_level: die hoechste der drei
     * Hauptstufen Sicherheit, Gesundheit und Naturgefahren.
     */
    protected const RISK_SQL = "GREATEST(
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(countries.risk_profile, '$.security.overall_risk_level')), 0),
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(countries.risk_profile, '$.health.health_risk_level')), 0),
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(countries.risk_profile, '$.natural_hazards.natural_hazard_level')), 0)
    ) + 0";

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    #[Url(except: '')]
    public string $continent = '';

    /** '' = alle, 'eu', 'schengen', 'both' = beides, 'none' = keines von beiden */
    #[Url(except: '')]
    public string $membership = '';

    /** '' = alle, '1'–'5' oder 'none' = nicht bewertet */
    #[Url(except: '')]
    public string $risk = '';

    /** '' = alle, 'missing' = ohne Koordinaten */
    #[Url(except: '')]
    public string $coordinates = '';

    /** '' = alle, 'none' = ohne gepflegten Flughafen, 'any' = mit */
    #[Url(except: '')]
    public string $airports = '';

    protected function masterDataModel(): string
    {
        return Country::class;
    }

    public function sortOptions(): array
    {
        return ['name' => 'Name', 'iso_code' => 'ISO-Code', 'continent' => 'Kontinent', 'population' => 'Bevölkerung', 'regions_count' => 'Anzahl Regionen', 'cities_count' => 'Anzahl Städte'];
    }

    protected function filterProperties(): array
    {
        return ['continent', 'membership', 'risk', 'coordinates', 'airports'];
    }

    #[Computed]
    public function continents(): Collection
    {
        return Continent::query()->ordered()->get();
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = $this->listQuery(['iso_code', 'iso3_code', 'currency_code'])
            ->with('continent')
            ->withCount(['regions', 'cities']);

        if ($this->continent !== '') {
            $query->where('continent_id', (int) $this->continent);
        }

        match ($this->membership) {
            'eu' => $query->where('is_eu_member', true),
            'schengen' => $query->where('is_schengen_member', true),
            'both' => $query->where('is_eu_member', true)->where('is_schengen_member', true),
            // Die Spalten duerfen leer sein – leer zaehlt wie "nein".
            'none' => $query
                ->where(fn ($query) => $query->where('is_eu_member', false)->orWhereNull('is_eu_member'))
                ->where(fn ($query) => $query->where('is_schengen_member', false)->orWhereNull('is_schengen_member')),
            default => null,
        };

        if ($this->risk === 'none') {
            $query->whereRaw(self::RISK_SQL.' = 0');
        } elseif (in_array($this->risk, ['1', '2', '3', '4', '5'], true)) {
            $query->whereRaw(self::RISK_SQL.' = ?', [(int) $this->risk]);
        }

        if ($this->coordinates === 'missing') {
            $query->where(fn ($query) => $query->whereNull('lat')->orWhereNull('lng'));
        }

        match ($this->airports) {
            'none' => $query->whereDoesntHave('airports'),
            'any' => $query->whereHas('airports'),
            default => null,
        };

        $direction = $this->sortDirection();

        match ($this->sortColumn()) {
            'iso_code' => $query->orderBy('iso_code', $direction),
            'continent' => $query->orderBy(
                Continent::query()->selectRaw(MasterData::nameSql('continents'))->whereColumn('continents.id', 'countries.continent_id')->limit(1),
                $direction,
            ),
            'population' => $query->orderBy('population', $direction),
            'regions_count' => $query->orderBy('regions_count', $direction),
            'cities_count' => $query->orderBy('cities_count', $direction),
            default => $query->orderByRaw(MasterData::nameSql('countries').' '.$direction),
        };

        return $query->orderByRaw(MasterData::nameSql('countries'))->paginate(self::PER_PAGE);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.countries.index');
    }
}
