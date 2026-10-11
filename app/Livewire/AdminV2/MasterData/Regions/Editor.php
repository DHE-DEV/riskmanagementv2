<?php

namespace App\Livewire\AdminV2\MasterData\Regions;

use App\Jobs\FillRegionInfoJob;
use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsCoordinates;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\EditsRegionInfo;
use App\Livewire\AdminV2\Concerns\RunsAiChecks;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Support\AdminV2\MasterData;
use App\Support\AdminV2\RegionInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Stammdaten > Regionen: eine Region anlegen oder bearbeiten – mit den
 * Regionsinfos (Beschreibung, Reiseinfos, Fakten), die nur fuer diese Region
 * gelten (siehe RegionInfo) und sich per KI vorbefuellen lassen.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsCoordinates, EditsMasterData, EditsRegionInfo, RunsAiChecks;

    /** So viele Staedte zeigt die Seitenspalte. */
    public const RELATED_LIMIT = 12;

    public string $nameDe = '';

    public string $nameEn = '';

    public string $code = '';

    public string $countryId = '';

    /** Kommagetrennt */
    public string $keywords = '';

    /** Laufender KI-Lauf fuer die Sehenswuerdigkeiten dieser Region (RegionInfoRun) */
    public ?int $sightsRunId = null;

    public function mount(?int $region = null): void
    {
        $this->info = RegionInfo::toForm(null);

        if ($region === null) {
            // Vorbelegt, wenn die Seite aus einem Land heraus geoeffnet wird.
            $country = (int) request()->query('country');
            $this->countryId = $country && Country::whereKey($country)->exists() ? (string) $country : '';

            return;
        }

        $record = Region::withTrashed()->findOrFail($region);

        $this->recordId = $record->id;
        $this->nameDe = (string) ($record->name_translations['de'] ?? '');
        $this->nameEn = (string) ($record->name_translations['en'] ?? '');
        $this->code = (string) $record->code;
        $this->countryId = (string) $record->country_id;
        $this->keywords = implode(', ', $record->keywords ?? []);
        $this->info = RegionInfo::toForm($record->info);
        $this->infoMeta = (array) ($record->info['meta'] ?? []);
        $this->fillCoordinates($record);
    }

    protected function masterDataModel(): string
    {
        return Region::class;
    }

    protected function routeBase(): string
    {
        return 'adminv2.master-data.regions';
    }

    protected function createAnotherParameters(Model $record): array
    {
        return ['country' => $record->country_id];
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()
            // Das Land eines Altbestands bleibt waehlbar, auch wenn es im Papierkorb liegt.
            ->when($this->record?->country_id, fn ($query, $id) => $query->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $id)))
            ->orderByRaw(MasterData::nameSql('countries'))
            ->get(['id', 'iso_code', 'name_translations']);
    }

    /**
     * Die Staedte der Region: Anzahl und die groessten fuer die Seitenspalte.
     *
     * @return array{count: int, items: Collection}|null
     */
    #[Computed]
    public function cities(): ?array
    {
        return $this->record ? [
            'count' => $this->record->cities()->count(),
            'items' => $this->record->cities()->orderByDesc('is_regional_capital')->orderByDesc('population')->limit(self::RELATED_LIMIT)->get(),
        ] : null;
    }

    /**
     * Die Teilgebiete der Region (Inseln, Reisegebiete) fuer die Seitenspalte.
     */
    #[Computed]
    public function subregions(): ?Collection
    {
        return $this->record?->subregions()->withCount('sights')->get();
    }

    /**
     * Die Sehenswuerdigkeiten der Region fuer die Seitenspalte, Highlights zuerst.
     *
     * @return array{count: int, items: Collection}|null
     */
    #[Computed]
    public function sights(): ?array
    {
        return $this->record ? [
            'count' => $this->record->sights()->count(),
            'items' => $this->record->sights()->with('city')->orderByDesc('is_highlight')->orderBy('sort_order')->limit(self::RELATED_LIMIT)->get(),
        ] : null;
    }

    /**
     * Sehenswuerdigkeiten der Region per KI anlegen lassen (im Hintergrund,
     * dauert zwei bis drei Minuten). Bestehende Eintraege bleiben, Dubletten
     * werden uebersprungen.
     */
    public function startSightsSuggestion(): void
    {
        if (! $this->record) {
            return;
        }

        $run = RegionInfoRun::create([
            'kind' => RegionInfoRun::KIND_SIGHTS_ONE,
            'region_id' => $this->record->id,
            'status' => RegionInfoRun::STATUS_RUNNING,
            'pending' => [$this->record->id],
            'total' => 1,
            'failed' => [],
            'started_by' => auth('web')->id(),
        ]);

        $this->sightsRunId = $run->id;
        FillRegionInfoJob::dispatch($run->id);

        $this->checkSightsSuggestion();
    }

    public function checkSightsSuggestion(): void
    {
        if (! $this->sightsRunId) {
            return;
        }

        $run = RegionInfoRun::query()->find($this->sightsRunId);

        if ($run?->isRunning()) {
            return;
        }

        $this->sightsRunId = null;
        unset($this->sights);

        $failed = (array) ($run?->failed ?? []);
        $created = (int) ($run?->result['created'] ?? 0);
        $run?->delete();

        $this->dispatch('adminv2-toast', ...($failed !== [] || ! $run
            ? ['message' => 'Sehenswürdigkeiten konnten nicht angelegt werden: '.(reset($failed) ?: 'unbekannter Fehler'), 'variant' => 'danger']
            : ['message' => $created > 0 ? $created.' Sehenswürdigkeiten als KI-Entwurf angelegt.' : 'Keine neuen Sehenswürdigkeiten – die Vorschläge gab es schon.']));
    }

    /**
     * @return array<string, mixed>
     */
    protected function suggestionRunAttributes(): array
    {
        return ['region_id' => $this->record->id];
    }

    public function save(bool $another = false): void
    {
        $this->code = trim($this->code);
        $this->normalizeCoordinates();

        $this->validate([
            'nameDe' => ['required', 'string', 'max:255'],
            'nameEn' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10'],
            'countryId' => ['required', Rule::in($this->countryOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'keywords' => ['nullable', 'string', 'max:1000'],
            'info.population' => ['nullable', 'regex:/^[0-9 .]*$/'],
            'info.area_km2' => ['nullable', 'regex:/^[0-9 .]*$/'],
            'info.timezone' => ['nullable', Rule::in(\DateTimeZone::listIdentifiers())],
            'info.best_months.*' => ['integer', 'between:1,12'],
        ] + $this->infoTextRules() + $this->coordinateRules(), [
            'info.population.regex' => 'Bitte nur eine ganze Zahl angeben.',
            'info.area_km2.regex' => 'Bitte nur eine ganze Zahl angeben.',
            'info.timezone.in' => 'Bitte eine Zeitzone aus der Liste wählen.',
            'info.texts.*.*.max' => 'Der Text ist zu lang (höchstens :max Zeichen).',
            'nameDe.required' => 'Bitte den deutschen Namen angeben.',
            'code.required' => 'Bitte einen Code angeben, z. B. BY für Bayern.',
            'code.max' => 'Der Code darf höchstens 10 Zeichen haben.',
            'countryId.required' => 'Bitte ein Land wählen.',
            'countryId.in' => 'Bitte ein Land wählen.',
        ] + $this->coordinateMessages());

        $record = $this->record ?? new Region;
        $created = ! $record->exists;

        $record->fill([
            'name_translations' => $this->translations($record->name_translations, $this->nameDe, $this->nameEn),
            'code' => $this->code,
            'country_id' => (int) $this->countryId,
            'keywords' => $this->tags($this->keywords),
            'info' => RegionInfo::fromForm($this->info, Country::withTrashed()->find((int) $this->countryId), $this->infoMeta),
        ] + $this->coordinateValues())->save();

        unset($this->cities);

        $this->finishSave($record, $created, $another);
    }

    /**
     * Zeitzone des Landes – die Region braucht nur eine eigene, wenn sie abweicht.
     */
    #[Computed]
    public function countryTimezone(): ?string
    {
        return $this->countryOptions->firstWhere('id', (int) $this->countryId)?->timezone ?: Country::withTrashed()->find((int) $this->countryId)?->timezone;
    }

    protected function aiArea(): string
    {
        return 'regions';
    }

    /**
     * Die aktuellen Formularwerte zu den Platzhaltern (siehe AiAreas).
     */
    protected function aiContext(): array
    {
        $cities = $this->cities;

        return [
            'name' => $this->nameDe,
            'name_en' => $this->nameEn,
            'code' => $this->code,
            'country' => $this->countryOptions->firstWhere('id', (int) $this->countryId)?->getName('de'),
            'description' => (string) ($this->info['texts']['short_description'][CustomEvent::sourceLocale()] ?? ''),
            'keywords' => $this->keywords,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'cities_count' => $cities['count'] ?? 0,
            'cities' => $cities ? $cities['items']->map(fn ($city) => $city->getName('de'))->all() : [],
        ];
    }

    /**
     * Vorschlag der KI-Feldpruefung in das Formular uebernehmen.
     */
    protected function aiApply(string $key, string $value): bool
    {
        switch ($key) {
            case 'name': $this->nameDe = $value;

                return true;
            case 'name_en': $this->nameEn = $value;

                return true;
            case 'code': $this->code = mb_strtoupper($value);

                return true;
            case 'description': $this->info['texts']['short_description'][CustomEvent::sourceLocale()] = $value;

                return true;
            case 'keywords': $this->keywords = $value;

                return true;
            case 'lat': $this->lat = $this->aiNumber($value);

                return true;
            case 'lng': $this->lng = $this->aiNumber($value);

                return true;
            case 'country':
                $country = $this->aiMatch($this->countryOptions, $value, fn ($country) => $country->getName('de'))
                    ?? $this->aiMatch($this->countryOptions, $value, fn ($country) => (string) $country->iso_code);
                if ($country) {
                    $this->countryId = (string) $country->id;
                }

                return $country !== null;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.regions.editor')
            ->title($this->record ? $this->record->getName('de') : 'Neue Region');
    }
}
