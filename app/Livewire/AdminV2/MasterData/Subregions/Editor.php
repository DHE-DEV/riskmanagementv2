<?php

namespace App\Livewire\AdminV2\MasterData\Subregions;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsCoordinates;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\EditsRegionInfo;
use App\Models\Region;
use App\Models\Subregion;
use App\Support\AdminV2\MasterData;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\SubregionInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Stammdaten > Teilgebiete: eine Insel, Inselgruppe oder ein Reisegebiet
 * einer Region anlegen oder bearbeiten – mit denselben Infos wie eine Region
 * (Beschreibung, Reiseinfos, Fakten), per KI vorbefuellbar.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsCoordinates, EditsMasterData, EditsRegionInfo;

    /** So viele Staedte und Sehenswuerdigkeiten zeigt die Seitenspalte. */
    public const RELATED_LIMIT = 12;

    /** @var array<string, string> Name je Sprache */
    public array $names = [];

    public string $regionId = '';

    public string $kind = 'island';

    public string $code = '';

    public int $sortOrder = 0;

    public function mount(?int $subregion = null): void
    {
        $this->info = RegionInfo::toForm(null);
        $this->names = array_fill_keys(RegionInfo::locales(), '');

        if ($subregion === null) {
            // Vorbelegt, wenn die Seite aus einer Region heraus geoeffnet wird.
            $region = (int) request()->query('region');
            $this->regionId = $region && Region::whereKey($region)->exists() ? (string) $region : '';

            return;
        }

        $record = Subregion::withTrashed()->findOrFail($subregion);

        $this->recordId = $record->id;
        foreach (RegionInfo::locales() as $locale) {
            $this->names[$locale] = (string) ($record->name_translations[$locale] ?? '');
        }
        $this->regionId = (string) $record->region_id;
        $this->kind = SubregionInfo::kind($record->kind);
        $this->code = (string) $record->code;
        $this->sortOrder = (int) $record->sort_order;
        $this->info = RegionInfo::toForm($record->info);
        $this->infoMeta = (array) ($record->info['meta'] ?? []);
        $this->fillCoordinates($record);
    }

    protected function masterDataModel(): string
    {
        return Subregion::class;
    }

    protected function routeBase(): string
    {
        return 'adminv2.master-data.subregions';
    }

    protected function createAnotherParameters(Model $record): array
    {
        return ['region' => $record->region_id];
    }

    protected function suggestionRunAttributes(): array
    {
        return ['region_id' => $this->record->region_id, 'subregion_id' => $this->record->id];
    }

    /**
     * Alle Regionen mit Land – die eines Altbestands bleibt waehlbar, auch im Papierkorb.
     */
    #[Computed]
    public function regionOptions(): Collection
    {
        return Region::query()
            ->when($this->record?->region_id, fn ($query, $id) => $query->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $id)))
            ->with('country:id,iso_code,name_translations,timezone')
            ->orderByRaw(MasterData::nameSql('regions'))
            ->get(['id', 'code', 'country_id', 'name_translations']);
    }

    #[Computed]
    public function region(): ?Region
    {
        return $this->regionOptions->firstWhere('id', (int) $this->regionId);
    }

    /**
     * Zeitzone des Landes – das Teilgebiet braucht nur eine eigene, wenn sie abweicht.
     */
    #[Computed]
    public function countryTimezone(): ?string
    {
        return $this->region?->country?->timezone;
    }

    /**
     * @return array{count: int, items: Collection}|null
     */
    #[Computed]
    public function cities(): ?array
    {
        return $this->record ? [
            'count' => $this->record->cities()->count(),
            'items' => $this->record->cities()->orderByDesc('population')->limit(self::RELATED_LIMIT)->get(),
        ] : null;
    }

    /**
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

    public function save(bool $another = false): void
    {
        $this->code = trim($this->code);
        $this->names = array_map(fn ($name) => trim((string) $name), $this->names);
        $this->normalizeCoordinates();

        $this->validate([
            'names.de' => ['required', 'string', 'max:255'],
            'names.*' => ['nullable', 'string', 'max:255'],
            'regionId' => ['required', Rule::in($this->regionOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'kind' => ['required', Rule::in(array_keys(SubregionInfo::KINDS))],
            'code' => ['nullable', 'string', 'max:20'],
            'sortOrder' => ['integer', 'min:0', 'max:9999'],
            'info.population' => ['nullable', 'regex:/^[0-9 .]*$/'],
            'info.area_km2' => ['nullable', 'regex:/^[0-9 .]*$/'],
            'info.timezone' => ['nullable', Rule::in(\DateTimeZone::listIdentifiers())],
            'info.best_months.*' => ['integer', 'between:1,12'],
        ] + $this->infoTextRules() + $this->coordinateRules(), [
            'names.de.required' => 'Bitte den deutschen Namen angeben.',
            'regionId.required' => 'Bitte eine Region wählen.',
            'regionId.in' => 'Bitte eine Region wählen.',
            'info.population.regex' => 'Bitte nur eine ganze Zahl angeben.',
            'info.area_km2.regex' => 'Bitte nur eine ganze Zahl angeben.',
            'info.timezone.in' => 'Bitte eine Zeitzone aus der Liste wählen.',
            'info.texts.*.*.max' => 'Der Text ist zu lang (höchstens :max Zeichen).',
        ] + $this->coordinateMessages());

        $region = $this->region;
        $record = $this->record ?? new Subregion;
        $created = ! $record->exists;

        $record->fill([
            'name_translations' => array_filter($this->names, fn ($name) => $name !== ''),
            'region_id' => $region->id,
            'country_id' => $region->country_id,
            'kind' => $this->kind,
            'code' => $this->code === '' ? null : $this->code,
            'sort_order' => $created && $this->sortOrder === 0 ? (int) Subregion::where('region_id', $region->id)->max('sort_order') + 1 : $this->sortOrder,
            'info' => RegionInfo::fromForm($this->info, $region->country, $this->infoMeta),
        ] + $this->coordinateValues())->save();

        unset($this->cities, $this->sights);

        $this->finishSave($record, $created, $another);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.subregions.editor')
            ->title($this->record ? $this->record->getName('de') : 'Neues Teilgebiet');
    }
}
