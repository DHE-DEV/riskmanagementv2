<?php

namespace App\Livewire\AdminV2\System;

use App\Jobs\RunAiEventSearch;
use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\StartsAiEventSearch;
use App\Models\AiEventSearch;
use App\Models\AiEventSearchProfile;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Models\SystemSetting;
use App\Services\AiEventSearchService;
use App\Services\ChatGptService;
use App\Services\OpenAiModelService;
use App\Support\AiSettings;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
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

    // Hinterlegte Suche (Formular im Dialog)
    #[Locked]
    public ?int $profileId = null;

    public string $profileName = '';

    /** Eigener Auftrag; leer = der Standard-Auftrag oben. */
    public string $profilePrompt = '';

    public bool $profileExcludeExisting = true;

    /** @var array<int, string> ISO-Codes */
    public array $profileCountries = [];

    /** @var array<int, string> Codes der Event-Typen */
    public array $profileTypes = [];

    /** @var array<int, string> */
    public array $profilePriorities = [];

    public string $profileKeyword = '';

    /** Zeitraum der Auswirkungen: heute bis in so vielen Tagen; leer = keine Eingrenzung. */
    public string $profileDaysAhead = '';

    /** @var array<int, string> Wochentage 1–7; leer = jeden Tag */
    public array $profileWeekdays = [];

    /** @var array<int, string> Uhrzeiten "HH:MM" */
    public array $profileTimes = ['07:00'];

    public bool $profileActive = true;

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
     * Wird waehrend einer laufenden Suche in kurzen Abstaenden aufgerufen.
     */
    public function refreshAiSearch(): void
    {
        unset($this->latestAiSearch, $this->recentAiSearches, $this->profiles);
    }

    // ------------------------------------------------------------------
    // Hinterlegte Suchen mit Zeitplan
    // ------------------------------------------------------------------

    #[Computed]
    public function profiles()
    {
        return AiEventSearchProfile::query()
            ->with(['searches' => fn ($query) => $query->latest('id')->limit(1)])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function countryOptions()
    {
        return Country::query()
            ->whereNotNull('iso_code')
            ->get(['id', 'iso_code', 'name_translations'])
            ->sortBy(fn (Country $country) => $country->getName('de'), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    #[Computed]
    public function eventTypeOptions()
    {
        return EventType::active()->get(['id', 'code', 'name', 'icon'])
            ->sortBy(fn (EventType $type) => Str::lower(Str::ascii($type->name)))
            ->values();
    }

    public function createProfile(): void
    {
        $this->reset([
            'profileId', 'profileName', 'profilePrompt', 'profileExcludeExisting', 'profileCountries', 'profileTypes',
            'profilePriorities', 'profileKeyword', 'profileDaysAhead', 'profileWeekdays', 'profileTimes', 'profileActive',
        ]);
        $this->resetValidation();

        $this->modal('search-profile')->show();
    }

    public function editProfile(int $profileId): void
    {
        $profile = AiEventSearchProfile::findOrFail($profileId);

        $this->resetValidation();
        $this->profileId = $profile->id;
        $this->profileName = $profile->name;
        $this->profilePrompt = (string) $profile->prompt;
        $this->profileExcludeExisting = $profile->exclude_existing;
        $this->profileCountries = array_values($profile->country_codes ?? []);
        $this->profileTypes = array_values($profile->event_type_codes ?? []);
        $this->profilePriorities = array_values($profile->priorities ?? []);
        $this->profileKeyword = (string) $profile->keyword;
        $this->profileDaysAhead = $profile->days_ahead !== null ? (string) $profile->days_ahead : '';
        $this->profileWeekdays = array_map('strval', $profile->sortedWeekdays());
        $this->profileTimes = $profile->sortedTimes() ?: [''];
        $this->profileActive = $profile->is_active;

        $this->modal('search-profile')->show();
    }

    /**
     * Alle Laender auswaehlen – danach lassen sich einzelne gezielt abwaehlen.
     */
    public function selectAllProfileCountries(): void
    {
        $this->profileCountries = $this->countryOptions
            ->map(fn (Country $country) => strtoupper((string) $country->iso_code))
            ->unique()
            ->values()
            ->all();
    }

    public function clearProfileCountries(): void
    {
        $this->profileCountries = [];
    }

    /**
     * Auswahl fuer "Zeitraum der Ereignisse": Tage ab dem Tag des Laufs.
     *
     * @return array<string, string>
     */
    public function profilePeriodOptions(): array
    {
        $options = [
            '' => 'Keine zeitliche Eingrenzung',
            '0' => 'Nur Ereignisse am Tag der Suche',
            '3' => 'Ereignisse in den nächsten 3 Tagen',
            '7' => 'Ereignisse in den nächsten 7 Tagen',
            '14' => 'Ereignisse in den nächsten 14 Tagen',
            '30' => 'Ereignisse in den nächsten 30 Tagen',
            '90' => 'Ereignisse in den nächsten 90 Tagen',
        ];

        // Ein frueher gespeicherter anderer Wert bleibt waehlbar.
        if ($this->profileDaysAhead !== '' && ! isset($options[$this->profileDaysAhead])) {
            $options[$this->profileDaysAhead] = 'Ereignisse in den nächsten '.(int) $this->profileDaysAhead.' Tagen';
        }

        return $options;
    }

    public function addProfileTime(): void
    {
        $this->profileTimes[] = '';
    }

    public function removeProfileTime(int $index): void
    {
        unset($this->profileTimes[$index]);
        $this->profileTimes = array_values($this->profileTimes);
    }

    public function fillProfilePromptWithDefault(): void
    {
        $this->profilePrompt = AiSettings::eventSearchPrompt();
    }

    public function saveProfile(): void
    {
        // Leere Zeilen bei den Uhrzeiten zaehlen nicht.
        $this->profileTimes = array_values(array_filter($this->profileTimes, fn ($time) => trim((string) $time) !== ''));

        $this->validate([
            'profileName' => ['required', 'string', 'max:100'],
            'profilePrompt' => ['nullable', 'string', 'max:6000'],
            'profileCountries' => ['array'],
            'profileCountries.*' => [Rule::in($this->countryOptions->map(fn (Country $country) => strtoupper((string) $country->iso_code))->all())],
            'profileTypes' => ['array'],
            'profileTypes.*' => [Rule::in($this->eventTypeOptions->pluck('code')->all())],
            'profilePriorities' => ['array'],
            'profilePriorities.*' => [Rule::in(array_keys(CustomEvent::getPriorityOptions()))],
            'profileKeyword' => ['nullable', 'string', 'max:200'],
            'profileDaysAhead' => ['nullable', 'integer', 'min:0', 'max:365'],
            'profileWeekdays' => ['array'],
            'profileWeekdays.*' => ['integer', 'between:1,7'],
            'profileTimes' => ['array', 'max:12'],
            'profileTimes.*' => ['date_format:H:i'],
        ], [
            'profileName.required' => 'Bitte einen Namen für die Suche eingeben.',
            'profileTimes.*.date_format' => 'Bitte die Uhrzeit als Stunde und Minute angeben.',
            'profileDaysAhead.integer' => 'Bitte eine Zahl von Tagen eingeben.',
        ]);

        $profile = $this->profileId ? AiEventSearchProfile::findOrFail($this->profileId) : new AiEventSearchProfile(['created_by' => auth('web')->id()]);

        $profile->fill([
            'name' => trim($this->profileName),
            'prompt' => filled($this->profilePrompt) ? trim($this->profilePrompt) : null,
            'exclude_existing' => $this->profileExcludeExisting,
            'country_codes' => array_values($this->profileCountries),
            'event_type_codes' => array_values($this->profileTypes),
            'priorities' => array_values($this->profilePriorities),
            'keyword' => filled($this->profileKeyword) ? trim($this->profileKeyword) : null,
            'days_ahead' => $this->profileDaysAhead !== '' ? (int) $this->profileDaysAhead : null,
            'weekdays' => array_map('intval', $this->profileWeekdays),
            'times' => array_values(array_unique($this->profileTimes)),
            'is_active' => $this->profileActive,
        ]);
        $profile->scheduleNext()->save();

        unset($this->profiles);
        $this->modal('search-profile')->close();

        $this->dispatch('adminv2-toast', message: $profile->next_run_at
            ? 'Suche gespeichert. Nächster Lauf am '.$profile->next_run_at->format('d.m.Y').' um '.$profile->next_run_at->format('H:i').' Uhr.'
            : 'Suche gespeichert'.($profile->is_active ? ' – ohne Zeitpunkt läuft sie nur von Hand.' : ' – pausiert.'));
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

    /**
     * Eine hinterlegte Suche sofort ausfuehren – ausser der Reihe.
     */
    public function runProfileNow(int $profileId): void
    {
        if (! AiSettings::apiKey()) {
            $this->dispatch('adminv2-toast', message: 'Es ist kein OpenAI-Schlüssel hinterlegt.', variant: 'danger');

            return;
        }

        if ($this->latestAiSearch?->isRunning()) {
            $this->dispatch('adminv2-toast', message: 'Es läuft bereits eine Suche. Bitte warten, bis sie fertig ist.', variant: 'danger');

            return;
        }

        $profile = AiEventSearchProfile::findOrFail($profileId);
        $search = app(AiEventSearchService::class)->createSearchFor($profile, auth('web')->id());

        $profile->forceFill(['last_run_at' => now()])->save();

        RunAiEventSearch::dispatchAfterResponse($search->id);

        unset($this->latestAiSearch, $this->recentAiSearches, $this->profiles);
    }

    /**
     * Die letzten Suchlaeufe.
     */
    #[Computed]
    public function recentAiSearches()
    {
        return AiEventSearch::query()->with(['starter', 'profile'])->latest('id')->limit(8)->get();
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
