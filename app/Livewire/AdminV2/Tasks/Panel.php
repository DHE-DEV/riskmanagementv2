<?php

namespace App\Livewire\AdminV2\Tasks;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\AdminTask;
use App\Models\CustomEvent;
use App\Support\AdminV2\TaskSubjects;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Aufgaben zu einem Datensatz – fuer die Seitenspalte seines Formulars.
 *
 * Ein Ereignis kommt ueber eventId (seine Aufgaben gelten fuer alle Versionen);
 * bei einem noch nicht gespeicherten Ereignis laufen die Aufgaben ueber ein
 * Kennzeichen (token) und werden beim ersten Speichern zugeordnet. Jeder andere
 * Datensatz kommt ueber Art und ID (siehe TaskSubjects).
 */
class Panel extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public ?int $eventId = null;

    #[Locked]
    public ?string $token = null;

    /** Art des Datensatzes, z. B. "airline" (siehe TaskSubjects). */
    #[Locked]
    public ?string $kind = null;

    #[Locked]
    public ?int $recordId = null;

    /**
     * Neu laden, wenn der Tab wieder sichtbar wird (Aufgaben werden auch in
     * einem eigenen Tab bearbeitet) oder eine KI-Pruefung eine Aufgabe angelegt hat.
     */
    #[On('adminv2-tasks-changed')]
    public function refresh(): void
    {
        unset($this->tasks);
    }

    /**
     * Offene Aufgaben zuerst, dann nach Faelligkeit.
     */
    #[Computed]
    public function tasks(): Collection
    {
        $query = AdminTask::query()->with(['category', 'responsible', 'responsibleTeam', 'nextAssignee', 'nextAssigneeTeam', 'reminders']);

        if ($this->eventId) {
            $event = CustomEvent::withTrashed()->find($this->eventId);

            if (! $event) {
                return collect();
            }

            $query->forEvent($event);
        } elseif ($this->token) {
            $query->where('subject_token', $this->token);
        } elseif ($subject = TaskSubjects::find($this->kind, $this->recordId)) {
            $query->forSubject($subject);
        } else {
            return collect();
        }

        return $query
            ->orderByRaw('status = ? asc', [AdminTask::STATUS_DONE])
            ->orderByRaw('due_date is null asc')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }

    public function toggleDone(int $taskId): void
    {
        $task = $this->tasks->firstWhere('id', $taskId);

        $task?->update([
            'status' => $task->isDone() ? AdminTask::STATUS_OPEN : AdminTask::STATUS_DONE,
        ]);

        unset($this->tasks);
    }

    public function render()
    {
        return view('livewire.admin-v2.tasks.panel');
    }
}
