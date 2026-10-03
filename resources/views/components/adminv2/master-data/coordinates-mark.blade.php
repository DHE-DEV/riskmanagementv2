{{-- Symbol, ob Koordinaten hinterlegt sind – mit Tooltip. --}}
@props(['lat' => null, 'lng' => null])

@php $has = $lat !== null && $lng !== null; @endphp

<flux:tooltip :content="$has ? 'Koordinaten hinterlegt' : 'Keine Koordinaten hinterlegt'">
    <span @class(['inline-flex size-6 items-center justify-center rounded-full', 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-400' => $has, 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800' => ! $has])>
        <flux:icon.map-pin variant="micro" />
        <span class="sr-only">{{ $has ? 'Koordinaten hinterlegt' : 'Keine Koordinaten hinterlegt' }}</span>
    </span>
</flux:tooltip>
