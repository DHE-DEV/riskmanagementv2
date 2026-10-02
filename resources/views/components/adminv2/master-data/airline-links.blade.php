{{--
    Karte mit den Verknuepfungen Airline <-> Flughafen (Trait ManagesAirlineLinks).

    - heading: "Airlines" bzw. "Flughäfen"
    - links: die verknuepften Eintraege (mit pivot direction/terminal)
    - options: waehlbare Partner fuer eine neue Verknuepfung
    - editUrl: Closure, das zur Bearbeitungsseite eines Partners fuehrt
    - linkId / linkDirection / linkTerminal / editingLinkId: Formularzustand
--}}
@props([
    'heading',
    'links',
    'options' => [],
    'editUrl',
    'linkId' => '',
    'linkDirection' => 'both',
    'linkTerminal' => '',
    'editingLinkId' => null,
    'noun' => 'Eintrag',
])

@php
    $directions = \App\Support\AdminV2\MasterData::LINK_DIRECTIONS;
@endphp

<x-adminv2.card :heading="$heading.' ('.$links->count().')'" description="Mit Richtung und Terminal." collapsible :collapsed="$links->isEmpty()" :collapse-key="'airline-links-'.\Illuminate\Support\Str::slug($heading)">
    <div class="flex flex-col gap-4">
        @if ($links->isNotEmpty())
            <ul class="flex max-h-[28rem] flex-col divide-y divide-zinc-100 overflow-y-auto text-sm dark:divide-zinc-800">
                @foreach ($links as $link)
                    <li wire:key="link-{{ $link->id }}" class="py-2">
                        @if ($editingLinkId === $link->id)
                            <div class="flex flex-col gap-2 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-900">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $link->name }}</p>
                                <flux:select wire:model="linkDirection" label="Richtung" size="sm">
                                    @foreach ($directions as $value => $label)
                                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="linkTerminal" label="Terminal" placeholder="z. B. T2" maxlength="50" size="sm" />
                                <flux:error name="linkTerminal" />
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" wire:click="cancelEditLink">Abbrechen</flux:button>
                                    <flux:button size="sm" variant="primary" wire:click="saveLink">Übernehmen</flux:button>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <a href="{{ $editUrl($link) }}" class="truncate font-medium text-zinc-900 hover:underline dark:text-white">{{ $link->name }}</a>
                                    @if ($link->iata_code) <span class="font-mono text-xs text-zinc-400">{{ $link->iata_code }}</span> @endif
                                    <div class="text-xs text-zinc-500">
                                        {{ $directions[$link->pivot->direction ?? 'both'] ?? $link->pivot->direction }}
                                        @if ($link->pivot->terminal) · Terminal {{ $link->pivot->terminal }} @endif
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center">
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editLink({{ $link->id }})" aria-label="Verknüpfung bearbeiten" />
                                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeLink({{ $link->id }})" aria-label="Verknüpfung entfernen" />
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Waehrend eine Verknuepfung bearbeitet wird, teilen sich beide Formulare Richtung und Terminal – deshalb nur eines zur Zeit. --}}
        @unless ($editingLinkId)
        <div class="flex flex-col gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ $noun }} hinzufügen</p>
            <x-adminv2.search-select :options="$options" model="linkId" :selected="$linkId" :placeholder="$noun.' wählen …'" search-placeholder="Name oder Code …" :label="$noun" live :empty-text="'Alle '.$heading.' sind bereits verknüpft.'" />
            <flux:error name="linkId" />
            <div class="grid gap-2 sm:grid-cols-2">
                <flux:select wire:model="linkDirection" aria-label="Richtung" size="sm">
                    @foreach ($directions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="linkTerminal" placeholder="Terminal (optional)" maxlength="50" size="sm" aria-label="Terminal" />
            </div>
            <flux:button size="sm" icon="link" wire:click="addLink" class="w-fit" :disabled="$linkId === ''">Verknüpfen</flux:button>
        </div>
        @endunless
    </div>
</x-adminv2.card>
