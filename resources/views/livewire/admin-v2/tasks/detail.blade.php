@php
    use App\Models\AdminTask;
    use App\Models\AdminTaskActivity;

    $task = $this->task;
    $subjectLabel = $task ? $task->subjectLabel() : $this->pendingSubjectLabel;
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('adminv2.tasks.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Aufgaben
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <flux:heading size="xl" level="1">{{ $task ? $task->title : 'Neue Aufgabe' }}</flux:heading>
                @if ($task)
                    <flux:badge
                        size="sm"
                        :color="['open' => 'zinc', 'in_progress' => 'sky', 'done' => 'green'][$task->status] ?? 'zinc'"
                    >{{ AdminTask::statusOptions()[$task->status] ?? $task->status }}</flux:badge>
                    @if ($task->isOverdue())
                        <flux:badge size="sm" color="red">überfällig</flux:badge>
                    @endif
                @endif
            </div>
            @if ($task?->recurrence)
                <flux:subheading class="mt-1 flex items-center gap-1.5">
                    <flux:icon.arrow-path variant="micro" class="shrink-0" />
                    <span>Wiederkehrend:</span>
                    @if ($task->recurrence->trashed())
                        <span class="truncate">{{ $task->recurrence->summary() }} (Serie gelöscht)</span>
                    @else
                        <a href="{{ route('adminv2.system.recurring-tasks.edit', $task->recurrence) }}" target="_blank" class="truncate underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">{{ $task->recurrence->summary() }}</a>
                    @endif
                </flux:subheading>
            @endif
            @if ($subjectLabel)
                <flux:subheading class="mt-1 flex items-center gap-1.5">
                    <flux:icon.link variant="micro" class="shrink-0" />
                    @if ($task?->subject instanceof \App\Models\CustomEvent)
                        <a href="{{ route('adminv2.events.edit', $task->subject_id) }}" target="_blank" class="truncate underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">{{ $subjectLabel }}</a>
                    @else
                        <span class="truncate">{{ $subjectLabel }}</span>
                    @endif
                </flux:subheading>
            @endif
        </div>
    </div>

    <div class="grid items-start gap-6 xl:grid-cols-2">
        <x-adminv2.card heading="Aufgabe" :description="$task ? null : 'Nach dem Anlegen lassen sich Notizen ergänzen.'">
            <form wire:submit="save" class="flex flex-col gap-5">
                <flux:input wire:model="title" label="Titel" placeholder="Was ist zu tun?" maxlength="255" />

                <flux:textarea wire:model="description" label="Beschreibung" rows="3" placeholder="Details, Hintergrund, Links …" />

                {{-- Rubrik, Status und Prioritaet als Boxen – wie die Event-Typen am Ereignis --}}
                <x-adminv2.choice-boxes
                    label="Rubrik"
                    model="categoryId"
                    :options="$this->categories->map(fn ($category) => ['value' => $category->id, 'label' => $category->name])->all()"
                />

                <x-adminv2.choice-boxes
                    label="Status"
                    model="status"
                    :options="collect(AdminTask::statusOptions())->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all()"
                />

                <x-adminv2.choice-boxes
                    label="Priorität"
                    model="priority"
                    :options="collect(AdminTask::priorityOptions())->map(fn ($label, $value) => ['value' => $value, 'label' => $label, 'dot' => AdminTask::priorityDots()[$value]])->values()->all()"
                />

                <div class="grid items-start gap-5 sm:grid-cols-2">
                    <x-adminv2.assignee-select model="responsibleId" label="Verantwortlich" :users="$this->users" :teams="$this->teams" />

                    <x-adminv2.assignee-select
                        model="nextAssigneeId"
                        label="Nächster Bearbeiter"
                        :users="$this->users"
                        :teams="$this->teams"
                        empty-label="Wie verantwortlich" clearable
                        description="Leer: die Aufgabe liegt beim Verantwortlichen."
                    />
                </div>

                <div class="grid items-start gap-5 sm:grid-cols-2">
                    <flux:input wire:model="dueDate" type="date" label="Fällig am" description:trailing="Ist sie dann nicht erledigt, geht eine E-Mail raus." />
                </div>

                {{-- Erinnerungen: beliebig viele, jede fuer eine Person --}}
                <flux:field>
                    <flux:label>Erinnerungen</flux:label>
                    <flux:description>Per E-Mail zum gewählten Zeitpunkt – beliebig viele, auch für verschiedene Personen.</flux:description>

                    <div class="flex flex-col gap-3">
                        @foreach ($reminders as $index => $reminder)
                            <div wire:key="reminder-{{ $reminder['id'] ?? 'new' }}-{{ $index }}" class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                                <div class="grid items-start gap-3 sm:grid-cols-[minmax(0,13rem)_minmax(0,1fr)_auto]">
                                    <div>
                                        <flux:input wire:model="reminders.{{ $index }}.remindAt" type="datetime-local" aria-label="Zeitpunkt der Erinnerung" />
                                        <flux:error name="reminders.{{ $index }}.remindAt" />
                                    </div>

                                    <flux:select wire:model="reminders.{{ $index }}.userId" aria-label="Erinnerung für">
                                        <flux:select.option value="">Wer die Aufgabe dann hat</flux:select.option>
                                        @foreach ($this->users as $user)
                                            <flux:select.option value="{{ $user->id }}">{{ trim($user->name) }}</flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:button variant="ghost" icon="trash" wire:click="removeReminder({{ $index }})" aria-label="Erinnerung entfernen" />
                                </div>

                                <div class="mt-3">
                                    <flux:input wire:model="reminders.{{ $index }}.note" placeholder="Hinweis in der Mail (optional)" maxlength="255" aria-label="Hinweis zur Erinnerung" />
                                </div>

                                @if ($reminder['sentAt'])
                                    <p class="mt-2 flex items-center gap-1 text-xs text-zinc-500">
                                        <flux:icon.check-circle variant="micro" class="text-green-600" />
                                        Verschickt am {{ $reminder['sentAt'] }}. Mit einem neuen Zeitpunkt geht sie erneut raus.
                                    </p>
                                @endif
                            </div>
                        @endforeach

                        <div>
                            <flux:button size="sm" icon="plus" wire:click="addReminder">Erinnerung hinzufügen</flux:button>
                        </div>
                    </div>
                </flux:field>

                @if ($task)
                    <flux:text class="text-xs">
                        Erfasst von {{ trim((string) $task->creator?->name) ?: 'unbekannt' }} am {{ $task->created_at?->format('d.m.Y H:i') }}
                        @if ($task->completed_at)
                            · erledigt am {{ $task->completed_at->format('d.m.Y H:i') }}
                        @endif
                    </flux:text>
                @else
                    <flux:text class="text-xs">Erfasser: {{ trim(auth('web')->user()->name) }}</flux:text>
                @endif

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button type="submit" variant="primary">{{ $task ? 'Speichern' : 'Aufgabe anlegen' }}</flux:button>

                    @if ($task)
                        <flux:button wire:click="toggleDone" icon="{{ $task->isDone() ? 'arrow-path' : 'check' }}">
                            {{ $task->isDone() ? 'Wieder öffnen' : 'Erledigt' }}
                        </flux:button>
                        <flux:spacer />
                        <flux:button variant="ghost" icon="trash" wire:click="delete" wire:confirm="Diese Aufgabe löschen?" class="!text-red-600 dark:!text-red-400">Löschen</flux:button>
                    @endif
                </div>
            </form>
        </x-adminv2.card>

        <x-adminv2.card heading="Notizen und Verlauf" :description="$task ? 'Wer wann was notiert oder geändert hat – neueste zuerst.' : null">
            @if ($task)
                <div class="flex flex-col gap-5">
            <form wire:submit="addNote" class="flex flex-col gap-2">
                    <flux:textarea wire:model="note" rows="2" placeholder="Notiz hinzufügen …" aria-label="Notiz" />
                    <flux:error name="note" />
                    <div>
                        <flux:button type="submit" size="sm" icon="plus">Notiz hinzufügen</flux:button>
                    </div>
                </form>

                <ol class="flex flex-col gap-4">
                    @foreach ($this->timeline as $entry)
                        <li wire:key="activity-{{ $entry->id }}" class="flex gap-3">
                            <span @class([
                                'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full',
                                'bg-[var(--color-accent)]/10 text-[var(--color-accent)]' => $entry->type === AdminTaskActivity::TYPE_NOTE,
                                'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300' => $entry->type !== AdminTaskActivity::TYPE_NOTE,
                            ])>
                                <flux:icon :icon="match ($entry->type) {
                                    AdminTaskActivity::TYPE_NOTE => 'chat-bubble-left-ellipsis',
                                    AdminTaskActivity::TYPE_CREATED => 'plus',
                                    default => 'pencil-square',
                                }" variant="micro" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline gap-x-2 text-sm">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ trim((string) $entry->user?->name) ?: 'System' }}</span>
                                    <span class="text-xs text-zinc-500 tabular-nums">{{ $entry->created_at?->format('d.m.Y H:i') }}</span>
                                </div>

                                @if ($entry->type === AdminTaskActivity::TYPE_NOTE)
                                    <p class="mt-1 text-sm break-words whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $entry->body }}</p>
                                @elseif ($entry->type === AdminTaskActivity::TYPE_CREATED)
                                    <p class="mt-0.5 text-sm text-zinc-500">hat die Aufgabe angelegt.</p>
                                @else
                                    <ul class="mt-1 flex flex-col gap-0.5 text-sm text-zinc-600 dark:text-zinc-400">
                                        @foreach ($entry->changes ?? [] as $change)
                                            <li class="break-words">
                                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $change['label'] }}:</span>
                                                @if ($change['field'] === 'description')
                                                    geändert
                                                @else
                                                    <span class="text-zinc-400 line-through">{{ $change['old'] ?? '–' }}</span>
                                                    <span aria-hidden="true">→</span>
                                                    <span>{{ $change['new'] ?? '–' }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
                </div>
            @else
                <p class="text-sm text-zinc-500">Notizen und der Verlauf der Änderungen erscheinen hier, sobald die Aufgabe angelegt ist.</p>
            @endif
        </x-adminv2.card>
    </div>
</div>
