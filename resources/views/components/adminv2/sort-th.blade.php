{{--
    Spaltenkopf, ueber den sich die Liste sortieren laesst (ruft sortBy() auf).

    - column: Name der Sortierspalte
    - sort / direction: aktuelle Sortierung der Liste
--}}
@props([
    'column',
    'sort',
    'direction' => 'asc',
    'align' => 'start',
])

@php
    $active = $sort === $column;
@endphp

<th
    {{ $attributes->class(['px-3 py-3 font-medium', 'text-end' => $align === 'end']) }}
    aria-sort="{{ $active ? ($direction === 'desc' ? 'descending' : 'ascending') : 'none' }}"
>
    <button
        type="button"
        wire:click="sortBy('{{ $column }}')"
        @class([
            'group/sort inline-flex items-center gap-1 uppercase tracking-wide hover:text-zinc-900 dark:hover:text-white',
            'text-zinc-900 dark:text-white' => $active,
            'flex-row-reverse' => $align === 'end',
        ])
    >
        <span>{{ $slot }}</span>
        @if ($active && $direction === 'desc')
            <flux:icon.chevron-down variant="micro" />
        @elseif ($active)
            <flux:icon.chevron-up variant="micro" />
        @else
            <flux:icon.chevron-up-down variant="micro" class="opacity-0 group-hover/sort:opacity-60" />
        @endif
    </button>
</th>
