<?php

namespace App\Livewire\AdminV2\System;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\StartsAiEventSearch;
use App\Models\AiCheck;
use App\Models\AiEventSearchProfile;
use App\Models\AiEventSearchPrompt;
use App\Models\SystemSetting;
use App\Services\ChatGptService;
use App\Services\OpenAiModelService;
use App\Support\AdminV2\AiAreas;
use App\Support\AiSettings;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * System > KI: API-Schluessel hinterlegen und das Modell waehlen, das der
 * KI-Assistent und die Quellen-Pruefung verwenden.
 *
 * Der Schluessel wird verschluesselt gespeichert und nie wieder im Klartext
 * ausgegeben – die Seite zeigt nur seine letzten Zeichen.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('KI')]
class Ai extends Component
{
    use AuthorizesAdminV2;
    use StartsAiEventSearch;

    // KI-Vorlage (Formular im Dialog)
    /** Reiter: general, events oder ein Stammdaten-Bereich (AiAreas) */
    #[Url(except: 'general')]
    public string $tab = 'general';

    /** KI-Pruefung, die gerade bearbeitet wird; null = neue */
    #[Locked]
    public ?int $checkId = null;

    public string $checkArea = 'countries';

    public string $checkSection = '';

    public string $checkName = '';

    public string $checkDescription = '';

    public string $checkPrompt = '';

    public string $checkModel = '';

    public bool $checkActive = true;

    #[Locked]
    public ?int $promptId = null;

    public string $promptName = '';

    public string $promptText = '';

    public bool $promptIsDefault = false;

    /** Eingabefeld fuer einen neuen Schluessel; wird nach dem Speichern geleert. */
    public string $newApiKey = '';

    public string $model = '';

    public string $modelSearch = '';

    /** Preise des aktiven Modells in US-Dollar je 1 Mio. Token (fuer die Kostenanzeige). */
    public string $priceInput = '';

    public string $priceOutput = '';

    /** Fehlermeldung beim Laden der Modell-Liste. */
    public ?string $modelsError = null;

    /** @var array{ok: bool, message: string}|null Ergebnis des letzten Verbindungstests */
    public ?array $testResult = null;

    public function mount(): void
    {
        $this->model = AiSettings::model();
        $this->loadPrices();

        if (! isset($this->tabs()[$this->tab])) {
            $this->tab = 'general';
        }
    }

    /**
     * @return array<string, string>
     */
    public function tabs(): array
    {
        return ['general' => 'Allgemein', 'events' => 'Passolution Ereignisse']
            + array_map(fn (string $label) => 'Stammdaten – '.$label, AiAreas::labels());
    }

    /**
     * Anzahl der KI-Pruefungen je Stammdaten-Reiter.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function tabCounts(): array
    {
        return AiCheck::query()->selectRaw('area, count(*) as count')->groupBy('area')->pluck('count', 'area')->map(fn ($count) => (int) $count)->all();
    }

    // ------------------------------------------------------------------
    // KI-Pruefungen der Stammdaten
    // ------------------------------------------------------------------

    /**
     * Die Pruefungen des offenen Bereichs.
     */
    #[Computed]
    public function checks(): Collection
    {
        if (! isset(AiAreas::areas()[$this->tab])) {
            return collect();
        }

        return AiCheck::query()->where('area', $this->tab)->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Modelle zur Auswahl fuer eine Pruefung: die verfuegbaren des Schluessels,
     * ersatzweise die mit hinterlegten Preisen – das gewaehlte bleibt immer dabei.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function checkModelOptions(): array
    {
        $ids = array_column($this->models, 'id') ?: array_keys(config('ai_prices.models', []));

        if ($this->checkModel !== '' && ! in_array($this->checkModel, $ids, true)) {
            array_unshift($ids, $this->checkModel);
        }

        return array_values($ids);
    }

    public function createCheck(): void
    {
        $this->reset(['checkId', 'checkSection', 'checkName', 'checkDescription', 'checkPrompt', 'checkModel']);
        $this->checkActive = true;
        $this->checkArea = isset(AiAreas::areas()[$this->tab]) ? $this->tab : 'countries';
        $this->resetValidation();

        $this->modal('ai-check-editor')->show();
    }

    public function editCheck(int $checkId): void
    {
        $check = AiCheck::findOrFail($checkId);

        $this->resetValidation();
        $this->checkId = $check->id;
        $this->checkArea = $check->area;
        $this->checkSection = (string) $check->section;
        $this->checkName = $check->name;
        $this->checkDescription = (string) $check->description;
        $this->checkPrompt = $check->prompt;
        $this->checkModel = (string) $check->model;
        $this->checkActive = $check->is_active;

        $this->modal('ai-check-editor')->show();
    }

    public function saveCheck(): void
    {
        $this->validate([
            'checkArea' => ['required', Rule::in(array_keys(AiAreas::areas()))],
            'checkSection' => ['nullable', Rule::in([AiAreas::GENERAL, ...array_keys(AiAreas::sectionLabels($this->checkArea))])],
            'checkName' => ['required', 'string', 'max:100'],
            'checkDescription' => ['nullable', 'string', 'max:255'],
            'checkPrompt' => ['required', 'string', 'min:10', 'max:6000'],
            'checkModel' => ['nullable', 'string', 'max:80'],
        ], [
            'checkName.required' => 'Bitte einen Namen für die Prüfung eingeben.',
            'checkPrompt.required' => 'Bitte den Prompt eingeben.',
            'checkPrompt.min' => 'Der Prompt ist zu kurz.',
            'checkSection.in' => 'Bitte einen Abschnitt dieses Bereichs wählen.',
        ]);

        $check = $this->checkId ? AiCheck::findOrFail($this->checkId) : new AiCheck(['created_by' => auth('web')->id()]);
        $check->fill([
            'area' => $this->checkArea,
            'section' => $this->checkSection ?: null,
            'name' => trim($this->checkName),
            'description' => trim($this->checkDescription) ?: null,
            'prompt' => trim($this->checkPrompt),
            'model' => trim($this->checkModel) ?: null,
            'is_active' => $this->checkActive,
        ])->save();

        unset($this->checks, $this->tabCounts);
        $this->modal('ai-check-editor')->close();

        $this->dispatch('adminv2-toast', message: $this->checkId ? 'Prüfung gespeichert.' : 'Prüfung angelegt.');
    }

    public function toggleCheck(int $checkId): void
    {
        $check = AiCheck::findOrFail($checkId);
        $check->update(['is_active' => ! $check->is_active]);

        unset($this->checks);

        $this->dispatch('adminv2-toast', message: $check->is_active ? 'Prüfung eingeschaltet.' : 'Prüfung ausgeschaltet.');
    }

    public function deleteCheck(int $checkId): void
    {
        AiCheck::findOrFail($checkId)->delete();

        unset($this->checks, $this->tabCounts);

        $this->dispatch('adminv2-toast', message: 'Prüfung gelöscht.');
    }

    /**
     * Wird waehrend einer laufenden Suche in kurzen Abstaenden aufgerufen.
     */
    public function refreshAiSearch(): void
    {
        unset($this->latestAiSearch, $this->profiles);
    }

    // ------------------------------------------------------------------
    // KI-Vorlagen: die Auftraege fuer die Suche nach Ereignissen
    // ------------------------------------------------------------------

    #[Computed]
    public function prompts()
    {
        return AiEventSearchPrompt::query()->withCount('profiles')->orderByDesc('is_default')->orderBy('name')->get();
    }

    public function createPrompt(): void
    {
        $this->reset(['promptId', 'promptName', 'promptText', 'promptIsDefault']);
        $this->resetValidation();

        $this->modal('ai-prompt')->show();
    }

    public function editPrompt(int $promptId): void
    {
        $prompt = AiEventSearchPrompt::findOrFail($promptId);

        $this->resetValidation();
        $this->promptId = $prompt->id;
        $this->promptName = $prompt->name;
        $this->promptText = $prompt->prompt;
        $this->promptIsDefault = $prompt->is_default;

        $this->modal('ai-prompt')->show();
    }

    public function savePrompt(): void
    {
        $this->validate([
            'promptName' => ['required', 'string', 'max:100', Rule::unique('ai_event_search_prompts', 'name')->ignore($this->promptId)],
            'promptText' => ['required', 'string', 'min:20', 'max:6000'],
        ], [
            'promptName.required' => 'Bitte einen Namen für die Vorlage eingeben.',
            'promptName.unique' => 'Eine Vorlage mit diesem Namen gibt es bereits.',
            'promptText.required' => 'Bitte den Auftrag an die KI eingeben.',
            'promptText.min' => 'Der Auftrag ist zu kurz – bitte beschreiben, wonach die KI suchen soll.',
        ]);

        $prompt = $this->promptId ? AiEventSearchPrompt::findOrFail($this->promptId) : new AiEventSearchPrompt(['created_by' => auth('web')->id()]);
        $prompt->fill(['name' => trim($this->promptName), 'prompt' => trim($this->promptText)])->save();

        // Es gibt immer genau einen Standard: abwaehlen laesst er sich nur, indem ein anderer gewaehlt wird.
        if ($this->promptIsDefault || AiEventSearchPrompt::query()->where('is_default', true)->doesntExist()) {
            $prompt->makeDefault();
        }

        unset($this->prompts, $this->profiles);
        $this->modal('ai-prompt')->close();

        $this->dispatch('adminv2-toast', message: $this->promptId ? 'Vorlage gespeichert.' : 'Vorlage angelegt.');
    }

    public function makeDefaultPrompt(int $promptId): void
    {
        AiEventSearchPrompt::findOrFail($promptId)->makeDefault();

        unset($this->prompts, $this->profiles);

        $this->dispatch('adminv2-toast', message: 'Standard-Vorlage geändert. Sie gilt für alle Suchen ohne eigene Vorlage.');
    }

    /**
     * Die Standard-Vorlage laesst sich nicht loeschen. Suchen, die eine
     * geloeschte Vorlage nutzten, laufen danach mit dem Standard.
     */
    public function deletePrompt(int $promptId): void
    {
        $prompt = AiEventSearchPrompt::findOrFail($promptId);

        if ($prompt->is_default) {
            $this->dispatch('adminv2-toast', message: 'Die Standard-Vorlage lässt sich nicht löschen. Bitte zuerst eine andere zum Standard machen.', variant: 'danger');

            return;
        }

        $prompt->delete();

        unset($this->prompts, $this->profiles);

        $this->dispatch('adminv2-toast', message: 'Vorlage gelöscht.');
    }

    /**
     * Der mitgelieferte Auftrag als Ausgangspunkt fuer eine neue Vorlage.
     */
    public function fillPromptWithBuiltIn(): void
    {
        $this->promptText = AiSettings::DEFAULT_EVENT_SEARCH_PROMPT;
    }

    // ------------------------------------------------------------------
    // Hinterlegte Suchen mit Zeitplan
    // ------------------------------------------------------------------

    #[Computed]
    public function profiles()
    {
        return AiEventSearchProfile::query()
            ->with(['promptTemplate', 'searches' => fn ($query) => $query->latest('id')->limit(1)])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    public function toggleProfile(int $profileId): void
    {
        $profile = AiEventSearchProfile::findOrFail($profileId);

        $profile->is_active = ! $profile->is_active;
        $profile->scheduleNext()->save();

        unset($this->profiles);
    }

    public function deleteProfile(int $profileId): void
    {
        AiEventSearchProfile::findOrFail($profileId)->delete();

        unset($this->profiles);

        $this->dispatch('adminv2-toast', message: 'Suche gelöscht. Bereits gefundene Vorschläge bleiben erhalten.');
    }

    protected function loadPrices(): void
    {
        // In den Feldern steht nur ein von Hand hinterlegter Preis; sonst gilt die Preisliste.
        $prices = AiSettings::customPrices(AiSettings::model());

        $this->priceInput = $prices ? $this->formatPrice($prices['input']) : '';
        $this->priceOutput = $prices ? $this->formatPrice($prices['output']) : '';
    }

    protected function formatPrice(float $price): string
    {
        return rtrim(rtrim(number_format($price, 4, ',', ''), '0'), ',');
    }

    /**
     * Preise des aktiven Modells speichern. Beide Felder leer = Preis entfernen.
     */
    public function savePrices(): void
    {
        $input = str_replace(',', '.', trim($this->priceInput));
        $output = str_replace(',', '.', trim($this->priceOutput));

        if ($input === '' && $output === '') {
            AiSettings::setPrices(AiSettings::model(), null, null);
            $this->dispatch('adminv2-toast', message: AiSettings::listPrices(AiSettings::model())
                ? 'Eigener Preis entfernt – es gilt wieder die Preisliste.'
                : 'Preise entfernt – Kosten werden nicht mehr angezeigt.');

            return;
        }

        if (! is_numeric($input) || ! is_numeric($output) || $input < 0 || $output < 0) {
            $this->addError('priceInput', 'Bitte beide Preise als Zahl eingeben, z. B. 2,50.');

            return;
        }

        $this->resetErrorBag('priceInput');
        AiSettings::setPrices(AiSettings::model(), (float) $input, (float) $output);
        $this->loadPrices();

        $this->dispatch('adminv2-toast', message: 'Preise für „'.AiSettings::model().'“ gespeichert.');
    }

    /**
     * Modelle, die mit dem hinterlegten Schluessel verfuegbar sind.
     *
     * @return array<int, array{id: string, created: ?string}>
     */
    #[Computed]
    public function models(): array
    {
        $this->modelsError = null;

        if (! AiSettings::apiKey()) {
            return [];
        }

        try {
            return app(OpenAiModelService::class)->chatModels();
        } catch (\Throwable $e) {
            $this->modelsError = $e->getMessage();

            return [];
        }
    }

    /**
     * @return array<int, array{id: string, created: ?string}>
     */
    #[Computed]
    public function filteredModels(): array
    {
        $search = mb_strtolower(trim($this->modelSearch));

        return $search === ''
            ? $this->models
            : array_values(array_filter($this->models, fn (array $model) => str_contains(mb_strtolower($model['id']), $search)));
    }

    public function refreshModels(): void
    {
        app(OpenAiModelService::class)->forgetCache();
        unset($this->models, $this->filteredModels);
    }

    public function saveApiKey(): void
    {
        $this->validate(
            ['newApiKey' => ['required', 'string', 'min:20', 'max:300', 'regex:/^\S+$/']],
            [
                'newApiKey.required' => 'Bitte den API-Schlüssel eingeben.',
                'newApiKey.min' => 'Das sieht nicht nach einem vollständigen Schlüssel aus.',
                'newApiKey.regex' => 'Der Schlüssel darf keine Leerzeichen enthalten.',
            ],
        );

        SystemSetting::write(AiSettings::KEY_API_KEY, trim($this->newApiKey), encrypted: true);

        $this->newApiKey = '';
        $this->testResult = null;
        $this->refreshModels();

        $this->dispatch('adminv2-toast', message: 'API-Schlüssel gespeichert.');
    }

    /**
     * Den im Admin hinterlegten Schluessel entfernen – danach gilt wieder die .env.
     */
    public function removeApiKey(): void
    {
        SystemSetting::write(AiSettings::KEY_API_KEY, null);

        $this->testResult = null;
        $this->refreshModels();

        $this->dispatch('adminv2-toast', message: 'Hinterlegter Schlüssel entfernt.');
    }

    public function saveModel(): void
    {
        $this->model = trim($this->model);

        $this->validate(
            ['model' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:-]+$/']],
            [
                'model.required' => 'Bitte ein Modell wählen.',
                'model.regex' => 'Der Modellname enthält unzulässige Zeichen.',
            ],
        );

        SystemSetting::write(AiSettings::KEY_MODEL, $this->model);
        $this->testResult = null;
        // Preise gelten je Modell – die des neu gewaehlten Modells anzeigen.
        $this->loadPrices();

        $this->dispatch('adminv2-toast', message: "Modell „{$this->model}“ gespeichert.");
    }

    /**
     * Kurze Anfrage an das gewaehlte Modell – zeigt, ob Schluessel, Guthaben
     * und Modell zusammen funktionieren.
     */
    public function testConnection(): void
    {
        set_time_limit(90);

        try {
            $answer = app(ChatGptService::class)->sendPrompt(
                'Antworte nur mit dem Wort: bereit',
                ['model' => trim($this->model) ?: AiSettings::model(), 'max_tokens' => 20, 'temperature' => 0],
            );

            $this->testResult = ['ok' => true, 'message' => 'Die KI antwortet: „'.mb_substr($answer, 0, 80).'“'];
        } catch (\Throwable $e) {
            $this->testResult = ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function render()
    {
        return view('livewire.admin-v2.system.ai');
    }
}
