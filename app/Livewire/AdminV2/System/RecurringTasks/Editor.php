<?php

namespace App\Livewire\AdminV2\System\RecurringTasks;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\AdminTask;
use App\Models\AdminTaskCategory;
use App\Models\AdminTaskRecurrence;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Formular einer wiederkehrenden Aufgabe: Vorlage, Rhythmus, Zeitraum,
 * Faelligkeit. Die Vorschau zeigt den Rhythmus in Worten und die naechsten
 * Termine – berechnet mit derselben Logik wie im Zeitplaner.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public ?int $recurrenceId = null;

    // Vorlage der Aufgabe
    public string $title = '';

    public string $description = '';

    public string $categoryId = '';

    public string $priority = AdminTask::PRIORITY_NORMAL;

    public string $responsibleId = '';

    public string $nextAssigneeId = '';

    // Rhythmus
    public string $frequency = AdminTaskRecurrence::FREQUENCY_WEEKLY;

    public string $interval = '1';

    /** @var array<int, string> Wochentage 1 (Mo) bis 7 (So) */
    public array $weekdays = [];

    /** day = fester Tag, weekday = z. B. zweiter Dienstag */
    public string $monthlyMode = 'day';

    /** 1–31; 0 = letzter Tag des Monats */
    public string $dayOfMonth = '1';

    public string $nth = '1';

    public string $nthWeekday = '1';

    public string $month = '1';

    public string $weekendMode = 'keep';

    /** Nur bei "taeglich": Samstag und Sonntag auslassen. */
    public bool $workdaysOnly = false;

    public string $createTime = '07:00';

    // Zeitraum
    public string $startsOn = '';

    /** never | date | count */
    public string $endMode = 'never';

    public string $endsOn = '';

    public string $maxOccurrences = '';

    // Faelligkeit und Erinnerung
    public bool $hasDue = true;

    public string $dueInDays = '0';

    public bool $hasReminder = false;

    public string $remindDaysBefore = '0';

    public string $remindTime = '09:00';

    // Verhalten
    public bool $skipIfOpen = false;

    public bool $isActive = true;

    public function mount($recurrence = null): void
    {
        if ($recurrence === null) {
            $this->responsibleId = (string) auth('web')->id();
            $this->startsOn = today()->format('Y-m-d');
            $this->weekdays = [(string) today()->dayOfWeekIso];
            $this->month = (string) today()->month;

            return;
        }

        $model = AdminTaskRecurrence::findOrFail((int) $recurrence);

        $this->recurrenceId = $model->id;
        $this->title = $model->title;
        $this->description = (string) $model->description;
        $this->categoryId = (string) $model->category_id;
        $this->priority = $model->priority ?: AdminTask::PRIORITY_NORMAL;
        // Person als ID, Team als "team:ID".
        $this->responsibleId = AdminTask::assigneeValue($model->responsible_id, $model->responsible_team_id);
        $this->nextAssigneeId = AdminTask::assigneeValue($model->next_assignee_id, $model->next_assignee_team_id);
        $this->frequency = $model->frequency;
        $this->interval = (string) $model->interval;
        $this->weekdays = array_map('strval', $model->weekdays ?? []);
        $this->monthlyMode = $model->monthly_mode;
        $this->dayOfMonth = (string) $model->day_of_month;
        $this->nth = (string) $model->nth;
        $this->nthWeekday = (string) $model->nth_weekday;
        $this->month = (string) ($model->month ?: $model->starts_on->month);
        $this->weekendMode = $model->weekend_mode;
        $this->workdaysOnly = $model->frequency === AdminTaskRecurrence::FREQUENCY_DAILY && $model->weekend_mode === 'skip';
        $this->createTime = substr((string) $model->create_time, 0, 5);
        $this->startsOn = $model->starts_on->format('Y-m-d');
        $this->endMode = match (true) {
            $model->max_occurrences !== null => 'count',
            $model->ends_on !== null => 'date',
            default => 'never',
        };
        $this->endsOn = $model->ends_on?->format('Y-m-d') ?? '';
        $this->maxOccurrences = (string) ($model->max_occurrences ?? '');
        $this->hasDue = $model->due_in_days !== null;
        $this->dueInDays = (string) ($model->due_in_days ?? 0);
        $this->hasReminder = $model->remind_days_before !== null;
        $this->remindDaysBefore = (string) ($model->remind_days_before ?? 0);
        $this->remindTime = substr((string) $model->remind_time, 0, 5);
        $this->skipIfOpen = $model->skip_if_open;
        $this->isActive = $model->is_active;
    }

    #[Computed]
    public function recurrence(): ?AdminTaskRecurrence
    {
        return $this->recurrenceId ? AdminTaskRecurrence::find($this->recurrenceId) : null;
    }

    #[Computed]
    public function categories(): Collection
    {
        // Eine inzwischen ausgeblendete Rubrik bleibt an ihrer Serie waehlbar.
        return AdminTaskCategory::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $this->recurrence?->category_id))
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
     * Die zuletzt aus dieser Serie angelegten Aufgaben.
     */
    #[Computed]
    public function recentTasks(): Collection
    {
        return $this->recurrence
            ? $this->recurrence->tasks()->latest('id')->limit(5)->get()
            : collect();
    }

    /**
     * Die Eingaben als (ungespeichertes) Modell – Grundlage fuer Vorschau und Speichern.
     */
    protected function draft(): AdminTaskRecurrence
    {
        $model = $this->recurrence ? clone $this->recurrence : new AdminTaskRecurrence;

        // Taeglich kennt nur "an jedem Tag" oder "nur werktags".
        $weekendMode = match (true) {
            $this->frequency === AdminTaskRecurrence::FREQUENCY_WEEKLY => 'keep',
            $this->frequency === AdminTaskRecurrence::FREQUENCY_DAILY => $this->workdaysOnly ? 'skip' : 'keep',
            default => $this->weekendMode,
        };

        [$responsibleUserId, $responsibleTeamId] = AdminTask::parseAssignee($this->responsibleId);
        [$nextUserId, $nextTeamId] = AdminTask::parseAssignee($this->nextAssigneeId);

        $model->fill([
            'title' => trim($this->title),
            'description' => filled($this->description) ? $this->description : null,
            'category_id' => (int) $this->categoryId ?: null,
            'priority' => $this->priority,
            'responsible_id' => $responsibleUserId,
            'responsible_team_id' => $responsibleTeamId,
            'next_assignee_id' => $nextUserId,
            'next_assignee_team_id' => $nextTeamId,
            'frequency' => $this->frequency,
            'interval' => max(1, (int) $this->interval),
            'weekdays' => array_values(array_map('intval', $this->weekdays)),
            'monthly_mode' => $this->monthlyMode === 'weekday' ? 'weekday' : 'day',
            'day_of_month' => max(0, min(31, (int) $this->dayOfMonth)),
            'nth' => (int) $this->nth,
            'nth_weekday' => (int) $this->nthWeekday,
            'month' => $this->frequency === AdminTaskRecurrence::FREQUENCY_YEARLY ? (int) $this->month : null,
            'weekend_mode' => $weekendMode,
            'create_time' => $this->validTime($this->createTime, '07:00').':00',
            'starts_on' => $this->validDate($this->startsOn) ?? today()->format('Y-m-d'),
            'ends_on' => $this->endMode === 'date' ? $this->validDate($this->endsOn) : null,
            'max_occurrences' => $this->endMode === 'count' && (int) $this->maxOccurrences > 0 ? (int) $this->maxOccurrences : null,
            'due_in_days' => $this->hasDue ? max(0, (int) $this->dueInDays) : null,
            'remind_days_before' => $this->hasDue && $this->hasReminder ? max(0, (int) $this->remindDaysBefore) : null,
            'remind_time' => $this->validTime($this->remindTime, '09:00').':00',
            'skip_if_open' => $this->skipIfOpen,
            'is_active' => $this->isActive,
        ]);

        return $model;
    }

    protected function validTime(string $value, string $fallback): string
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : $fallback;
    }

    protected function validDate(string $value): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false ? $value : null;
    }

    /**
     * Vorschau: Rhythmus in Worten und die naechsten Termine.
     *
     * @return array{summary: string, dates: array<int, array{date: string, weekday: string, title: string, due: ?string}>, ended: bool}
     */
    #[Computed]
    public function preview(): array
    {
        $draft = $this->draft();
        $dates = $draft->nextOccurrences(now(), 6);

        return [
            'summary' => $draft->summary(),
            'ended' => $dates === [],
            'dates' => array_map(fn ($date) => [
                'date' => $date->format('d.m.Y H:i'),
                'weekday' => AdminTaskRecurrence::WEEKDAYS_SHORT[$date->dayOfWeekIso],
                'title' => $draft->titleFor($date),
                'due' => $draft->due_in_days !== null ? $date->addDays($draft->due_in_days)->format('d.m.Y') : null,
            ], $dates),
        ];
    }

    protected function rules(): array
    {
        // Verantwortlich und naechster Bearbeiter: eine Person oder ein Team.
        $assignees = array_merge(
            $this->users->pluck('id')->map(fn ($id) => (string) $id)->all(),
            $this->teams->map(fn ($team) => 'team:'.$team->id)->all(),
        );

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'categoryId' => ['required', Rule::exists('admin_task_categories', 'id')],
            'priority' => ['required', Rule::in(array_keys(AdminTask::priorityOptions()))],
            'responsibleId' => ['required', Rule::in($assignees)],
            'nextAssigneeId' => ['nullable', Rule::in(array_merge([''], $assignees))],
            'frequency' => ['required', Rule::in(array_keys(AdminTaskRecurrence::frequencyOptions()))],
            'interval' => ['required', 'integer', 'min:1', 'max:365'],
            'weekdays' => [Rule::requiredIf($this->frequency === AdminTaskRecurrence::FREQUENCY_WEEKLY), 'array'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'monthlyMode' => ['required', Rule::in(['day', 'weekday'])],
            'dayOfMonth' => ['required', 'integer', 'between:0,31'],
            'nth' => ['required', Rule::in(['1', '2', '3', '4', '-1'])],
            'nthWeekday' => ['required', 'integer', 'between:1,7'],
            'month' => ['required', 'integer', 'between:1,12'],
            'weekendMode' => ['required', Rule::in(['keep', 'skip', 'before', 'after'])],
            'createTime' => ['required', 'date_format:H:i'],
            'startsOn' => ['required', 'date'],
            'endMode' => ['required', Rule::in(['never', 'date', 'count'])],
            // Felder einer nicht gewaehlten Variante bleiben ungeprueft – ihr Inhalt wird nicht gespeichert.
            'endsOn' => $this->endMode === 'date' ? ['required', 'date', 'after_or_equal:startsOn'] : [],
            'maxOccurrences' => $this->endMode === 'count' ? ['required', 'integer', 'min:1', 'max:10000'] : [],
            'dueInDays' => $this->hasDue ? ['required', 'integer', 'min:0', 'max:3650'] : [],
            'remindDaysBefore' => $this->hasDue && $this->hasReminder ? ['required', 'integer', 'min:0', 'max:3650'] : [],
            'remindTime' => ['required', 'date_format:H:i'],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Bitte einen Titel für die Aufgabe eingeben.',
            'categoryId.required' => 'Bitte eine Rubrik wählen.',
            'responsibleId.required' => 'Bitte eine verantwortliche Person wählen.',
            'weekdays.required' => 'Bitte mindestens einen Wochentag wählen.',
            'interval.min' => 'Der Abstand muss mindestens 1 sein.',
            'endsOn.required' => 'Bitte das Enddatum angeben.',
            'endsOn.after_or_equal' => 'Das Enddatum darf nicht vor dem Beginn liegen.',
            'maxOccurrences.required' => 'Bitte angeben, nach wie vielen Aufgaben Schluss ist.',
            'dueInDays.required' => 'Bitte angeben, wie viele Tage nach dem Anlegen die Aufgabe fällig ist.',
        ];
    }

    public function save()
    {
        $this->validate();

        $model = $this->draft();

        if (! $model->exists) {
            $model->created_by = auth('web')->id();
        }

        // Eine Erinnerung, die vor dem Anlegen der Aufgabe laege, gibt es nicht.
        if ($model->remind_days_before !== null && $model->remind_days_before > $model->due_in_days) {
            $this->addError('remindDaysBefore', 'Die Erinnerung läge vor dem Anlegen der Aufgabe.');

            return;
        }

        if ($model->max_occurrences !== null && $model->max_occurrences <= $model->occurrences_count) {
            $this->addError('maxOccurrences', "Aus dieser Serie wurden bereits {$model->occurrences_count} Aufgaben angelegt.");

            return;
        }

        $model->last_error = null;
        $model->scheduleNext()->save();

        session()->flash('adminv2-toast', $model->next_run_at
            ? 'Gespeichert. Nächste Aufgabe am '.$model->next_run_at->format('d.m.Y').' um '.$model->next_run_at->format('H:i').' Uhr.'
            : ($model->is_active ? 'Gespeichert. Mit diesen Angaben gibt es keinen weiteren Termin.' : 'Gespeichert – pausiert.'));

        return $this->redirectRoute('adminv2.system.recurring-tasks.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.recurring-tasks.editor')
            ->title($this->recurrence ? 'Wiederkehrende Aufgabe bearbeiten' : 'Neue wiederkehrende Aufgabe');
    }
}
