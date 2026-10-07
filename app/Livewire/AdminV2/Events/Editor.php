<?php

namespace App\Livewire\AdminV2\Events;

use App\Jobs\SendGtmNotifications;
use App\Jobs\SendTravelAlertNotifications;
use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\AdminTask;
use App\Models\CustomEvent;
use App\Models\CustomEventSourceCheck;
use App\Models\EventDisplaySetting;
use App\Models\EventType;
use App\Services\CustomEventLocationService;
use App\Services\CustomEventVersionService;
use App\Services\DeepLTranslationService;
use App\Services\EventSourceCheckService;
use App\Support\AdminV2\EventState;
use App\Support\AdminV2\RichText;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Erfassen und Bearbeiten eines Ereignisses auf EINER Seite: Inhalt,
 * Einordnung, Zeitraum, Standorte und Quellen werden gemeinsam gespeichert.
 *
 * Speichern und Veroeffentlichen sind getrennte Schritte. Ein neues Ereignis
 * entsteht als Entwurf und wird erst ausgeliefert, wenn es vollstaendig ist
 * (mindestens ein Standort) und ausdruecklich veroeffentlicht wird.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public ?int $eventId = null;

    /** @var array<string, string> Titel je Sprache */
    public array $titles = [];

    /** @var array<string, string> Beschreibung (HTML) je Sprache */
    public array $contents = [];

    /** @var array<int, string> */
    public array $eventTypeIds = [];

    public string $displayTypeId = '';

    public string $priority = 'medium';

    public string $startDate = '';

    public string $endDate = '';

    public bool $isNationwide = false;

    /** @var array<int, array<string, mixed>> */
    public array $locations = [];

    /** @var array<int, array{show_frontend: bool, link_text: ?string, link_url: ?string}> */
    public array $sources = [];

    /**
     * Letztes Ergebnis der KI-Pruefung je Quelle, Schluessel = Hash der URL.
     *
     * @var array<string, array{status: string, summary: string, changes: array<int, string>, suggestion: ?string, proposals: array<int, array<string, mixed>>, checked_at: string, checked_by: ?string}>
     */
    public array $sourceChecks = [];

    public string $versionNote = '';

    /** Interne Notiz dieser Version – Kunden sehen sie nicht. */
    public string $versionInternalNote = '';

    public string $locationSearch = '';

    /** Land, dessen Regionen unter dem Suchtreffer aufgeklappt sind. */
    public ?int $browseCountryId = null;

    /** Region, deren Staedte unter dem Suchtreffer aufgeklappt sind. */
    public ?int $browseRegionId = null;

    public string $newVersionNote = '';

    public string $newVersionInternalNote = '';

    public bool $overwriteTranslations = false;

    /** Dialog "Regeln der Kunden pruefen": all = alle Kunden, one = ein bestimmter. */
    public string $ruleCheckScope = 'all';

    public string $ruleCheckCustomer = '';

    /**
     * Kennzeichen fuer Aufgaben, die zu einem noch nicht gespeicherten Ereignis
     * angelegt werden – beim ersten Speichern werden sie dem Ereignis zugeordnet.
     */
    #[Locked]
    public ?string $taskToken = null;

    public function mount($event = null): void
    {
        foreach (CustomEvent::translationLocales() as $locale) {
            $this->titles[$locale] = '';
            $this->contents[$locale] = '';
        }

        if ($event === null) {
            $this->startDate = now()->format('Y-m-d\TH:i');
            $this->taskToken = (string) Str::uuid();

            // Fast jedes Ereignis hat eine Quelle – die erste Zeile steht deshalb
            // schon bereit. Bleibt sie leer, wird sie beim Speichern verworfen.
            $this->addSource();

            return;
        }

        $record = CustomEvent::withTrashed()->findOrFail($event);
        $this->eventId = $record->id;

        $this->fillFrom($record);
    }

    protected function fillFrom(CustomEvent $event): void
    {
        $source = CustomEvent::sourceLocale();
        $titles = $event->title_translations ?? [];
        $contents = $event->popup_content_translations ?? [];

        foreach (CustomEvent::translationLocales() as $locale) {
            $this->titles[$locale] = (string) ($titles[$locale] ?? '');
            $this->contents[$locale] = (string) ($contents[$locale] ?? '');
        }

        // Aeltere Ereignisse haben nur die Einzelspalten, noch keine Uebersetzungen.
        if ($this->titles[$source] === '') {
            $this->titles[$source] = (string) $event->getRawOriginal('title');
        }
        if ($this->contents[$source] === '') {
            $this->contents[$source] = (string) $event->getRawOriginal('popup_content');
        }

        $this->eventTypeIds = $event->eventTypes()->pluck('event_types.id')->map(fn ($id) => (string) $id)->all();
        $this->displayTypeId = (string) ($event->selected_display_event_type_id ?? '');
        $this->priority = $event->priority ?: 'medium';
        $this->startDate = $event->start_date?->format('Y-m-d\TH:i') ?? '';
        $this->endDate = $event->end_date?->format('Y-m-d\TH:i') ?? '';
        $this->isNationwide = (bool) $event->is_nationwide;
        $this->sources = $event->normalizedSourceLinks();
        $this->versionNote = (string) $event->version_note;
        $this->versionInternalNote = (string) $event->version_internal_note;
        $this->locations = app(CustomEventLocationService::class)->rowsFor($event);
        $this->sourceChecks = $this->latestSourceChecks($event);
    }

    /**
     * Das jeweils juengste Pruefergebnis je Quelle dieses Ereignisses.
     */
    protected function latestSourceChecks(CustomEvent $event): array
    {
        return CustomEventSourceCheck::query()
            ->where('custom_event_id', $event->id)
            ->with('checker')
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->unique('url_hash')
            ->mapWithKeys(fn (CustomEventSourceCheck $check) => [$check->url_hash => [
                'status' => $check->status,
                'summary' => (string) $check->summary,
                'changes' => $check->changes ?? [],
                'suggestion' => $check->suggestion,
                'proposals' => $check->proposals ?? [],
                'usage' => $check->input_tokens !== null ? [
                    'model' => (string) $check->model,
                    'input_tokens' => $check->input_tokens,
                    'output_tokens' => (int) $check->output_tokens,
                    'total_tokens' => $check->input_tokens + (int) $check->output_tokens,
                    'cost' => $check->cost,
                ] : null,
                'checked_at' => $check->created_at?->format('d.m.Y H:i') ?? '',
                'checked_by' => $check->checker ? trim($check->checker->name) : null,
            ]])
            ->all();
    }

    #[Computed]
    public function event(): ?CustomEvent
    {
        return $this->eventId
            ? CustomEvent::withTrashed()->with(['supersededBy', 'apiClient'])->find($this->eventId)
            : null;
    }

    #[Computed]
    public function state(): ?EventState
    {
        return $this->event ? EventState::of($this->event) : null;
    }

    /**
     * Im Formular alphabetisch – so findet man einen Typ schneller als in der
     * Sortierung der Stammdaten. Umlaute ordnen sich unter ihrem Grundbuchstaben ein.
     */
    #[Computed]
    public function eventTypes(): Collection
    {
        return EventType::active()->get()
            ->sortBy(fn (EventType $type) => Str::lower(Str::ascii($type->name)))
            ->values();
    }

    /**
     * Darf bei mehreren Event-Typen das Karten-Icon von Hand gewaehlt werden?
     */
    #[Computed]
    public function allowsDisplayTypeSelection(): bool
    {
        return EventDisplaySetting::current()->shouldShowManualSelection();
    }

    #[Computed]
    public function locationResults(): array
    {
        return app(CustomEventLocationService::class)->search($this->locationSearch);
    }

    /**
     * Regionen des aufgeklappten Landes.
     *
     * @return array<int, array{id: int, name: string}>
     */
    #[Computed]
    public function browseRegions(): array
    {
        return $this->browseCountryId
            ? app(CustomEventLocationService::class)->regionsOf($this->browseCountryId)
            : [];
    }

    /**
     * Staedte der aufgeklappten Region.
     *
     * @return array<int, array{id: int, name: string, is_regional_capital: bool}>
     */
    #[Computed]
    public function browseCities(): array
    {
        return $this->browseRegionId
            ? app(CustomEventLocationService::class)->citiesOf($this->browseRegionId)
            : [];
    }

    /**
     * Alle Versionen dieses Ereignisses, neueste zuerst.
     */
    #[Computed]
    public function versions(): Collection
    {
        return $this->event?->versionHistory() ?? collect();
    }

    /**
     * @return array{total: int, today: int}
     */
    #[Computed]
    public function clickStats(): array
    {
        if (! $this->event) {
            return ['total' => 0, 'today' => 0];
        }

        return [
            'total' => $this->event->clicks()->count(),
            'today' => $this->event->clicks()->whereDate('clicked_at', today())->count(),
        ];
    }

    // ------------------------------------------------------------------
    // Standorte
    // ------------------------------------------------------------------

    public function updatedLocationSearch(): void
    {
        $this->browseCountryId = null;
        $this->browseRegionId = null;
    }

    /**
     * Unter einem Land in den Suchtreffern seine Regionen zeigen – oder wieder
     * einklappen.
     */
    public function browseCountry(int $countryId): void
    {
        $this->browseRegionId = null;
        $this->browseCountryId = $this->browseCountryId === $countryId ? null : $countryId;
    }

    /**
     * Unter einer Region ihre Staedte zeigen – oder wieder einklappen.
     */
    public function browseRegion(int $regionId): void
    {
        $this->browseRegionId = $this->browseRegionId === $regionId ? null : $regionId;
    }

    public function addLocation(string $type, int $id): void
    {
        $service = app(CustomEventLocationService::class);
        $resolved = $service->resolve($type, $id);

        if (! $resolved) {
            return;
        }

        $alreadyListed = collect($this->locations)->contains(fn (array $row) => (int) $row['country_id'] === $resolved['country_id']
            && (int) ($row['region_id'] ?? 0) === (int) $resolved['region_id']
            && (int) ($row['city_id'] ?? 0) === (int) $resolved['city_id']);

        $this->locationSearch = '';
        $this->browseCountryId = null;
        $this->browseRegionId = null;

        if ($alreadyListed) {
            $this->dispatch('adminv2-toast', message: 'Dieser Standort ist bereits zugeordnet.');

            return;
        }

        $row = $this->withDefaultCoordinates($resolved + [
            'label' => $service->label($resolved['country_id'], $resolved['region_id'], $resolved['city_id']),
            'iso_code' => \App\Models\Country::whereKey($resolved['country_id'])->value('iso_code'),
            'use_default_coordinates' => true,
            'location_note' => '',
        ]);

        $this->locations[] = $row;

        if ($row['coordinate_issue']) {
            $this->dispatch('adminv2-toast', message: $row['coordinate_issue'], variant: 'danger');
        }

        $this->resetValidation('locations');
    }

    /**
     * Standard-Koordinaten der Zeile ermitteln; gibt es keine, steht der Grund
     * in "coordinate_issue", damit die Zeile ihn zeigen kann.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function withDefaultCoordinates(array $row): array
    {
        $service = app(CustomEventLocationService::class);
        $countryId = (int) $row['country_id'];
        $regionId = ! empty($row['region_id']) ? (int) $row['region_id'] : null;
        $cityId = ! empty($row['city_id']) ? (int) $row['city_id'] : null;

        $coordinates = $service->defaultCoordinatesFor($countryId, $regionId, $cityId);

        $row['coordinates'] = $coordinates ? $coordinates[0].', '.$coordinates[1] : '';
        $row['coordinate_issue'] = $coordinates ? null : $service->missingDefaultCoordinatesReason($countryId, $regionId, $cityId);

        return $row;
    }

    public function removeLocation(int $index): void
    {
        unset($this->locations[$index]);
        $this->locations = array_values($this->locations);
    }

    /**
     * Wird auf Standard-Koordinaten zurueckgeschaltet, zeigt die Zeile wieder
     * die Koordinaten der Stadt, der Regionshauptstadt oder der Landeshauptstadt.
     */
    public function updatedLocations($value, string $key): void
    {
        [$index, $field] = array_pad(explode('.', $key, 2), 2, null);

        if ($field !== 'use_default_coordinates' || ! isset($this->locations[$index])) {
            return;
        }

        if (! $value) {
            $this->locations[$index]['coordinate_issue'] = null;

            return;
        }

        $this->locations[$index] = $this->withDefaultCoordinates($this->locations[$index]);
        $this->resetValidation("locations.{$index}.coordinates");
    }

    // ------------------------------------------------------------------
    // Quellen
    // ------------------------------------------------------------------

    public function addSource(): void
    {
        $this->sources[] = ['show_frontend' => true, 'link_text' => '', 'link_url' => ''];
    }

    public function removeSource(int $index): void
    {
        unset($this->sources[$index]);
        $this->sources = array_values($this->sources);
    }

    /**
     * Eine Quelle per KI pruefen: Passt der erfasste Stand noch zu dem, was die
     * Quelle heute sagt? Verglichen wird mit dem aktuellen Stand des Formulars,
     * am Ereignis selbst aendert die Pruefung nichts.
     */
    public function checkSource(int $index): void
    {
        $url = trim((string) ($this->sources[$index]['link_url'] ?? ''));

        if ($url === '') {
            return;
        }

        // Abruf der Quelle plus KI-Antwort koennen laenger dauern als das uebliche Zeitlimit.
        set_time_limit(150);

        $source = CustomEvent::sourceLocale();
        $start = filled($this->startDate) ? rescue(fn () => Carbon::parse($this->startDate)->format('d.m.Y'), null, false) : null;
        $end = filled($this->endDate) ? rescue(fn () => Carbon::parse($this->endDate)->format('d.m.Y'), null, false) : null;

        $result = app(EventSourceCheckService::class)->check([
            'title' => trim((string) ($this->titles[$source] ?? '')) ?: '(ohne Titel)',
            'description' => Str::limit(trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '</li>'], "\n", (string) ($this->contents[$source] ?? ''))))), 5000) ?: '(keine Beschreibung)',
            'period' => ($start ?? 'unbekannt').' bis '.($end ?? 'offen'),
            'locations' => collect($this->locations)->pluck('label')->filter()->implode('; ') ?: '(keine Angabe)',
            'types' => $this->eventTypes->whereIn('id', array_map('intval', $this->eventTypeIds))->pluck('name')->implode(', ') ?: '(keine)',
            'available_types' => $this->eventTypes->pluck('name')->all(),
            'priority' => CustomEvent::getPriorityOptions()[$this->priority] ?? $this->priority,
        ], $url);

        $hash = CustomEventSourceCheck::hashFor($url);

        // Festgehalten wird nur ein Befund – ein fehlgeschlagener Versuch ist keiner.
        if ($this->eventId && $result['status'] !== CustomEventSourceCheck::STATUS_ERROR) {
            CustomEventSourceCheck::create([
                'custom_event_id' => $this->eventId,
                'url' => $url,
                'url_hash' => $hash,
                'status' => $result['status'],
                'summary' => $result['summary'],
                'changes' => $result['changes'],
                'suggestion' => $result['suggestion'],
                'proposals' => $result['proposals'],
                'model' => $result['usage']['model'] ?? null,
                'input_tokens' => $result['usage']['input_tokens'] ?? null,
                'output_tokens' => $result['usage']['output_tokens'] ?? null,
                'cost' => $result['usage']['cost'] ?? null,
                'checked_by' => auth('web')->id(),
            ]);
        }

        $this->sourceChecks[$hash] = $result + [
            'checked_at' => now()->format('d.m.Y H:i'),
            'checked_by' => trim((string) auth('web')->user()?->name) ?: null,
        ];
    }

    /**
     * Einen Vorschlag der KI ins Formular uebernehmen. Gespeichert wird damit
     * noch nichts – die Redaktion prueft und speichert selbst.
     */
    public function applyProposal(string $hash, int $index): void
    {
        $proposal = $this->sourceChecks[$hash]['proposals'][$index] ?? null;

        if (! $proposal || ! empty($proposal['applied'])) {
            return;
        }

        $source = CustomEvent::sourceLocale();
        $value = $proposal['value'] ?? null;

        // Der Stand vor dem Uebernehmen – damit sich der Vorschlag zuruecknehmen laesst.
        $previous = match ($proposal['field']) {
            'title' => $this->titles[$source] ?? '',
            'description' => $this->contents[$source] ?? '',
            'event_types' => ['ids' => $this->eventTypeIds, 'display' => $this->displayTypeId],
            'priority' => $this->priority,
            'period' => ['start' => $this->startDate, 'end' => $this->endDate],
            default => null,
        };

        switch ($proposal['field']) {
            case 'title':
                $this->titles[$source] = Str::limit((string) $value, 255, '');
                break;

            case 'description':
                // Fliesstext der KI in Absaetze wandeln; der Editor wird im Browser aktualisiert.
                $html = collect(preg_split('/\n\s*\n/', trim((string) $value)) ?: [])
                    ->map(fn (string $paragraph) => '<p>'.nl2br(e(trim($paragraph)), false).'</p>')
                    ->implode('');
                $this->contents[$source] = $html;
                $this->dispatch('adminv2-editor-set', locale: $source, html: $html);
                break;

            case 'event_types':
                $ids = $this->eventTypes->whereIn('name', (array) $value)->pluck('id')->map(fn ($id) => (string) $id)->all();
                if ($ids === []) {
                    return;
                }
                $this->eventTypeIds = $ids;
                $this->displayTypeId = '';
                break;

            case 'priority':
                if (! isset(CustomEvent::getPriorityOptions()[$value])) {
                    return;
                }
                $this->priority = $value;
                break;

            case 'period':
                // Die Uhrzeit des bisherigen Beginns bleibt; das Ende gilt bis Tagesende.
                $time = filled($this->startDate) ? Str::after($this->startDate, 'T') : '00:00';
                $this->startDate = $value['start'].'T'.$time;
                $this->endDate = filled($value['end'] ?? null) ? $value['end'].'T23:59' : '';
                break;

            default:
                // Standorte lassen sich nicht sicher zuordnen – sie bleiben ein Hinweis.
                return;
        }

        $this->sourceChecks[$hash]['proposals'][$index]['applied'] = true;
        $this->sourceChecks[$hash]['proposals'][$index]['previous'] = $previous;
        $this->dispatch('adminv2-toast', message: $proposal['label'].' übernommen – bitte prüfen und speichern.');
    }

    /**
     * Einen uebernommenen Vorschlag zuruecknehmen: das Feld bekommt wieder den
     * Stand, den es vor dem Uebernehmen hatte.
     */
    public function undoProposal(string $hash, int $index): void
    {
        $proposal = $this->sourceChecks[$hash]['proposals'][$index] ?? null;

        if (! $proposal || empty($proposal['applied']) || ! array_key_exists('previous', $proposal)) {
            return;
        }

        $source = CustomEvent::sourceLocale();
        $previous = $proposal['previous'];

        switch ($proposal['field']) {
            case 'title':
                $this->titles[$source] = (string) $previous;
                break;

            case 'description':
                $this->contents[$source] = (string) $previous;
                $this->dispatch('adminv2-editor-set', locale: $source, html: (string) $previous);
                break;

            case 'event_types':
                $this->eventTypeIds = array_values(array_map('strval', (array) ($previous['ids'] ?? [])));
                $this->displayTypeId = (string) ($previous['display'] ?? '');
                break;

            case 'priority':
                if (isset(CustomEvent::getPriorityOptions()[$previous])) {
                    $this->priority = $previous;
                }
                break;

            case 'period':
                $this->startDate = (string) ($previous['start'] ?? '');
                $this->endDate = (string) ($previous['end'] ?? '');
                break;

            default:
                return;
        }

        unset(
            $this->sourceChecks[$hash]['proposals'][$index]['applied'],
            $this->sourceChecks[$hash]['proposals'][$index]['previous'],
        );

        $this->dispatch('adminv2-toast', message: $proposal['label'].' wieder auf den vorherigen Stand gesetzt.');
    }

    // ------------------------------------------------------------------
    // Speichern und Veroeffentlichen
    // ------------------------------------------------------------------

    protected function rules(): array
    {
        $source = CustomEvent::sourceLocale();

        return [
            "titles.{$source}" => ['required', 'string', 'max:255'],
            'titles.*' => ['nullable', 'string', 'max:255'],
            'contents.*' => ['nullable', 'string'],
            'eventTypeIds' => ['required', 'array', 'min:1'],
            'eventTypeIds.*' => [Rule::exists('event_types', 'id')],
            'displayTypeId' => ['nullable', Rule::in(array_merge([''], $this->eventTypeIds))],
            'priority' => ['required', Rule::in(array_keys(CustomEvent::getPriorityOptions()))],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'locations.*.country_id' => ['required', Rule::exists('countries', 'id')],
            'locations.*.region_id' => ['nullable', Rule::exists('regions', 'id')],
            'locations.*.city_id' => ['nullable', Rule::exists('cities', 'id')],
            'locations.*.location_note' => ['nullable', 'string', 'max:1000'],
            'sources.*.link_text' => ['nullable', 'string', 'max:255'],
            'sources.*.link_url' => ['nullable', 'url', 'max:2048'],
            'versionNote' => ['nullable', 'string', 'max:2000'],
            'versionInternalNote' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function messages(): array
    {
        $source = CustomEvent::sourceLocale();

        return [
            "titles.{$source}.required" => 'Bitte einen Titel in der Ausgangssprache eingeben.',
            'titles.*.max' => 'Der Titel darf höchstens 255 Zeichen lang sein.',
            'eventTypeIds.required' => 'Bitte mindestens einen Event-Typ wählen.',
            'eventTypeIds.min' => 'Bitte mindestens einen Event-Typ wählen.',
            'startDate.required' => 'Bitte ein Startdatum angeben.',
            'endDate.after_or_equal' => 'Das Enddatum darf nicht vor dem Startdatum liegen.',
            'sources.*.link_url.url' => 'Bitte eine vollständige Adresse eingeben (https://…).',
        ];
    }

    /**
     * Jeder Standort braucht eine Position auf der Karte: eigene Koordinaten
     * muessen lesbar sein, Standard-Koordinaten muessen sich ermitteln lassen.
     */
    protected function validateLocations(bool $requireAtLeastOne): void
    {
        $service = app(CustomEventLocationService::class);
        $errors = [];

        foreach ($this->locations as $index => $row) {
            if (empty($row['use_default_coordinates'])) {
                if (! $service->parseCoordinates($row['coordinates'] ?? '')) {
                    $errors["locations.{$index}.coordinates"] = 'Koordinaten nicht lesbar. Erwartet wird z. B. „50.1109, 8.6821“.';
                }

                continue;
            }

            // Ohne Standard-Koordinaten haette der Standort keine Position auf der
            // Karte – der Grund (z. B. Region ohne Hauptstadt) steht in der Meldung.
            $reason = $service->missingDefaultCoordinatesReason(
                (int) $row['country_id'],
                ! empty($row['region_id']) ? (int) $row['region_id'] : null,
                ! empty($row['city_id']) ? (int) $row['city_id'] : null,
            );

            if ($reason) {
                $errors["locations.{$index}.coordinates"] = $reason.' Bis dahin lassen sich eigene Koordinaten eintragen.';
            }
        }

        if ($requireAtLeastOne && $this->locations === []) {
            $errors['locations'] = 'Zum Veröffentlichen wird mindestens ein Standort benötigt – sonst erscheint das Ereignis nicht auf der Karte und kann keiner Reise zugeordnet werden.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function persist(bool $forPublishing = false): CustomEvent
    {
        $this->validate();
        $this->validateLocations($forPublishing);

        return DB::transaction(function () {
            $event = $this->event ?? new CustomEvent([
                // Ein neues Ereignis ist ein Entwurf, bis es veroeffentlicht wird.
                'is_active' => false,
                'data_source' => 'manual',
                'created_by' => auth()->id(),
            ]);

            $titles = array_filter(array_map('trim', $this->titles), fn ($title) => $title !== '');

            $storedContents = $event->popup_content_translations ?? [];
            $storedContents[CustomEvent::sourceLocale()] ??= $event->getRawOriginal('popup_content');
            $contents = [];
            foreach ($this->contents as $locale => $html) {
                // Unveraenderte Texte bleiben Byte fuer Byte erhalten; bereinigt
                // wird nur, was im Editor tatsaechlich angefasst wurde.
                $contents[$locale] = $html === ($storedContents[$locale] ?? null)
                    ? $html
                    : RichText::sanitize($html);
            }
            $contents = array_filter($contents, fn ($html) => filled($html));

            $typeIds = array_map('intval', $this->eventTypeIds);

            $event->fill([
                'title_translations' => $titles,
                'popup_content_translations' => $contents,
                'priority' => $this->priority,
                'start_date' => Carbon::parse($this->startDate),
                'end_date' => $this->normalizedEndDate(),
                'is_nationwide' => $this->isNationwide,
                'source_links' => $this->cleanSources(),
                'selected_display_event_type_id' => in_array((int) $this->displayTypeId, $typeIds, true)
                    ? (int) $this->displayTypeId
                    : null,
                'updated_by' => auth()->id(),
            ]);
            $event->version_note = filled($this->versionNote) ? $this->versionNote : null;
            $event->version_internal_note = filled($this->versionInternalNote) ? $this->versionInternalNote : null;
            $event->save();

            $event->eventTypes()->sync($typeIds);
            app(CustomEventLocationService::class)->replace($event, $this->locations);

            // Typen und Standorte laufen an den Model-Events vorbei – touch()
            // stoesst den Observer an (Caches verwerfen, Marker-Icon nachziehen).
            $event->unsetRelation('eventTypes');
            $event->touch();

            if ($this->taskToken !== null) {
                AdminTask::where('subject_token', $this->taskToken)->get()->each->update([
                    'subject_type' => $event->getMorphClass(),
                    'subject_id' => $event->id,
                    'subject_token' => null,
                ]);
                $this->taskToken = null;
            }

            $this->eventId = $event->id;
            unset($this->event, $this->state, $this->versions);

            return $event;
        });
    }

    /**
     * Ein Enddatum um 00:00 Uhr wird wie im bisherigen Admin auf 00:01 gelegt.
     */
    protected function normalizedEndDate(): ?Carbon
    {
        if (blank($this->endDate)) {
            return null;
        }

        $end = Carbon::parse($this->endDate);

        return $end->format('H:i') === '00:00' ? $end->setTime(0, 1) : $end;
    }

    /**
     * @return array<int, array{show_frontend: bool, link_text: ?string, link_url: ?string}>
     */
    protected function cleanSources(): array
    {
        return collect($this->sources)
            ->map(fn (array $source) => [
                'show_frontend' => (bool) ($source['show_frontend'] ?? true),
                'link_text' => filled($source['link_text'] ?? null) ? trim($source['link_text']) : null,
                'link_url' => filled($source['link_url'] ?? null) ? trim($source['link_url']) : null,
            ])
            ->filter(fn (array $source) => $source['link_text'] !== null || $source['link_url'] !== null)
            ->values()
            ->all();
    }

    public function save()
    {
        $isNew = $this->eventId === null;
        $event = $this->persist();

        if ($isNew) {
            session()->flash('adminv2-toast', 'Entwurf gespeichert.');

            return $this->redirectRoute('adminv2.events.edit', $event);
        }

        $this->fillFrom($event->fresh());
        $this->dispatch('adminv2-toast', message: 'Änderungen gespeichert.');
    }

    /**
     * Speichert und liefert das Ereignis aus. Eine neue Version loest dabei
     * ihre Vorgaengerin ab; der naechste Benachrichtigungslauf greift sie auf.
     */
    public function publish()
    {
        $event = $this->persist(forPublishing: true);

        if ($event->review_status !== 'approved') {
            $event->forceFill([
                'review_status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);
        }

        if ($event->archived) {
            $event->archived = false;
        }

        $event->activateVersion(auth()->id());

        session()->flash('adminv2-toast', 'Ereignis veröffentlicht.');

        return $this->redirectRoute('adminv2.events.edit', $event);
    }

    public function deactivate(): void
    {
        $this->event?->update(['is_active' => false, 'updated_by' => auth()->id()]);

        unset($this->event, $this->state);
        $this->modal('deactivate')->close();
        $this->dispatch('adminv2-toast', message: 'Ereignis deaktiviert – es wird nicht mehr ausgeliefert.');
    }

    public function reject(): void
    {
        if ($this->event?->review_status !== 'pending_review') {
            return;
        }

        $this->event->reject(auth()->id());

        unset($this->event, $this->state);
        $this->dispatch('adminv2-toast', message: 'Ereignis abgelehnt.');
    }

    public function toggleArchive(): void
    {
        if (! $this->event) {
            return;
        }

        $this->event->archived ? $this->event->unarchive() : $this->event->archive();
        $archived = (bool) $this->event->archived;

        unset($this->event, $this->state);
        $this->dispatch('adminv2-toast', message: $archived ? 'Ereignis archiviert.' : 'Archivierung aufgehoben.');
    }

    /**
     * Naechste Version als Entwurf anlegen – der veroeffentlichte Stand bleibt
     * unveraendert, bis die neue Version veroeffentlicht wird.
     */
    public function createVersion()
    {
        if (! $this->event) {
            return;
        }

        $version = app(CustomEventVersionService::class)->createNewVersion(
            $this->event,
            auth()->id(),
            filled($this->newVersionNote) ? $this->newVersionNote : null,
            filled($this->newVersionInternalNote) ? $this->newVersionInternalNote : null,
        );

        session()->flash('adminv2-toast', "Version {$version->version} als Entwurf angelegt.");

        return $this->redirectRoute('adminv2.events.edit', $version);
    }

    /**
     * Speichert und uebersetzt Titel und Beschreibung aus der Ausgangssprache
     * per DeepL in die uebrigen Sprachen.
     */
    public function translate()
    {
        $deepl = app(DeepLTranslationService::class);

        if (! $deepl->isConfigured()) {
            $this->modal('translate')->close();
            $this->dispatch('adminv2-toast', message: 'DeepL ist nicht konfiguriert (DEEPL_KEY fehlt).', variant: 'danger');

            return;
        }

        $event = $this->persist();
        $source = CustomEvent::sourceLocale();
        $titles = $event->title_translations ?? [];
        $contents = $event->popup_content_translations ?? [];
        $translated = 0;
        $errors = [];

        foreach (CustomEvent::translationLocales() as $locale) {
            if ($locale === $source) {
                continue;
            }

            try {
                if (filled($titles[$source] ?? null) && ($this->overwriteTranslations || blank($titles[$locale] ?? null))) {
                    $titles[$locale] = $deepl->translate($titles[$source], $locale, $source);
                    $translated++;
                }

                if (filled($contents[$source] ?? null) && ($this->overwriteTranslations || blank($contents[$locale] ?? null))) {
                    $contents[$locale] = $deepl->translateHtml($contents[$source], $locale, $source);
                    $translated++;
                }
            } catch (\Throwable $e) {
                $errors[] = strtoupper($locale).': '.$e->getMessage();
            }
        }

        $event->update([
            'title_translations' => $titles,
            'popup_content_translations' => $contents,
        ]);

        session()->flash('adminv2-toast', match (true) {
            $errors !== [] => 'Übersetzung teilweise fehlgeschlagen – '.implode(' | ', $errors),
            $translated > 0 => "{$translated} Feld(er) übersetzt.",
            default => 'Nichts zu übersetzen – alle Sprachen waren bereits ausgefüllt.',
        });

        // Neu laden, damit die Editoren die uebersetzten Texte zeigen.
        return $this->redirectRoute('adminv2.events.edit', $event);
    }

    /**
     * Zur Vorschau, welche Regeln der Kunden bei diesem Ereignis greifen wuerden –
     * fuer alle Kunden oder fuer eine Kundennummer.
     */
    public function openRuleCheck()
    {
        if (! $this->eventId) {
            return;
        }

        $number = $this->ruleCheckScope === 'one' ? trim($this->ruleCheckCustomer) : '';

        if ($this->ruleCheckScope === 'one') {
            if ($number === '') {
                $this->addError('ruleCheckCustomer', 'Bitte eine Kundennummer eingeben.');

                return;
            }

            if (RuleCheck::customersFor($number)->isEmpty()) {
                $this->addError('ruleCheckCustomer', "Zur Kundennummer {$number} gibt es keinen Kunden.");

                return;
            }
        }

        return $this->redirectRoute('adminv2.events.rules', array_filter(['event' => $this->eventId, 'customer' => $number]));
    }

    public function triggerNotifications(): void
    {
        $event = $this->event;
        $this->modal('notify')->close();

        if (! $event || ! $event->is_active || $event->review_status !== 'approved') {
            $this->dispatch('adminv2-toast', message: 'Benachrichtigungen sind nur für veröffentlichte Ereignisse möglich.', variant: 'danger');

            return;
        }

        SendTravelAlertNotifications::dispatch($event, force: true);
        SendGtmNotifications::dispatch($event, force: true);

        $this->dispatch('adminv2-toast', message: 'Benachrichtigungen ausgelöst – sie werden im Hintergrund verarbeitet.');
    }

    public function restore(): void
    {
        $this->event?->restore();

        unset($this->event, $this->state);
        $this->dispatch('adminv2-toast', message: 'Ereignis wiederhergestellt.');
    }

    public function delete()
    {
        $this->event?->delete();

        session()->flash('adminv2-toast', 'Ereignis in den Papierkorb verschoben.');

        return $this->redirectRoute('adminv2.events.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.events.editor')
            ->title($this->event ? 'Ereignis bearbeiten' : 'Neues Ereignis');
    }
}
