{{--
    Seitenspalte: was an einem Eintrag haengt – Anzahl, die ersten Eintraege
    und der Weg zur ganzen Liste.

    - heading: Ueberschrift, z. B. "Regionen"
    - count: Gesamtzahl
    - allUrl / createUrl: Liste mit passendem Filter bzw. neuer Eintrag (optional)
    - Slot: die Eintraege als <li>
--}}
@props([
    'heading',
    'count' => 0,
    'shown' => 0,
    'allUrl' => null,
    'createUrl' => null,
    'createLabel' => 'Neu',
    'emptyText' => 'Noch nichts zugeordnet.',
    'external' => false,
    'scroll' => false,
    // Abschnitt fuer die KI-Pruefung (Schaltflaeche in der Kopfzeile)
    'aiSection' => null,
])

<x-adminv2.card :heading="$heading.' ('.number_format($count, 0, ',', '.').')'" collapsible :collapsed="$count === 0">
    @if ($createUrl || $aiSection)
        <x-slot:actions>
            @if ($aiSection)
                <x-adminv2.ai-check-button :section="$aiSection" />
            @endif
            @if ($createUrl)
                <flux:button size="sm" variant="ghost" icon="plus" :href="$createUrl">{{ $createLabel }}</flux:button>
            @endif
        </x-slot:actions>
    @endif

    @if ($count === 0)
        <p class="text-sm text-zinc-500">{{ $emptyText }}</p>
    @else
        <ul @class(['flex flex-col divide-y divide-zinc-100 text-sm dark:divide-zinc-800', 'max-h-96 overflow-y-auto' => $scroll])>
            {{ $slot }}
        </ul>

        @if ($allUrl)
            <a
                href="{{ $allUrl }}"
                @if ($external) target="_blank" @endif
                class="mt-3 inline-flex items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300"
            >
                {{ $count > $shown ? 'Alle '.number_format($count, 0, ',', '.').' anzeigen' : 'In der Liste anzeigen' }}
                @if ($external) <flux:icon.arrow-top-right-on-square variant="micro" /> @endif
            </a>
        @endif
    @endif
</x-adminv2.card>
