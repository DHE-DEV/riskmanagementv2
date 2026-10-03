@php
    use App\Models\Airline;

    $rows = $this->rows;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $cabinClasses = Airline::getCabinClassOptions();
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.master-data.list-header section="airlines" create-label="Neue Airline" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name, IATA/ICAO-Code oder Hauptsitz suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-60">
            <x-adminv2.multi-select :options="$countryOptions" model="countryIds" :selected="$countryIds" all-label="Alle Heimatländer" noun="Länder" searchable search-placeholder="Land oder ISO-Code …" label="Heimatland" />
        </div>
        <div class="w-48">
            <flux:select wire:model.live="cabinClass" aria-label="Kabinenklasse">
                <flux:select.option value="">Alle Kabinenklassen</flux:select.option>
                @foreach ($cabinClasses as $value => $label)
                    <flux:select.option value="{{ $value }}">Mit {{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-44">
            <flux:select wire:model.live="pets" aria-label="Haustiere">
                <flux:select.option value="">Haustiere: alle</flux:select.option>
                <flux:select.option value="yes">Mit Haustiermitnahme</flux:select.option>
            </flux:select>
        </div>
        <div class="w-40">
            <flux:select wire:model.live="active" aria-label="Status">
                <flux:select.option value="">Aktiv und inaktiv</flux:select.option>
                <flux:select.option value="active">Nur aktive</flux:select.option>
                <flux:select.option value="inactive">Nur inaktive</flux:select.option>
            </flux:select>
        </div>
        <div class="w-48">
            <x-adminv2.master-data.trashed-filter />
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Airline', 'Airlines']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Airlines" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, countryIds, cabinClass, pets, active, trashed, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $airline)
                @php $classes = array_intersect_key($cabinClasses, array_flip($airline->cabin_classes ?? [])); @endphp
                <x-adminv2.master-data.record-card
                    wire:key="airline-{{ $airline->id }}"
                    :id="$airline->id"
                    :edit-url="route('adminv2.master-data.airlines.edit', $airline->id)"
                    :title="$airline->name"
                    :tags="array_filter([$airline->iata_code, $airline->icao_code])"
                    :trashed="$airline->trashed()"
                    :inactive="! $airline->is_active"
                >
                    @if ($classes || ($airline->pet_policy['allowed'] ?? false))
                        <x-slot:badges>
                            @foreach ($classes as $label)
                                <flux:badge size="sm" color="zinc" inset="top bottom">{{ $label }}</flux:badge>
                            @endforeach
                            @if ($airline->pet_policy['allowed'] ?? false) <flux:badge size="sm" color="green" inset="top bottom">Haustiere erlaubt</flux:badge> @endif
                        </x-slot:badges>
                    @endif
                    <x-adminv2.master-data.card-row icon="flag" label="Heimatland und Hauptsitz">
                        @if ($airline->homeCountry)
                            <a href="{{ route('adminv2.master-data.countries.edit', $airline->homeCountry->id) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $airline->homeCountry->getName('de') }}</a>
                        @else
                            –
                        @endif
                        @if ($airline->headquarters) <span class="text-zinc-400">·</span> {{ $airline->headquarters }} @endif
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="paper-airplane" label="Direktverbindungen">{{ $airline->airports_count > 0 ? $airline->airports_count.' '.($airline->airports_count === 1 ? 'Direktverbindung' : 'Direktverbindungen') : 'Keine Direktverbindung hinterlegt' }}</x-adminv2.master-data.card-row>
                    <x-slot:footer>
                        <span class="tabular-nums">geändert {{ $airline->updated_at?->format('d.m.Y') ?? '–' }}</span>
                    </x-slot:footer>
                </x-adminv2.master-data.record-card>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</div>
