{{--
    Karte mit den Verknuepfungen Airline <-> Flughafen (Trait ManagesAirlineLinks).

    - heading: "Airlines" bzw. "Flughäfen"
    - links: die verknuepften Eintraege (mit pivot direction/terminal)
    - options: waehlbare Partner fuer eine neue Verknuepfung
    - editUrl: Closure, das zur Bearbeitungsseite eines Partners fuehrt
    - linkId / linkDirection / linkTerminal / editingLinkId: Formularzustand

    Die Liste laesst sich im Browser durchsuchen (Name, Code, Terminal) – sie
    ist ohnehin vollstaendig geladen.
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
    <x-slot:actions><x-adminv2.ai-check-button :section="$heading === 'Airlines' ? 'airlines' : 'airports'" /></x-slot:actions>
    <div class="flex flex-col gap-4">
        @if ($links->isNotEmpty())
            <div
                class="flex flex-col gap-2"
                x-data="{
                    query: '',
                    matches(el) {
                        const q = this.query.trim().toLowerCase();
                        return q === '' || el.dataset.search.includes(q);
                    },
                    any() {
                        return [...this.$refs.list.querySelectorAll('[data-search]')].some((el) => this.matches(el));
                    },
                }"
            >
                <div class="relative">
                    <flux:icon.magnifying-glass variant="mini" class="pointer-events-none absolute start-2.5 top-1/2 -translate-y-1/2 text-zinc-400" />
                    <input
                        type="search"
                        x-model="query"
                        x-on:keydown.escape="query = ''"
                        placeholder="{{ $noun }} suchen …"
                        aria-label="{{ $heading }} durchsuchen"
                        autocomplete="off"
                        class="h-9 w-full rounded-lg border border-zinc-200 bg-white ps-8 pe-3 text-sm outline-none placeholder:text-zinc-400 focus:border-[var(--color-accent)] dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    />
                </div>

                {{-- Scrollbar, aber ohne sichtbaren Scrollbalken. --}}
                <ul x-ref="list" class="flex max-h-[28rem] flex-col divide-y divide-zinc-100 overflow-y-auto text-sm [scrollbar-width:none] dark:divide-zinc-800 [&::-webkit-scrollbar]:hidden">
                    @foreach ($links as $link)
                        @if ($editingLinkId === $link->id)
                            {{-- Die Verknuepfung in Bearbeitung bleibt auch bei einer Suche stehen. --}}
                            <li wire:key="link-{{ $link->id }}" class="py-2">
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
                            </li>
                        @else
                            <li
                                wire:key="link-{{ $link->id }}"
                                class="py-2"
                                data-search="{{ mb_strtolower(trim($link->name.' '.$link->iata_code.' '.$link->pivot->terminal)) }}"
                                x-show="matches($el)"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <a href="{{ $editUrl($link) }}" class="truncate font-medium text-zinc-900 hover:underline dark:text-white">{{ $link->name }}</a>
                                        @if ($link->iata_code) <span class="font-mono text-xs text-zinc-400">{{ $link->iata_code }}</span> @endif
                                        <div class="text-xs text-zinc-500">
                                            {{ $directions[$link->pivot->direction ?? 'both'] ?? $link->pivot->direction }}
                                            @if ($link->pivot->terminal) · Terminal {{ $link->pivot->terminal }} @endif
                                        </div>
                                    </div>
                                    <flux:dropdown align="end" class="shrink-0">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Aktionen für {{ $link->name }}" />

                                        <flux:menu>
                                            <flux:menu.item icon="arrow-top-right-on-square" :href="$editUrl($link)">{{ $noun }} öffnen</flux:menu.item>
                                            <flux:menu.item icon="pencil-square" wire:click="editLink({{ $link->id }})">Verknüpfung bearbeiten</flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item icon="x-mark" variant="danger" wire:click="removeLink({{ $link->id }})">Verknüpfung entfernen</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>

                <p x-show="! any()" x-cloak class="py-2 text-sm text-zinc-500">Zur Suche passt kein Eintrag.</p>
            </div>
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
