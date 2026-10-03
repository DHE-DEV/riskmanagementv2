@php
    use App\Models\AdminTask;
    use Illuminate\Support\Carbon;

    $statusOptions = $this->statusOptions();
    $priorityOptions = AdminTask::priorityOptions();
    $priorityDots = AdminTask::priorityDots();
    $dueOptions = $this->dueOptions();
    $roleOptions = $this->personRoleOptions();
    $subjectOptions = $this->subjectOptions();
    $sortOptions = $this->sortOptions();

    $formatDate = fn (string $date) => rescue(fn () => Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y'), $date, false);

    // Die aktiven Filter als Badges: [Gruppe, Wert, Filter, zu entfernender Wert].
    $badges = [];

    if ($search !== '') {
        $badges[] = ['Suche', '„'.$search.'“', 'search', null];
    }
    if ($status !== 'open' && isset($statusOptions[$status])) {
        $badges[] = ['Status', $statusOptions[$status], 'status', null];
    }
    foreach ($priorities as $value) {
        if (isset($priorityOptions[$value])) {
            $badges[] = ['Priorität', $priorityOptions[$value], 'priorities', $value];
        }
    }
    foreach ($categories as $value) {
        if ($categoryOption = $this->categoryOptions->firstWhere('id', (int) $value)) {
            $badges[] = ['Rubrik', $categoryOption->name, 'categories', $value];
        }
    }
    if (isset($dueOptions[$due])) {
        $badges[] = ['Fälligkeit', $dueOptions[$due], 'due', null];
    }
    if ($dueFrom !== '' || $dueTo !== '') {
        $badges[] = ['Fällig', match (true) {
            $dueFrom !== '' && $dueTo !== '' => $formatDate($dueFrom).' – '.$formatDate($dueTo),
            $dueFrom !== '' => 'ab '.$formatDate($dueFrom),
            default => 'bis '.$formatDate($dueTo),
        }, 'period', null];
    }
    foreach ($persons as $value) {
        if ($user = $this->users->firstWhere('id', (int) $value)) {
            $badges[] = [$roleOptions[$personRole] ?? 'Person', trim($user->name), 'persons', $value];
        }
    }
    foreach ($teams as $value) {
        if ($teamOption = $this->teamOptions->firstWhere('id', (int) $value)) {
            $badges[] = ['Team', $teamOption->name, 'teams', $value];
        }
    }
    if (isset($subjectOptions[$subject])) {
        $badges[] = ['Bezug', $subjectOptions[$subject], 'subject', null];
    }

    $hasFilters = $badges !== [];
    // Die Suche steht immer sichtbar in der Leiste und zaehlt nicht zu den Filtern im Bereich.
    $panelFilterCount = count($badges) - ($search !== '' ? 1 : 0);

    $chip = 'inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-700 transition select-none hover:border-zinc-300 '
        .'has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)] has-[:checked]:text-[var(--color-accent-foreground)] '
        .'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[var(--color-accent)]/40 '
        .'dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-600';
    $groupLabel = 'mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400';
@endphp

{{-- Aufgaben werden in einem eigenen Tab bearbeitet – beim Zurueckkehren die Liste neu laden. --}}
<div class="flex flex-col gap-6" x-data x-on:visibilitychange.document="document.visibilityState === 'visible' && $wire.refresh()">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Aufgaben</flux:heading>
            <flux:subheading>Für alle Mitarbeiter: eigene Aufgaben und Aufgaben für andere.</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:modal.trigger name="task-categories">
                <flux:button icon="adjustments-horizontal">Rubriken</flux:button>
            </flux:modal.trigger>
            <flux:button variant="primary" icon="plus" :href="route('adminv2.tasks.create')" target="_blank">Neue Aufgabe</flux:button>
        </div>
    </div>

    {{-- Reiter: Zahl = Treffer mit den gewaehlten Filtern --}}
    <div class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1">
        @foreach ($this->tabs() as $key => $label)
            <button
                type="button"
                wire:click="$set('tab', '{{ $key }}')"
                wire:key="tab-{{ $key }}"
                @class([
                    'inline-flex shrink-0 items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium transition',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $tab === $key,
                    'text-zinc-600 hover:bg-zinc-200/60 dark:text-zinc-400 dark:hover:bg-zinc-800' => $tab !== $key,
                ])
            >
                {{ $label }}
                <span @class([
                    'rounded-full px-1.5 text-xs tabular-nums',
                    'bg-white/20 dark:bg-zinc-900/10' => $tab === $key,
                    'bg-zinc-200 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => $tab !== $key,
                ])>{{ number_format($this->tabCounts[$key] ?? 0, 0, ',', '.') }}</span>
            </button>
        @endforeach
    </div>

    {{-- Suche und Filter. Ob der Filterbereich offen ist, merkt sich der Browser. --}}
    <section
        class="rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-950"
        x-data="{ filtersOpen: $persist(false).as('adminv2-tasks-filters-open') }"
    >
        <div class="flex flex-wrap items-center gap-3 p-4">
            <div class="min-w-64 flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Aufgabe suchen …"
                    clearable
                />
            </div>

            <button
                type="button"
                x-on:click="filtersOpen = ! filtersOpen"
                :aria-expanded="filtersOpen"
                aria-controls="tasks-filter-panel"
                class="inline-flex h-10 items-center gap-2 rounded-lg border px-3.5 text-sm font-medium shadow-xs transition"
                :class="filtersOpen
                    ? 'border-[var(--color-accent)] bg-[var(--color-accent)] text-[var(--color-accent-foreground)]'
                    : 'border-zinc-200 border-b-zinc-300/80 bg-white text-zinc-800 hover:bg-zinc-50 dark:border-white/10 dark:bg-white/10 dark:text-white'"
            >
                <flux:icon.funnel variant="mini" />
                Filter
                @if ($panelFilterCount > 0)
                    <span
                        class="rounded-full px-1.5 text-xs tabular-nums"
                        :class="filtersOpen ? 'bg-white/20' : 'bg-[var(--color-accent)] text-[var(--color-accent-foreground)]'"
                    >{{ $panelFilterCount }}</span>
                @endif
                <flux:icon.chevron-down variant="micro" class="transition-transform" ::class="filtersOpen && 'rotate-180'" />
            </button>
        </div>

        {{-- Aufklappbarer Filterbereich: die Filter stehen nebeneinander --}}
        <div id="tasks-filter-panel" x-show="filtersOpen" x-collapse x-cloak>
            <div class="flex flex-wrap gap-x-12 gap-y-6 border-t border-zinc-100 bg-zinc-50/60 px-5 py-5 dark:border-zinc-800 dark:bg-zinc-900/40 [&>div]:max-w-full">
                <div>
                    <div class="{{ $groupLabel }}">Status</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($statusOptions as $value => $label)
                            <label wire:key="status-chip-{{ $value }}" class="{{ $chip }}">
                                <input type="radio" wire:model.live="status" value="{{ $value }}" class="sr-only" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="{{ $groupLabel }}">Priorität</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach (array_reverse($priorityOptions, true) as $value => $label)
                            <label wire:key="priority-chip-{{ $value }}" class="{{ $chip }}">
                                <input type="checkbox" wire:model.live="priorities" value="{{ $value }}" class="sr-only" />
                                <span class="size-2 rounded-full {{ $priorityDots[$value] }}"></span>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="{{ $groupLabel }}">Rubrik</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->categoryOptions as $categoryOption)
                            <label wire:key="category-chip-{{ $categoryOption->id }}" class="{{ $chip }}">
                                <input type="checkbox" wire:model.live="categories" value="{{ $categoryOption->id }}" class="sr-only" />
                                {{ $categoryOption->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="{{ $groupLabel }}">Fälligkeit</div>
                    <div class="flex flex-wrap gap-2">
                        <label class="{{ $chip }}">
                            <input type="radio" wire:model.live="due" value="" class="sr-only" />
                            Alle
                        </label>
                        @foreach ($dueOptions as $value => $label)
                            <label wire:key="due-chip-{{ $value }}" class="{{ $chip }}">
                                <input type="radio" wire:model.live="due" value="{{ $value }}" class="sr-only" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="{{ $groupLabel }}">Fällig im Zeitfenster</div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="w-40">
                            <flux:input wire:model.live="dueFrom" type="date" aria-label="Fällig ab" />
                        </div>
                        <span class="text-sm text-zinc-500">bis</span>
                        <div class="w-40">
                            <flux:input wire:model.live="dueTo" type="date" aria-label="Fällig bis" />
                        </div>
                    </div>
                </div>

                <div>
                    <div class="{{ $groupLabel }}">Person</div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-adminv2.multi-select
                            class="w-64 max-w-full"
                            model="persons"
                            :selected="$persons"
                            :options="$this->users->map(fn ($user) => ['value' => $user->id, 'label' => trim($user->name)])->all()"
                            all-label="Alle Personen"
                            noun="Personen"
                            label="Person"
                            searchable
                            search-placeholder="Name suchen"
                        />

                        <label class="{{ $chip }}">
                            <input type="radio" wire:model.live="personRole" value="" class="sr-only" />
                            Beteiligt
                        </label>
                        @foreach ($roleOptions as $value => $label)
                            <label wire:key="role-chip-{{ $value }}" class="{{ $chip }}">
                                <input type="radio" wire:model.live="personRole" value="{{ $value }}" class="sr-only" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                @if ($this->teamOptions->isNotEmpty())
                    <div>
                        <div class="{{ $groupLabel }}">Team</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->teamOptions as $teamOption)
                                <label wire:key="team-chip-{{ $teamOption->id }}" class="{{ $chip }}">
                                    <input type="checkbox" wire:model.live="teams" value="{{ $teamOption->id }}" class="sr-only" />
                                    <flux:icon.user-group variant="micro" class="opacity-70" />
                                    {{ $teamOption->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div>
                    <div class="{{ $groupLabel }}">Bezug</div>
                    <div class="flex flex-wrap gap-2">
                        <label class="{{ $chip }}">
                            <input type="radio" wire:model.live="subject" value="" class="sr-only" />
                            Alle
                        </label>
                        @foreach ($subjectOptions as $value => $label)
                            <label wire:key="subject-chip-{{ $value }}" class="{{ $chip }}">
                                <input type="radio" wire:model.live="subject" value="{{ $value }}" class="sr-only" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Aktive Filter als Badges unter dem Filterbereich --}}
    @if ($hasFilters)
        <div class="-mt-2 flex flex-wrap items-center gap-2" aria-label="Aktive Filter">
            <span class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                <flux:icon.funnel variant="micro" /> Gefiltert nach
            </span>

            @foreach ($badges as [$group, $text, $filter, $value])
                <span
                    wire:key="badge-{{ $filter }}-{{ $value ?? 'x' }}"
                    class="inline-flex items-center gap-1.5 rounded-full border border-[var(--color-accent)]/25 bg-[var(--color-accent)]/10 py-1 ps-3 pe-1 text-sm font-medium text-zinc-900 dark:text-white"
                >
                    <span class="font-normal text-zinc-500 dark:text-zinc-400">{{ $group }}</span>
                    {{ $text }}
                    <button
                        type="button"
                        wire:click="removeFilter('{{ $filter }}', @js($value))"
                        class="rounded-full p-0.5 text-zinc-500 hover:bg-zinc-900/10 hover:text-zinc-900 dark:hover:bg-white/10 dark:hover:text-white"
                        aria-label="Filter {{ $group }} {{ $text }} entfernen"
                    >
                        <flux:icon.x-mark variant="micro" />
                    </button>
                </span>
            @endforeach

            <button type="button" wire:click="resetFilters" class="ms-1 text-sm font-medium text-zinc-500 underline decoration-zinc-300 underline-offset-2 hover:text-zinc-900 dark:hover:text-white">
                Alle zurücksetzen
            </button>
        </div>
    @endif

    {{-- Sortierung: links ueber der Liste, getrennt vom Filterbereich --}}
    <div class="-mb-2 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-sm text-zinc-600 dark:text-zinc-400">Sortieren nach</span>
            <div class="w-48">
                <flux:select wire:model.live="sort" aria-label="Sortierung" size="sm">
                    @foreach ($sortOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:button
                variant="ghost"
                size="sm"
                :icon="$direction === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'"
                wire:click="toggleDirection"
                :aria-label="$direction === 'asc' ? 'Aufsteigend sortiert – umkehren' : 'Absteigend sortiert – umkehren'"
            >
                {{ $direction === 'asc' ? 'Aufsteigend' : 'Absteigend' }}
            </flux:button>
        </div>

        <span class="text-sm text-zinc-500 tabular-nums">
            {{ number_format($tasks->total(), 0, ',', '.') }} {{ $tasks->total() === 1 ? 'Aufgabe' : 'Aufgaben' }}
        </span>
    </div>

    {{-- Ergebnisse als Karten --}}
    <div
        class="grid gap-4 lg:grid-cols-2"
        wire:loading.class="opacity-60"
        wire:target="tab, search, status, priorities, categories, due, dueFrom, dueTo, persons, personRole, teams, subject, sort, toggleDirection, removeFilter, resetFilters, toggleDone, gotoPage, nextPage, previousPage"
    >
        @forelse ($tasks as $task)
            @php
                $dueSoon = ! $task->isDone() && $task->due_date && ! $task->isOverdue() && $task->due_date->lte(today()->addDays(2));
            @endphp
            <article
                wire:key="task-{{ $task->id }}"
                @class([
                    'group relative flex flex-col rounded-2xl border border-s-4 border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                    'border-s-green-500' => $task->isDone(),
                    'border-s-red-500' => $task->isOverdue(),
                    'border-s-amber-500' => $dueSoon,
                    'border-s-sky-500' => ! $task->isDone() && ! $task->isOverdue() && ! $dueSoon && $task->status === AdminTask::STATUS_IN_PROGRESS,
                    'border-s-zinc-300 dark:border-s-zinc-600' => ! $task->isDone() && ! $task->isOverdue() && ! $dueSoon && $task->status !== AdminTask::STATUS_IN_PROGRESS,
                ])
            >
                {{-- Ueberschrift zuerst, daneben der Erledigt-Schalter --}}
                <div class="flex items-start justify-between gap-3">
                    <h2 class="min-w-0 text-base font-semibold leading-snug">
                        {{-- Die ganze Karte oeffnet die Aufgabe in einem neuen Tab; nur der Schalter liegt darueber. --}}
                        <a
                            href="{{ route('adminv2.tasks.show', $task) }}"
                            target="_blank"
                            @class([
                                'line-clamp-2 after:absolute after:inset-0 after:rounded-2xl group-hover:underline',
                                'text-zinc-900 dark:text-white' => ! $task->isDone(),
                                'text-zinc-400 line-through' => $task->isDone(),
                            ])
                        >{{ $task->title }}</a>
                    </h2>

                    <button
                        type="button"
                        wire:click="toggleDone({{ $task->id }})"
                        title="{{ $task->isDone() ? 'Wieder öffnen' : 'Als erledigt markieren' }}"
                        aria-label="{{ $task->isDone() ? 'Wieder öffnen' : 'Als erledigt markieren' }}"
                        @class([
                            'relative z-10 flex size-6 shrink-0 items-center justify-center rounded-full border transition',
                            'border-green-600 bg-green-600 text-white' => $task->isDone(),
                            'border-zinc-300 text-transparent hover:border-green-600 hover:text-green-600 dark:border-zinc-600' => ! $task->isDone(),
                        ])
                    >
                        <flux:icon.check variant="micro" />
                    </button>
                </div>

                {{-- Darunter die Badges: Status, Rubrik und Merkmale --}}
                <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1.5">
                    <flux:badge
                        size="sm"
                        inset="top bottom"
                        :color="['open' => 'zinc', 'in_progress' => 'sky', 'done' => 'green'][$task->status] ?? 'zinc'"
                    >{{ AdminTask::statusOptions()[$task->status] ?? $task->status }}</flux:badge>

                    <span class="inline-flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-300">
                        <span class="size-2 rounded-full {{ $priorityDots[$task->priority] ?? 'bg-zinc-400' }}"></span>
                        {{ $priorityOptions[$task->priority] ?? $task->priority }}
                    </span>

                    @if ($task->category)
                        <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $task->category->name }}</span>
                    @endif
                    @if ($task->isOverdue())
                        <span class="rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-400/10 dark:text-red-300">überfällig</span>
                    @endif
                    @if ($task->recurrence_id)
                        <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            <flux:icon.arrow-path variant="micro" /> wiederkehrend
                        </span>
                    @endif
                    @if (! $task->isDone() && ($nextReminder = $task->nextReminder()))
                        @php $moreReminders = $task->reminders->reject->isSent()->count() - 1; @endphp
                        <span
                            @class([
                                'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs',
                                'bg-amber-50 font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-300' => $task->isReminderDue(),
                                'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => ! $task->isReminderDue(),
                            ])
                        >
                            <flux:icon.bell variant="micro" /> Erinnerung {{ $nextReminder->remind_at->format('d.m.Y H:i') }}{{ $moreReminders > 0 ? ' (+'.$moreReminders.')' : '' }}
                        </span>
                    @endif
                </div>

                {{-- Faelligkeit, Bezug und Personen jeweils in einer eigenen Zeile --}}
                <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                    <div class="flex items-center gap-2">
                        <dt class="shrink-0"><flux:icon.calendar variant="mini" class="text-zinc-400" /><span class="sr-only">Fällig</span></dt>
                        <dd class="tabular-nums">
                            @if ($task->due_date)
                                <span @class(['font-medium text-red-600 dark:text-red-400' => $task->isOverdue()])>Fällig {{ $task->due_date->format('d.m.Y') }}</span>
                            @else
                                Ohne Fälligkeit
                            @endif
                        </dd>
                    </div>
                    <div class="flex min-w-0 items-start gap-2">
                        <dt class="mt-0.5 shrink-0"><flux:icon.user variant="mini" class="text-zinc-400" /><span class="sr-only">Personen</span></dt>
                        <dd class="min-w-0">
                            <span class="text-zinc-900 dark:text-white">Liegt bei {{ $task->handlerLabel() ?? '–' }}</span>
                            <span class="text-zinc-400">·</span>
                            verantwortlich {{ $task->responsibleLabel() ?? '–' }}
                        </dd>
                    </div>
                    @if ($subjectLabel = $task->subjectLabel())
                        <div class="flex min-w-0 items-start gap-2">
                            <dt class="mt-0.5 shrink-0"><flux:icon.link variant="mini" class="text-zinc-400" /><span class="sr-only">Bezug</span></dt>
                            <dd class="min-w-0"><span class="line-clamp-1">{{ $subjectLabel }}</span></dd>
                        </div>
                    @endif
                    @if ($task->parent)
                        <div class="flex min-w-0 items-start gap-2">
                            <dt class="mt-0.5 shrink-0"><flux:icon.arrow-turn-down-right variant="mini" class="text-zinc-400" /><span class="sr-only">Hauptaufgabe</span></dt>
                            <dd class="min-w-0">
                                <span class="line-clamp-1">Unteraufgabe von <a href="{{ route('adminv2.tasks.show', $task->parent) }}" target="_blank" class="text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $task->parent->title }}</a></span>
                            </dd>
                        </div>
                    @endif
                    @if ($task->subtasks_count > 0)
                        @php [$subtasksDone, $subtasksTotal] = $task->subtaskProgress(); @endphp
                        <div class="flex min-w-0 items-center gap-2">
                            <dt class="shrink-0"><flux:icon.list-bullet variant="mini" class="text-zinc-400" /><span class="sr-only">Unteraufgaben</span></dt>
                            <dd class="flex min-w-0 flex-1 items-center gap-2">
                                <span class="shrink-0 tabular-nums">{{ $subtasksDone }}/{{ $subtasksTotal }} Unteraufgaben</span>
                                <span class="h-1.5 w-24 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <span class="block h-full rounded-full bg-green-500" style="width: {{ round($subtasksDone / $subtasksTotal * 100) }}%"></span>
                                </span>
                            </dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-auto flex items-center justify-between gap-3 pt-4 text-xs text-zinc-500">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        <span>erfasst von {{ trim((string) $task->creator?->name) ?: '–' }}</span>
                        <span @class(['inline-flex items-center gap-1 tabular-nums', 'font-medium text-zinc-900 dark:text-white' => $task->notes_count > 0])>
                            <flux:icon.chat-bubble-left-ellipsis variant="micro" />
                            {{ $task->notes_count }} {{ $task->notes_count === 1 ? 'Notiz' : 'Notizen' }}
                        </span>
                    </div>
                    <span class="tabular-nums">geändert {{ $task->updated_at?->format('d.m.Y') }}</span>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 px-5 py-16 text-center dark:border-zinc-700">
                <div class="mx-auto flex max-w-sm flex-col items-center gap-2">
                    <flux:icon.clipboard-document-check class="size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine Aufgaben</p>
                    <p class="text-sm text-zinc-500">
                        @if ($hasFilters)
                            Für diese Filter gibt es im Reiter „{{ $this->tabs()[$tab] ?? 'Alle' }}“ keine Treffer.
                        @else
                            Im Reiter „{{ $this->tabs()[$tab] ?? 'Alle' }}“ ist nichts offen.
                        @endif
                    </p>
                    @if ($hasFilters)
                        <flux:button size="sm" variant="ghost" wire:click="resetFilters" class="mt-1">Filter zurücksetzen</flux:button>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    @if ($tasks->total() > 0)
        <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-500">
            <span>{{ $tasks->firstItem() }}–{{ $tasks->lastItem() }} von {{ number_format($tasks->total(), 0, ',', '.') }}</span>

            @if ($tasks->hasPages())
                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previousPage" :disabled="$tasks->onFirstPage()">Zurück</flux:button>
                    <span class="tabular-nums">Seite {{ $tasks->currentPage() }} von {{ $tasks->lastPage() }}</span>
                    <flux:button size="sm" variant="ghost" icon-trailing="chevron-right" wire:click="nextPage" :disabled="! $tasks->hasMorePages()">Weiter</flux:button>
                </div>
            @endif
        </div>
    @endif

    {{-- Rubriken verwalten --}}
    <flux:modal name="task-categories" class="md:w-[30rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Rubriken</flux:heading>
                <flux:text class="mt-1">Ausgeblendete Rubriken stehen für neue Aufgaben nicht mehr zur Wahl; bestehende Aufgaben behalten sie.</flux:text>
            </div>

            <ul class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-700">
                @foreach ($this->categoryOptions as $categoryOption)
                    <li wire:key="category-{{ $categoryOption->id }}" class="flex items-center justify-between gap-3 py-2">
                        <span @class(['text-sm', 'text-zinc-900 dark:text-white' => $categoryOption->is_active, 'text-zinc-400 line-through' => ! $categoryOption->is_active])>
                            {{ $categoryOption->name }}
                        </span>
                        <flux:button size="xs" variant="ghost" wire:click="toggleCategory({{ $categoryOption->id }})">
                            {{ $categoryOption->is_active ? 'Ausblenden' : 'Einblenden' }}
                        </flux:button>
                    </li>
                @endforeach
            </ul>

            <form wire:submit="addCategory" class="flex items-start gap-2">
                <div class="flex-1">
                    <flux:input wire:model="newCategory" placeholder="Neue Rubrik" aria-label="Neue Rubrik" />
                    <flux:error name="newCategory" />
                </div>
                <flux:button type="submit" icon="plus">Hinzufügen</flux:button>
            </form>
        </div>
    </flux:modal>

</div>
