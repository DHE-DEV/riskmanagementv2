@php
    use App\Support\AdminV2\Coordinates;

    $rows = $this->rows;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $regionOptions = $this->regionOptions;
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.master-data.list-header section="cities" create-label="Neue Stadt" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-60">
            <x-adminv2.multi-select :options="$countryOptions" model="countryIds" :selected="$countryIds" all-label="Alle Länder" noun="Länder" searchable search-placeholder="Land oder ISO-Code …" label="Land" />
        </div>
        {{-- Regionen gehoeren zu einem Land – der Filter erscheint, sobald genau eines gewaehlt ist. --}}
        @if ($regionOptions->isNotEmpty())
            <div class="w-56">
                <flux:select wire:model.live="region" aria-label="Region">
                    <flux:select.option value="">Alle Regionen</flux:select.option>
                    <flux:select.option value="none">Ohne Region</flux:select.option>
                    @foreach ($regionOptions as $option)
                        <flux:select.option value="{{ $option->id }}">{{ $option->getName('de') }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif
        <div class="w-52">
            <flux:select wire:model.live="capital" aria-label="Hauptstädte">
                <flux:select.option value="">Alle Städte</flux:select.option>
                <flux:select.option value="capital">Nur Hauptstädte</flux:select.option>
                <flux:select.option value="regional">Nur Regionshauptstädte</flux:select.option>
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
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Städte" />
        @else
            <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search, countryIds, region, capital, coordinates, trashed, sortBy, resetFilters, gotoPage, nextPage, previousPage">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800">
                        <tr>
                            <x-adminv2.sort-th column="name" :sort="$sort" :direction="$direction" class="ps-5">Name</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="country" :sort="$sort" :direction="$direction">Land</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="region" :sort="$sort" :direction="$direction">Region</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="population" :sort="$sort" :direction="$direction" align="end">Bevölkerung</x-adminv2.sort-th>
                            <th class="px-3 py-3 font-medium uppercase tracking-wide">Koordinaten</th>
                            <th class="px-3 py-3 pe-5"><span class="sr-only">Aktionen</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($rows as $city)
                            @php $editUrl = route('adminv2.master-data.cities.edit', $city->id); @endphp
                            <tr wire:key="city-{{ $city->id }}" @class(['bg-zinc-50/70 text-zinc-500 dark:bg-zinc-900/40' => $city->trashed()])>
                                <td class="px-3 py-3 ps-5">
                                    <a href="{{ $editUrl }}" class="font-medium text-zinc-900 hover:underline dark:text-white">{{ $city->getName('de') }}</a>
                                    @if ($city->is_capital) <flux:badge size="sm" color="blue" inset="top bottom" class="ms-1">Hauptstadt</flux:badge> @endif
                                    @if ($city->is_regional_capital) <flux:badge size="sm" color="sky" inset="top bottom" class="ms-1">Regionshauptstadt</flux:badge> @endif
                                    @if ($city->trashed()) <flux:badge size="sm" color="zinc" inset="top bottom" class="ms-1">Papierkorb</flux:badge> @endif
                                    @if (($city->name_translations['en'] ?? '') !== '' && $city->name_translations['en'] !== ($city->name_translations['de'] ?? null))
                                        <div class="text-xs text-zinc-500">{{ $city->name_translations['en'] }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($city->country)
                                        <a href="{{ route('adminv2.master-data.countries.edit', $city->country->id) }}" class="text-zinc-700 hover:underline dark:text-zinc-300">{{ $city->country->getName('de') }}</a>
                                        @if ($city->country->trashed()) <span class="text-xs text-zinc-400">(Papierkorb)</span> @endif
                                    @else
                                        <span class="text-zinc-400">–</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($city->region)
                                        <a href="{{ route('adminv2.master-data.regions.edit', $city->region->id) }}" class="text-zinc-700 hover:underline dark:text-zinc-300">{{ $city->region->getName('de') }}</a>
                                        @if ($city->region->trashed()) <span class="text-xs text-zinc-400">(Papierkorb)</span> @endif
                                    @else
                                        <span class="text-zinc-400">–</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-end tabular-nums text-zinc-700 dark:text-zinc-300">{{ $city->population === null ? '–' : number_format($city->population, 0, ',', '.') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap tabular-nums text-zinc-600 dark:text-zinc-400">
                                    @if ($city->lat !== null && $city->lng !== null)
                                        {{ Coordinates::format($city->lat) }}, {{ Coordinates::format($city->lng) }}
                                    @else
                                        <flux:badge size="sm" color="amber" inset="top bottom">fehlen</flux:badge>
                                    @endif
                                </td>
                                <td class="px-3 py-2 pe-5">
                                    <x-adminv2.master-data.row-actions :id="$city->id" :trashed="$city->trashed()" :edit-url="$editUrl" :name="$city->getName('de')" />
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
