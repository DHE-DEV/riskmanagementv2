<?php

namespace App\Livewire\AdminV2\System;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\AdminTask;
use App\Models\AdminTaskRecurrence;
use App\Models\AdminTeam;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * System > Teams: Teams der Mitarbeiter anlegen, Mitglieder zuordnen und
 * festlegen, ob Benachrichtigungen an die zentrale Adresse des Teams oder an
 * jedes Mitglied einzeln gehen.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Teams')]
class Teams extends Component
{
    use AuthorizesAdminV2;

    /** Team, das gerade bearbeitet wird; null = neues Team. */
    #[Locked]
    public ?int $teamId = null;

    public string $name = '';

    public string $description = '';

    public string $email = '';

    public string $notifyMode = AdminTeam::NOTIFY_MEMBERS;

    /** @var array<int, string> IDs der Mitglieder */
    public array $memberIds = [];

    #[Computed]
    public function teams(): Collection
    {
        return AdminTeam::query()->with('users')->orderBy('name')->get();
    }

    #[Computed]
    public function users(): Collection
    {
        return AdminTask::assignableUsers();
    }

    /**
     * Offene Aufgaben je Team (verantwortlich oder am Zug).
     *
     * @return array<int, int>
     */
    #[Computed]
    public function openTaskCounts(): array
    {
        $counts = [];

        foreach ($this->teams as $team) {
            $counts[$team->id] = $this->openTasksOf($team->id);
        }

        return $counts;
    }

    protected function openTasksOf(int $teamId): int
    {
        return AdminTask::query()->open()
            ->where(fn ($query) => $query->where('responsible_team_id', $teamId)->orWhere('next_assignee_team_id', $teamId))
            ->count();
    }

    public function create(): void
    {
        $this->reset(['teamId', 'name', 'description', 'email', 'notifyMode', 'memberIds']);
        $this->resetValidation();

        $this->modal('team')->show();
    }

    public function edit(int $teamId): void
    {
        $team = AdminTeam::with('users')->findOrFail($teamId);

        $this->resetValidation();
        $this->teamId = $team->id;
        $this->name = $team->name;
        $this->description = (string) $team->description;
        $this->email = (string) $team->email;
        $this->notifyMode = $team->notify_mode;
        $this->memberIds = $team->users->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->modal('team')->show();
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('admin_teams', 'name')->ignore($this->teamId)->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:255'],
            // Ohne zentrale Adresse lassen sich Benachrichtigungen nicht dorthin schicken.
            'email' => [Rule::requiredIf($this->notifyMode === AdminTeam::NOTIFY_TEAM_EMAIL), 'nullable', 'email', 'max:255'],
            'notifyMode' => ['required', Rule::in([AdminTeam::NOTIFY_MEMBERS, AdminTeam::NOTIFY_TEAM_EMAIL])],
            'memberIds' => ['array'],
            'memberIds.*' => [Rule::in($this->users->pluck('id')->map(fn ($id) => (string) $id)->all())],
        ], [
            'name.required' => 'Bitte einen Namen für das Team eingeben.',
            'name.unique' => 'Ein Team mit diesem Namen gibt es bereits.',
            'email.required' => 'Bitte die zentrale E-Mail-Adresse des Teams angeben – oder die Mitglieder einzeln benachrichtigen.',
            'email.email' => 'Bitte eine gültige E-Mail-Adresse eingeben.',
        ]);

        $team = $this->teamId ? AdminTeam::findOrFail($this->teamId) : new AdminTeam;

        $team->fill([
            'name' => trim($this->name),
            'description' => filled($this->description) ? trim($this->description) : null,
            'email' => filled($this->email) ? trim($this->email) : null,
            'notify_mode' => $this->notifyMode,
        ])->save();

        $team->users()->sync(array_map('intval', $this->memberIds));

        unset($this->teams, $this->openTaskCounts);
        $this->modal('team')->close();

        $this->dispatch('adminv2-toast', message: $this->teamId ? 'Team gespeichert.' : 'Team angelegt.');
    }

    /**
     * Ein Team laesst sich nur loeschen, wenn ihm nichts Offenes mehr zugeordnet ist.
     */
    public function delete(int $teamId): void
    {
        $team = AdminTeam::findOrFail($teamId);

        $openTasks = $this->openTasksOf($team->id);
        $series = AdminTaskRecurrence::query()
            ->where(fn ($query) => $query->where('responsible_team_id', $team->id)->orWhere('next_assignee_team_id', $team->id))
            ->count();

        if ($openTasks > 0 || $series > 0) {
            $parts = array_filter([
                $openTasks > 0 ? $openTasks.' offene '.($openTasks === 1 ? 'Aufgabe' : 'Aufgaben') : null,
                $series > 0 ? $series.' wiederkehrende '.($series === 1 ? 'Aufgabe' : 'Aufgaben') : null,
            ]);

            $this->dispatch('adminv2-toast', message: 'Dem Team sind noch '.implode(' und ', $parts).' zugeordnet. Bitte zuerst umhängen.', variant: 'danger');

            return;
        }

        $team->delete();

        unset($this->teams, $this->openTaskCounts);

        $this->dispatch('adminv2-toast', message: 'Team gelöscht.');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.teams');
    }
}
