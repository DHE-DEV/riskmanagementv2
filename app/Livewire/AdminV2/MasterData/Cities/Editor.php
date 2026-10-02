<?php

namespace App\Livewire\AdminV2\MasterData\Cities;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsCoordinates;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\RunsAiAssistant;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use App\Support\AdminV2\MasterData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Stammdaten > Staedte: eine Stadt anlegen oder bearbeiten.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsCoordinates, EditsMasterData, RunsAiAssistant;

    public string $nameDe = '';

    public string $nameEn = '';

    public string $countryId = '';

    public string $regionId = '';

    public bool $isCapital = false;

    public bool $isRegionalCapital = false;

    public string $population = '';

    public function mount(?int $city = null): void
    {
        if ($city === null) {
            // Vorbelegt, wenn die Seite aus einem Land oder einer Region heraus geoeffnet wird.
            if ($region = Region::find((int) request()->query('region'))) {
                $this->regionId = (string) $region->id;
                $this->countryId = (string) $region->country_id;
            } elseif (Country::whereKey((int) request()->query('country'))->exists()) {
                $this->countryId = (string) (int) request()->query('country');
            }

            return;
        }

        $record = City::withTrashed()->findOrFail($city);

        $this->recordId = $record->id;
        $this->nameDe = (string) ($record->name_translations['de'] ?? '');
        $this->nameEn = (string) ($record->name_translations['en'] ?? '');
        $this->countryId = (string) $record->country_id;
        $this->regionId = (string) $record->region_id;
        $this->isCapital = (bool) $record->is_capital;
        $this->isRegionalCapital = (bool) $record->is_regional_capital;
        $this->population = (string) $record->population;
        $this->fillCoordinates($record);
    }

    protected function masterDataModel(): string
    {
        return City::class;
    }

    protected function routeBase(): string
    {
        return 'adminv2.master-data.cities';
    }

    protected function createAnotherParameters(Model $record): array
    {
        return array_filter(['country' => $record->country_id, 'region' => $record->region_id]);
    }

    public function updatedCountryId(): void
    {
        // Die Region gehoert zu einem Land – passt sie nicht mehr, faellt sie weg.
        if ($this->regionId !== '' && ! $this->regionOptions->contains('id', (int) $this->regionId)) {
            $this->regionId = '';
        }
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
     * Die Regionen des gewaehlten Landes.
     */
    #[Computed]
    public function regionOptions(): Collection
    {
        if ($this->countryId === '') {
            return collect();
        }

        return Region::query()
            ->where('country_id', (int) $this->countryId)
            ->orderByRaw(MasterData::nameSql('regions'))
            ->get(['id', 'code', 'name_translations']);
    }

    /**
     * Andere Hauptstaedte des gewaehlten Landes – als Hinweis, kein Hindernis:
     * manche Laender haben mehrere.
     */
    #[Computed]
    public function otherCapitals(): Collection
    {
        if ($this->countryId === '') {
            return collect();
        }

        return City::query()
            ->where('country_id', (int) $this->countryId)
            ->where('is_capital', true)
            ->when($this->recordId, fn ($query, $id) => $query->whereKeyNot($id))
            ->get();
    }

    /**
     * Die Flughaefen der Stadt – zum Nachsehen.
     */
    #[Computed]
    public function airports(): Collection
    {
        return $this->record ? $this->record->airports()->orderBy('name')->get(['id', 'name', 'iata_code', 'icao_code']) : collect();
    }

    public function save(bool $another = false): void
    {
        $this->population = str_replace(['.', ' '], '', trim($this->population));
        $this->normalizeCoordinates();
        unset($this->regionOptions);

        $this->validate([
            'nameDe' => ['required', 'string', 'max:255'],
            'nameEn' => ['nullable', 'string', 'max:255'],
            'countryId' => ['required', Rule::in($this->countryOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'regionId' => ['nullable', Rule::in($this->regionOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'population' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
        ] + $this->coordinateRules(), [
            'nameDe.required' => 'Bitte den deutschen Namen angeben.',
            'countryId.required' => 'Bitte ein Land wählen.',
            'countryId.in' => 'Bitte ein Land wählen.',
            'regionId.in' => 'Diese Region gehört nicht zum gewählten Land.',
            'population.integer' => 'Die Bevölkerung muss eine ganze Zahl sein.',
        ] + $this->coordinateMessages());

        $record = $this->record ?? new City;
        $created = ! $record->exists;

        $record->fill([
            'name_translations' => $this->translations($record->name_translations, $this->nameDe, $this->nameEn),
            'country_id' => (int) $this->countryId,
            'region_id' => $this->regionId === '' ? null : (int) $this->regionId,
            'is_capital' => $this->isCapital,
            'is_regional_capital' => $this->isRegionalCapital,
            'population' => $this->population === '' ? null : (int) $this->population,
        ] + $this->coordinateValues())->save();

        unset($this->otherCapitals, $this->airports);

        $this->finishSave($record, $created, $another);
    }

    protected function aiModelType(): string
    {
        return 'City';
    }

    protected function aiPlaceholderData(): array
    {
        $city = $this->record;
        $country = $city->country()->withTrashed()->first();
        $region = $city->region()->withTrashed()->first();

        return [
            'name' => $city->getName('de'),
            'name_en' => $city->getName('en'),
            'country' => $country?->getName('de') ?? 'N/A',
            'country_en' => $country?->getName('en') ?? 'N/A',
            'country_code' => $country?->iso_code ?? 'N/A',
            'region' => $region?->getName('de') ?? 'N/A',
            'region_en' => $region?->getName('en') ?? 'N/A',
            'population' => $city->population ?? 'N/A',
            'is_capital' => $city->is_capital ? 'Ja' : 'Nein',
            'is_regional_capital' => $city->is_regional_capital ? 'Ja' : 'Nein',
            'lat' => $city->lat ?? 'N/A',
            'lng' => $city->lng ?? 'N/A',
        ];
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.cities.editor')
            ->title($this->record ? $this->record->getName('de') : 'Neue Stadt');
    }
}
