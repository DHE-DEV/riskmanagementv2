<?php

namespace App\Livewire\AdminV2\Tasks;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\AdminTask;
use App\Models\AdminTaskActivity;
use App\Models\AdminTaskCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Aufgaben aller Mitarbeiter: was bei mir liegt, was ich verantworte, was ich
 * erfasst habe – und alle.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Aufgaben')]
class Index extends Component
{
    use AuthorizesAdminV2;
    use WithPagination;

    #[Url(except: 'mine')]
    public string $tab = 'mine';

    #[Url(except: '')]
    public string $search = '';

    /** open = offen und in Arbeit, in_progress = nur in Arbeit, done = erledigt, all = alle */
    #[Url(except: 'open')]
    public string $status = 'open';

    /** @var array<int, string> Prioritaeten; leer = keine Einschraenkung */
    #[Url(as: 'priority')]
    public array $priorities = [];

    /** @var array<int, string> IDs der Rubriken; leer = keine Einschraenkung */
    #[Url(as: 'category')]
    public array $categories = [];

    /** Faelligkeit: overdue | today | week | none (ohne Datum) */
    #[Url(except: '')]
    public string $due = '';

    /** Zeitfenster (Y-m-d) fuer das Faelligkeitsdatum. */
    #[Url(except: '')]
    public string $dueFrom = '';

    #[Url(except: '')]
    public string $dueTo = '';

    /** @var array<int, string> IDs von Mitarbeitern */
    #[Url(as: 'person')]
    public array $persons = [];

    /** @var array<int, string> IDs von Teams, die verantwortlich oder am Zug sind */
    #[Url(as: 'team')]
    public array $teams = [];

    /** In welcher Rolle die Personen gesucht werden: handler | responsible | creator; leer = jede */
    #[Url(as: 'role', except: '')]
    public string $personRole = '';

    /** Bezug: event (haengt an einem Ereignis) | none (ohne Bezug) */
    #[Url(except: '')]
    public string $subject = '';

    #[Url(except: 'due_date')]
    public string $sort = 'due_date';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    public string $newCategory = '';

    private const SORTABLE = ['due_date', 'priority', 'title', 'updated_at', 'created_at'];

    private const FILTERS = ['search', 'status', 'priorities', 'categories', 'due', 'dueFrom', 'dueTo', 'persons', 'personRole', 'teams', 'subject'];

    /**
     * @return array<string, string>
     */
    public function tabs(): array
    {
        return [
            'mine' => 'Bei mir',
            'responsible' => 'Ich verantworte',
            'created' => 'Von mir erfasst',
            'all' => 'Alle',
        ];
    }

    public function updated(string $property): void
    {
        // Nach Prioritaet sortiert stehen die dringenden zuerst.
        if ($property === 'sort') {
            $this->direction = $this->sort === 'priority' ? 'desc' : 'asc';
        }

        if (in_array(Str::before($property, '.'), ['tab', 'sort', ...self::FILTERS], true)) {
            $this->resetPage();
        }
    }

    /**
     * Neu laden, wenn der Tab wieder sichtbar wird – Aufgaben werden in einem
     * eigenen Tab bearbeitet.
     */
    public function refresh(): void
    {
        unset($this->tabCounts);
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return [
            'open' => 'Offene',
            'in_progress' => 'In Arbeit',
            'done' => 'Erledigte',
            'all' => 'Alle',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function dueOptions(): array
    {
        return [
            'overdue' => 'Überfällig',
            'today' => 'Heute fällig',
            'week' => 'In den nächsten 7 Tagen',
            'none' => 'Ohne Datum',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function personRoleOptions(): array
    {
        return [
            'handler' => 'Liegt bei',
            'responsible' => 'Verantwortlich',
            'creator' => 'Erfasst von',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function subjectOptions(): array
    {
        return [
            'event' => 'Mit Ereignis',
            'none' => 'Ohne Bezug',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function sortOptions(): array
    {
        return [
            'due_date' => 'Fällig am',
            'priority' => 'Priorität',
            'title' => 'Titel',
            'updated_at' => 'Zuletzt geändert',
            'created_at' => 'Angelegt',
        ];
    }

    /**
     * Einen einzelnen Filter ueber sein Badge entfernen.
     */
    public function removeFilter(string $filter, ?string $value = null): void
    {
        match ($filter) {
            'categories', 'persons', 'priorities', 'teams' => $this->{$filter} = array_values(array_filter(
                $this->{$filter},
                fn ($current) => (string) $current !== (string) $value,
            )),
            'period' => [$this->dueFrom, $this->dueTo] = ['', ''],
            'status' => $this->status = 'open',
            'due' => $this->due = '',
            'personRole' => $this->personRole = '',
            'subject' => $this->subject = '',
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
        $this->reset(self::FILTERS);
        $this->resetPage();
    }

    /**
     * Anzahl je Reiter – mit Suche und Filtern, wie in der Ereignisliste.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function tabCounts(): array
    {
        return collect(array_keys($this->tabs()))
            ->mapWithKeys(fn (string $tab) => [$tab => $this->applyFilters($this->scopeTab(AdminTask::query(), $tab))->count()])
            ->all();
    }

    #[Computed]
    public function categoryOptions(): Collection
    {
        return AdminTaskCategory::ordered()->get();
    }

    #[Computed]
    public function teamOptions(): Collection
    {
        return AdminTask::assignableTeams();
    }

    #[Computed]
    public function users(): Collection
    {
        return AdminTask::assignableUsers();
    }

    public function toggleDone(int $taskId): void
    {
        $task = AdminTask::findOrFail($taskId);

        $task->update([
            'status' => $task->isDone() ? AdminTask::STATUS_OPEN : AdminTask::STATUS_DONE,
        ]);

        unset($this->tabCounts);
    }

    // ------------------------------------------------------------------
    // Rubriken
    // ------------------------------------------------------------------

    public function addCategory(): void
    {
        $this->validate(
            ['newCategory' => ['required', 'string', 'max:100', 'unique:admin_task_categories,name']],
            [
                'newCategory.required' => 'Bitte einen Namen für die Rubrik eingeben.',
                'newCategory.unique' => 'Diese Rubrik gibt es bereits.',
            ],
        );

        AdminTaskCategory::create([
            'name' => trim($this->newCategory),
            'sort_order' => (int) AdminTaskCategory::max('sort_order') + 1,
        ]);

        $this->newCategory = '';
        unset($this->categoryOptions);
    }

    /**
     * Eine Rubrik wird nicht geloescht, sondern ausgeblendet – bestehende
     * Aufgaben behalten sie.
     */
    public function toggleCategory(int $categoryId): void
    {
        $category = AdminTaskCategory::findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);

        unset($this->categoryOptions);
    }

    protected function scopeTab(Builder $query, string $tab): Builder
    {
        $userId = (int) auth('web')->id();

        return match ($tab) {
            'mine' => $query->handledBy($userId),
            'responsible' => $query->responsibleFor($userId),
            'created' => $query->where('created_by', $userId),
            default => $query,
        };
    }

    /**
     * Suche und Filter – fuer die Liste und fuer die Zahlen an den Reitern.
     */
    protected function applyFilters(Builder $query): Builder
    {
        match ($this->status) {
            'in_progress' => $query->where('status', AdminTask::STATUS_IN_PROGRESS),
            'done' => $query->where('status', AdminTask::STATUS_DONE),
            'all' => null,
            default => $query->open(),
        };

        if (($search = trim($this->search)) !== '') {
            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%'));
        }

        $priorities = array_values(array_intersect($this->priorities, array_keys(AdminTask::priorityOptions())));

        if ($priorities !== []) {
            $query->whereIn('priority', $priorities);
        }

        if (($categoryIds = $this->integerIds($this->categories)) !== []) {
            $query->whereIn('category_id', $categoryIds);
        }

        match ($this->due) {
            // Ueberfaellig ist nur, was noch nicht erledigt ist.
            'overdue' => $query->open()->where('due_date', '<', today()),
            'today' => $query->whereDate('due_date', today()),
            'week' => $query->whereBetween('due_date', [today(), today()->addDays(7)]),
            'none' => $query->whereNull('due_date'),
            default => null,
        };

        if ($from = $this->parseDate($this->dueFrom)) {
            $query->where('due_date', '>=', $from->startOfDay());
        }

        if ($to = $this->parseDate($this->dueTo)) {
            $query->where('due_date', '<=', $to->endOfDay());
        }

        // Personen: in der gewaehlten Rolle – ohne Rolle alles, womit sie zu tun haben.
        if (($personIds = $this->integerIds($this->persons)) !== []) {
            // Eine Person ist auch ueber ihre Teams beteiligt.
            match ($this->personRole) {
                'handler' => $query->handledBy($personIds),
                'responsible' => $query->responsibleFor($personIds),
                'creator' => $query->whereIn('created_by', $personIds),
                default => $query->where(fn (Builder $q) => $q
                    ->where(fn (Builder $sub) => $sub->handledBy($personIds))
                    ->orWhere(fn (Builder $sub) => $sub->responsibleFor($personIds))
                    ->orWhereIn('created_by', $personIds)),
            };
        }

        if (($teamIds = $this->integerIds($this->teams)) !== []) {
            $query->where(fn (Builder $q) => $q
                ->whereIn('responsible_team_id', $teamIds)
                ->orWhereIn('next_assignee_team_id', $teamIds));
        }

        match ($this->subject) {
            'event' => $query->whereNotNull('subject_id'),
            'none' => $query->whereNull('subject_id'),
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

    protected function tasks()
    {
        $query = $this->applyFilters($this->scopeTab(AdminTask::query(), $this->tab))
            ->with(['category', 'responsible', 'responsibleTeam', 'nextAssignee', 'nextAssigneeTeam', 'creator', 'subject', 'reminders'])
            ->withCount(['activities as notes_count' => fn (Builder $q) => $q->where('type', AdminTaskActivity::TYPE_NOTE)]);

        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'due_date';
        $direction = $this->direction === 'desc' ? 'desc' : 'asc';

        // Erledigtes steht immer hinten; Aufgaben ohne Datum nach denen mit Datum.
        $query->orderByRaw('status = ? asc', [AdminTask::STATUS_DONE]);

        if ($sort === 'due_date') {
            $query->orderByRaw('due_date is null asc');
        }

        if ($sort === 'priority') {
            // Aufsteigend: niedrig bis dringend.
            $query->orderByRaw("FIELD(priority, 'low', 'normal', 'high', 'urgent') ".$direction)
                ->orderByRaw('due_date is null asc')
                ->orderBy('due_date');
        } else {
            $query->orderBy($sort, $direction);
        }

        return $query
            ->orderByDesc('id')
            // 24 = volle Reihen bei zwei, drei und vier Karten nebeneinander.
            ->paginate(24);
    }

    public function render()
    {
        return view('livewire.admin-v2.tasks.index', [
            'tasks' => $this->tasks(),
        ]);
    }
}
