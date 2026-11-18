{{--
    Labels einer Reise oder eines Ereignisses als farbige Chips (Travel Alert).
    Ein Klick auf einen Chip setzt bzw. entfernt den Label-Filter.

    - items: JS-Ausdruck mit der Label-Liste, z. B. "trip.labels" oder "event.labels"
    - max:   so viele Chips direkt, der Rest als "+N" (Tooltip nennt die übrigen)
--}}
@props([
    'items',
    'max' => 3,
])

<template x-if="({{ $items }} || []).length > 0">
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-1']) }}>
        <template x-for="chip in ({{ $items }} || []).slice(0, {{ (int) $max }})" :key="chip.id">
            <button type="button"
                @click.stop="toggleLabelFilter(chip.id)"
                :title="isLabelFiltered(chip.id) ? 'Filter „' + chip.name + '“ entfernen' : 'Nach „' + chip.name + '“ filtern'"
                class="inline-flex max-w-[10rem] items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium leading-tight transition hover:opacity-90"
                :class="isLabelFiltered(chip.id) ? 'ring-2 ring-offset-1 ring-gray-900/50' : ''"
                :style="'background-color: ' + labelColor(chip) + '; color: ' + labelTextColor(labelColor(chip))">
                <i class="fa-solid fa-tag text-[8px] opacity-80"></i>
                <span class="truncate" x-text="labelName(chip)"></span>
            </button>
        </template>
        <template x-if="({{ $items }} || []).length > {{ (int) $max }}">
            <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600"
                :title="({{ $items }}).slice({{ (int) $max }}).map(l => labelName(l)).join(', ')"
                x-text="'+' + (({{ $items }}).length - {{ (int) $max }})"></span>
        </template>
    </div>
</template>
