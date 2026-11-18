{{--
    Labels einer Reise oder eines Ereignisses als farbige Chips (Travel Alert).
    Ein Klick auf einen Chip setzt bzw. entfernt den Label-Filter; der Tooltip nennt
    Beschreibung und Zeitpunkt der Vergabe.

    - items:      JS-Ausdruck mit der Label-Liste, z. B. "trip.labels" oder "event.labels"
    - max:        so viele Chips direkt, der Rest als "+N" (Tooltip nennt die übrigen)
    - quick:      JS-Ausdruck der Karte (Ereignis oder Reise) – Knopf, der Labels direkt an
                  der Karte vergibt (Popover); zugleich Ziel der Mehrfachauswahl
    - quick-kind: "event" (Standard) oder "trip"
--}}
@props([
    'items',
    'max' => 3,
    'quick' => null,
    'quickKind' => 'event',
])

@php
    $selectionMode = $quickKind === 'trip' ? 'trips' : 'events';
@endphp

<template x-if="{{ $quick ? 'true' : 'false' }} || ({{ $items }} || []).length > 0">
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-1']) }}>
        @if ($quick)
            {{-- Mehrfachauswahl: Häkchen statt Schnellvergabe --}}
            <template x-if="selection.mode === '{{ $selectionMode }}'">
                <button type="button" @click.stop="toggleSelected({{ $quick }})"
                    class="inline-flex items-center gap-1 rounded-full border px-1.5 py-0.5 text-[10px] font-medium leading-tight transition"
                    :class="isSelected({{ $quick }}) ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white text-gray-600 hover:border-gray-400'"
                    :aria-pressed="isSelected({{ $quick }}).toString()">
                    <i class="fa-solid text-[8px]" :class="isSelected({{ $quick }}) ? 'fa-check' : 'fa-plus'"></i>
                    <span x-text="isSelected({{ $quick }}) ? 'Ausgewählt' : 'Auswählen'"></span>
                </button>
            </template>
        @endif

        <template x-for="chip in ({{ $items }} || []).slice(0, {{ (int) $max }})" :key="chip.id">
            <button type="button"
                @click.stop="toggleLabelFilter(chip.id)"
                :title="labelTooltip(chip, isLabelFiltered(chip.id) ? 'Klick: Filter entfernen' : 'Klick: danach filtern')"
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
        @if ($quick)
            <template x-if="selection.mode !== '{{ $selectionMode }}'">
                <button type="button" @click.stop="openQuickLabel({{ $quick }}, $event, '{{ $quickKind }}')"
                    class="inline-flex items-center gap-1 rounded-full border border-dashed border-gray-300 px-1.5 py-0.5 text-[10px] font-medium leading-tight text-gray-500 transition hover:border-gray-400 hover:bg-gray-50 hover:text-gray-700"
                    :class="quickLabel.item && quickLabel.item === {{ $quick }} ? 'border-gray-500 bg-gray-50 text-gray-800' : ''"
                    title="Labels vergeben">
                    <i class="fa-regular fa-tag text-[8px]"></i>
                    <span x-text="({{ $items }} || []).length > 0 ? '' : 'Label'"></span>
                    <i class="fa-regular fa-plus text-[8px]" x-show="({{ $items }} || []).length > 0"></i>
                </button>
            </template>
        @endif
    </div>
</template>
