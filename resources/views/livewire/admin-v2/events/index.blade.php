@php
    use App\Models\CustomEvent;
    use App\Support\AdminV2\EventState;
    use Illuminate\Support\Carbon;

    $priorityOptions = CustomEvent::getPriorityOptions();
    $priorityDots = ['high' => 'bg-red-500', 'medium' => 'bg-amber-500', 'low' => 'bg-sky-500', 'info' => 'bg-zinc-400'];
    $priorityEdges = ['high' => 'border-s-red-500', 'medium' => 'border-s-amber-500', 'low' => 'border-s-sky-500', 'info' => 'border-s-zinc-300 dark:border-s-zinc-600'];
    $scopeOptions = ['nationwide' => 'Landesweit', 'local' => 'Standortbezogen', 'none' => 'Ohne Standort'];
    $sortOptions = ['start_date' => 'Zeitraum', 'title' => 'Titel', 'updated_at' => 'Zuletzt geändert', 'clicks_count' => 'Klicks'];

    $formatDate = fn (string $date) => rescue(fn () => Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y'), $date, false);

    // Die aktiven Filter als Badges: [Gruppe, Wert, Filter, zu entfernender Wert].
    $badges = [];

    if ($search !== '') {
        $badges[] = ['Suche', '„'.$search.'“', 'search', null];
    }
    if (isset($scopeOptions[$scope])) {
        $badges[] = ['Geltung', $scopeOptions[$scope], 'scope', null];
    }
    foreach ($priorities as $value) {
        if (isset($priorityOptions[$value])) {
            $badges[] = ['Priorität', $priorityOptions[$value], 'priorities', $value];
        }
    }
    foreach ($types as $value) {
        if ($eventType = $this->eventTypes->firstWhere('id', (int) $value)) {
            $badges[] = ['Typ', $eventType->name, 'types', $value];
        }
    }
    foreach ($countryIds as $value) {
        if ($countryOption = $this->countryOptions->firstWhere('id', (int) $value)) {
            $badges[] = ['Land', $countryOption->getName('de'), 'countryIds', $value];
        }
    }
    if ($periodFrom !== '' || $periodTo !== '') {
        $badges[] = ['Zeitfenster', match (true) {
            $periodFrom !== '' && $periodTo !== '' => $formatDate($periodFrom).' – '.$formatDate($periodTo),
            $periodFrom !== '' => 'ab '.$formatDate($periodFrom),
            default => 'bis '.$formatDate($periodTo),
        }, 'period', null];
    }
    if (isset($this->taskFilterOptions()[$taskFilter])) {
        $badges[] = ['Aufgaben', $this->taskFilterOptions()[$taskFilter], 'taskFilter', null];
    }
    if ($taskPerson !== '' && ($employee = $this->employees->firstWhere('id', (int) $taskPerson))) {
        $badges[] = ['Offene Aufgaben bei', trim($employee->name), 'taskPerson', null];
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

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Passolution Ereignisse</flux:heading>
            <flux:subheading>Ereignisse für den Global Travel Monitor und Travel Alert.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('adminv2.events.create')">Neues Ereignis</flux:button>
    </div>

    {{-- Reiter nach Zustand --}}
    <div class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1">
        @foreach ($this->tabs() as $key => $label)
            @php $count = $this->tabCounts[$key] ?? 0; @endphp
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
                    'bg-zinc-200 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => $tab !== $key && ! ($key === 'pending' && $count > 0),
                    'bg-amber-100 text-amber-800 dark:bg-amber-400/20 dark:text-amber-300' => $tab !== $key && $key === 'pending' && $count > 0,
                ])>{{ number_format($count, 0, ',', '.') }}</span>
            </button>
        @endforeach
    </div>

    {{-- Suche, Filter und Sortierung. Ob der Filterbereich offen ist, merkt sich der Browser. --}}
    <section
        class="rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-950"
        x-data="{ filtersOpen: $persist(false).as('adminv2-events-filters-open') }"
    >
        <div class="flex flex-wrap items-center gap-3 p-4">
            <div class="min-w-64 flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Titel oder Land suchen …"
                    clearable
                />
            </div>

            <button
                type="button"
                x-on:click="filtersOpen = ! filtersOpen"
                :aria-expanded="filtersOpen"
                aria-controls="events-filter-panel"
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
        <div id="events-filter-panel" x-show="filtersOpen" x-collapse x-cloak>
            {{-- Jede Gruppe ist so breit wie ihr Inhalt: die Auswahl steht in einer Zeile,
                 die Gruppen reihen sich nebeneinander und brechen erst bei Platzmangel um. --}}
            <div class="flex flex-wrap gap-x-12 gap-y-6 border-t border-zinc-100 bg-zinc-50/60 px-5 py-5 dark:border-zinc-800 dark:bg-zinc-900/40 [&>div]:max-w-full">
                <div>
                    <div class="{{ $groupLabel }}">Geltung</div>
                    <div class="flex flex-wrap gap-2">
                        <label class="{{ $chip }}">
                            <input type="radio" wire:model.live="scope" value="" class="sr-only" />
                            Alle
                        </label>
                        @foreach ($scopeOptions as $value => $label)
                            <label wire:key="scope-chip-{{ $value }}" class="{{ $chip }}">
                                <input type="radio" wire:model.live="scope" value="{{ $value }}" class="sr-only" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="{{ $groupLabel }}">Priorität</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($priorityOptions as $value => $label)
                            <label wire:key="priority-chip-{{ $value }}" class="{{ $chip }}">
                                <input type="checkbox" wire:model.live="priorities" value="{{ $value }}" class="sr-only" />
                                <span class="size-2 rounded-full {{ $priorityDots[$value] ?? 'bg-zinc-400' }}"></span>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="{{ $groupLabel }}">Event-Typ</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->eventTypes as $eventType)
                            <label wire:key="type-chip-{{ $eventType->id }}" class="{{ $chip }}">
                                <input type="checkbox" wire:model.live="types" value="{{ $eventType->id }}" class="sr-only" />
                                <i class="fas {{ $eventType->icon ?: 'fa-map-marker' }} text-xs opacity-70" aria-hidden="true"></i>
                                {{ $eventType->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="{{ $groupLabel }}">Land</div>
                    <x-adminv2.multi-select
                        class="w-72 max-w-full"
                        model="countryIds"
                        :selected="$countryIds"
                        :options="$this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all()"
                        all-label="Alle Länder"
                        noun="Länder"
                        label="Land"
                        searchable
                        search-placeholder="Code (2 Buchstaben) oder Name"
                    />
                </div>

                <div>
                    <div class="{{ $groupLabel }}">Zeitfenster</div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="w-40">
                            <flux:input wire:model.live="periodFrom" type="date" aria-label="Zeitfenster von" />
                        </div>
                        <span class="text-sm text-zinc-500">bis</span>
                        <div class="w-40">
                            <flux:input wire:model.live="periodTo" type="date" aria-label="Zeitfenster bis" />
                        </div>
                    </div>
                    <p class="mt-2 max-w-sm text-xs text-zinc-500">
                        Findet alle Ereignisse, deren Zeitraum sich damit überschneidet – wie bei betroffenen Reisen.
                    </p>
                </div>

                <div>
                    <div class="{{ $groupLabel }}">Aufgaben</div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="{{ $chip }}">
                            <input type="radio" wire:model.live="taskFilter" value="" class="sr-only" />
                            Alle
                        </label>
                        @foreach ($this->taskFilterOptions() as $value => $label)
                            <label wire:key="task-chip-{{ $value }}" class="{{ $chip }}">
                                <input type="radio" wire:model.live="taskFilter" value="{{ $value }}" class="sr-only" />
                                {{ $label }}
                            </label>
                        @endforeach

                        <div class="w-56">
                            <flux:select wire:model.live="taskPerson" aria-label="Offene Aufgaben bei">
                                <flux:select.option value="">Offene Aufgaben bei …</flux:select.option>
                                @foreach ($this->employees as $employee)
                                    <flux:select.option value="{{ $employee->id }}">{{ trim($employee->name) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Aktive Filter als Badges: eigene Zeile unter dem Filterbereich, damit sie
         auch bei zugeklapptem Bereich sofort ins Auge fallen. --}}
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

    {{-- Reiter "Heute angelegt": dazu die Themen, die die KI vorschlaegt --}}
    @if ($tab === 'today')
        @php
            $suggestions = $this->aiSuggestions;
            $allSuggestions = $this->allAiSuggestions;
            $aiFilters = $this->aiSearchFilters;
            $latestSearch = $this->latestAiSearch;
            $typeNames = $this->eventTypes->pluck('name', 'code');
            $typeIcons = $this->eventTypes->pluck('icon', 'code');
            $suggestionCountries = \App\Models\Country::query()
                ->whereIn('iso_code', $suggestions->flatMap(fn ($suggestion) => $suggestion->country_codes ?? [])->unique())
                ->get()
                ->mapWithKeys(fn ($country) => [strtoupper((string) $country->iso_code) => $country->getName('de')]);
        @endphp

        {{-- Auf- und zuklappbar; zugeklappt zeigt die Kopfzeile, wie viele Vorschlaege warten. --}}
        <section class="rounded-2xl border border-[var(--color-accent)]/25 bg-[var(--color-accent)]/[0.03] p-4 dark:bg-[var(--color-accent)]/10" aria-label="KI-Vorschläge" x-data="{ open: false }">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <button type="button" x-on:click="open = ! open" :aria-expanded="open" class="group/collapse flex min-w-0 flex-1 items-start gap-2 text-start">
                    <flux:icon.chevron-down variant="mini" class="mt-0.5 shrink-0 text-zinc-400 transition-transform group-hover/collapse:text-zinc-700 dark:group-hover/collapse:text-zinc-200" ::class="open || '-rotate-90'" />
                    <span class="min-w-0">
                        <span class="flex items-center gap-2 text-base font-semibold text-zinc-900 dark:text-white">
                            <flux:icon.sparkles variant="mini" class="text-[var(--color-accent)]" />
                            KI-Vorschläge
                            <span @class([
                                'rounded-full px-1.5 text-xs font-medium tabular-nums',
                                'bg-[var(--color-accent)] text-[var(--color-accent-foreground)]' => $suggestions->isNotEmpty(),
                                'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => $suggestions->isEmpty(),
                            ])>{{ $suggestions->count() }}{{ $suggestions->count() !== $allSuggestions->count() ? ' von '.$allSuggestions->count() : '' }}</span>
                        </span>
                        <span class="mt-0.5 block text-sm text-zinc-600 dark:text-zinc-400">
                            Themen, die die KI im Internet gefunden hat – noch keine Ereignisse. Jedes Thema steht nur einmal da, auch wenn mehrere Quellen darüber berichten.
                        </span>
                    </span>
                </button>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    @if ($this->olderAiSuggestionsCount > 0)
                        <flux:button size="sm" variant="ghost" wire:click="$toggle('showOlderSuggestions')" x-on:click="open = true">
                            {{ $showOlderSuggestions ? 'Nur heutige zeigen' : 'Auch ältere zeigen ('.$this->olderAiSuggestionsCount.')' }}
                        </flux:button>
                    @endif
                    @if ($aiFilters)
                        <flux:button size="sm" variant="primary" icon="funnel" wire:click="startFilteredAiSearch" x-on:click="open = true" wire:loading.attr="disabled" wire:target="startFilteredAiSearch, startAiSearch" :disabled="(bool) $latestSearch?->isRunning()">KI mit diesen Filtern suchen lassen</flux:button>
                    @endif
                    <flux:button size="sm" icon="sparkles" wire:click="startAiSearch" x-on:click="open = true" wire:loading.attr="disabled" wire:target="startAiSearch, startFilteredAiSearch" :disabled="(bool) $latestSearch?->isRunning()">{{ $aiFilters ? 'Allgemein suchen lassen' : 'KI jetzt suchen lassen' }}</flux:button>
                </div>
            </div>

            {{-- Mit gesetzten Filtern: gezielte Suche moeglich, die Anzeige folgt den Filtern –
                 die uebrigen Vorschlaege bleiben erhalten und lassen sich einblenden. --}}
            @if ($aiFilters)
                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl bg-white/70 px-3 py-2 text-sm text-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-300">
                    <flux:icon.funnel variant="micro" class="shrink-0 text-zinc-400" />
                    <span>
                        <span class="font-medium text-zinc-900 dark:text-white">Filter der Liste:</span>
                        {{ collect($aiFilters['labels'])->map(fn ($entry) => $entry['label'].': '.$entry['value'])->implode(' · ') }}
                    </span>
                    @if ($allSuggestions->count() !== $suggestions->count() || $showAllSuggestions)
                        <button type="button" wire:click="$toggle('showAllSuggestions')" x-on:click="open = true" class="font-medium underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">
                            {{ $showAllSuggestions ? 'Nur passende Vorschläge zeigen' : 'Alle '.$allSuggestions->count().' Vorschläge zeigen' }}
                        </button>
                    @endif
                </div>
            @endif

            {{-- Eine laufende Suche ist auch zugeklappt zu sehen. --}}
            @if ($latestSearch?->isRunning())
                <x-adminv2.ai-search-status :search="$latestSearch" class="mt-3" />
            @endif

            <div x-show="open" x-collapse x-cloak>
            @unless ($latestSearch?->isRunning())
                <x-adminv2.ai-search-status :search="$latestSearch" class="mt-3" />
            @endunless

            @if ($suggestions->isNotEmpty())
                <div class="mt-4 grid gap-4 lg:grid-cols-2" wire:loading.class="opacity-60" wire:target="createDraftFromSuggestion, dismissSuggestion, showOlderSuggestions, showAllSuggestions">
                    @foreach ($suggestions as $suggestion)
                        <x-adminv2.ai-suggestion-card
                            wire:key="suggestion-{{ $suggestion->id }}"
                            :suggestion="$suggestion"
                            :type-names="$typeNames"
                            :type-icons="$typeIcons"
                            :country-names="$suggestionCountries"
                        />
                    @endforeach
                </div>
            @elseif (! $latestSearch?->isRunning())
                <p class="mt-3 text-sm text-zinc-500">
                    @if ($allSuggestions->isNotEmpty())
                        Zu den gesetzten Filtern passt keiner der {{ $allSuggestions->count() }} offenen Vorschläge. Mit „KI mit diesen Filtern suchen lassen“ sucht die KI gezielt danach.
                    @else
                        {{ $showOlderSuggestions || $this->olderAiSuggestionsCount === 0 ? 'Es gibt keine offenen Vorschläge.' : 'Heute gibt es noch keine offenen Vorschläge.' }}
                        Mit „{{ $aiFilters ? 'Allgemein suchen lassen' : 'KI jetzt suchen lassen' }}“ startest du eine neue Suche.
                    @endif
                </p>
            @endif
            </div>
        </section>
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
            {{ number_format($events->total(), 0, ',', '.') }} {{ $events->total() === 1 ? 'Ereignis' : 'Ereignisse' }}
        </span>
    </div>

    {{-- Ergebnisse als Karten --}}
    <div
        class="grid gap-4 lg:grid-cols-2"
        wire:loading.class="opacity-60"
        wire:target="tab, search, priorities, types, countryIds, periodFrom, periodTo, scope, taskFilter, taskPerson, sort, toggleDirection, removeFilter, resetFilters, gotoPage, nextPage, previousPage"
    >
        @forelse ($events as $event)
            @php
                $state = EventState::of($event);
                $countryNames = $event->countries->map->getName('de')->unique()->values();
            @endphp
            <article
                wire:key="event-{{ $event->id }}"
                class="group relative flex flex-col rounded-2xl border border-s-4 border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700 {{ $priorityEdges[$event->priority] ?? $priorityEdges['info'] }}"
            >
                {{-- Ueberschrift zuerst, daneben das Menue --}}
                <div class="flex items-start justify-between gap-3">
                    <h2 class="min-w-0 text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                        {{-- Die ganze Karte fuehrt zum Ereignis; nur das Menue liegt darueber. --}}
                        <a href="{{ route('adminv2.events.edit', $event) }}" class="line-clamp-2 after:absolute after:inset-0 after:rounded-2xl group-hover:underline">
                            {{ $event->getTitle('de') ?: 'Ohne Titel' }}
                        </a>
                    </h2>

                    <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                        <flux:dropdown align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen" />

                            <flux:menu>
                                <flux:menu.item icon="pencil-square" :href="route('adminv2.events.edit', $event)">Bearbeiten</flux:menu.item>

                                @if ($state === EventState::PendingReview)
                                    <flux:menu.item icon="check-circle" wire:click="approve({{ $event->id }})">Freigeben</flux:menu.item>
                                    <flux:menu.item icon="x-circle" wire:click="reject({{ $event->id }})" wire:confirm="Dieses Ereignis ablehnen?">Ablehnen</flux:menu.item>
                                @endif

                                @if ($event->activated_at)
                                    <flux:menu.item icon="document-duplicate" wire:click="createVersion({{ $event->id }})">Neue Version anlegen</flux:menu.item>
                                @endif

                                <flux:menu.separator />

                                <flux:menu.item icon="archive-box" wire:click="toggleArchive({{ $event->id }})">
                                    {{ $event->archived ? 'Archivierung aufheben' : 'Archivieren' }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>

                {{-- Darunter die Badges: Zustand, Prioritaet, Typen und Merkmale --}}
                <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1.5">
                    <x-adminv2.state-badge :state="$state" />
                    <x-adminv2.priority-badge :priority="$event->priority" />

                    @foreach ($event->eventTypes as $eventType)
                        <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            <i class="fas {{ $eventType->icon ?: 'fa-map-marker' }} text-[0.65rem] opacity-60" aria-hidden="true"></i>
                            {{ $eventType->name }}
                        </span>
                    @endforeach
                    @if ($event->is_nationwide)
                        <span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 dark:bg-sky-400/10 dark:text-sky-300">landesweit</span>
                    @endif
                    @if (($event->version ?? 1) > 1)
                        <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Version {{ $event->version }}</span>
                    @endif
                    @if ($event->apiClient)
                        <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">API: {{ $event->apiClient->name }}</span>
                    @endif
                </div>

                {{-- Zeitraum und Laender jeweils in einer eigenen Zeile --}}
                <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                    <div class="flex items-center gap-2">
                        <dt class="shrink-0"><flux:icon.calendar variant="mini" class="text-zinc-400" /><span class="sr-only">Zeitraum</span></dt>
                        <dd class="tabular-nums whitespace-nowrap">
                            {{ $event->start_date?->format('d.m.Y') ?? '–' }} – {{ $event->end_date?->format('d.m.Y') ?? 'offen' }}
                        </dd>
                    </div>
                    <div class="flex min-w-0 items-start gap-2">
                        <dt class="mt-0.5 shrink-0"><flux:icon.map-pin variant="mini" class="text-zinc-400" /><span class="sr-only">Länder</span></dt>
                        <dd class="min-w-0">
                            @if ($countryNames->isEmpty())
                                <span class="font-medium text-amber-600 dark:text-amber-400">Kein Standort</span>
                            @else
                                <span class="line-clamp-2">{{ $countryNames->take(6)->implode(', ') }}@if ($countryNames->count() > 6) +{{ $countryNames->count() - 6 }}@endif</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <div class="mt-auto flex items-center justify-between gap-3 pt-4 text-xs text-zinc-500">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        <span class="inline-flex items-center gap-1 tabular-nums" title="Klicks">
                            <flux:icon.cursor-arrow-rays variant="micro" /> {{ number_format($event->clicks_count, 0, ',', '.') }}
                        </span>

                        @php
                            $tasks = $taskCounts[$event->id] ?? ['total' => 0, 'open' => 0];
                            $taskLabel = $tasks['total'].' '.($tasks['total'] === 1 ? 'Aufgabe' : 'Aufgaben')
                                .($tasks['total'] > 0 ? ', '.$tasks['open'].' offen' : '');
                        @endphp
                        <span @class(['inline-flex items-center gap-1 tabular-nums', 'font-medium text-zinc-900 dark:text-white' => $tasks['open'] > 0])>
                            <flux:icon.clipboard-document-check variant="micro" />
                            {{ $taskLabel }}
                        </span>
                    </div>
                    <span class="tabular-nums">geändert {{ $event->updated_at?->format('d.m.Y') }}</span>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 px-5 py-16 text-center dark:border-zinc-700">
                <div class="mx-auto flex max-w-sm flex-col items-center gap-2">
                    <flux:icon.inbox class="size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine Ereignisse gefunden</p>
                    <p class="text-sm text-zinc-500">
                        @if ($hasFilters)
                            Für diese Filter gibt es im Reiter „{{ $this->tabs()[$tab] ?? 'Alle' }}“ keine Treffer.
                        @else
                            In diesem Reiter liegt aktuell nichts.
                        @endif
                    </p>
                    @if ($hasFilters)
                        <flux:button size="sm" variant="ghost" wire:click="resetFilters" class="mt-1">Filter zurücksetzen</flux:button>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    @if ($events->total() > 0)
        <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-500">
            <span>
                {{ number_format($events->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($events->lastItem() ?? 0, 0, ',', '.') }}
                von {{ number_format($events->total(), 0, ',', '.') }}
            </span>

            @if ($events->hasPages())
                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previousPage" :disabled="$events->onFirstPage()">Zurück</flux:button>
                    <span class="tabular-nums">Seite {{ $events->currentPage() }} von {{ $events->lastPage() }}</span>
                    <flux:button size="sm" variant="ghost" icon-trailing="chevron-right" wire:click="nextPage" :disabled="! $events->hasMorePages()">Weiter</flux:button>
                </div>
            @endif
        </div>
    @endif
</div>
