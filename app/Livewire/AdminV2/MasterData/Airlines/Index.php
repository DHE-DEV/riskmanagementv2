<?php

namespace App\Livewire\AdminV2\MasterData\Airlines;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\ManagesMasterDataList;
use App\Models\Airline;
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
 * Stammdaten > Airlines: Liste mit Suche, Filtern, Sortierung und Papierkorb.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Airlines')]
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

    /** '' = alle, 'active', 'inactive' */
    #[Url(except: '')]
    public string $active = '';

    #[Url(except: '')]
    public string $cabinClass = '';

    /** '' = alle, 'yes' = mit Haustiermitnahme */
    #[Url(except: '')]
    public string $pets = '';

    protected function masterDataModel(): string
    {
        return Airline::class;
    }

    protected function sortable(): array
    {
        return ['name', 'iata_code', 'country', 'airports_count'];
    }

    protected function filterProperties(): array
    {
        return ['countryIds', 'active', 'cabinClass', 'pets'];
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()->orderByRaw(MasterData::nameSql('countries'))->get(['id', 'iso_code', 'name_translations']);
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = $this->masterDataModel()::query()
            ->with(['homeCountry' => fn ($query) => $query->withTrashed()])
            ->withCount('airports');

        match ($this->trashed) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };

        if (($term = trim($this->search)) !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(fn ($query) => $query
                ->where('name', 'like', $like)
                ->orWhere('iata_code', 'like', $like)
                ->orWhere('icao_code', 'like', $like)
                ->orWhere('headquarters', 'like', $like));
        }

        if ($countryIds = array_filter(array_map('intval', $this->countryIds))) {
            $query->whereIn('home_country_id', $countryIds);
        }

        match ($this->active) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default => null,
        };

        if ($this->cabinClass !== '' && isset(Airline::getCabinClassOptions()[$this->cabinClass])) {
            $query->whereJsonContains('cabin_classes', $this->cabinClass);
        }

        if ($this->pets === 'yes') {
            $query->where('pet_policy->allowed', true);
        }

        $direction = $this->sortDirection();

        match ($this->sortColumn()) {
            'iata_code' => $query->orderByRaw("(iata_code is null or iata_code = '') asc")->orderBy('iata_code', $direction),
            'country' => $query->orderBy(Country::query()->withTrashed()->selectRaw(MasterData::nameSql('countries'))->whereColumn('countries.id', 'airlines.home_country_id')->limit(1), $direction),
            'airports_count' => $query->orderBy('airports_count', $direction),
            default => $query->orderBy('name', $direction),
        };

        return $query->orderBy('name')->orderBy('airlines.id')->paginate(self::PER_PAGE);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.airlines.index');
    }
}
