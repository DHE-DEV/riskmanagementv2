<?php

namespace App\Livewire\AdminV2\MasterData\Regions;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsCoordinates;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\RunsAiAssistant;
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
 * Stammdaten > Regionen: eine Region anlegen oder bearbeiten.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsCoordinates, EditsMasterData, RunsAiAssistant;

    /** So viele Staedte zeigt die Seitenspalte. */
    public const RELATED_LIMIT = 12;

    public string $nameDe = '';

    public string $nameEn = '';

    public string $code = '';

    public string $countryId = '';

    public string $description = '';

    /** Kommagetrennt */
    public string $keywords = '';

    public function mount(?int $region = null): void
    {
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
        $this->description = (string) $record->description;
        $this->keywords = implode(', ', $record->keywords ?? []);
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

    public function save(bool $another = false): void
    {
        $this->code = trim($this->code);
        $this->normalizeCoordinates();

        $this->validate([
            'nameDe' => ['required', 'string', 'max:255'],
            'nameEn' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10'],
            'countryId' => ['required', Rule::in($this->countryOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'description' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'string', 'max:1000'],
        ] + $this->coordinateRules(), [
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
            'description' => trim($this->description) ?: null,
            'keywords' => $this->tags($this->keywords),
        ] + $this->coordinateValues())->save();

        unset($this->cities);

        $this->finishSave($record, $created, $another);
    }

    protected function aiModelType(): string
    {
        return 'Region';
    }

    protected function aiPlaceholderData(): array
    {
        $region = $this->record;
        $country = $region->country()->withTrashed()->first();

        return [
            'name' => $region->getName('de'),
            'name_en' => $region->getName('en'),
            'code' => $region->code,
            'country' => $country?->getName('de') ?? 'N/A',
            'country_en' => $country?->getName('en') ?? 'N/A',
            'description' => $region->description ?? 'N/A',
            'keywords' => is_array($region->keywords) ? implode(', ', $region->keywords) : 'N/A',
            'lat' => $region->lat ?? 'N/A',
            'lng' => $region->lng ?? 'N/A',
            'cities_count' => $region->cities()->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.regions.editor')
            ->title($this->record ? $this->record->getName('de') : 'Neue Region');
    }
}
