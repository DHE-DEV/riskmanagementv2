{{--
    Karten Lounges, Mobilitaetsangebote und Hotels eines Flughafens
    (Trait EditsAirportExtras: Eigenschaften lounges, mobility, hotels).

    - review: $aiReview des Formulars – die Hinweise der KI-Feldpruefung stehen
      unter den Feldern jeder Lounge bzw. jedes Hotels
--}}
@props([
    'lounges' => [],
    'mobility' => [],
    'hotels' => [],
    'review' => null,
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
            <div wire:key="lounge-{{ $index }}" class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid items-start gap-4 sm:grid-cols-2">
                    <div><flux:input wire:model="lounges.{{ $index }}.name" label="Name der Lounge" maxlength="255" /><x-adminv2.ai-field-hint :key="'lounge_'.$index.'_name'" :review="$review" /></div>
                    <div><flux:input wire:model="lounges.{{ $index }}.location" label="Standort" placeholder="z. B. Terminal 2" maxlength="255" /><x-adminv2.ai-field-hint :key="'lounge_'.$index.'_location'" :review="$review" /></div>
                    <div><flux:input wire:model="lounges.{{ $index }}.access" label="Zugang" placeholder="z. B. alle Passagiere mit Bordkarte" maxlength="255" /><x-adminv2.ai-field-hint :key="'lounge_'.$index.'_access'" :review="$review" /></div>
                    <div><flux:input wire:model="lounges.{{ $index }}.price_per_person" label="Preis pro Person ab" placeholder="z. B. 45" inputmode="decimal" /><x-adminv2.ai-field-hint :key="'lounge_'.$index.'_price_per_person'" :review="$review" /></div>
                    <div class="sm:col-span-2"><x-adminv2.url-input wire:model="lounges.{{ $index }}.url" label="Website/Info-URL" /><x-adminv2.ai-field-hint :key="'lounge_'.$index.'_url'" :review="$review" /></div>
                </div>
                <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
                    <div><flux:switch wire:model="lounges.{{ $index }}.children_welcome" label="Kinder willkommen" align="left" /><x-adminv2.ai-field-hint :key="'lounge_'.$index.'_children_welcome'" :review="$review" /></div>
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeLounge({{ $index }})" class="!text-red-600">Entfernen</flux:button>
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500">Mit „Lounge“ eine Lounge mit Standort, Zugang und Preis eintragen.</p>
        @endforelse

        {{-- Lounges, die laut KI fehlen – "Uebernehmen" haengt sie als neue Eintraege an. --}}
        @if (($review['fields']['lounges_new']['status'] ?? null) === 'change' || ($review['fields']['lounges_new']['note'] ?? null))
            <div class="rounded-xl border border-dashed border-zinc-300 p-4 dark:border-zinc-700">
                <p class="text-sm font-medium text-zinc-800 dark:text-white">Fehlende Lounges laut KI</p>
                <x-adminv2.ai-field-hint key="lounges_new" :review="$review" />
            </div>
        @endif
    </div>
</x-adminv2.card>

{{-- Mobilitaet --}}
<x-adminv2.card heading="Mobilitätsangebote" :description="$available ? $available.' von '.count($definitions).' Angeboten verfügbar' : 'Mietwagen, ÖPNV, Shuttle, Taxi und Parken – noch nichts als verfügbar markiert.'" collapsible :collapsed="$available === 0">
    <x-slot:actions><x-adminv2.ai-check-button section="mobility" /></x-slot:actions>
    <div class="flex flex-col gap-4">
        {{-- Ergebnis der Feldpruefung zum ganzen Abschnitt – bleibt stehen, auch wenn das KI-Fenster zu ist. --}}
        <x-adminv2.ai-field-hint key="mobility" :review="$review" :applyable="false" class="!mt-0 rounded-xl border border-dashed border-zinc-300 p-3 dark:border-zinc-700" />
        @foreach ($definitions as $key => $definition)
            @php $on = (bool) ($mobility[$key]['available'] ?? false); @endphp
            <div wire:key="mobility-{{ $key }}" class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:switch wire:model.live="mobility.{{ $key }}.available" :label="$definition['label']" align="left" />

                @if ($on)
                    <div class="mt-4 flex flex-col gap-3">
                        @if (isset($definition['list']))
                            @foreach ($mobility[$key][$definition['list']['key']] ?? [] as $index => $row)
                                <div wire:key="mobility-{{ $key }}-{{ $index }}" class="flex flex-wrap items-end gap-2">
                                    @foreach ($definition['list']['fields'] as $field => $label)
                                        <div class="min-w-40 flex-1">
                                            @if ($field === 'url')
                                                <x-adminv2.url-input wire:model="mobility.{{ $key }}.{{ $definition['list']['key'] }}.{{ $index }}.{{ $field }}" :label="$label" />
                                            @else
                                                <flux:input wire:model="mobility.{{ $key }}.{{ $definition['list']['key'] }}.{{ $index }}.{{ $field }}" :label="$label" />
                                            @endif
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
                            @elseif ($meta['type'] === 'url')
                                <x-adminv2.url-input wire:model="mobility.{{ $key }}.{{ $field }}" :label="$meta['label']" />
                            @else
                                <flux:input wire:model="mobility.{{ $key }}.{{ $field }}" :label="$meta['label']" />
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
            <div wire:key="hotel-{{ $index }}" class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid items-start gap-4 sm:grid-cols-2">
                    <div><flux:input wire:model="hotels.{{ $index }}.name" label="Name des Hotels" maxlength="255" /><x-adminv2.ai-field-hint :key="'hotel_'.$index.'_name'" :review="$review" /></div>
                    <div><flux:input wire:model="hotels.{{ $index }}.distance_km" label="Entfernung (km)" placeholder="z. B. 0,5" inputmode="decimal" /><x-adminv2.ai-field-hint :key="'hotel_'.$index.'_distance_km'" :review="$review" /></div>
                    <div class="sm:col-span-2"><x-adminv2.url-input wire:model="hotels.{{ $index }}.booking_url" label="Buchungs-URL" /><x-adminv2.ai-field-hint :key="'hotel_'.$index.'_booking_url'" :review="$review" /></div>
                    <div class="sm:col-span-2"><flux:textarea wire:model="hotels.{{ $index }}.notes" label="Zusätzliche Informationen" rows="2" /><x-adminv2.ai-field-hint :key="'hotel_'.$index.'_notes'" :review="$review" /></div>
                </div>
                <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
                    <div><flux:switch wire:model="hotels.{{ $index }}.shuttle" label="Shuttle-Service verfügbar" align="left" /><x-adminv2.ai-field-hint :key="'hotel_'.$index.'_shuttle'" :review="$review" /></div>
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeHotel({{ $index }})" class="!text-red-600">Entfernen</flux:button>
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500">Mit „Hotel“ ein Hotel mit Entfernung und Buchungslink eintragen.</p>
        @endforelse

        @if (($review['fields']['hotels_new']['status'] ?? null) === 'change' || ($review['fields']['hotels_new']['note'] ?? null))
            <div class="rounded-xl border border-dashed border-zinc-300 p-4 dark:border-zinc-700">
                <p class="text-sm font-medium text-zinc-800 dark:text-white">Fehlende Hotels laut KI</p>
                <x-adminv2.ai-field-hint key="hotels_new" :review="$review" />
            </div>
        @endif
    </div>
</x-adminv2.card>
