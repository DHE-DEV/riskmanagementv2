@php
    $states = [
        'active' => ['Aktiv', 'green'],
        'paused' => ['Pausiert', 'amber'],
        'finished' => ['Beendet', 'zinc'],
    ];
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Wiederkehrende Aufgaben</flux:heading>
            <flux:subheading>System · Vorlagen mit Rhythmus. Zum jeweiligen Termin wird daraus automatisch eine Aufgabe angelegt.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('adminv2.system.recurring-tasks.create')">Neue wiederkehrende Aufgabe</flux:button>
    </div>

    <div class="grid gap-4 lg:grid-cols-2" wire:loading.class="opacity-60" wire:target="toggleActive, createNow, delete">
        @forelse ($this->recurrences as $recurrence)
            @php
                $state = $recurrence->state();
                [$stateLabel, $stateColor] = $states[$state];
            @endphp
            <article
                wire:key="recurrence-{{ $recurrence->id }}"
                @class([
                    'group relative flex flex-col rounded-2xl border border-s-4 border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                    'border-s-green-500' => $state === 'active',
                    'border-s-amber-500' => $state === 'paused',
                    'border-s-zinc-300 dark:border-s-zinc-600' => $state === 'finished',
                ])
            >
                <div class="flex items-start justify-between gap-3">
                    <h2 class="min-w-0 text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                        <a href="{{ route('adminv2.system.recurring-tasks.edit', $recurrence) }}" class="line-clamp-2 after:absolute after:inset-0 after:rounded-2xl group-hover:underline">
                            {{ $recurrence->title }}
                        </a>
                    </h2>

                    <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                        <flux:dropdown align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen" />

                            <flux:menu>
                                <flux:menu.item icon="pencil-square" :href="route('adminv2.system.recurring-tasks.edit', $recurrence)">Bearbeiten</flux:menu.item>
                                <flux:menu.item icon="bolt" wire:click="createNow({{ $recurrence->id }})" wire:confirm="Jetzt außer der Reihe eine Aufgabe anlegen? Der Rhythmus bleibt unverändert.">Jetzt eine Aufgabe anlegen</flux:menu.item>
                                @if ($state !== 'finished')
                                    <flux:menu.item :icon="$recurrence->is_active ? 'pause' : 'play'" wire:click="toggleActive({{ $recurrence->id }})">
                                        {{ $recurrence->is_active ? 'Pausieren' : 'Fortsetzen' }}
                                    </flux:menu.item>
                                @endif
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $recurrence->id }})" wire:confirm="Diese wiederkehrende Aufgabe löschen? Bereits angelegte Aufgaben bleiben erhalten.">Löschen</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>

                <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1.5">
                    <flux:badge size="sm" inset="top bottom" :color="$stateColor">{{ $stateLabel }}</flux:badge>
                    @if ($recurrence->category)
                        <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $recurrence->category->name }}</span>
                    @endif
                    @if ($recurrence->skip_if_open)
                        <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">nur wenn vorige erledigt</span>
                    @endif
                </div>

                <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                    <div class="flex items-start gap-2">
                        <dt class="mt-0.5 shrink-0"><flux:icon.arrow-path variant="mini" class="text-zinc-400" /><span class="sr-only">Rhythmus</span></dt>
                        <dd>{{ $recurrence->summary() }}</dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <dt class="shrink-0"><flux:icon.calendar variant="mini" class="text-zinc-400" /><span class="sr-only">Nächster Termin</span></dt>
                        <dd class="tabular-nums">
                            @if ($recurrence->next_run_at)
                                <span class="font-medium text-zinc-900 dark:text-white">Nächste Aufgabe {{ $recurrence->next_run_at->format('d.m.Y H:i') }}</span>
                            @elseif ($state === 'paused')
                                Pausiert – es wird nichts angelegt
                            @else
                                Kein weiterer Termin
                            @endif
                        </dd>
                    </div>
                    <div class="flex min-w-0 items-start gap-2">
                        <dt class="mt-0.5 shrink-0"><flux:icon.user variant="mini" class="text-zinc-400" /><span class="sr-only">Personen</span></dt>
                        <dd class="min-w-0">
                            Verantwortlich {{ $recurrence->responsibleLabel() ?? '–' }}
                            @if ($nextLabel = $recurrence->nextAssigneeLabel())
                                <span class="text-zinc-400">·</span> liegt bei {{ $nextLabel }}
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($recurrence->last_error)
                    <p class="mt-3 rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">{{ $recurrence->last_error }}</p>
                @endif

                <div class="mt-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pt-4 text-xs text-zinc-500">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 tabular-nums">
                        <span>
                            {{ $recurrence->occurrences_count }}{{ $recurrence->max_occurrences ? ' von '.$recurrence->max_occurrences : '' }}
                            {{ $recurrence->occurrences_count === 1 && ! $recurrence->max_occurrences ? 'Aufgabe' : 'Aufgaben' }} angelegt
                        </span>
                        <span @class(['font-medium text-zinc-900 dark:text-white' => $recurrence->open_tasks_count > 0])>{{ $recurrence->open_tasks_count }} offen</span>
                        @if ($recurrence->last_skipped_at)
                            <span title="Die vorige Aufgabe war noch offen">übersprungen {{ $recurrence->last_skipped_at->format('d.m.Y') }}</span>
                        @endif
                    </div>
                    <span class="tabular-nums">
                        {{ $recurrence->last_run_at ? 'zuletzt '.$recurrence->last_run_at->format('d.m.Y H:i') : 'noch nichts angelegt' }}
                    </span>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 px-5 py-16 text-center dark:border-zinc-700">
                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                    <flux:icon.arrow-path class="size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Noch keine wiederkehrenden Aufgaben</p>
                    <p class="text-sm text-zinc-500">
                        Lege fest, was regelmäßig zu tun ist – etwa „Jeden Montag die Quellen prüfen“. Die Aufgabe erscheint dann von selbst in der Aufgabenliste.
                    </p>
                    <flux:button size="sm" icon="plus" :href="route('adminv2.system.recurring-tasks.create')" class="mt-1">Neue wiederkehrende Aufgabe</flux:button>
                </div>
            </div>
        @endforelse
    </div>
</div>
