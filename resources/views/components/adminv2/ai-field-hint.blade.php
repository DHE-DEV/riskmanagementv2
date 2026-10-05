{{--
    Hinweis der KI-Feldpruefung unter einem Formularfeld (Trait RunsAiChecks):
    korrekt, Vorschlag mit "Uebernehmen" oder nicht pruefbar.

    - key: Platzhalter-Schluessel des Feldes
    - review: $aiReview des Formulars
    - noteable: das Feld hat eine Notiz – die Begruendung der KI laesst sich dorthin uebernehmen
    - applyable: false fuer Sammelangaben (Listen), die sich nicht in ein Feld uebernehmen lassen –
      der Hinweis bleibt stehen, die Schaltflaeche "Uebernehmen" entfaellt
--}}
@props([
    'key',
    'review' => null,
    'noteable' => false,
    'applyable' => true,
])

@php
    $field = $review['fields'][$key] ?? null;
@endphp

@if ($field)
    @php
        $applied = $field['applied'] ?? false;
        $change = $field['status'] === 'change';
    @endphp
    <div {{ $attributes->class('mt-1.5 flex flex-col gap-1 text-xs') }}>
        {{-- Status und Schaltflaechen stehen immer in einer Zeile, Wert und Begruendung darunter. --}}
        <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap">
            @if ($field['status'] === 'ok')
                <span class="inline-flex shrink-0 items-center gap-1 text-green-700 dark:text-green-400"><flux:icon.check-circle variant="micro" /> KI: korrekt</span>
            @elseif ($change && $applied)
                <span class="inline-flex shrink-0 items-center gap-1 text-green-700 dark:text-green-400"><flux:icon.check-circle variant="micro" /> KI: übernommen</span>
            @elseif ($change)
                <span class="inline-flex shrink-0 items-center gap-1 text-amber-700 dark:text-amber-400"><flux:icon.light-bulb variant="micro" /> {{ $applyable ? 'KI-Vorschlag: nicht übernommen' : 'KI-Vorschlag' }}</span>
                @if ($applyable)
                    <button type="button" wire:click="applyAiSuggestion('{{ $key }}')" class="inline-flex shrink-0 items-center gap-1 rounded-md bg-[var(--color-accent)]/10 px-2 py-0.5 font-medium text-[var(--color-accent)] hover:bg-[var(--color-accent)]/20">
                        <flux:icon.arrow-down-tray variant="micro" /> Übernehmen
                    </button>
                @endif
            @else
                <span class="inline-flex shrink-0 items-center gap-1 text-zinc-500"><flux:icon.question-mark-circle variant="micro" /> KI: nicht prüfbar</span>
            @endif

            @if ($noteable && $field['note'])
                @if ($field['note_applied'] ?? false)
                    <span class="inline-flex shrink-0 items-center gap-1 text-green-700 dark:text-green-400"><flux:icon.check-circle variant="micro" /> Text in Notiz übernommen</span>
                @else
                    <button type="button" wire:click="applyAiNote('{{ $key }}')" class="inline-flex shrink-0 items-center gap-1 rounded-md bg-zinc-100 px-2 py-0.5 font-medium text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        <flux:icon.pencil-square variant="micro" /> Text in Notiz übernehmen
                    </button>
                @endif
            @endif
        </div>

        @if ($change)
            <x-adminv2.ai-value :text="$field['value']" class="font-medium text-zinc-900 dark:text-white" />
        @endif
        @if ($field['note'])
            <p class="break-words whitespace-pre-line text-zinc-500">{{ $field['note'] }}</p>
        @endif
    </div>
@endif
