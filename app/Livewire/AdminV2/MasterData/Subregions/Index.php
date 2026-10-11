<?php

namespace App\Livewire\AdminV2\MasterData\Subregions;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\ManagesMasterDataList;
use App\Livewire\AdminV2\MasterData\Sights\Index as SightsIndex;
use App\Models\Country;
use App\Models\Region;
use App\Models\Subregion;
use App\Support\AdminV2\MasterData;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\SubregionInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Stammdaten > Teilgebiete: Inseln, Inselgruppen und Reisegebiete der Regionen
 * – Liste mit Suche, Filtern, Sortierung und Papierkorb.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Teilgebiete')]
class Index extends Component
{
    use AuthorizesAdminV2, ManagesMasterDataList;

    #[Url(except: 'region')]
    public string $sort = 'region';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    /** @var array<int, string> */
    #[Url(as: 'country')]
    public array $countryIds = [];

    /** Region – nur waehlbar, solange genau ein Land gefiltert ist */
    #[Url(except: '')]
    public string $region = '';

    #[Url(except: '')]
    public string $kind = '';

    /** '' = alle, sonst ein Zustand aus RegionInfo::STATUSES */
    #[Url(except: '')]
    public string $status = '';

    protected function masterDataModel(): string
    {
        return Subregion::class;
    }

    public function sortOptions(): array
    {
        return ['region' => 'Region', 'name' => 'Name', 'country' => 'Land', 'updated' => 'Zuletzt geändert'];
    }

    protected function filterProperties(): array
    {
        return ['countryIds', 'region', 'kind', 'status'];
    }

    public function updatedCountryIds(): void
    {
        $this->region = '';
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()->whereIn('id', Subregion::query()->withTrashed()->select('country_id'))->orderByRaw(MasterData::nameSql('countries'))->get(['id', 'iso_code', 'name_translations']);
    }

    #[Computed]
    public function regionOptions(): Collection
    {
        $countryIds = array_values(array_filter(array_map('intval', $this->countryIds)));

        if (count($countryIds) !== 1) {
            return collect();
        }

        return Region::query()->where('country_id', $countryIds[0])->whereIn('id', Subregion::query()->withTrashed()->select('region_id'))->orderByRaw(MasterData::nameSql('regions'))->get(['id', 'code', 'name_translations']);
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = $this->listQuery(['code'])->with([
            'country' => fn ($query) => $query->withTrashed(),
            'region' => fn ($query) => $query->withTrashed(),
        ])->withCount(['sights', 'cities']);

        if ($countryIds = array_filter(array_map('intval', $this->countryIds))) {
            $query->whereIn('country_id', $countryIds);
        }

        if ($this->region !== '' && $this->regionOptions->contains('id', (int) $this->region)) {
            $query->where('region_id', (int) $this->region);
        }

        if (isset(SubregionInfo::KINDS[$this->kind])) {
            $query->where('kind', $this->kind);
        }

        if ($this->status === RegionInfo::STATUS_EMPTY) {
            $query->where(fn ($query) => $query->whereNull('info')->orWhereNull('info->texts'));
        } elseif ($this->status !== '') {
            SightsIndex::whereStatus($query, $this->status);
        }

        $direction = $this->sortDirection();

        match ($this->sortColumn()) {
            'name' => $query->orderByRaw(MasterData::nameSql('subregions').' '.$direction),
            'country' => $query->orderBy(Country::query()->withTrashed()->selectRaw(MasterData::nameSql('countries'))->whereColumn('countries.id', 'subregions.country_id')->limit(1), $direction),
            'updated' => $query->orderBy('updated_at', $direction),
            default => $query->orderBy(Region::query()->withTrashed()->selectRaw(MasterData::nameSql('regions'))->whereColumn('regions.id', 'subregions.region_id')->limit(1), $direction)->orderBy('sort_order'),
        };

        return $query->orderByRaw(MasterData::nameSql('subregions'))->orderBy('subregions.id')->paginate(self::PER_PAGE);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.subregions.index');
    }
}
