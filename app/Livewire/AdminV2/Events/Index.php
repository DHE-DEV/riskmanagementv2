<?php

namespace App\Livewire\AdminV2\Events;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\HandlesAiSuggestions;
use App\Livewire\AdminV2\Concerns\StartsAiEventSearch;
use App\Models\AdminTask;
use App\Models\AdminTeam;
use App\Models\AiEventSuggestion;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Services\CustomEventVersionService;
use App\Support\AdminV2\EventState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.adminv2.app')]
#[Title('Passolution Ereignisse')]
class Index extends Component
{
    use AuthorizesAdminV2;
    use HandlesAiSuggestions;
    use StartsAiEventSearch;
    use WithPagination;

    /** Im Reiter "Heute angelegt": auch offene KI-Vorschlaege frueherer Tage zeigen. */
    public bool $showOlderSuggestions = false;

    /** Im Reiter "Heute angelegt": alle Vorschlaege zeigen, auch wenn sie nicht zu den Filtern passen. */
    public bool $showAllSuggestions = false;

    /** Zustand als Reiter; "all" zeigt alles ausser abgeloesten Versionen. */
    #[Url(except: 'live')]
    public string $tab = 'live';

    #[Url(except: '')]
    public string $search = '';

    /** @var array<int, string> Mehrfachauswahl; leer = keine Einschraenkung */
    #[Url(as: 'priority')]
    public array $priorities = [];

    /** @var array<int, string> IDs der Event-Typen */
    #[Url(as: 'type')]
    public array $types = [];

    /** @var array<int, string> IDs der Laender */
    #[Url(as: 'country')]
    public array $countryIds = [];

    /** Zeitfenster (Y-m-d): Ereignisse, deren Zeitraum sich damit ueberschneidet. */
    #[Url(except: '')]
    public string $periodFrom = '';

    #[Url(except: '')]
    public string $periodTo = '';

    /** nationwide | local | none (ohne Standort) */
    #[Url(except: '')]
    public string $scope = '';

    /** Aufgaben am Ereignis: open | mine | overdue | done | none */
    #[Url(as: 'tasks', except: '')]
    public string $taskFilter = '';

    /** ID eines Mitarbeiters: Ereignisse mit offenen Aufgaben, die bei ihm liegen. */
    #[Url(as: 'taskPerson', except: '')]
    public string $taskPerson = '';

    #[Url(except: 'start_date')]
    public string $sort = 'start_date';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    private const SORTABLE = ['title', 'start_date', 'end_date', 'updated_at', 'clicks_count'];

    /**
     * Reiter in Anzeige-Reihenfolge.
     *
     * @return array<string, string>
     */
    public function tabs(): array
    {
        return [
            // Live und Geplant zusammen: alles, was ausgeliefert wird.
            'active' => 'Aktiv',
            EventState::Live->value => 'Live',
            EventState::Scheduled->value => 'Geplant',
            EventState::Draft->value => 'Entwürfe',
            EventState::PendingReview->value => 'Prüfung',
            EventState::Inactive->value => 'Inaktiv',
            EventState::Expired->value => 'Abgelaufen',
            EventState::Archived->value => 'Archiv',
            // Alles, was heute erfasst wurde – gleich in welchem Zustand.
            'today' => 'Heute angelegt',
            'all' => 'Alle',
        ];
    }

    public function updated(string $property): void
    {
        if (in_array(Str::before($property, '.'), ['tab', 'search', 'priorities', 'types', 'countryIds', 'periodFrom', 'periodTo', 'scope', 'taskFilter', 'taskPerson', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = $column === 'title' ? 'asc' : 'desc';
        }

        $this->resetPage();
    }

    /**
     * Einen einzelnen Filter ueber sein Badge entfernen.
     */
    public function removeFilter(string $filter, ?string $value = null): void
    {
        match ($filter) {
            'priorities', 'types', 'countryIds' => $this->{$filter} = array_values(array_filter(
                $this->{$filter},
                fn ($current) => (string) $current !== (string) $value,
            )),
            'period' => [$this->periodFrom, $this->periodTo] = ['', ''],
            'scope' => $this->scope = '',
            'taskFilter' => $this->taskFilter = '',
            'taskPerson' => $this->taskPerson = '',
            'search' => $this->search = '',
            default => null,
        };

        $this->resetPage();
    }

    public function toggleDirection(): void
    {
        $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'priorities', 'types', 'countryIds', 'periodFrom', 'periodTo', 'scope', 'taskFilter', 'taskPerson']);
        $this->resetPage();
    }

    /**
     * Anzahl je Reiter – mit Suche und Filtern. So zeigt z. B. ein Zeitfenster
     * sofort, wie viele Treffer live, geplant oder schon abgelaufen sind.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function tabCounts(): array
    {
        $counts = [];

        foreach (array_keys($this->tabs()) as $tab) {
            $counts[$tab] = $this->applyFilters($this->scopeTab(CustomEvent::query(), $tab))->count();
        }

        return $counts;
    }

    #[Computed]
    public function eventTypes()
    {
        // Alphabetisch wie im Formular; Umlaute unter ihrem Grundbuchstaben.
        return EventType::active()->get(['id', 'code', 'name', 'icon'])
            ->sortBy(fn (EventType $type) => Str::lower(Str::ascii($type->name)))
            ->values();
    }

    /**
     * Auswahl des Aufgaben-Filters.
     *
     * @return array<string, string>
     */
    public function taskFilterOptions(): array
    {
        return [
            'open' => 'Offene Aufgaben',
            'mine' => 'Offene bei mir',
            'overdue' => 'Überfällige Aufgaben',
            'done' => 'Alle Aufgaben erledigt',
            'none' => 'Ohne Aufgaben',
        ];
    }

    #[Computed]
    public function employees()
    {
        return AdminTask::assignableUsers();
    }

    /**
     * Nur Laender, denen ueberhaupt Ereignisse zugeordnet sind.
     */
    #[Computed]
    public function countryOptions()
    {
        return Country::query()
            ->whereHas('customEvents')
            ->get(['id', 'name_translations', 'iso_code'])
            ->sortBy(fn (Country $country) => $country->getName('de'), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Die Filter der Liste, soweit sie fuer die KI-Suche taugen: Suchbegriff,
     * Prioritaeten, Event-Typen, Laender und Zeitfenster. Null, wenn keiner
     * davon gesetzt ist.
     *
     * @return array{search: string, priorities: array<int, string>, types: array<int, string>, countries: array<int, string>, from: ?string, to: ?string, labels: array<int, array{label: string, value: string}>}|null
     */
    #[Computed]
    public function aiSearchFilters(): ?array
    {
        $priorityOptions = CustomEvent::getPriorityOptions();
        $priorities = array_values(array_intersect($this->priorities, array_keys($priorityOptions)));
        $types = $this->eventTypes->whereIn('id', $this->integerIds($this->types))->values();
        $countries = $this->countryOptions->whereIn('id', $this->integerIds($this->countryIds))->values();
        $from = $this->parseDate($this->periodFrom);
        $to = $this->parseDate($this->periodTo);
        $search = trim($this->search);

        $labels = array_filter([
            'Länder' => $countries->map(fn (Country $country) => $country->getName('de').' ('.strtoupper((string) $country->iso_code).')')->implode(', '),
            'Event-Typen' => $types->pluck('name')->implode(', '),
            'Priorität' => implode(', ', array_map(fn ($priority) => $priorityOptions[$priority], $priorities)),
            'Zeitraum' => match (true) {
                $from && $to => $from->format('d.m.Y').' bis '.$to->format('d.m.Y'),
                (bool) $from => 'ab '.$from->format('d.m.Y'),
                (bool) $to => 'bis '.$to->format('d.m.Y'),
                default => '',
            },
            'Stichwort' => $search,
        ]);

        if ($labels === []) {
            return null;
        }

        return [
            'search' => $search,
            'priorities' => $priorities,
            'types' => EventType::query()->whereIn('id', $types->pluck('id'))->pluck('code')->all(),
            'countries' => $countries->map(fn (Country $country) => strtoupper((string) $country->iso_code))->all(),
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
            // Als Liste gespeichert – die Datenbank wuerde die Reihenfolge eines Objekts umsortieren.
            'labels' => collect($labels)->map(fn ($value, $label) => ['label' => $label, 'value' => $value])->values()->all(),
        ];
    }

    /**
     * Die KI gezielt mit den gerade gesetzten Filtern suchen lassen. Die
     * allgemeinen Vorschlaege bleiben daneben erhalten.
     */
    public function startFilteredAiSearch(): void
    {
        if ($filters = $this->aiSearchFilters) {
            $this->startAiSearch($filters);
        }
    }

    /**
     * Alle offenen KI-Vorschlaege fuer den Reiter "Heute angelegt": die von
     * heute, auf Wunsch auch aeltere – noch ohne die Filter der Liste.
     */
    #[Computed]
    public function allAiSuggestions()
    {
        return AiEventSuggestion::query()
            ->open()
            ->when(! $this->showOlderSuggestions, fn (Builder $query) => $query->whereDate('created_at', today()))
            ->with('search.profile')
            ->latest('id')
            ->get();
    }

    /**
     * Die angezeigten Vorschlaege: sind Filter gesetzt, nur die dazu passenden –
     * ausser alle sollen gezeigt werden. Es geht dabei nichts verloren.
     */
    #[Computed]
    public function aiSuggestions()
    {
        $filters = $this->aiSearchFilters;

        if (! $filters || $this->showAllSuggestions) {
            return $this->allAiSuggestions;
        }

        $needle = mb_strtolower($filters['search']);

        return $this->allAiSuggestions->filter(function (AiEventSuggestion $suggestion) use ($filters, $needle) {
            if ($filters['priorities'] !== [] && ! in_array($suggestion->priority, $filters['priorities'], true)) {
                return false;
            }

            if ($filters['types'] !== [] && array_intersect($filters['types'], $suggestion->event_type_codes ?? []) === []) {
                return false;
            }

            if ($filters['countries'] !== [] && array_intersect($filters['countries'], $suggestion->country_codes ?? []) === []) {
                return false;
            }

            // Zeitfenster: wie bei den Ereignissen zaehlt die Ueberschneidung; ohne Ende ist ein Vorschlag offen.
            if ($filters['from'] && $suggestion->end_date && $suggestion->end_date->format('Y-m-d') < $filters['from']) {
                return false;
            }

            if ($filters['to'] && $suggestion->start_date && $suggestion->start_date->format('Y-m-d') > $filters['to']) {
                return false;
            }

            return $needle === '' || str_contains(mb_strtolower($suggestion->title.' '.$suggestion->summary.' '.$suggestion->location), $needle);
        })->values();
    }

    #[Computed]
    public function olderAiSuggestionsCount(): int
    {
        return AiEventSuggestion::query()->open()->whereDate('created_at', '<', today())->count();
    }

    /**
     * Wird waehrend der Suche in kurzen Abstaenden aufgerufen – neue
     * Vorschlaege erscheinen, sobald die Suche fertig ist.
     */
    public function refreshAiSearch(): void
    {
        unset($this->latestAiSearch, $this->allAiSuggestions, $this->aiSuggestions, $this->olderAiSuggestionsCount);
    }

    protected function forgetAiSuggestions(): void
    {
        unset($this->allAiSuggestions, $this->aiSuggestions, $this->olderAiSuggestionsCount);
    }

    public function approve(int $eventId): void
    {
        $event = CustomEvent::findOrFail($eventId);

        if ($event->review_status !== 'pending_review') {
            return;
        }

        $event->approve(auth()->id());

        $this->dispatch('adminv2-toast', message: 'Ereignis freigegeben und veröffentlicht.');
    }

    public function reject(int $eventId): void
    {
        $event = CustomEvent::findOrFail($eventId);

        if ($event->review_status !== 'pending_review') {
            return;
        }

        $event->reject(auth()->id());

        $this->dispatch('adminv2-toast', message: 'Ereignis abgelehnt.');
    }

    /**
     * Legt die naechste Version als Entwurf an und oeffnet sie.
     */
    public function createVersion(int $eventId)
    {
        $event = CustomEvent::findOrFail($eventId);

        $version = app(CustomEventVersionService::class)->createNewVersion($event, auth()->id());

        session()->flash('adminv2-toast', "Version {$version->version} als Entwurf angelegt.");

        return $this->redirectRoute('adminv2.events.edit', $version);
    }

    public function toggleArchive(int $eventId): void
    {
        $event = CustomEvent::findOrFail($eventId);

        $event->archived ? $event->unarchive() : $event->archive();

        $this->dispatch('adminv2-toast', message: $event->archived ? 'Ereignis archiviert.' : 'Archivierung aufgehoben.');
    }

    protected function scopeTab(Builder $query, string $tab): Builder
    {
        if ($tab === 'active') {
            return EventState::applyActive($query);
        }

        if ($tab === 'today') {
            return $query->whereNull('superseded_by_id')->whereDate('created_at', today());
        }

        $state = EventState::tryFrom($tab);

        return $state
            ? $state->apply($query)
            : $query->whereNull('superseded_by_id');
    }

    /**
     * Suche und Filter – fuer die Liste und fuer die Zahlen an den Reitern.
     */
    protected function applyFilters(Builder $query): Builder
    {
        if (($search = trim($this->search)) !== '') {
            $like = '%'.mb_strtolower($search).'%';

            $query->where(function (Builder $q) use ($like) {
                $q->whereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereHas('countries', function (Builder $country) use ($like) {
                        $country->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))) LIKE ?", [$like])
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en'))) LIKE ?", [$like]);
                    });
            });
        }

        // Mehrfachauswahl: innerhalb eines Filters reicht ein Treffer (ODER),
        // die Filter untereinander muessen alle zutreffen (UND).
        $priorities = array_values(array_intersect($this->priorities, array_keys(CustomEvent::getPriorityOptions())));

        if ($priorities !== []) {
            $query->whereIn('priority', $priorities);
        }

        if (($typeIds = $this->integerIds($this->types)) !== []) {
            $query->whereHas('eventTypes', fn (Builder $q) => $q->whereIn('event_types.id', $typeIds));
        }

        if (($countryIds = $this->integerIds($this->countryIds)) !== []) {
            $query->whereHas('countries', fn (Builder $q) => $q->whereIn('countries.id', $countryIds));
        }

        // Zeitfenster: alles, was sich damit ueberschneidet – dieselbe Regel, nach
        // der Reisen als betroffen gelten. Ein Ereignis ohne Enddatum ist offen.
        if ($from = $this->parseDate($this->periodFrom)) {
            $query->where(fn (Builder $q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $from->startOfDay()));
        }

        if ($to = $this->parseDate($this->periodTo)) {
            $query->where('start_date', '<=', $to->endOfDay());
        }

        $this->applyTaskFilters($query);

        match ($this->scope) {
            'nationwide' => $query->where('is_nationwide', true),
            'local' => $query->where('is_nationwide', false)->whereHas('countries'),
            'none' => $query->whereDoesntHave('countries')->whereNull('country_id'),
            default => null,
        };

        return $query;
    }

    /**
     * Die Werte kommen aus der URL bzw. vom Browser – nur ganze Zahlen zulassen.
     *
     * @return array<int, int>
     */
    protected function integerIds(array $values): array
    {
        return array_values(array_unique(array_filter(
            array_map(fn ($value) => is_scalar($value) ? (int) $value : 0, $values),
            fn (int $id) => $id > 0,
        )));
    }

    /**
     * Filter nach den Aufgaben eines Ereignisses. Aufgaben zaehlen ueber alle
     * Versionen eines Ereignisses hinweg – wie die Zahlen auf den Karten.
     */
    protected function applyTaskFilters(Builder $query): void
    {
        $open = fn ($tasks) => $tasks->where('admin_tasks.status', '<>', AdminTask::STATUS_DONE);

        // Bei wem die Aufgabe liegt: naechster Bearbeiter, sonst der Verantwortliche –
        // jeweils die Person selbst oder eines ihrer Teams.
        $handledBy = function (int $userId) {
            $teamIds = AdminTeam::idsForUser($userId);

            return fn ($tasks) => $tasks->where(fn ($q) => $q
                ->where('admin_tasks.next_assignee_id', $userId)
                ->orWhereIn('admin_tasks.next_assignee_team_id', $teamIds)
                ->orWhere(fn ($sub) => $sub
                    ->whereNull('admin_tasks.next_assignee_id')
                    ->whereNull('admin_tasks.next_assignee_team_id')
                    ->where(fn ($responsible) => $responsible
                        ->where('admin_tasks.responsible_id', $userId)
                        ->orWhereIn('admin_tasks.responsible_team_id', $teamIds))));
        };

        match ($this->taskFilter) {
            'open' => $this->whereHasTasks($query, [$open]),
            'mine' => $this->whereHasTasks($query, [$open, $handledBy((int) auth('web')->id())]),
            'overdue' => $this->whereHasTasks($query, [$open, fn ($tasks) => $tasks->where('admin_tasks.due_date', '<', today())]),
            // Es gibt Aufgaben, aber keine offene mehr.
            'done' => $this->whereHasTasks($this->whereHasTasks($query), [$open], exists: false),
            'none' => $this->whereHasTasks($query, exists: false),
            default => null,
        };

        if (($personId = (int) $this->taskPerson) > 0) {
            $this->whereHasTasks($query, [$open, $handledBy($personId)]);
        }
    }

    /**
     * Ereignisse, zu denen es (k)eine Aufgabe gibt, die alle Bedingungen erfuellt.
     */
    protected function whereHasTasks(Builder $query, array $constraints = [], bool $exists = true): Builder
    {
        $subquery = function ($tasks) use ($constraints) {
            $tasks->selectRaw('1')
                ->from('admin_tasks')
                ->join('custom_events as task_events', 'task_events.id', '=', 'admin_tasks.subject_id')
                ->where('admin_tasks.subject_type', (new CustomEvent)->getMorphClass())
                ->whereNull('admin_tasks.deleted_at')
                ->where(fn ($q) => $q
                    ->whereColumn('task_events.id', 'custom_events.id')
                    ->orWhereColumn('task_events.version_group_uuid', 'custom_events.version_group_uuid'));

            foreach ($constraints as $constraint) {
                $constraint($tasks);
            }
        };

        return $exists ? $query->whereExists($subquery) : $query->whereNotExists($subquery);
    }

    protected function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function events()
    {
        $query = $this->applyFilters($this->scopeTab(CustomEvent::query(), $this->tab))
            ->with(['countries', 'eventTypes', 'apiClient'])
            ->withCount('clicks');

        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'start_date';

        return $query
            ->orderBy($sort, $this->direction === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            // 24 = volle Reihen bei zwei, drei und vier Karten nebeneinander.
            ->paginate(24);
    }

    /**
     * Aufgaben je Ereignis der aktuellen Seite: insgesamt und davon offen.
     * Gezaehlt wird ueber alle Versionen eines Ereignisses, wie in der
     * Seitenspalte des Formulars.
     *
     * @return array<int, array{total: int, open: int}>
     */
    protected function taskCounts($events): array
    {
        if ($events->isEmpty()) {
            return [];
        }

        // Versions-ID => Gruppe, fuer alle Versionen der gezeigten Ereignisse.
        $groupOfVersion = CustomEvent::withTrashed()
            ->whereIn('version_group_uuid', $events->pluck('version_group_uuid')->filter()->unique())
            ->pluck('version_group_uuid', 'id');

        $perVersion = AdminTask::query()
            ->where('subject_type', (new CustomEvent)->getMorphClass())
            ->whereIn('subject_id', $groupOfVersion->keys()->merge($events->pluck('id'))->unique())
            ->selectRaw('subject_id, count(*) as total, sum(status <> ?) as open_count', [AdminTask::STATUS_DONE])
            ->groupBy('subject_id')
            ->get();

        $perGroup = [];

        foreach ($perVersion as $row) {
            $group = $groupOfVersion[$row->subject_id] ?? 'event-'.$row->subject_id;
            $perGroup[$group]['total'] = ($perGroup[$group]['total'] ?? 0) + (int) $row->total;
            $perGroup[$group]['open'] = ($perGroup[$group]['open'] ?? 0) + (int) $row->open_count;
        }

        $counts = [];

        foreach ($events as $event) {
            $counts[$event->id] = $perGroup[$event->version_group_uuid ?: 'event-'.$event->id] ?? ['total' => 0, 'open' => 0];
        }

        return $counts;
    }

    public function render()
    {
        $events = $this->events();

        return view('livewire.admin-v2.events.index', [
            'events' => $events,
            'taskCounts' => $this->taskCounts($events->getCollection()),
        ]);
    }
}
