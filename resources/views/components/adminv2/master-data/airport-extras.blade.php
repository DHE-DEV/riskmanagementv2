{{--
    Karten Lounges, Mobilitaetsangebote und Hotels eines Flughafens
    (Trait EditsAirportExtras: Eigenschaften lounges, mobility, hotels).
--}}
@props([
    'lounges' => [],
    'mobility' => [],
    'hotels' => [],
])

@php
    $definitions = \App\Support\AdminV2\AirportExtras::mobility();
    $available = collect($mobility)->filter(fn ($option) => $option['available'] ?? false)->count();
@endphp

{{-- Lounges --}}
<x-adminv2.card heading="Lounges" :description="count($lounges) ? count($lounges).' '.(count($lounges) === 1 ? 'Lounge' : 'Lounges') : 'Noch keine Lounge eingetragen.'" collapsible :collapsed="count($lounges) === 0">
    <x-slot:actions>
        <x-adminv2.ai-check-button section="lounges" />
        <flux:button size="sm" icon="plus" wire:click="addLounge">Lounge</flux:button>
    </x-slot:actions>

    <div class="flex flex-col gap-4">
        @forelse ($lounges as $index => $lounge)
            <div wire:key="lounge-{{ $index }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="lounges.{{ $index }}.name" label="Name der Lounge" maxlength="255" />
                    <flux:input wire:model="lounges.{{ $index }}.location" label="Standort" placeholder="z. B. Terminal 2" maxlength="255" />
                    <flux:input wire:model="lounges.{{ $index }}.access" label="Zugang" placeholder="z. B. alle Passagiere mit Bordkarte" maxlength="255" />
                    <flux:input wire:model="lounges.{{ $index }}.price_per_person" label="Preis pro Person ab" placeholder="z. B. 45" inputmode="decimal" />
                    <flux:input wire:model="lounges.{{ $index }}.url" label="Website/Info-URL" placeholder="https://…" class="sm:col-span-2" />
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <flux:switch wire:model="lounges.{{ $index }}.children_welcome" label="Kinder willkommen" align="left" />
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeLounge({{ $index }})" class="!text-red-600">Entfernen</flux:button>
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500">Mit „Lounge“ eine Lounge mit Standort, Zugang und Preis eintragen.</p>
        @endforelse
    </div>
</x-adminv2.card>

{{-- Mobilitaet --}}
<x-adminv2.card heading="Mobilitätsangebote" :description="$available ? $available.' von '.count($definitions).' Angeboten verfügbar' : 'Mietwagen, ÖPNV, Shuttle, Taxi und Parken – noch nichts als verfügbar markiert.'" collapsible :collapsed="$available === 0">
    <x-slot:actions><x-adminv2.ai-check-button section="mobility" /></x-slot:actions>
    <div class="flex flex-col gap-4">
        @foreach ($definitions as $key => $definition)
            @php $on = (bool) ($mobility[$key]['available'] ?? false); @endphp
            <div wire:key="mobility-{{ $key }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                <flux:switch wire:model.live="mobility.{{ $key }}.available" :label="$definition['label']" align="left" />

                @if ($on)
                    <div class="mt-4 flex flex-col gap-3">
                        @if (isset($definition['list']))
                            @foreach ($mobility[$key][$definition['list']['key']] ?? [] as $index => $row)
                                <div wire:key="mobility-{{ $key }}-{{ $index }}" class="flex flex-wrap items-end gap-2">
                                    @foreach ($definition['list']['fields'] as $field => $label)
                                        <div class="min-w-40 flex-1">
                                            <flux:input wire:model="mobility.{{ $key }}.{{ $definition['list']['key'] }}.{{ $index }}.{{ $field }}" :label="$label" />
                                        </div>
                                    @endforeach
                                    <flux:button variant="ghost" icon="x-mark" wire:click="removeMobilityRow('{{ $key }}', {{ $index }})" aria-label="Zeile entfernen" />
                                </div>
                            @endforeach
                            <flux:button size="sm" icon="plus" wire:click="addMobilityRow('{{ $key }}')" class="w-fit">{{ $definition['list']['label'] }}</flux:button>
                        @endif

                        @foreach ($definition['fields'] ?? [] as $field => $meta)
                            @if ($meta['type'] === 'textarea')
                                <flux:textarea wire:model="mobility.{{ $key }}.{{ $field }}" :label="$meta['label']" rows="2" />
                            @else
                                <flux:input wire:model="mobility.{{ $key }}.{{ $field }}" :label="$meta['label']" :placeholder="$meta['type'] === 'url' ? 'https://…' : ''" />
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-adminv2.card>

{{-- Hotels --}}
<x-adminv2.card heading="Hotels in der Nähe" :description="count($hotels) ? count($hotels).' '.(count($hotels) === 1 ? 'Hotel' : 'Hotels') : 'Noch kein Hotel eingetragen.'" collapsible :collapsed="count($hotels) === 0">
    <x-slot:actions>
        <x-adminv2.ai-check-button section="hotels" />
        <flux:button size="sm" icon="plus" wire:click="addHotel">Hotel</flux:button>
    </x-slot:actions>

    <div class="flex flex-col gap-4">
        @forelse ($hotels as $index => $hotel)
            <div wire:key="hotel-{{ $index }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="hotels.{{ $index }}.name" label="Name des Hotels" maxlength="255" />
                    <flux:input wire:model="hotels.{{ $index }}.distance_km" label="Entfernung (km)" placeholder="z. B. 0,5" inputmode="decimal" />
                    <flux:input wire:model="hotels.{{ $index }}.booking_url" label="Buchungs-URL" placeholder="https://…" class="sm:col-span-2" />
                    <flux:textarea wire:model="hotels.{{ $index }}.notes" label="Zusätzliche Informationen" rows="2" class="sm:col-span-2" />
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <flux:switch wire:model="hotels.{{ $index }}.shuttle" label="Shuttle-Service verfügbar" align="left" />
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeHotel({{ $index }})" class="!text-red-600">Entfernen</flux:button>
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500">Mit „Hotel“ ein Hotel mit Entfernung und Buchungslink eintragen.</p>
        @endforelse
    </div>
</x-adminv2.card>
