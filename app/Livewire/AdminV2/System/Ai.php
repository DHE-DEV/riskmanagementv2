<?php

namespace App\Livewire\AdminV2\System;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\StartsAiEventSearch;
use App\Models\AiEventSearch;
use App\Models\SystemSetting;
use App\Services\ChatGptService;
use App\Services\OpenAiModelService;
use App\Support\AiSettings;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
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

    /** Auftrag fuer die KI-Suche nach aktuellen Ereignissen. */
    public string $eventSearchPrompt = '';

    /** Bereits erfasste Ereignisse ausschliessen – nur neue suchen. */
    public bool $eventSearchExcludeExisting = true;

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

        $this->eventSearchPrompt = AiSettings::eventSearchPrompt();
        $this->eventSearchExcludeExisting = AiSettings::eventSearchExcludesExisting();
    }

    /**
     * Auftrag und Ausschluss fuer die KI-Suche nach Ereignissen speichern.
     */
    public function saveEventSearchSettings(): void
    {
        $this->validate(
            ['eventSearchPrompt' => ['required', 'string', 'min:20', 'max:6000']],
            [
                'eventSearchPrompt.required' => 'Bitte einen Auftrag für die Suche eingeben.',
                'eventSearchPrompt.min' => 'Der Auftrag ist zu kurz – bitte beschreiben, wonach die KI suchen soll.',
            ],
        );

        $prompt = trim($this->eventSearchPrompt);

        // Entspricht der Auftrag dem Standard, wird nichts gespeichert – so
        // greifen spaetere Verbesserungen des Standards von selbst.
        SystemSetting::write(AiSettings::KEY_EVENT_SEARCH_PROMPT, $prompt === AiSettings::DEFAULT_EVENT_SEARCH_PROMPT ? null : $prompt);
        SystemSetting::write(AiSettings::KEY_EVENT_SEARCH_EXCLUDE, $this->eventSearchExcludeExisting ? '1' : '0');

        $this->dispatch('adminv2-toast', message: 'Einstellungen der Ereignis-Suche gespeichert.');
    }

    public function resetEventSearchPrompt(): void
    {
        $this->eventSearchPrompt = AiSettings::DEFAULT_EVENT_SEARCH_PROMPT;
        $this->resetErrorBag('eventSearchPrompt');
    }

    /**
     * Die letzten Suchlaeufe.
     */
    #[Computed]
    public function recentAiSearches()
    {
        return AiEventSearch::query()->with('starter')->latest('id')->limit(5)->get();
    }

    /**
     * Speichern und sofort suchen – damit gilt, was gerade im Formular steht.
     */
    public function searchEventsNow(): void
    {
        $this->saveEventSearchSettings();

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->startAiSearch();
        unset($this->recentAiSearches);
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
