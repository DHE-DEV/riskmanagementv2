{{--
    Rueckfrage vor dem Loeschen eines Stammdaten-Eintrags. Zeigt, was noch an
    dem Eintrag haengt – endgueltig loeschen laesst er sich erst ohne das.

    - pending: ['label' => …, 'force' => bool, 'dependents' => [Bezeichnung => Anzahl]] oder null
--}}
@props(['pending' => null])

@php
    $dependents = $pending['dependents'] ?? [];
    $force = (bool) ($pending['force'] ?? false);
    $blocked = $force && $dependents !== [];
    $summary = collect($dependents)->map(fn ($count, $label) => number_format($count, 0, ',', '.').' '.$label)->implode(', ');
@endphp

<flux:modal name="master-data-delete" class="md:w-[32rem]">
    <div class="flex flex-col gap-5">
        <div>
            <flux:heading size="lg">
                @if ($blocked)
                    Endgültiges Löschen nicht möglich
                @elseif ($force)
                    Endgültig löschen?
                @else
                    In den Papierkorb legen?
                @endif
            </flux:heading>

            <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                @if ($blocked)
                    An „{{ $pending['label'] ?? '' }}“ hängen noch Einträge: {{ $summary }}.
                    Erst wenn sie gelöscht oder anders zugeordnet sind, lässt sich der Eintrag endgültig löschen.
                @elseif ($force)
                    „{{ $pending['label'] ?? '' }}“ wird unwiderruflich gelöscht.
                @else
                    „{{ $pending['label'] ?? '' }}“ wird in den Papierkorb gelegt und lässt sich von dort wiederherstellen.
                @endif
            </p>

            @if (! $force && $dependents !== [])
                <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2.5 text-sm leading-relaxed text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                    Daran hängen noch: {{ $summary }}. Sie bleiben erhalten, verweisen dann aber auf einen Eintrag im Papierkorb.
                </p>
            @endif
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close><flux:button variant="ghost">{{ $blocked ? 'Schließen' : 'Abbrechen' }}</flux:button></flux:modal.close>
            @unless ($blocked)
                <flux:button variant="danger" icon="trash" wire:click="deleteConfirmed">{{ $force ? 'Endgültig löschen' : 'In den Papierkorb' }}</flux:button>
            @endunless
        </div>
    </div>
</flux:modal>
