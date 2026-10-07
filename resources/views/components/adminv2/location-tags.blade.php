{{--
    Orte zum Anklicken unter einem Suchtreffer der Standort-Suche: die
    Regionen eines Landes oder die Staedte einer Region.

    - items: [{id, name, is_regional_capital?}]
    - click: Livewire-Aufruf je Tag. Ein ":id" wird durch die ID ersetzt;
      ohne ":id" wird die ID als Argument angehaengt, z. B. "browseRegion".
    - active: ID des hervorgehobenen Tags (aufgeklappte Region)
    - nested: als Unterebene eingerueckt darstellen
    - Slot: zusaetzliche Knoepfe vor den Tags, z. B. "Nur die Region zuordnen"
--}}
@props([
    'heading',
    'items' => [],
    'empty' => 'Nichts hinterlegt.',
    'click',
    'active' => null,
    'nested' => false,
])

<div {{ $attributes->class(['flex flex-col gap-1.5', 'ms-3 border-s-2 border-zinc-200 ps-3 dark:border-zinc-700' => $nested]) }}>
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ $heading }}</span>
        {{ $slot }}
    </div>

    @if ($items === [])
        <p class="text-sm text-zinc-500">{{ $empty }}</p>
    @else
        <div class="flex flex-wrap gap-1.5">
            @foreach ($items as $item)
                @php
                    $isActive = $active !== null && (int) $active === (int) $item['id'];
                    $call = str_contains($click, ':id') ? str_replace(':id', $item['id'], $click) : "{$click}({$item['id']})";
                @endphp
                <button
                    type="button"
                    wire:key="tag-{{ $item['id'] }}"
                    wire:click="{{ $call }}"
                    @class([
                        'inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium transition',
                        'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' => $isActive,
                        'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500 dark:hover:bg-zinc-800' => ! $isActive,
                    ])
                    @if ($isActive) aria-pressed="true" @endif
                    @if (! empty($item['is_regional_capital'])) title="Hauptstadt der Region" @endif
                >
                    @if (! empty($item['is_regional_capital']))
                        <flux:icon.star variant="micro" class="size-3 {{ $isActive ? 'text-amber-300' : 'text-amber-500' }}" />
                    @endif
                    {{ $item['name'] }}
                </button>
            @endforeach
        </div>
    @endif
</div>
