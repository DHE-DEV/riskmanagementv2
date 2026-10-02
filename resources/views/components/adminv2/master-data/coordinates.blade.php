{{--
    Karte "Koordinaten" einer Stammdaten-Bearbeitungsseite. Erwartet die
    Eigenschaften lat, lng und coordinatesImport (Trait EditsCoordinates).
--}}
@props([
    'lat' => '',
    'lng' => '',
    'description' => 'Mittelpunkt für die Darstellung auf der Karte.',
])

@php
    $hasPoint = is_numeric($lat) && is_numeric($lng);
@endphp

<x-adminv2.card heading="Koordinaten" :description="$description">
    <div class="flex flex-col gap-5">
        <flux:field>
            <flux:label>Aus Google Maps übernehmen</flux:label>
            <flux:description>Koordinaten oder Link einfügen – Breiten- und Längengrad werden daraus gefüllt.</flux:description>
            <flux:input wire:model.live.debounce.500ms="coordinatesImport" icon="map-pin" placeholder="z. B. 48.1351, 11.5820 oder https://www.google.com/maps/…" autocomplete="off" />
            <flux:error name="coordinatesImport" />
        </flux:field>

        <div class="grid gap-5 sm:grid-cols-2">
            <flux:input wire:model.blur="lat" label="Breitengrad" placeholder="z. B. 48.1351" inputmode="decimal" autocomplete="off" />
            <flux:input wire:model.blur="lng" label="Längengrad" placeholder="z. B. 11.5820" inputmode="decimal" autocomplete="off" />
        </div>

        @if ($hasPoint)
            <a
                href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}"
                target="_blank"
                rel="noopener"
                class="inline-flex w-fit items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300"
            >
                In Google Maps ansehen <flux:icon.arrow-top-right-on-square variant="micro" />
            </a>
        @endif
    </div>
</x-adminv2.card>
