@php
    $open = $this->tasks->reject->isDone();
    // Neue Aufgabe mit Bezug: gespeichertes Ereignis ueber die ID, sonst ueber das Kennzeichen.
    $createUrl = route('adminv2.tasks.create', array_filter([
        'event' => $eventId,
        'token' => $eventId ? null : $token,
        'category' => 'Global Travel Monitor',
    ]));
@endphp

@php
    $total = $this->tasks->count();
    $summary = $total === 0
        ? 'Noch keine Aufgaben zu diesem Ereignis.'
        : $total.' '.($total === 1 ? 'Aufgabe' : 'Aufgaben').', '.$open->count().' offen';
@endphp

{{-- Aufgaben werden in einem eigenen Tab bearbeitet – beim Zurueckkehren neu laden. --}}
<div x-data x-on:visibilitychange.document="document.visibilityState === 'visible' && $wire.refresh()">
<x-adminv2.card heading="Aufgaben" :description="$summary">
    <x-slot:actions>
        <flux:button size="sm" icon="plus" :href="$createUrl" target="_blank">Aufgabe</flux:button>
    </x-slot:actions>

    @if ($this->tasks->isEmpty())
        <p class="text-sm text-zinc-500">
            Hier lassen sich Aufgaben zu diesem Ereignis festhalten – für dich oder für Kolleginnen und Kollegen.
        </p>
    @else
        <ul class="-my-1 flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach ($this->tasks as $task)
                <li wire:key="panel-task-{{ $task->id }}" class="flex items-start gap-3 py-2.5">
                    <button
                        type="button"
                        wire:click="toggleDone({{ $task->id }})"
                        title="{{ $task->isDone() ? 'Wieder öffnen' : 'Als erledigt markieren' }}"
                        @class([
                            'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border transition',
                            'border-green-600 bg-green-600 text-white' => $task->isDone(),
                            'border-zinc-300 text-transparent hover:border-green-600 hover:text-green-600 dark:border-zinc-600' => ! $task->isDone(),
                        ])
                    >
                        <flux:icon.check variant="micro" />
                    </button>

                    <a href="{{ route('adminv2.tasks.show', $task) }}" target="_blank" class="block min-w-0 flex-1">
                        <span @class([
                            'block text-sm font-medium break-words',
                            'text-zinc-900 hover:underline dark:text-white' => ! $task->isDone(),
                            'text-zinc-400 line-through' => $task->isDone(),
                        ])>{{ $task->title }}</span>

                        <span class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-zinc-500">
                            @if (in_array($task->priority, [\App\Models\AdminTask::PRIORITY_HIGH, \App\Models\AdminTask::PRIORITY_URGENT], true) && ! $task->isDone())
                                <span class="inline-flex items-center gap-1 font-medium text-zinc-700 dark:text-zinc-300">
                                    <span class="size-1.5 rounded-full {{ \App\Models\AdminTask::priorityDots()[$task->priority] }}"></span>
                                    {{ \App\Models\AdminTask::priorityOptions()[$task->priority] }}
                                </span>
                            @endif
                            <span>{{ $task->handlerLabel() ?? 'Niemand' }}</span>
                            @if ($task->due_date)
                                <span @class(['tabular-nums', 'font-medium text-red-600 dark:text-red-400' => $task->isOverdue()])>
                                    fällig {{ $task->due_date->format('d.m.Y') }}
                                </span>
                            @endif
                            @if (! $task->isDone() && ($nextReminder = $task->nextReminder()))
                                <span @class(['inline-flex items-center gap-1', 'font-medium text-amber-600 dark:text-amber-400' => $task->isReminderDue()])>
                                    <flux:icon.bell variant="micro" /> {{ $nextReminder->remind_at->format('d.m. H:i') }}
                                </span>
                            @endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-adminv2.card>
</div>
