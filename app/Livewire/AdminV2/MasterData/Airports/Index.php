<?php

namespace App\Livewire\AdminV2\MasterData\Airports;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\ManagesMasterDataList;
use App\Models\Airport;
use App\Models\City;
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
 * Stammdaten > Flughaefen: die gepflegten Flughaefen mit Suche, Filtern,
 * Sortierung und Papierkorb.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Flughäfen')]
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

    #[Url(except: '')]
    public string $type = '';

    /** '' = alle, 'active', 'inactive' */
    #[Url(except: '')]
    public string $active = '';

    /** '' = alle, 'missing' = ohne Koordinaten */
    #[Url(except: '')]
    public string $coordinates = '';

    protected function masterDataModel(): string
    {
        return Airport::class;
    }

    protected function sortable(): array
    {
        return ['name', 'iata_code', 'city', 'country', 'type', 'airlines_count'];
    }

    protected function filterProperties(): array
    {
        return ['countryIds', 'type', 'active', 'coordinates'];
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
            ->with(['country' => fn ($query) => $query->withTrashed(), 'city' => fn ($query) => $query->withTrashed()])
            ->withCount('airlines');

        match ($this->trashed) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };

        if (($term = trim($this->search)) !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(fn ($query) => $query
                ->where('airports.name', 'like', $like)
                ->orWhere('airports.iata_code', 'like', $like)
                ->orWhere('airports.icao_code', 'like', $like)
                ->orWhereExists(fn ($sub) => $sub->selectRaw('1')->from('cities')->whereColumn('cities.id', 'airports.city_id')->whereRaw(MasterData::nameSql('cities').' LIKE ?', [$like])));
        }

        if ($countryIds = array_filter(array_map('intval', $this->countryIds))) {
            $query->whereIn('country_id', $countryIds);
        }

        if ($this->type !== '' && isset(Airport::getTypeOptions()[$this->type])) {
            $query->where('type', $this->type);
        }

        match ($this->active) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default => null,
        };

        if ($this->coordinates === 'missing') {
            $query->where(fn ($query) => $query->whereNull('lat')->orWhereNull('lng'));
        }

        $direction = $this->sortDirection();

        match ($this->sortColumn()) {
            'iata_code' => $query->orderBy('iata_code', $direction),
            'city' => $query->orderBy(City::query()->withTrashed()->selectRaw(MasterData::nameSql('cities'))->whereColumn('cities.id', 'airports.city_id')->limit(1), $direction),
            'country' => $query->orderBy(Country::query()->withTrashed()->selectRaw(MasterData::nameSql('countries'))->whereColumn('countries.id', 'airports.country_id')->limit(1), $direction),
            'type' => $query->orderBy('type', $direction),
            'airlines_count' => $query->orderBy('airlines_count', $direction),
            default => $query->orderBy('name', $direction),
        };

        return $query->orderBy('name')->orderBy('airports.id')->paginate(self::PER_PAGE);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.airports.index');
    }
}
