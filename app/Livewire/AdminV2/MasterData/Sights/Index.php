<?php

namespace App\Livewire\AdminV2\MasterData\Sights;

use App\Jobs\FillRegionInfoJob;
use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\ManagesMasterDataList;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Models\Sight;
use App\Models\Subregion;
use App\Services\SightGenerator;
use App\Support\AdminV2\MasterData;
use App\Support\AdminV2\RegionInfoFillRun;
use App\Support\AdminV2\SightInfo;
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
 * Stammdaten > Sehenswuerdigkeiten: Liste mit Suche, Filtern, Sortierung und Papierkorb.
 *
 * Mit genau einem gefilterten Land: „Mit KI ergänzen“ legt fuer Regionen, Teilgebiete und wichtige Staedte des
 * Landes Sehenswuerdigkeiten an (im Hintergrund, FillRegionInfoJob), „Per KI suchen“ findet eine bestimmte
 * Sehenswuerdigkeit und legt sie nach Vorschau an. Alles als ungepruefter KI-Entwurf.
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

    /** KI-Lauf: auch Gebiete, die schon Sehenswuerdigkeiten haben (ergaenzt nur Neue) */
    public bool $aiAll = false;

    public bool $aiCities = true;

    public bool $aiSubregions = true;

    /** „Per KI suchen“: Suchbegriff, Ergebnis (Vorschau) bzw. Fehlermeldung */
    public string $aiQuery = '';

    /** @var array<string, mixed>|null */
    public ?array $aiFound = null;

    public ?string $aiError = null;

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

    // MARK: KI fuer ein Land

    /** Das Land, wenn genau eines gefiltert ist (Voraussetzung fuer die KI-Funktionen). */
    #[Computed]
    public function aiCountry(): ?Country
    {
        $ids = array_values(array_filter(array_map('intval', $this->countryIds)));

        return count($ids) === 1 ? Country::query()->find($ids[0]) : null;
    }

    /**
     * Gebiete des Landes, die der KI-Lauf bearbeiten wuerde – Regionen, Teilgebiete, wichtige Staedte
     * (Hauptstadt, Regionshauptstaedte, grosse Staedte); ohne „auch vorhandene“ nur Gebiete ohne Sehenswuerdigkeiten.
     *
     * @return array{region: array<int, int>, subregion: array<int, int>, city: array<int, int>}
     */
    #[Computed]
    public function aiTargets(): array
    {
        $country = $this->aiCountry;
        if (! $country) {
            return ['region' => [], 'subregion' => [], 'city' => []];
        }
        $regionIds = Region::query()->where('country_id', $country->id)->pluck('id');
        $regions = Region::query()->whereIn('id', $regionIds)->when(! $this->aiAll, fn ($q) => $q->whereDoesntHave('sights'))->orderBy('id')->pluck('id')->all();
        $subregions = ! $this->aiSubregions ? [] : Subregion::query()->whereIn('region_id', $regionIds)
            ->when(! $this->aiAll, fn ($q) => $q->whereNotIn('id', Sight::query()->whereNotNull('subregion_id')->select('subregion_id')))
            ->orderBy('region_id')->orderBy('sort_order')->pluck('id')->all();
        $cities = ! $this->aiCities ? [] : City::query()->where('country_id', $country->id)->whereNotNull('region_id')
            ->where(fn ($q) => $q->where('is_capital', true)->orWhere('is_regional_capital', true)->orWhere('is_major', true))
            ->when(! $this->aiAll, fn ($q) => $q->whereNotIn('id', Sight::query()->whereNotNull('city_id')->select('city_id')))
            ->orderByDesc('population')->pluck('id')->all();

        return ['region' => $regions, 'subregion' => $subregions, 'city' => $cities];
    }

    public function updatedAiAll(): void
    {
        unset($this->aiTargets);
    }

    public function updatedAiCities(): void
    {
        unset($this->aiTargets);
    }

    public function updatedAiSubregions(): void
    {
        unset($this->aiTargets);
    }

    /** Laufender bzw. zuletzt beendeter (nicht ausgeblendeter) Laender-Lauf. */
    #[Computed]
    public function aiRun(): ?RegionInfoRun
    {
        return RegionInfoFillRun::current(RegionInfoRun::KIND_SIGHTS_COUNTRY);
    }

    public function aiRunCountry(): ?Country
    {
        $id = $this->aiRun?->result['country_id'] ?? null;

        return $id ? Country::query()->find($id) : null;
    }

    public function startAiFill(): void
    {
        $this->modal('sights-ai-fill')->close();
        $country = $this->aiCountry;

        if (! $country) {
            return;
        }
        if (RegionInfoFillRun::isRunning(RegionInfoRun::KIND_SIGHTS_COUNTRY)) {
            $this->dispatch('adminv2-toast', message: 'Es läuft bereits ein KI-Lauf für Sehenswürdigkeiten.', variant: 'danger');

            return;
        }
        if (blank(AiSettings::apiKey())) {
            $this->dispatch('adminv2-toast', message: 'Kein OpenAI-Schlüssel hinterlegt (System > KI).', variant: 'danger');

            return;
        }

        $targets = $this->aiTargets;
        $keys = array_merge(
            array_map(fn ($id) => 'region:'.$id, $targets['region']),
            array_map(fn ($id) => 'subregion:'.$id, $targets['subregion']),
            array_map(fn ($id) => 'city:'.$id, $targets['city']),
        );

        if ($keys === []) {
            $this->dispatch('adminv2-toast', message: 'Nichts zu bearbeiten – alle Gebiete haben schon Sehenswürdigkeiten.', variant: 'danger');

            return;
        }

        // Ein frueher beendeter Lauf verschwindet aus der Anzeige.
        RegionInfoRun::query()->where('kind', RegionInfoRun::KIND_SIGHTS_COUNTRY)->where('status', '!=', RegionInfoRun::STATUS_RUNNING)
            ->update(['status' => RegionInfoRun::STATUS_DISMISSED]);
        $run = RegionInfoRun::create([
            'kind' => RegionInfoRun::KIND_SIGHTS_COUNTRY,
            'status' => RegionInfoRun::STATUS_RUNNING,
            'pending' => $keys,
            'total' => count($keys),
            'failed' => [],
            'result' => ['country_id' => $country->id, 'created' => 0],
            'started_by' => auth('web')->id(),
        ]);
        FillRegionInfoJob::dispatch($run->id);

        unset($this->aiRun);
        $this->dispatch('adminv2-toast', message: count($keys).' Gebiete in '.$country->getName('de').' werden im Hintergrund bearbeitet.');
    }

    public function cancelAiFill(): void
    {
        RegionInfoFillRun::cancel(RegionInfoRun::KIND_SIGHTS_COUNTRY);
        unset($this->aiRun);
        $this->dispatch('adminv2-toast', message: 'KI-Lauf angehalten. Bereits angelegte Sehenswürdigkeiten bleiben.');
    }

    public function dismissAiFill(): void
    {
        RegionInfoFillRun::dismiss(RegionInfoRun::KIND_SIGHTS_COUNTRY);
        unset($this->aiRun);
    }

    public function refreshAiFill(): void
    {
        unset($this->aiRun, $this->rows, $this->aiTargets);
    }

    // MARK: Per KI suchen

    public function openAiFind(): void
    {
        $this->reset('aiQuery', 'aiFound', 'aiError');
        $this->modal('sights-ai-find')->show();
    }

    /** Die KI nach genau dieser Sehenswuerdigkeit im gefilterten Land fragen (Vorschau, noch nichts angelegt). */
    public function aiFind(SightGenerator $generator): void
    {
        $this->aiFound = null;
        $this->aiError = null;
        $country = $this->aiCountry;
        $query = trim($this->aiQuery);

        if (! $country || mb_strlen($query) < 3) {
            $this->aiError = 'Bitte einen Namen mit mindestens drei Zeichen eingeben.';

            return;
        }
        if (blank(AiSettings::apiKey())) {
            $this->aiError = 'Kein OpenAI-Schlüssel hinterlegt (System > KI).';

            return;
        }

        @set_time_limit(150);
        try {
            $found = $generator->findOne($country, $query);
        } catch (\Throwable $e) {
            $this->aiError = str_contains($e->getMessage(), 'keine Sehenswürdigkeiten')
                ? "Die KI kennt „{$query}“ in {$country->getName('de')} nicht eindeutig. Anders schreiben oder genauer angeben (z. B. mit Ort)."
                : 'Die Suche hat nicht geklappt: '.mb_substr($e->getMessage(), 0, 200);

            return;
        }

        $s = $found['suggestion'];
        $this->aiFound = [
            'suggestion' => $s,
            'region_id' => $found['region']?->id,
            'subregion_id' => $found['subregion']?->id,
            'city_id' => $found['city']?->id,
            'existing_id' => $found['existing']?->id,
            // fuer die Vorschau
            'name' => $s['name']['de'],
            'place' => collect([$found['city']?->getName('de') ?: ($s['city'] ?: null), $found['subregion']?->getName('de'), $found['region']?->getName('de') ?? ($s['region'] ? $s['region'].' (nicht in der Datenbank)' : null)])->filter()->implode(' · '),
            'category' => SightInfo::categoryLabel($s['category']),
            'short' => $s['texts']['short_description']['de'] ?? '',
            'coordinates' => $s['lat'] !== null ? number_format($s['lat'], 5, '.', '').', '.number_format($s['lng'], 5, '.', '') : null,
        ];
    }

    /** Gefundene Sehenswuerdigkeit als KI-Entwurf anlegen und im Editor oeffnen. */
    public function aiCreate(SightGenerator $generator)
    {
        $country = $this->aiCountry;
        if (! $country || ! $this->aiFound || $this->aiFound['existing_id']) {
            return null;
        }

        @set_time_limit(60);
        $sight = $generator->createFound(
            $country,
            $this->aiFound['suggestion'],
            $this->aiFound['region_id'] ? Region::query()->find($this->aiFound['region_id']) : null,
            $this->aiFound['subregion_id'] ? Subregion::query()->find($this->aiFound['subregion_id']) : null,
            $this->aiFound['city_id'] ? City::query()->find($this->aiFound['city_id']) : null,
        );

        $this->modal('sights-ai-find')->close();
        session()->flash('adminv2-toast', '„'.$sight->getName('de').'“ als KI-Entwurf angelegt – bitte prüfen.');

        return $this->redirectRoute('adminv2.master-data.sights.edit', $sight->id);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.sights.index');
    }
}
