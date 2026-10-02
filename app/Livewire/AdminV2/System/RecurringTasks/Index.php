<?php

namespace App\Livewire\AdminV2\System\RecurringTasks;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\AdminTaskRecurrence;
use App\Services\AdminTaskRecurrenceService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * System > Wiederkehrende Aufgaben: Vorlagen mit Rhythmus, aus denen der
 * Zeitplaner zum jeweiligen Termin Aufgaben anlegt.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Wiederkehrende Aufgaben')]
class Index extends Component
{
    use AuthorizesAdminV2;

    #[Computed]
    public function recurrences(): Collection
    {
        return AdminTaskRecurrence::query()
            ->with(['category', 'responsible', 'responsibleTeam', 'nextAssignee', 'nextAssigneeTeam'])
            ->withCount(['tasks as open_tasks_count' => fn ($query) => $query->open()])
            // Aktive zuerst, dann nach dem naechsten Termin.
            ->orderByDesc('is_active')
            ->orderByRaw('next_run_at is null asc')
            ->orderBy('next_run_at')
            ->orderBy('title')
            ->get();
    }

    /**
     * Pausieren bzw. fortsetzen. Beim Fortsetzen zaehlt der naechste Termin
     * ab jetzt – waehrend der Pause verpasste Termine werden nicht nachgeholt.
     */
    public function toggleActive(int $recurrenceId): void
    {
        $recurrence = AdminTaskRecurrence::findOrFail($recurrenceId);

        $recurrence->is_active = ! $recurrence->is_active;
        $recurrence->last_error = null;
        $recurrence->scheduleNext()->save();

        unset($this->recurrences);

        $this->dispatch('adminv2-toast', message: $recurrence->is_active ? 'Fortgesetzt.' : 'Pausiert.');
    }

    /**
     * Ausser der Reihe sofort eine Aufgabe anlegen.
     */
    public function createNow(int $recurrenceId): void
    {
        $recurrence = AdminTaskRecurrence::findOrFail($recurrenceId);
        $task = app(AdminTaskRecurrenceService::class)->createNow($recurrence);

        unset($this->recurrences);

        $this->dispatch(
            'adminv2-toast',
            message: $task ? 'Aufgabe „'.$task->title.'“ angelegt.' : (string) $recurrence->last_error,
            variant: $task ? 'success' : 'danger',
        );
    }

    public function delete(int $recurrenceId): void
    {
        AdminTaskRecurrence::findOrFail($recurrenceId)->delete();

        unset($this->recurrences);

        $this->dispatch('adminv2-toast', message: 'Wiederkehrende Aufgabe gelöscht. Bereits angelegte Aufgaben bleiben erhalten.');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.recurring-tasks.index');
    }
}
