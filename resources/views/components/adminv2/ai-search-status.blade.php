{{-- Stand der letzten KI-Suche nach Ereignissen: laeuft, fertig oder fehlgeschlagen. --}}
@props(['search' => null])

@if ($search?->isRunning())
    {{-- Solange die Suche laeuft, den Stand alle drei Sekunden abfragen. --}}
    <div wire:poll.3s="refreshAiSearch" {{ $attributes->class('flex items-center gap-3 rounded-xl bg-[var(--color-accent)]/5 px-4 py-3') }} role="status">
        <flux:icon.arrow-path variant="mini" class="shrink-0 animate-spin text-[var(--color-accent)]" />
        <div>
            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $search->isTargeted() ? 'Die KI sucht gezielt nach aktuellen Ereignissen …' : 'Die KI sucht nach aktuellen Ereignissen …' }}</p>
            @if ($search->isTargeted())
                <p class="text-xs text-zinc-700 dark:text-zinc-300">Eingrenzung: {{ $search->filterSummary() }}</p>
            @endif
            <p class="text-xs text-zinc-500">Sie durchsucht das Internet und liest die Quellen. Das dauert meist ein bis zwei Minuten; die Seite aktualisiert sich von selbst.</p>
        </div>
    </div>
@elseif ($search)
    <div {{ $attributes->class('text-sm') }}>
        @if ($search->status === \App\Models\AiEventSearch::STATUS_DONE)
            <p class="text-zinc-700 dark:text-zinc-300">
                <span class="font-medium text-zinc-900 dark:text-white">Letzte Suche {{ $search->finished_at?->format('d.m.Y H:i') }}</span>{{ $search->isTargeted() ? ' (gezielt – '.$search->filterSummary().')' : '' }}{{ $search->starter ? ' von '.trim($search->starter->name) : '' }}:
                {{ $search->found_count }} {{ $search->found_count === 1 ? 'Thema' : 'Themen' }} gefunden, davon {{ $search->new_count }} neu.
                {{ $search->exclude_existing ? 'Bereits erfasste Ereignisse waren ausgeschlossen.' : 'Bereits erfasste Ereignisse waren nicht ausgeschlossen.' }}
            </p>
            @if ($search->input_tokens !== null)
                <p class="mt-1 text-xs tabular-nums text-zinc-500">
                    Verbrauch: {{ number_format($search->input_tokens + (int) $search->output_tokens, 0, ',', '.') }} Token
                    ({{ number_format($search->input_tokens, 0, ',', '.') }} Eingabe, {{ number_format((int) $search->output_tokens, 0, ',', '.') }} Ausgabe)
                    · Modell {{ $search->model }}
                    · {{ $search->cost !== null ? 'Kosten ca. '.number_format($search->cost, 4, ',', '.').' $' : 'Kosten: kein Preis für dieses Modell hinterlegt' }}
                </p>
            @endif
        @else
            <p class="font-medium text-red-700 dark:text-red-400">
                Die letzte Suche ({{ $search->created_at?->format('d.m.Y H:i') }}) ist {{ $search->isStale() ? 'abgebrochen' : 'fehlgeschlagen' }}.
            </p>
            @if ($search->error)
                <p class="mt-1 text-xs break-words text-red-700 dark:text-red-400">{{ $search->error }}</p>
            @endif
        @endif
    </div>
@endif
