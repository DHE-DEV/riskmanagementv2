<?php

namespace App\Livewire\AdminV2\MasterData\Continents;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsCoordinates;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\RunsAiChecks;
use App\Models\Continent;
use App\Support\AdminV2\MasterData;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Stammdaten > Kontinente: einen Kontinent anlegen oder bearbeiten.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsCoordinates, EditsMasterData, RunsAiChecks;

    public string $nameDe = '';

    public string $nameEn = '';

    public string $code = '';

    public string $sortOrder = '0';

    public string $description = '';

    /** Kommagetrennt */
    public string $keywords = '';

    public function mount(?int $continent = null): void
    {
        if ($continent === null) {
            $this->sortOrder = (string) ((int) Continent::max('sort_order') + 1);

            return;
        }

        $record = Continent::withTrashed()->findOrFail($continent);

        $this->recordId = $record->id;
        $this->nameDe = (string) ($record->name_translations['de'] ?? '');
        $this->nameEn = (string) ($record->name_translations['en'] ?? '');
        $this->code = (string) $record->code;
        $this->sortOrder = (string) $record->sort_order;
        $this->description = (string) $record->description;
        $this->keywords = implode(', ', $record->keywords ?? []);
        $this->fillCoordinates($record);
    }

    protected function masterDataModel(): string
    {
        return Continent::class;
    }

    protected function routeBase(): string
    {
        return 'adminv2.master-data.continents';
    }

    /**
     * Die Laender des Kontinents.
     */
    #[Computed]
    public function countries(): Collection
    {
        return $this->record
            ? $this->record->countries()->orderByRaw(MasterData::nameSql('countries'))->get(['id', 'iso_code', 'name_translations'])
            : collect();
    }

    public function save(bool $another = false): void
    {
        $this->code = trim($this->code);
        $this->normalizeCoordinates();

        // Ein unveraenderter Code wird nicht erneut auf Eindeutigkeit geprueft –
        // sonst liessen sich Altbestaende mit doppeltem Code nicht mehr speichern.
        $codeChanged = ! $this->record || mb_strtolower((string) $this->record->code) !== mb_strtolower($this->code);

        $this->validate([
            'nameDe' => ['required', 'string', 'max:255'],
            'nameEn' => ['required', 'string', 'max:255'],
            'code' => array_filter(['required', 'string', 'max:5', $codeChanged ? Rule::unique('continents', 'code')->ignore($this->recordId) : null]),
            'sortOrder' => ['required', 'integer', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'string', 'max:1000'],
        ] + $this->coordinateRules(), [
            'nameDe.required' => 'Bitte den deutschen Namen angeben.',
            'nameEn.required' => 'Bitte den englischen Namen angeben.',
            'code.required' => 'Bitte einen Code angeben, z. B. EU für Europa.',
            'code.max' => 'Der Code darf höchstens 5 Zeichen haben.',
            'code.unique' => 'Diesen Code trägt bereits ein anderer Kontinent (auch der Papierkorb zählt).',
            'sortOrder.required' => 'Bitte eine Zahl für die Sortierung angeben.',
            'sortOrder.integer' => 'Die Sortierung muss eine ganze Zahl sein.',
        ] + $this->coordinateMessages());

        $record = $this->record ?? new Continent;
        $created = ! $record->exists;

        $record->fill([
            'name_translations' => $this->translations($record->name_translations, $this->nameDe, $this->nameEn),
            'code' => $this->code,
            'sort_order' => (int) $this->sortOrder,
            'description' => trim($this->description) ?: null,
            'keywords' => $this->tags($this->keywords),
        ] + $this->coordinateValues())->save();

        $this->finishSave($record, $created, $another);
    }

    protected function aiArea(): string
    {
        return 'continents';
    }

    /**
     * Die aktuellen Formularwerte zu den Platzhaltern (siehe AiAreas).
     */
    protected function aiContext(): array
    {
        return [
            'name' => $this->nameDe,
            'name_en' => $this->nameEn,
            'code' => $this->code,
            'sort_order' => $this->sortOrder,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'countries_count' => $this->countries->count(),
            'countries' => $this->countries->map(fn ($country) => $country->getName('de').' ('.$country->iso_code.')')->all(),
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
            case 'sort_order': $this->sortOrder = $this->aiNumber($value);

                return true;
            case 'description': $this->description = $value;

                return true;
            case 'keywords': $this->keywords = $value;

                return true;
            case 'lat': $this->lat = $this->aiNumber($value);

                return true;
            case 'lng': $this->lng = $this->aiNumber($value);

                return true;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.continents.editor')
            ->title($this->record ? $this->record->getName('de') : 'Neuer Kontinent');
    }
}
