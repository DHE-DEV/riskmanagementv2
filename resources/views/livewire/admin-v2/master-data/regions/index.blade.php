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

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Region', 'Regionen']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Regionen" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, countryIds, coordinates, trashed, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $region)
                <x-adminv2.master-data.record-card
                    wire:key="region-{{ $region->id }}"
                    :id="$region->id"
                    :edit-url="route('adminv2.master-data.regions.edit', $region->id)"
                    :title="$region->getName('de')"
                    :tags="array_filter([$region->code])"
                    :trashed="$region->trashed()"
                >
                    <x-slot:aside><x-adminv2.master-data.coordinates-mark :lat="$region->lat" :lng="$region->lng" /></x-slot:aside>
                    @if (($region->name_translations['en'] ?? '') !== '' && $region->name_translations['en'] !== $region->getName('de'))
                        <x-adminv2.master-data.card-row icon="language" label="Englisch">{{ $region->name_translations['en'] }}</x-adminv2.master-data.card-row>
                    @endif
                    <x-adminv2.master-data.card-row icon="flag" label="Land">
                        @if ($region->country)
                            <a href="{{ route('adminv2.master-data.countries.edit', $region->country->id) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $region->country->getName('de') }}</a>
                            @if ($region->country->trashed()) <span class="text-xs text-zinc-400">(Papierkorb)</span> @endif
                        @else
                            –
                        @endif
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="building-office-2" label="Städte">
                        @if ($region->cities_count > 0)
                            <a href="{{ route('adminv2.master-data.cities.index', ['country' => [$region->country_id], 'region' => $region->id]) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ number_format($region->cities_count, 0, ',', '.') }} {{ $region->cities_count === 1 ? 'Stadt' : 'Städte' }}</a>
                        @else
                            Keine Städte
                        @endif
                    </x-adminv2.master-data.card-row>
                    <x-slot:footer>
                        <span class="tabular-nums">geändert {{ $region->updated_at?->format('d.m.Y') ?? '–' }}</span>
                    </x-slot:footer>
                </x-adminv2.master-data.record-card>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</div>
