@php
    use App\Support\AdminV2\Coordinates;

    $rows = $this->rows;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.master-data.list-header section="regions" create-label="Neue Region" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name oder Code suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-60">
            <x-adminv2.multi-select :options="$countryOptions" model="countryIds" :selected="$countryIds" all-label="Alle Länder" noun="Länder" searchable search-placeholder="Land oder ISO-Code …" label="Land" />
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
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Regionen" />
        @else
            <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search, countryIds, coordinates, trashed, sortBy, resetFilters, gotoPage, nextPage, previousPage">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800">
                        <tr>
                            <x-adminv2.sort-th column="name" :sort="$sort" :direction="$direction" class="ps-5">Name</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="code" :sort="$sort" :direction="$direction">Code</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="country" :sort="$sort" :direction="$direction">Land</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="cities_count" :sort="$sort" :direction="$direction" align="end">Städte</x-adminv2.sort-th>
                            <th class="px-3 py-3 font-medium uppercase tracking-wide">Koordinaten</th>
                            <th class="px-3 py-3 pe-5"><span class="sr-only">Aktionen</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($rows as $region)
                            @php $editUrl = route('adminv2.master-data.regions.edit', $region->id); @endphp
                            <tr wire:key="region-{{ $region->id }}" @class(['bg-zinc-50/70 text-zinc-500 dark:bg-zinc-900/40' => $region->trashed()])>
                                <td class="px-3 py-3 ps-5">
                                    <a href="{{ $editUrl }}" class="font-medium text-zinc-900 hover:underline dark:text-white">{{ $region->getName('de') }}</a>
                                    @if ($region->trashed())
                                        <flux:badge size="sm" color="zinc" inset="top bottom" class="ms-1">Papierkorb</flux:badge>
                                    @endif
                                    @if (($region->name_translations['en'] ?? '') !== '' && $region->name_translations['en'] !== ($region->name_translations['de'] ?? null))
                                        <div class="text-xs text-zinc-500">{{ $region->name_translations['en'] }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $region->code ?: '–' }}</td>
                                <td class="px-3 py-3">
                                    @if ($region->country)
                                        <a href="{{ route('adminv2.master-data.countries.edit', $region->country->id) }}" class="text-zinc-700 hover:underline dark:text-zinc-300">{{ $region->country->getName('de') }}</a>
                                        @if ($region->country->trashed()) <span class="text-xs text-zinc-400">(Papierkorb)</span> @endif
                                    @else
                                        <span class="text-zinc-400">–</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-end tabular-nums">
                                    @if ($region->cities_count > 0)
                                        <a href="{{ route('adminv2.master-data.cities.index', ['country' => [$region->country_id], 'region' => $region->id]) }}" class="text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ number_format($region->cities_count, 0, ',', '.') }}</a>
                                    @else
                                        <span class="text-zinc-400">0</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap tabular-nums text-zinc-600 dark:text-zinc-400">
                                    {{ $region->lat !== null && $region->lng !== null ? Coordinates::format($region->lat).', '.Coordinates::format($region->lng) : '–' }}
                                </td>
                                <td class="px-3 py-2 pe-5">
                                    <x-adminv2.master-data.row-actions :id="$region->id" :trashed="$region->trashed()" :edit-url="$editUrl" :name="$region->getName('de')" />
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
