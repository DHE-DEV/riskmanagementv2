<?php

namespace App\Livewire\AdminV2\System;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\HandlesAiSuggestions;
use App\Livewire\AdminV2\Concerns\StartsAiEventSearch;
use App\Models\AdminTask;
use App\Models\AiEventSearch;
use App\Models\AiEventSearchProfile;
use App\Models\AiEventSearchPrompt;
use App\Models\AiEventSuggestion;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Support\AiSettings;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Eigene Seite einer hinterlegten KI-Suche: KI-Vorlage, Filter, Zeitplan und
 * Benachrichtigung – darunter die Laeufe dieser Suche mit ihren Ergebnissen,
 * der letzte zuerst.
 */
#[Layout('components.layouts.adminv2.app')]
class AiSearchEditor extends Component
{
    use AuthorizesAdminV2;
    use HandlesAiSuggestions;
    use StartsAiEventSearch;
    use WithPagination;

    #[Locked]
    public ?int $profileId = null;

    public string $name = '';

    /** ID einer KI-Vorlage; leer = die Standard-Vorlage. */
    public string $promptId = '';

    public bool $excludeExisting = true;

    /** @var array<int, string> ISO-Codes */
    public array $countries = [];

    /** @var array<int, string> Codes der Event-Typen */
    public array $types = [];

    /** @var array<int, string> */
    public array $priorities = [];

    public string $keyword = '';

    /** Zeitraum der Ereignisse: Tage ab dem Tag des Laufs; leer = keine Eingrenzung. */
    public string $daysAhead = '';

    /** Hoechstzahl der Ergebnisse je Lauf; leer = der Standard. */
    public string $maxResults = '';

    /** @var array<int, string> Wochentage 1–7; leer = jeden Tag */
    public array $weekdays = [];

    /** @var array<int, string> Uhrzeiten "HH:MM" */
    public array $times = ['07:00'];

    public bool $active = true;

    /** @var array<int, string> IDs der Benutzer, die das Ergebnis per Mail bekommen */
    public array $notifyUsers = [];

    /** @var array<int, string> IDs der Teams, die das Ergebnis per Mail bekommen */
    public array $notifyTeams = [];

    public bool $notifyWhenEmpty = false;

    private const RUNS_PER_PAGE = 5;

    public function mount($profile = null): void
    {
        if ($profile === null) {
            // "Filter als Suche hinterlegen" aus der Ereignisliste: die Filter kommen ueber die Adresse.
            $this->countries = $this->queryList('countries', fn ($code) => strtoupper($code));
            $this->types = $this->queryList('types');
            $this->priorities = $this->queryList('priorities');
            $this->keyword = Str::limit(trim((string) request()->query('keyword', '')), 200, '');

            return;
        }

        $model = AiEventSearchProfile::findOrFail((int) $profile);

        $this->profileId = $model->id;
        $this->name = $model->name;
        $this->promptId = (string) ($model->prompt_id ?? '');
        $this->excludeExisting = $model->exclude_existing;
        $this->countries = array_values($model->country_codes ?? []);
        $this->types = array_values($model->event_type_codes ?? []);
        $this->priorities = array_values($model->priorities ?? []);
        $this->keyword = (string) $model->keyword;
        $this->daysAhead = $model->days_ahead !== null ? (string) $model->days_ahead : '';
        $this->maxResults = $model->max_results !== null ? (string) $model->max_results : '';
        $this->weekdays = array_map('strval', $model->sortedWeekdays());
        $this->times = $model->sortedTimes() ?: [''];
        $this->active = $model->is_active;
        $this->notifyUsers = array_map('strval', $model->notify_user_ids ?? []);
        $this->notifyTeams = array_map('strval', $model->notify_team_ids ?? []);
        $this->notifyWhenEmpty = $model->notify_when_empty;
    }

    /**
     * @return array<int, string>
     */
    protected function queryList(string $key, ?callable $map = null): array
    {
        $values = array_filter(array_map('trim', explode(',', (string) request()->query($key, ''))), fn ($value) => $value !== '');

        return array_values(array_unique($map ? array_map($map, $values) : $values));
    }

    #[Computed]
    public function profile(): ?AiEventSearchProfile
    {
        return $this->profileId ? AiEventSearchProfile::with('promptTemplate')->find($this->profileId) : null;
    }

    #[Computed]
    public function prompts()
    {
        return AiEventSearchPrompt::query()->orderByDesc('is_default')->orderBy('name')->get();
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

    #[Computed]
    public function notifyUserOptions()
    {
        return AdminTask::assignableUsers();
    }

    #[Computed]
    public function notifyTeamOptions()
    {
        return AdminTask::assignableTeams();
    }

    /**
     * Alle Laender auswaehlen – danach lassen sich einzelne gezielt abwaehlen.
     */
    public function selectAllCountries(): void
    {
        $this->countries = $this->countryOptions
            ->map(fn (Country $country) => strtoupper((string) $country->iso_code))
            ->unique()
            ->values()
            ->all();
    }

    public function clearCountries(): void
    {
        $this->countries = [];
    }

    /**
     * Auswahl fuer "Zeitraum der Ereignisse": Tage ab dem Tag des Laufs.
     *
     * @return array<string, string>
     */
    public function periodOptions(): array
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
        if ($this->daysAhead !== '' && ! isset($options[$this->daysAhead])) {
            $options[$this->daysAhead] = 'Ereignisse in den nächsten '.(int) $this->daysAhead.' Tagen';
        }

        return $options;
    }

    public function addTime(): void
    {
        $this->times[] = '';
    }

    public function removeTime(int $index): void
    {
        unset($this->times[$index]);
        $this->times = array_values($this->times);
    }

    public function save()
    {
        // Leere Zeilen bei den Uhrzeiten zaehlen nicht.
        $this->times = array_values(array_filter($this->times, fn ($time) => trim((string) $time) !== ''));

        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'promptId' => ['nullable', Rule::in(array_merge([''], $this->prompts->pluck('id')->map(fn ($id) => (string) $id)->all()))],
            'countries' => ['array'],
            'countries.*' => [Rule::in($this->countryOptions->map(fn (Country $country) => strtoupper((string) $country->iso_code))->all())],
            'types' => ['array'],
            'types.*' => [Rule::in($this->eventTypeOptions->pluck('code')->all())],
            'priorities' => ['array'],
            'priorities.*' => [Rule::in(array_keys(CustomEvent::getPriorityOptions()))],
            'keyword' => ['nullable', 'string', 'max:200'],
            'daysAhead' => ['nullable', 'integer', 'min:0', 'max:365'],
            'maxResults' => ['nullable', 'integer', 'min:1', 'max:'.AiSettings::EVENT_SEARCH_MAX_RESULTS_LIMIT],
            'weekdays' => ['array'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'times' => ['array', 'max:12'],
            'times.*' => ['date_format:H:i'],
            'notifyUsers' => ['array'],
            'notifyUsers.*' => [Rule::in($this->notifyUserOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'notifyTeams' => ['array'],
            'notifyTeams.*' => [Rule::in($this->notifyTeamOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
        ], [
            'name.required' => 'Bitte einen Namen für die Suche eingeben.',
            'times.*.date_format' => 'Bitte die Uhrzeit als Stunde und Minute angeben.',
            'maxResults.*' => 'Bitte eine Zahl zwischen 1 und '.AiSettings::EVENT_SEARCH_MAX_RESULTS_LIMIT.' eingeben – oder das Feld leer lassen.',
        ]);

        $isNew = ! $this->profileId;
        $profile = $this->profile ?? new AiEventSearchProfile(['created_by' => auth('web')->id()]);

        $profile->fill([
            'name' => trim($this->name),
            'prompt_id' => $this->promptId !== '' ? (int) $this->promptId : null,
            'exclude_existing' => $this->excludeExisting,
            'country_codes' => array_values($this->countries),
            'event_type_codes' => array_values($this->types),
            'priorities' => array_values($this->priorities),
            'keyword' => filled($this->keyword) ? trim($this->keyword) : null,
            'days_ahead' => $this->daysAhead !== '' ? (int) $this->daysAhead : null,
            'max_results' => $this->maxResults !== '' ? (int) $this->maxResults : null,
            'weekdays' => array_map('intval', $this->weekdays),
            'times' => array_values(array_unique($this->times)),
            'is_active' => $this->active,
            'notify_user_ids' => array_values(array_map('intval', $this->notifyUsers)),
            'notify_team_ids' => array_values(array_map('intval', $this->notifyTeams)),
            'notify_when_empty' => $this->notifyWhenEmpty,
        ]);
        $profile->scheduleNext()->save();

        $message = $profile->next_run_at
            ? 'Suche gespeichert. Nächster Lauf am '.$profile->next_run_at->format('d.m.Y').' um '.$profile->next_run_at->format('H:i').' Uhr.'
            : 'Suche gespeichert'.($profile->is_active ? ' – ohne Zeitpunkt läuft sie nur von Hand.' : ' – pausiert.');

        if ($isNew) {
            session()->flash('adminv2-toast', $message);

            return $this->redirectRoute('adminv2.system.ai.searches.edit', $profile);
        }

        $this->times = $profile->sortedTimes() ?: [''];
        unset($this->profile);

        $this->dispatch('adminv2-toast', message: $message);
    }

    /**
     * Diese Suche sofort ausfuehren – mit dem gespeicherten Stand.
     */
    public function runNow(): void
    {
        if ($this->profileId) {
            $this->runAiProfile($this->profileId);
        }
    }

    public function delete()
    {
        $this->profile?->delete();

        session()->flash('adminv2-toast', 'Suche gelöscht. Bereits gefundene Vorschläge bleiben erhalten.');

        return $this->redirectRoute('adminv2.system.ai');
    }

    protected function forgetAiSuggestions(): void
    {
        // Die Laeufe samt Ergebnissen werden bei jedem Rendern frisch geladen.
    }

    /**
     * Die Laeufe dieser Suche, der letzte zuerst – jeder mit seinen Ergebnissen.
     */
    protected function runs()
    {
        if (! $this->profileId) {
            return null;
        }

        return AiEventSearch::query()
            ->where('profile_id', $this->profileId)
            ->with(['starter', 'suggestions' => fn ($query) => $query->with('customEvent')->oldest('id')])
            ->latest('id')
            ->paginate(self::RUNS_PER_PAGE);
    }

    public function render()
    {
        $runs = $this->runs();

        $codes = $runs
            ? $runs->getCollection()->flatMap(fn (AiEventSearch $run) => $run->suggestions->flatMap(fn (AiEventSuggestion $suggestion) => $suggestion->country_codes ?? []))->unique()
            : collect();

        return view('livewire.admin-v2.system.ai-search-editor', [
            'runs' => $runs,
            'countryNames' => Country::query()->whereIn('iso_code', $codes)->get()
                ->mapWithKeys(fn (Country $country) => [strtoupper((string) $country->iso_code) => $country->getName('de')]),
        ])->title($this->profile ? 'Suche „'.$this->profile->name.'“' : 'Neue Suche');
    }
}
