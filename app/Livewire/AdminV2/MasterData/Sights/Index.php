<?php

namespace App\Livewire\AdminV2\MasterData\Sights;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\ManagesMasterDataList;
use App\Models\Country;
use App\Models\Region;
use App\Models\Sight;
use App\Models\Subregion;
use App\Support\AdminV2\MasterData;
use App\Support\AdminV2\SightInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Stammdaten > Sehenswuerdigkeiten: Liste mit Suche, Filtern, Sortierung und Papierkorb.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Sehenswürdigkeiten')]
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

    /** Teilgebiet der gewaehlten Region; 'none' = ohne Teilgebiet */
    #[Url(except: '')]
    public string $subregion = '';

    #[Url(except: '')]
    public string $category = '';

    /** '' = alle, 'yes' = nur Highlights */
    #[Url(except: '')]
    public string $highlight = '';

    /** '' = alle, sonst ein Zustand aus SightInfo::STATUSES */
    #[Url(except: '')]
    public string $status = '';

    /** '' = alle, 'missing' = ohne Koordinaten */
    #[Url(except: '')]
    public string $coordinates = '';

    protected function masterDataModel(): string
    {
        return Sight::class;
    }

    public function sortOptions(): array
    {
        return ['name' => 'Name', 'country' => 'Land', 'region' => 'Region', 'order' => 'Reihenfolge der Region', 'updated' => 'Zuletzt geändert'];
    }

    protected function filterProperties(): array
    {
        return ['countryIds', 'region', 'subregion', 'category', 'highlight', 'status', 'coordinates'];
    }

    public function updatedCountryIds(): void
    {
        $this->region = '';
        $this->subregion = '';
    }

    public function updatedRegion(): void
    {
        $this->subregion = '';
    }

    /**
     * Teilgebiete der gewaehlten Region (Inseln, Reisegebiete).
     */
    #[Computed]
    public function subregionOptions(): Collection
    {
        return ctype_digit($this->region) ? Subregion::query()->where('region_id', (int) $this->region)->orderBy('sort_order')->get(['id', 'name_translations']) : collect();
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()->orderByRaw(MasterData::nameSql('countries'))->get(['id', 'iso_code', 'name_translations']);
    }

    #[Computed]
    public function regionOptions(): Collection
    {
        $countryIds = array_values(array_filter(array_map('intval', $this->countryIds)));

        if (count($countryIds) !== 1) {
            return collect();
        }

        return Region::query()->where('country_id', $countryIds[0])->orderByRaw(MasterData::nameSql('regions'))->get(['id', 'code', 'name_translations']);
    }

    public static function whereStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            SightInfo::STATUS_AI => $query->whereNotNull('info->meta->ai_generated_at')->whereNull('info->meta->reviewed_at'),
            SightInfo::STATUS_REVIEWED => $query->whereNotNull('info->meta->reviewed_at'),
            SightInfo::STATUS_MANUAL => $query->where(fn ($query) => $query->whereNull('info')->orWhere(fn ($query) => $query->whereNull('info->meta->ai_generated_at')->whereNull('info->meta->reviewed_at'))),
            default => $query,
        };
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = $this->listQuery(['address'])->with([
            'country' => fn ($query) => $query->withTrashed(),
            'region' => fn ($query) => $query->withTrashed(),
            'subregion' => fn ($query) => $query->withTrashed(),
            'city' => fn ($query) => $query->withTrashed(),
        ]);

        if ($countryIds = array_filter(array_map('intval', $this->countryIds))) {
            $query->whereIn('country_id', $countryIds);
        }

        if ($this->region === 'none') {
            $query->whereNull('region_id');
        } elseif ($this->region !== '' && $this->regionOptions->contains('id', (int) $this->region)) {
            $query->where('region_id', (int) $this->region);
        }

        if ($this->subregion === 'none') {
            $query->whereNull('subregion_id');
        } elseif ($this->subregion !== '' && $this->subregionOptions->contains('id', (int) $this->subregion)) {
            $query->where('subregion_id', (int) $this->subregion);
        }

        if (isset(SightInfo::CATEGORIES[$this->category])) {
            $query->where('category', $this->category);
        }

        if ($this->highlight === 'yes') {
            $query->where('is_highlight', true);
        }

        self::whereStatus($query, $this->status);

        if ($this->coordinates === 'missing') {
            $query->where(fn ($query) => $query->whereNull('lat')->orWhereNull('lng'));
        }

        $direction = $this->sortDirection();

        match ($this->sortColumn()) {
            'country' => $query->orderBy(Country::query()->withTrashed()->selectRaw(MasterData::nameSql('countries'))->whereColumn('countries.id', 'sights.country_id')->limit(1), $direction),
            'region' => $query->orderBy(Region::query()->withTrashed()->selectRaw(MasterData::nameSql('regions'))->whereColumn('regions.id', 'sights.region_id')->limit(1), $direction),
            'order' => $query->orderBy('region_id', $direction)->orderByDesc('is_highlight')->orderBy('sort_order', $direction),
            'updated' => $query->orderBy('updated_at', $direction),
            default => $query->orderByRaw(MasterData::nameSql('sights').' '.$direction),
        };

        return $query->orderByRaw(MasterData::nameSql('sights'))->orderBy('sights.id')->paginate(self::PER_PAGE);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.sights.index');
    }
}
