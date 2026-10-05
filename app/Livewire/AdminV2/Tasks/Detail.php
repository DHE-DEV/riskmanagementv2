<?php

namespace App\Livewire\AdminV2\Tasks;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\AdminTask;
use App\Models\AdminTaskActivity;
use App\Models\AdminTaskCategory;
use App\Models\AdminTaskReminder;
use App\Models\CustomEvent;
use App\Support\AdminV2\TaskSubjects;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Eigene Seite zum Anlegen und Bearbeiten einer Aufgabe – samt Notizen und
 * Verlauf. Aus der Aufgabenliste und aus dem Ereignis-Formular oeffnet sie sich
 * in einem neuen Browser-Tab, von der Seite eines anderen Datensatzes aus im
 * selben Tab – nach dem Anlegen geht es dorthin zurueck.
 *
 * Eine neue Aufgabe kann ueber die Adresse einen Bezug mitbekommen:
 * ?event=ID (gespeichertes Ereignis), ?token=… (noch nicht gespeichertes
 * Ereignis) oder ?subject=Art&subject_id=ID (jeder andere Datensatz, siehe
 * TaskSubjects) sowie ?category=Name fuer die vorbelegte Rubrik.
 */
#[Layout('components.layouts.adminv2.app')]
class Detail extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public ?int $taskId = null;

    #[Locked]
    public ?string $subjectKind = null;

    #[Locked]
    public ?int $subjectId = null;

    #[Locked]
    public ?string $subjectToken = null;

    /** Hauptaufgabe, zu der eine neue Unteraufgabe gehoert (?parent=ID). */
    #[Locked]
    public ?int $parentId = null;

    /** Titel fuer eine schnell angelegte Unteraufgabe. */
    public string $subtaskTitle = '';

    public string $title = '';

    public string $description = '';

    public string $categoryId = '';

    public string $status = AdminTask::STATUS_OPEN;

    public string $dueDate = '';

    public string $priority = AdminTask::PRIORITY_NORMAL;

    /**
     * Beliebig viele Erinnerungen – jede mit Zeitpunkt, Person und Hinweis.
     *
     * @var array<int, array{id: ?int, remindAt: string, userId: string, note: string, sentAt: ?string}>
     */
    public array $reminders = [];

    public string $responsibleId = '';

    public string $nextAssigneeId = '';

    public string $note = '';

    public function mount($task = null): void
    {
        if ($task !== null) {
            $task = AdminTask::findOrFail((int) $task);

            $this->taskId = $task->id;
            $this->fillFrom($task);

            return;
        }

        // Neue Unteraufgabe: Rubrik, Prioritaet, Faelligkeit, Verantwortung und
        // Bezug kommen von der Hauptaufgabe.
        if ($parent = AdminTask::find((int) request()->query('parent'))) {
            $this->parentId = $parent->id;
            $this->subjectKind = TaskSubjects::kindOf($parent->subject_type) ?? ($parent->subject_token ? 'event' : null);
            $this->subjectId = $parent->subject_id;
            $this->subjectToken = $parent->subject_token;
            $this->categoryId = (string) $parent->category_id;
            $this->priority = $parent->priority ?: AdminTask::PRIORITY_NORMAL;
            $this->dueDate = $parent->due_date?->format('Y-m-d') ?? '';
            $this->responsibleId = AdminTask::assigneeValue($parent->responsible_id, $parent->responsible_team_id);

            return;
        }

        // Neue Aufgabe – optional mit Bezug auf ein Ereignis.
        $eventId = (int) request()->query('event');
        $token = (string) request()->query('token', '');

        if ($eventId > 0 && CustomEvent::withTrashed()->whereKey($eventId)->exists()) {
            $this->subjectKind = 'event';
            $this->subjectId = $eventId;
        } elseif (preg_match('/^[A-Za-z0-9-]{8,64}$/', $token)) {
            $this->subjectKind = 'event';
            $this->subjectToken = $token;
        } elseif ($subject = TaskSubjects::find((string) request()->query('subject', ''), (int) request()->query('subject_id'))) {
            // Bezug auf einen anderen Datensatz, z. B. eine Airline oder einen Kunden.
            $this->subjectKind = TaskSubjects::kindOf($subject);
            $this->subjectId = $subject->getKey();
        }

        $this->responsibleId = (string) auth('web')->id();

        if (($categoryName = (string) request()->query('category', '')) !== '') {
            $this->categoryId = (string) (AdminTaskCategory::active()->where('name', $categoryName)->value('id') ?? '');
        }
    }

    protected function fillFrom(AdminTask $task): void
    {
        $this->title = $task->title;
        $this->description = (string) $task->description;
        $this->categoryId = (string) $task->category_id;
        $this->status = $task->status;
        $this->dueDate = $task->due_date?->format('Y-m-d') ?? '';
        $this->priority = $task->priority ?: AdminTask::PRIORITY_NORMAL;
        $this->reminders = $task->reminders()->get()->map(fn (AdminTaskReminder $reminder) => [
            'id' => $reminder->id,
            'remindAt' => $reminder->remind_at->format('Y-m-d\TH:i'),
            'userId' => (string) ($reminder->user_id ?? ''),
            'note' => (string) $reminder->note,
            'sentAt' => $reminder->sent_at?->format('d.m.Y H:i'),
        ])->all();
        // Person als ID, Team als "team:ID".
        $this->responsibleId = AdminTask::assigneeValue($task->responsible_id, $task->responsible_team_id);
        $this->nextAssigneeId = AdminTask::assigneeValue($task->next_assignee_id, $task->next_assignee_team_id);
    }

    #[Computed]
    public function task(): ?AdminTask
    {
        return $this->taskId
            ? AdminTask::with(['creator', 'subject', 'category', 'recurrence', 'responsibleTeam', 'nextAssigneeTeam', 'parent', 'aiCheck'])->find($this->taskId)
            : null;
    }

    /**
     * Die Hauptaufgabe – der geoeffneten Unteraufgabe oder der gerade neu angelegten.
     */
    #[Computed]
    public function parentTask(): ?AdminTask
    {
        return $this->task?->parent ?? ($this->parentId ? AdminTask::find($this->parentId) : null);
    }

    #[Computed]
    public function subtasks(): Collection
    {
        return $this->task
            ? $this->task->subtasks()->with(['responsible', 'responsibleTeam', 'nextAssignee', 'nextAssigneeTeam'])->get()
            : collect();
    }

    #[Computed]
    public function categories(): Collection
    {
        // Eine inzwischen deaktivierte Rubrik bleibt an ihrer Aufgabe waehlbar.
        return AdminTaskCategory::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $this->task?->category_id))
            ->ordered()
            ->get();
    }

    #[Computed]
    public function teams(): Collection
    {
        return AdminTask::assignableTeams();
    }

    #[Computed]
    public function users(): Collection
    {
        return AdminTask::assignableUsers();
    }

    /**
     * Notizen und Aenderungen, neueste zuerst.
     */
    #[Computed]
    public function timeline(): Collection
    {
        return $this->task
            ? $this->task->activities()->with('user')->latest('created_at')->latest('id')->get()
            : collect();
    }

    /**
     * Bezeichnung des Bezugs bei einer neuen Aufgabe (vor dem Speichern).
     */
    #[Computed]
    public function pendingSubjectLabel(): ?string
    {
        if ($this->subjectKind === null) {
            return null;
        }

        return $this->subjectId
            ? TaskSubjects::label(TaskSubjects::find($this->subjectKind, $this->subjectId))
            : 'Ereignis (wird beim Speichern des Ereignisses zugeordnet)';
    }

    /**
     * Seite des Datensatzes, zu dem die neue Aufgabe gehoert – fuer den Weg zurueck.
     */
    #[Computed]
    public function pendingSubjectUrl(): ?string
    {
        return $this->subjectId ? TaskSubjects::url(TaskSubjects::find($this->subjectKind, $this->subjectId)) : null;
    }

    protected function rules(): array
    {
        $userIds = $this->users->pluck('id')->all();
        // Verantwortlich und naechster Bearbeiter: eine Person oder ein Team.
        $assignees = array_merge(
            array_map('strval', $userIds),
            $this->teams->map(fn ($team) => 'team:'.$team->id)->all(),
        );

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'categoryId' => ['required', Rule::exists('admin_task_categories', 'id')],
            'status' => ['required', Rule::in(array_keys(AdminTask::statusOptions()))],
            'dueDate' => ['nullable', 'date'],
            'priority' => ['required', Rule::in(array_keys(AdminTask::priorityOptions()))],
            'reminders' => ['array', 'max:50'],
            'reminders.*.remindAt' => ['required', 'date'],
            'reminders.*.userId' => ['nullable', Rule::in(array_merge([''], array_map('strval', $userIds)))],
            'reminders.*.note' => ['nullable', 'string', 'max:255'],
            'responsibleId' => ['required', Rule::in($assignees)],
            'nextAssigneeId' => ['nullable', Rule::in(array_merge([''], $assignees))],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Bitte einen Titel für die Aufgabe eingeben.',
            'categoryId.required' => 'Bitte eine Rubrik wählen.',
            'responsibleId.required' => 'Bitte eine verantwortliche Person wählen.',
            'reminders.*.remindAt.required' => 'Bitte Datum und Uhrzeit der Erinnerung angeben.',
            'reminders.*.remindAt.date' => 'Bitte Datum und Uhrzeit der Erinnerung angeben.',
        ];
    }

    /**
     * Eine weitere Erinnerung – vorbelegt mit dem Morgen des Faelligkeitstags
     * bzw. morgen frueh, fuer die Person, bei der die Aufgabe liegt.
     */
    public function addReminder(): void
    {
        $day = rescue(fn () => $this->dueDate !== '' ? Carbon::parse($this->dueDate) : null, null, false);

        if (! $day || $day->lt(today())) {
            $day = today()->addDay();
        }

        $this->reminders[] = [
            'id' => null,
            'remindAt' => $day->setTime(9, 0)->format('Y-m-d\TH:i'),
            'userId' => '',
            'note' => '',
            'sentAt' => null,
        ];
    }

    public function removeReminder(int $index): void
    {
        unset($this->reminders[$index]);
        $this->reminders = array_values($this->reminders);
        $this->resetValidation();
    }

    /**
     * Die Erinnerungen in der Form, die das Modell erwartet.
     */
    protected function reminderRows(): array
    {
        return array_map(fn (array $row) => [
            'id' => $row['id'] ?? null,
            'remind_at' => $row['remindAt'],
            'user_id' => ($row['userId'] ?? '') !== '' ? (int) $row['userId'] : null,
            'note' => $row['note'] ?? null,
        ], $this->reminders);
    }

    public function save()
    {
        $this->validate();

        [$responsibleUserId, $responsibleTeamId] = AdminTask::parseAssignee($this->responsibleId);
        [$nextUserId, $nextTeamId] = AdminTask::parseAssignee($this->nextAssigneeId);

        $attributes = [
            'title' => trim($this->title),
            'description' => filled($this->description) ? $this->description : null,
            'category_id' => (int) $this->categoryId,
            'status' => $this->status,
            'due_date' => $this->dueDate ?: null,
            'priority' => $this->priority,
            'responsible_id' => $responsibleUserId,
            'responsible_team_id' => $responsibleTeamId,
            'next_assignee_id' => $nextUserId,
            'next_assignee_team_id' => $nextTeamId,
        ];

        if ($this->task) {
            $this->task->update($attributes);
            $this->task->syncReminders($this->reminderRows());
            $this->fillFrom($this->task->fresh());
            $message = 'Aufgabe gespeichert.';
        } else {
            $task = AdminTask::create($attributes + [
                'parent_id' => $this->parentId,
                'created_by' => auth('web')->id(),
                'subject_type' => $this->subjectId ? TaskSubjects::morphClass($this->subjectKind) : null,
                'subject_id' => $this->subjectId,
                'subject_token' => $this->subjectToken,
            ]);

            $task->syncReminders($this->reminderRows(), log: false);

            session()->flash('adminv2-toast', 'Aufgabe angelegt.');

            // Von der Seite eines Datensatzes aus angelegt (im selben Tab): zurueck dorthin.
            // Ereignisse oeffnen die Aufgabe in einem eigenen Tab – dort bleibt es bei der Aufgabe.
            if ($this->subjectKind !== 'event' && ! $this->parentId && ($returnUrl = $this->pendingSubjectUrl)) {
                return $this->redirect($returnUrl);
            }

            // Sonst weiter auf der Seite der neuen Aufgabe – so stimmt die Adresse
            // und es laesst sich direkt eine erste Notiz ergaenzen.
            return $this->redirectRoute('adminv2.tasks.show', $task);
        }

        unset($this->task, $this->timeline);

        $this->dispatch('adminv2-toast', message: $message);
    }

    /**
     * Unteraufgabe mit nur einem Titel anlegen – alles Weitere kommt von der
     * Hauptaufgabe und laesst sich auf der eigenen Seite der Unteraufgabe aendern.
     */
    public function addSubtask(): void
    {
        $this->validate(['subtaskTitle' => ['required', 'string', 'max:255']], ['subtaskTitle.required' => 'Bitte einen Titel für die Unteraufgabe eingeben.']);

        if (! $this->task) {
            return;
        }

        AdminTask::create($this->task->subtaskDefaults() + [
            'title' => trim($this->subtaskTitle),
            'created_by' => auth('web')->id(),
        ]);
        $this->task->touch();

        $this->subtaskTitle = '';
        unset($this->subtasks, $this->task, $this->timeline);
    }

    /**
     * Unteraufgabe direkt aus der Liste erledigen bzw. wieder oeffnen.
     */
    public function toggleSubtask(int $subtaskId): void
    {
        $subtask = $this->task?->subtasks()->whereKey($subtaskId)->first();

        if (! $subtask) {
            return;
        }

        $subtask->update(['status' => $subtask->isDone() ? AdminTask::STATUS_OPEN : AdminTask::STATUS_DONE]);

        unset($this->subtasks);

        [$done, $total] = $this->task->fresh()->subtaskProgress();

        if ($done === $total && ! $this->task->isDone()) {
            $this->dispatch('adminv2-toast', message: 'Alle Unteraufgaben sind erledigt – die Hauptaufgabe ist noch offen.');
        }
    }

    public function addNote(): void
    {
        $this->validate(['note' => ['required', 'string', 'max:10000']], ['note.required' => 'Bitte einen Text für die Notiz eingeben.']);

        if (! $this->task) {
            return;
        }

        $this->task->activities()->create([
            'user_id' => auth('web')->id(),
            'type' => AdminTaskActivity::TYPE_NOTE,
            'body' => trim($this->note),
        ]);
        $this->task->touch();

        $this->note = '';
        unset($this->timeline);
    }

    /**
     * Erledigt bzw. wieder geoeffnet – ohne die uebrigen Felder zu speichern.
     */
    public function toggleDone(): void
    {
        if (! $this->task) {
            return;
        }

        $this->task->update([
            'status' => $this->task->isDone() ? AdminTask::STATUS_OPEN : AdminTask::STATUS_DONE,
        ]);
        $this->status = $this->task->status;

        unset($this->task, $this->timeline);
    }

    public function delete()
    {
        $parent = $this->task?->parent;
        $subtasks = $this->task ? $this->task->subtasks()->count() : 0;

        $this->task?->delete();

        session()->flash('adminv2-toast', $subtasks > 0
            ? 'Aufgabe samt '.$subtasks.' '.($subtasks === 1 ? 'Unteraufgabe' : 'Unteraufgaben').' gelöscht.'
            : 'Aufgabe gelöscht.');

        // Von einer Unteraufgabe zurueck zur Hauptaufgabe.
        return $parent
            ? $this->redirectRoute('adminv2.tasks.show', $parent)
            : $this->redirectRoute('adminv2.tasks.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.tasks.detail')
            ->title($this->task ? $this->task->title : 'Neue Aufgabe');
    }
}
