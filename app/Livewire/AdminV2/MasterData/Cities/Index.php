<?php

namespace App\Livewire\AdminV2\MasterData\Cities;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\ManagesMasterDataList;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use App\Support\AdminV2\MasterData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Stammdaten > Staedte: Liste mit Suche, Filtern, Sortierung und Papierkorb.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Städte')]
class Index extends Component
{
    use AuthorizesAdminV2, ManagesMasterDataList;

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    /** @var array<int, string> */
    #[Url(as: 'country')]
    public array $countryIds = [];

    /** Region – nur waehlbar, solange genau ein Land gefiltert ist. 'none' = ohne Region */
    #[Url(except: '')]
    public string $region = '';

    /** '' = alle, 'capital' = Hauptstaedte, 'regional' = Regionshauptstaedte */
    #[Url(except: '')]
    public string $capital = '';

    /** '' = alle, 'missing' = ohne Koordinaten */
    #[Url(except: '')]
    public string $coordinates = '';

    protected function masterDataModel(): string
    {
        return City::class;
    }

    public function sortOptions(): array
    {
        return ['name' => 'Name', 'country' => 'Land', 'region' => 'Region', 'population' => 'Bevölkerung'];
    }

    protected function filterProperties(): array
    {
        return ['countryIds', 'region', 'capital', 'coordinates'];
    }

    public function updatedCountryIds(): void
    {
        // Die Region gehoert zu einem Land – mit dem Land faellt auch sie weg.
        $this->region = '';
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()->orderByRaw(MasterData::nameSql('countries'))->get(['id', 'iso_code', 'name_translations']);
    }

    /**
     * Regionen des gefilterten Landes – leer, solange nicht genau eines gewaehlt ist.
     */
    #[Computed]
    public function regionOptions(): Collection
    {
        $countryIds = array_values(array_filter(array_map('intval', $this->countryIds)));

        if (count($countryIds) !== 1) {
            return collect();
        }

        return Region::query()->where('country_id', $countryIds[0])->orderByRaw(MasterData::nameSql('regions'))->get(['id', 'code', 'name_translations']);
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = $this->listQuery()->with([
            'country' => fn ($query) => $query->withTrashed(),
            'region' => fn ($query) => $query->withTrashed(),
        ]);

        if ($countryIds = array_filter(array_map('intval', $this->countryIds))) {
            $query->whereIn('country_id', $countryIds);
        }

        if ($this->region === 'none') {
            $query->whereNull('region_id');
        } elseif ($this->region !== '' && $this->regionOptions->contains('id', (int) $this->region)) {
            $query->where('region_id', (int) $this->region);
        }

        match ($this->capital) {
            'capital' => $query->where('is_capital', true),
            'regional' => $query->where('is_regional_capital', true),
            default => null,
        };

        if ($this->coordinates === 'missing') {
            $query->where(fn ($query) => $query->whereNull('lat')->orWhereNull('lng'));
        }

        $direction = $this->sortDirection();

        match ($this->sortColumn()) {
            'country' => $query->orderBy(
                Country::query()->withTrashed()->selectRaw(MasterData::nameSql('countries'))->whereColumn('countries.id', 'cities.country_id')->limit(1),
                $direction,
            ),
            'region' => $query->orderBy(
                Region::query()->withTrashed()->selectRaw(MasterData::nameSql('regions'))->whereColumn('regions.id', 'cities.region_id')->limit(1),
                $direction,
            ),
            'population' => $query->orderBy('population', $direction),
            default => $query->orderByRaw(MasterData::nameSql('cities').' '.$direction),
        };

        return $query->orderByRaw(MasterData::nameSql('cities'))->orderBy('cities.id')->paginate(self::PER_PAGE);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.cities.index');
    }
}
