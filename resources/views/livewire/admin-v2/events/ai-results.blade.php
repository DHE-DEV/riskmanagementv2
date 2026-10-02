@php
    use App\Models\AiEventSearch;

    $latestSearch = $this->latestAiSearch;
    $typeNames = $this->eventTypes->pluck('name', 'code');
    $typeIcons = $this->eventTypes->pluck('icon', 'code');
    $selectedRun = $searchId !== '' ? AiEventSearch::with('profile')->find((int) $searchId) : null;
    $runName = fn (AiEventSearch $run) => $run->profile?->name ?? ($run->isTargeted() ? 'Gezielte Suche' : 'Allgemeine Suche');
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">KI Suchergebnisse</flux:heading>
            <flux:subheading>Was die KI im Internet gefunden hat – und aus welcher Suche. Aus jedem offenen Ergebnis lässt sich ein Ereignis als Entwurf anlegen.</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button icon="cog-6-tooth" :href="route('adminv2.system.ai')">Suchen verwalten</flux:button>
            <flux:button variant="primary" icon="sparkles" wire:click="startAiSearch" wire:loading.attr="disabled" wire:target="startAiSearch" :disabled="(bool) $latestSearch?->isRunning()">KI jetzt suchen lassen</flux:button>
        </div>
    </div>

    @if ($latestSearch?->isRunning())
        <x-adminv2.ai-search-status :search="$latestSearch" />
    @endif

    {{-- Die Suchlaeufe --}}
    <x-adminv2.card heading="Suchen" :description="$runs->total() === 0 ? 'Es wurde noch nicht gesucht.' : $runs->total().' '.($runs->total() === 1 ? 'Lauf' : 'Läufe').', neueste zuerst. Ein Klick zeigt die Ergebnisse eines Laufs.'" flush collapsible collapse-key="ai-result-runs">
        @if ($runs->total() > 0)
            <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="showRun, gotoPage, nextPage, previousPage">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-zinc-100 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:border-zinc-800">
                        <tr>
                            <th class="px-3 py-3 ps-5 font-medium">Gestartet</th>
                            <th class="px-3 py-3 font-medium">Suche</th>
                            <th class="px-3 py-3 font-medium">Eingrenzung</th>
                            <th class="px-3 py-3 font-medium">Ergebnis</th>
                            <th class="px-3 py-3 font-medium">Kosten</th>
                            <th class="px-3 py-3 pe-5 font-medium"><span class="sr-only">Ergebnisse</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($runs as $run)
                            <tr wire:key="run-{{ $run->id }}" @class(['align-top', 'bg-[var(--color-accent)]/5' => (string) $run->id === $searchId])>
                                <td class="px-3 py-3 ps-5 whitespace-nowrap tabular-nums text-zinc-900 dark:text-white">
                                    {{ $run->created_at?->format('d.m.Y H:i') }}
                                    <div class="mt-0.5 text-xs text-zinc-500">{{ $run->starter ? trim($run->starter->name) : ($run->profile_id ? 'automatisch' : '–') }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="text-zinc-900 dark:text-white">{{ $runName($run) }}</div>
                                    <div class="mt-0.5 text-xs text-zinc-500">
                                        {{ $run->prompt ? 'eigener Auftrag' : 'Standard-Auftrag' }} · {{ $run->exclude_existing ? 'nur Neues' : 'auch bereits Erfasstes' }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-zinc-700 dark:text-zinc-300">{{ $run->filterSummary() ?? '–' }}</td>
                                <td class="px-3 py-3">
                                    @if ($run->isRunning())
                                        <span class="inline-flex items-center gap-1 font-medium text-[var(--color-accent)]"><flux:icon.arrow-path variant="micro" class="animate-spin" /> läuft …</span>
                                    @elseif ($run->status === AiEventSearch::STATUS_DONE)
                                        <span class="tabular-nums text-zinc-900 dark:text-white">{{ $run->found_count }} gefunden, {{ $run->new_count }} neu</span>
                                    @else
                                        <flux:badge color="red" size="sm" inset="top bottom">{{ $run->isStale() ? 'Abgebrochen' : 'Fehlgeschlagen' }}</flux:badge>
                                        @if ($run->error)
                                            <div class="mt-1 max-w-xs text-xs text-red-700 dark:text-red-400">{{ \Illuminate\Support\Str::limit($run->error, 160) }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap tabular-nums text-zinc-700 dark:text-zinc-300">
                                    @if ($run->cost !== null)
                                        {{ number_format($run->cost, 4, ',', '.') }} $
                                    @elseif ($run->input_tokens !== null)
                                        <span class="text-zinc-500">{{ number_format($run->input_tokens + (int) $run->output_tokens, 0, ',', '.') }} Token</span>
                                    @else
                                        <span class="text-zinc-400">–</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 pe-5 text-right whitespace-nowrap">
                                    @if ($run->suggestions_count > 0)
                                        <flux:button size="xs" :variant="(string) $run->id === $searchId ? 'primary' : 'outline'" wire:click="showRun({{ $run->id }})">
                                            {{ (string) $run->id === $searchId ? 'Auswahl aufheben' : $run->suggestions_count.' '.($run->suggestions_count === 1 ? 'Ergebnis' : 'Ergebnisse').' zeigen' }}
                                        </flux:button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($runs->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 px-5 py-3 text-sm text-zinc-500 dark:border-zinc-800">
                    <span class="tabular-nums">{{ $runs->firstItem() }}–{{ $runs->lastItem() }} von {{ $runs->total() }}</span>
                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previousPage('runs')" :disabled="$runs->onFirstPage()">Zurück</flux:button>
                        <span class="tabular-nums">Seite {{ $runs->currentPage() }} von {{ $runs->lastPage() }}</span>
                        <flux:button size="sm" variant="ghost" icon-trailing="chevron-right" wire:click="nextPage('runs')" :disabled="! $runs->hasMorePages()">Weiter</flux:button>
                    </div>
                </div>
            @endif
        @endif
    </x-adminv2.card>

    {{-- Die Ergebnisse --}}
    <div class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1">
        @foreach ($this->tabs() as $key => $label)
            <button
                type="button"
                wire:click="$set('status', '{{ $key }}')"
                wire:key="tab-{{ $key }}"
                @class([
                    'inline-flex shrink-0 items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium transition',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $status === $key,
                    'text-zinc-600 hover:bg-zinc-200/60 dark:text-zinc-400 dark:hover:bg-zinc-800' => $status !== $key,
                ])
            >
                {{ $label }}
                <span @class([
                    'rounded-full px-1.5 text-xs tabular-nums',
                    'bg-white/20 dark:bg-zinc-900/10' => $status === $key,
                    'bg-zinc-200 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => $status !== $key,
                ])>{{ number_format($this->tabCounts[$key] ?? 0, 0, ',', '.') }}</span>
            </button>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Titel, Text oder Ort suchen …" clearable />
        </div>

        @if ($selectedRun)
            <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--color-accent)]/25 bg-[var(--color-accent)]/10 py-1 ps-3 pe-1 text-sm font-medium text-zinc-900 dark:text-white">
                <span class="font-normal text-zinc-500 dark:text-zinc-400">Suche</span>
                {{ $runName($selectedRun) }} vom {{ $selectedRun->created_at?->format('d.m.Y H:i') }}
                <button type="button" wire:click="showRun({{ $selectedRun->id }})" class="rounded-full p-0.5 text-zinc-500 hover:bg-zinc-900/10 hover:text-zinc-900 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Auswahl der Suche aufheben">
                    <flux:icon.x-mark variant="micro" />
                </button>
            </span>
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-2" wire:loading.class="opacity-60" wire:target="status, search, showRun, createDraftFromSuggestion, dismissSuggestion, restoreSuggestion, gotoPage, nextPage, previousPage">
        @forelse ($suggestions as $suggestion)
            <x-adminv2.ai-suggestion-card
                wire:key="suggestion-{{ $suggestion->id }}"
                :suggestion="$suggestion"
                :type-names="$typeNames"
                :type-icons="$typeIcons"
                :country-names="$countryNames"
            />
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 px-5 py-16 text-center dark:border-zinc-700">
                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                    <flux:icon.sparkles class="size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine Ergebnisse</p>
                    <p class="text-sm text-zinc-500">
                        @if ($search !== '' || $selectedRun)
                            Für diese Auswahl gibt es im Reiter „{{ $this->tabs()[$status] ?? 'Alle' }}“ keine Treffer.
                        @elseif ($status === \App\Models\AiEventSuggestion::STATUS_NEW)
                            Es gibt keine offenen Ergebnisse. Mit „KI jetzt suchen lassen“ startest du eine neue Suche.
                        @else
                            In diesem Reiter liegt nichts.
                        @endif
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    @if ($suggestions->total() > 0)
        <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-500">
            <span class="tabular-nums">{{ $suggestions->firstItem() }}–{{ $suggestions->lastItem() }} von {{ $suggestions->total() }}</span>

            @if ($suggestions->hasPages())
                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previousPage" :disabled="$suggestions->onFirstPage()">Zurück</flux:button>
                    <span class="tabular-nums">Seite {{ $suggestions->currentPage() }} von {{ $suggestions->lastPage() }}</span>
                    <flux:button size="sm" variant="ghost" icon-trailing="chevron-right" wire:click="nextPage" :disabled="! $suggestions->hasMorePages()">Weiter</flux:button>
                </div>
            @endif
        </div>
    @endif
</div>
