<?php

namespace App\Livewire\AdminV2\MasterData\Regions;

use App\Jobs\FillRegionInfoJob;
use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\ManagesMasterDataList;
use App\Models\Country;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Support\AdminV2\MasterData;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\RegionInfoFillRun;
use App\Support\AiSettings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Stammdaten > Regionen: Liste mit Suche, Filtern, Sortierung und Papierkorb –
 * und die KI-Vorbefuellung der Regionsinfos fuer die gefilterten Regionen.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Regionen')]
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

    /** '' = alle, 'missing' = ohne Koordinaten */
    #[Url(except: '')]
    public string $coordinates = '';

    /** '' = alle, sonst ein Zustand aus RegionInfo::STATUSES */
    #[Url(except: '')]
    public string $info = '';

    /** KI-Vorbefuellung: was – "fill" (Regionsinfos) oder "sights" (Sehenswuerdigkeiten) */
    public string $fillKind = RegionInfoRun::KIND_FILL;

    /** KI-Vorbefuellung: auch Regionen, die schon Infos haben */
    public bool $fillAll = false;

    /** KI-Vorbefuellung: vorhandene Texte ueberschreiben */
    public bool $fillOverwrite = false;

    protected function masterDataModel(): string
    {
        return Region::class;
    }

    public function sortOptions(): array
    {
        return ['name' => 'Name', 'code' => 'Code', 'country' => 'Land', 'cities_count' => 'Anzahl Städte'];
    }

    protected function filterProperties(): array
    {
        return ['countryIds', 'coordinates', 'info'];
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()->orderByRaw(MasterData::nameSql('countries'))->get(['id', 'iso_code', 'name_translations']);
    }

    /**
     * Die Filter der Liste (ohne Sortierung) – auch Grundlage der KI-Vorbefuellung.
     */
    protected function filteredQuery(): Builder
    {
        $query = $this->listQuery(['code']);

        if ($countryIds = array_filter(array_map('intval', $this->countryIds))) {
            $query->whereIn('country_id', $countryIds);
        }

        if ($this->coordinates === 'missing') {
            $query->where(fn ($query) => $query->whereNull('lat')->orWhereNull('lng'));
        }

        self::whereInfoStatus($query, $this->info);

        return $query;
    }

    public static function whereInfoStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            RegionInfo::STATUS_EMPTY => $query->whereNull('info'),
            RegionInfo::STATUS_AI => $query->whereNotNull('info->meta->ai_generated_at')->whereNull('info->meta->reviewed_at'),
            RegionInfo::STATUS_REVIEWED => $query->whereNotNull('info->meta->reviewed_at'),
            RegionInfo::STATUS_MANUAL => $query->whereNotNull('info')->whereNull('info->meta->ai_generated_at')->whereNull('info->meta->reviewed_at'),
            default => $query,
        };
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = $this->filteredQuery()
            ->with(['country' => fn ($query) => $query->withTrashed()])
            ->withCount('cities');

        $direction = $this->sortDirection();

        match ($this->sortColumn()) {
            'code' => $query->orderBy('code', $direction),
            'country' => $query->orderBy(
                Country::query()->withTrashed()->selectRaw(MasterData::nameSql('countries'))->whereColumn('countries.id', 'regions.country_id')->limit(1),
                $direction,
            ),
            'cities_count' => $query->orderBy('cities_count', $direction),
            default => $query->orderByRaw(MasterData::nameSql('regions').' '.$direction),
        };

        return $query->orderByRaw(MasterData::nameSql('regions'))->paginate(self::PER_PAGE);
    }

    /**
     * So viele Regionen wuerde die KI-Vorbefuellung mit den aktuellen Filtern bearbeiten.
     */
    #[Computed]
    public function fillCount(): int
    {
        return $this->fillQuery()->count();
    }

    public function updatedFillKind(): void
    {
        unset($this->fillCount);
    }

    protected function fillQuery(): Builder
    {
        $query = $this->filteredQuery()->withoutTrashed();

        if ($this->fillKind === RegionInfoRun::KIND_SIGHTS) {
            return $this->fillAll ? $query : $query->whereDoesntHave('sights');
        }

        return $this->fillAll || $this->fillOverwrite ? $query : $query->whereNull('info');
    }

    /**
     * Die Laeufe der Liste, die gerade laufen oder noch nicht ausgeblendet sind.
     *
     * @return \Illuminate\Support\Collection<int, RegionInfoRun>
     */
    #[Computed]
    public function fillRuns(): \Illuminate\Support\Collection
    {
        return collect([RegionInfoRun::KIND_FILL, RegionInfoRun::KIND_SIGHTS])
            ->map(fn (string $kind) => RegionInfoFillRun::current($kind))
            ->filter()
            ->values();
    }

    public function startFill(): void
    {
        $this->modal('region-info-fill')->close();

        $kind = $this->fillKind === RegionInfoRun::KIND_SIGHTS ? RegionInfoRun::KIND_SIGHTS : RegionInfoRun::KIND_FILL;

        if (RegionInfoFillRun::isRunning($kind)) {
            $this->dispatch('adminv2-toast', message: 'Es läuft bereits eine KI-Vorbefüllung.', variant: 'danger');

            return;
        }

        if (blank(AiSettings::apiKey())) {
            $this->dispatch('adminv2-toast', message: 'Kein OpenAI-Schlüssel hinterlegt (System > KI).', variant: 'danger');

            return;
        }

        $ids = $this->fillQuery()->orderBy('country_id')->orderBy('id')->pluck('id')->all();

        if ($ids === []) {
            $this->dispatch('adminv2-toast', message: $kind === RegionInfoRun::KIND_SIGHTS ? 'Keine Region zu bearbeiten – alle gefilterten Regionen haben schon Sehenswürdigkeiten.' : 'Keine Region zu bearbeiten – alle gefilterten Regionen haben schon Infos.', variant: 'danger');

            return;
        }

        $run = RegionInfoFillRun::start($ids, $kind === RegionInfoRun::KIND_FILL && $this->fillOverwrite, auth('web')->id(), $kind);
        FillRegionInfoJob::dispatch($run->id);

        unset($this->fillRuns, $this->rows);
        $this->dispatch('adminv2-toast', message: count($ids).' '.(count($ids) === 1 ? 'Region wird' : 'Regionen werden').' im Hintergrund vorbefüllt.');
    }

    public function cancelFill(string $kind = RegionInfoRun::KIND_FILL): void
    {
        RegionInfoFillRun::cancel($kind);
        unset($this->fillRuns);
        $this->dispatch('adminv2-toast', message: 'KI-Vorbefüllung angehalten. Bereits gefüllte Regionen bleiben gespeichert.');
    }

    public function dismissFill(string $kind = RegionInfoRun::KIND_FILL): void
    {
        RegionInfoFillRun::dismiss($kind);

        unset($this->fillRuns);
    }

    public function refreshFill(): void
    {
        unset($this->fillRuns, $this->rows);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.regions.index');
    }
}
