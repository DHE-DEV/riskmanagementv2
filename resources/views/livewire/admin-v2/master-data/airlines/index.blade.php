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

    <x-adminv2.card flush>
        @if ($rows->isEmpty())
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Airlines" />
        @else
            <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search, countryIds, cabinClass, pets, active, trashed, sortBy, resetFilters, gotoPage, nextPage, previousPage">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800">
                        <tr>
                            <x-adminv2.sort-th column="name" :sort="$sort" :direction="$direction" class="ps-5">Name</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="iata_code" :sort="$sort" :direction="$direction">Codes</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="country" :sort="$sort" :direction="$direction">Heimatland</x-adminv2.sort-th>
                            <th class="px-3 py-3 font-medium uppercase tracking-wide">Hauptsitz</th>
                            <th class="px-3 py-3 font-medium uppercase tracking-wide">Kabinenklassen</th>
                            <th class="px-3 py-3 font-medium uppercase tracking-wide">Haustiere</th>
                            <x-adminv2.sort-th column="airports_count" :sort="$sort" :direction="$direction" align="end">Direktverbindungen</x-adminv2.sort-th>
                            <th class="px-3 py-3 pe-5"><span class="sr-only">Aktionen</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($rows as $airline)
                            @php $editUrl = route('adminv2.master-data.airlines.edit', $airline->id); @endphp
                            <tr wire:key="airline-{{ $airline->id }}" @class(['bg-zinc-50/70 text-zinc-500 dark:bg-zinc-900/40' => $airline->trashed()])>
                                <td class="px-3 py-3 ps-5">
                                    <a href="{{ $editUrl }}" class="font-medium text-zinc-900 hover:underline dark:text-white">{{ $airline->name }}</a>
                                    @unless ($airline->is_active) <flux:badge size="sm" color="zinc" inset="top bottom" class="ms-1">inaktiv</flux:badge> @endunless
                                    @if ($airline->trashed()) <flux:badge size="sm" color="zinc" inset="top bottom" class="ms-1">Papierkorb</flux:badge> @endif
                                </td>
                                <td class="px-3 py-3 font-mono text-xs whitespace-nowrap text-zinc-700 dark:text-zinc-300">{{ $airline->iata_code ?: '–' }} · {{ $airline->icao_code ?: '–' }}</td>
                                <td class="px-3 py-3">
                                    @if ($airline->homeCountry)
                                        <a href="{{ route('adminv2.master-data.countries.edit', $airline->homeCountry->id) }}" class="text-zinc-700 hover:underline dark:text-zinc-300">{{ $airline->homeCountry->getName('de') }}</a>
                                    @else
                                        <span class="text-zinc-400">–</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-zinc-700 dark:text-zinc-300">{{ $airline->headquarters ?: '–' }}</td>
                                <td class="px-3 py-3">
                                    @php $classes = array_intersect_key($cabinClasses, array_flip($airline->cabin_classes ?? [])); @endphp
                                    @if ($classes)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($classes as $label)
                                                <flux:badge size="sm" color="zinc" inset="top bottom">{{ $label }}</flux:badge>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-zinc-400">–</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($airline->pet_policy['allowed'] ?? false)
                                        <flux:badge size="sm" color="green" inset="top bottom">erlaubt</flux:badge>
                                    @else
                                        <span class="text-zinc-400">–</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-end tabular-nums text-zinc-700 dark:text-zinc-300">{{ $airline->airports_count ?: '–' }}</td>
                                <td class="px-3 py-2 pe-5">
                                    <x-adminv2.master-data.row-actions :id="$airline->id" :trashed="$airline->trashed()" :edit-url="$editUrl" :name="$airline->name" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-adminv2.pagination :paginator="$rows" class="border-t border-zinc-100 px-5 py-3 dark:border-zinc-800" />
        @endif
    </x-adminv2.card>

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</div>
