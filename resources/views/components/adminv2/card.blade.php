{{--
    Karte mit optionaler Ueberschrift.

    - collapsible: Die Karte laesst sich ueber ihre Kopfzeile auf- und zuklappen.
      Mit "collapse-key" merkt sich der Browser den Zustand.
    - collapsed: zugeklappt starten (solange der Browser nichts anderes gespeichert hat)
--}}
@props([
    'heading' => null,
    'description' => null,
    'actions' => null,
    'flush' => false,
    'collapsible' => false,
    'collapseKey' => null,
    'collapsed' => false,
])

@php
    $collapsible = $collapsible && $heading;
    $open = $collapsed ? 'false' : 'true';
@endphp

<section
    {{ $attributes->class('rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-950') }}
    @if ($collapsible)
        x-data="{ open: {{ $collapseKey ? '$persist('.$open.').as('.\Illuminate\Support\Js::from('adminv2-card-'.$collapseKey).')' : $open }} }"
    @endif
>
    @if ($heading || $actions)
        <header
            @class([
                'flex flex-wrap items-start justify-between gap-3 px-5 py-4',
                // Ohne Klappfunktion ist der Inhalt immer sichtbar – die Trennlinie steht fest.
                'border-b border-zinc-100 dark:border-zinc-800' => ! $collapsible,
            ])
            @if ($collapsible) :class="open && 'border-b border-zinc-100 dark:border-zinc-800'" @endif
        >
            @if ($collapsible)
                <button
                    type="button"
                    x-on:click="open = ! open"
                    :aria-expanded="open"
                    class="group/collapse flex min-w-0 flex-1 items-start gap-2 text-start"
                >
                    <flux:icon.chevron-down variant="mini" class="mt-0.5 shrink-0 text-zinc-400 transition-transform group-hover/collapse:text-zinc-700 dark:group-hover/collapse:text-zinc-200" ::class="open || '-rotate-90'" />
                    <span class="min-w-0">
                        <span class="block text-base font-semibold text-zinc-900 dark:text-white">{{ $heading }}</span>
                        @if ($description)
                            <span class="mt-0.5 block text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</span>
                        @endif
                    </span>
                </button>
            @else
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ $heading }}</h2>
                    @if ($description)
                        <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                    @endif
                </div>
            @endif

            @if ($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endif
        </header>
    @endif

    <div @class(['p-5' => ! $flush]) @if ($collapsible) x-show="open" x-collapse @if ($collapsed) x-cloak @endif @endif>
        {{ $slot }}
    </div>
</section>
