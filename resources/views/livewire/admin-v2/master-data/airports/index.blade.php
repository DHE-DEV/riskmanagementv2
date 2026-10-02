@php
    use App\Models\Airport;
    use App\Support\AdminV2\Coordinates;

    $rows = $this->rows;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $types = Airport::getTypeOptions();
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.master-data.list-header section="airports" create-label="Neuer Flughafen" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name, IATA/ICAO-Code oder Stadt suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-60">
            <x-adminv2.multi-select :options="$countryOptions" model="countryIds" :selected="$countryIds" all-label="Alle Länder" noun="Länder" searchable search-placeholder="Land oder ISO-Code …" label="Land" />
        </div>
        <div class="w-52">
            <flux:select wire:model.live="type" aria-label="Typ">
                <flux:select.option value="">Alle Typen</flux:select.option>
                @foreach ($types as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
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
            <flux:select wire:model.live="coordinates" aria-label="Koordinaten">
                <flux:select.option value="">Koordinaten: alle</flux:select.option>
                <flux:select.option value="missing">Ohne Koordinaten</flux:select.option>
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
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Flughäfen" />
        @else
            <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search, countryIds, type, active, coordinates, trashed, sortBy, resetFilters, gotoPage, nextPage, previousPage">
                <table class="w-full min-w-[960px] text-left text-sm">
                    <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800">
                        <tr>
                            <x-adminv2.sort-th column="name" :sort="$sort" :direction="$direction" class="ps-5">Name</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="iata_code" :sort="$sort" :direction="$direction">Codes</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="city" :sort="$sort" :direction="$direction">Stadt</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="country" :sort="$sort" :direction="$direction">Land</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="type" :sort="$sort" :direction="$direction">Typ</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="airlines_count" :sort="$sort" :direction="$direction" align="end">Airlines</x-adminv2.sort-th>
                            <th class="px-3 py-3 font-medium uppercase tracking-wide">Koordinaten</th>
                            <th class="px-3 py-3 pe-5"><span class="sr-only">Aktionen</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($rows as $airport)
                            @php $editUrl = route('adminv2.master-data.airports.edit', $airport->id); @endphp
                            <tr wire:key="airport-{{ $airport->id }}" @class(['bg-zinc-50/70 text-zinc-500 dark:bg-zinc-900/40' => $airport->trashed()])>
                                <td class="px-3 py-3 ps-5">
                                    <a href="{{ $editUrl }}" class="font-medium text-zinc-900 hover:underline dark:text-white">{{ $airport->name }}</a>
                                    @unless ($airport->is_active) <flux:badge size="sm" color="zinc" inset="top bottom" class="ms-1">inaktiv</flux:badge> @endunless
                                    @if ($airport->trashed()) <flux:badge size="sm" color="zinc" inset="top bottom" class="ms-1">Papierkorb</flux:badge> @endif
                                </td>
                                <td class="px-3 py-3 font-mono text-xs whitespace-nowrap text-zinc-700 dark:text-zinc-300">{{ $airport->iata_code ?: '–' }} · {{ $airport->icao_code ?: '–' }}</td>
                                <td class="px-3 py-3">
                                    @if ($airport->city)
                                        <a href="{{ route('adminv2.master-data.cities.edit', $airport->city->id) }}" class="text-zinc-700 hover:underline dark:text-zinc-300">{{ $airport->city->getName('de') }}</a>
                                    @else
                                        <span class="text-zinc-400">–</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($airport->country)
                                        <a href="{{ route('adminv2.master-data.countries.edit', $airport->country->id) }}" class="text-zinc-700 hover:underline dark:text-zinc-300">{{ $airport->country->getName('de') }}</a>
                                    @else
                                        <span class="text-zinc-400">–</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-zinc-700 dark:text-zinc-300">{{ $types[$airport->type] ?? ($airport->type ?: '–') }}</td>
                                <td class="px-3 py-3 text-end tabular-nums text-zinc-700 dark:text-zinc-300">{{ $airport->airlines_count ?: '–' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap tabular-nums text-zinc-600 dark:text-zinc-400">
                                    @if ($airport->lat !== null && $airport->lng !== null)
                                        {{ Coordinates::format($airport->lat) }}, {{ Coordinates::format($airport->lng) }}
                                    @else
                                        <flux:badge size="sm" color="amber" inset="top bottom">fehlen</flux:badge>
                                    @endif
                                </td>
                                <td class="px-3 py-2 pe-5">
                                    <x-adminv2.master-data.row-actions :id="$airport->id" :trashed="$airport->trashed()" :edit-url="$editUrl" :name="$airport->name" />
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
