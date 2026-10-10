{{--
    Karte "Koordinaten" einer Stammdaten-Bearbeitungsseite. Erwartet die
    Eigenschaften lat, lng und coordinatesImport (Trait EditsCoordinates).
--}}
@props([
    'lat' => '',
    'lng' => '',
    'description' => 'Mittelpunkt für die Darstellung auf der Karte.',
    // Ergebnis der KI-Feldpruefung ($aiReview) fuer die Hinweise unter den Feldern
    'review' => null,
    'collapsible' => false,
    'collapseKey' => null,
])

@php
    $hasPoint = is_numeric($lat) && is_numeric($lng);
@endphp

<x-adminv2.card heading="Koordinaten" :description="$description" :collapsible="$collapsible" :collapse-key="$collapseKey">
    <x-slot:actions><x-adminv2.ai-check-button section="coordinates" /></x-slot:actions>
    <div class="flex flex-col gap-5">
        <flux:field>
            <flux:label>Aus Google Maps übernehmen</flux:label>
            <flux:description>Koordinaten oder Link einfügen – Breiten- und Längengrad werden daraus gefüllt.</flux:description>
            <flux:input wire:model.live.debounce.500ms="coordinatesImport" icon="map-pin" placeholder="z. B. 48.1351, 11.5820 oder https://www.google.com/maps/…" autocomplete="off" />
            <flux:error name="coordinatesImport" />
        </flux:field>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <flux:input wire:model.blur="lat" label="Breitengrad" placeholder="z. B. 48.1351" inputmode="decimal" autocomplete="off" />
                <x-adminv2.ai-field-hint key="lat" :review="$review" />
            </div>
            <div>
                <flux:input wire:model.blur="lng" label="Längengrad" placeholder="z. B. 11.5820" inputmode="decimal" autocomplete="off" />
                <x-adminv2.ai-field-hint key="lng" :review="$review" />
            </div>
        </div>

        {{-- Optional eine Karte, z. B. mit den Laendergrenzen. --}}
        {{ $map ?? '' }}

        @if ($hasPoint)
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                <a
                    href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex w-fit items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300"
                >
                    In Google Maps ansehen <flux:icon.arrow-top-right-on-square variant="micro" />
                </a>
                <a
                    href="https://www.openstreetmap.org/?mlat={{ $lat }}&amp;mlon={{ $lng }}#map=8/{{ $lat }}/{{ $lng }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex w-fit items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300"
                >
                    In OpenStreetMap ansehen <flux:icon.arrow-top-right-on-square variant="micro" />
                </a>
            </div>
        @endif
    </div>
</x-adminv2.card>
