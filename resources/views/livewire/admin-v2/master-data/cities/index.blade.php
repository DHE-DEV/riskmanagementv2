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

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Stadt', 'Städte']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Städte" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, countryIds, region, capital, coordinates, trashed, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $city)
                <x-adminv2.master-data.record-card
                    wire:key="city-{{ $city->id }}"
                    :id="$city->id"
                    :edit-url="route('adminv2.master-data.cities.edit', $city->id)"
                    :title="$city->getName('de')"
                    :trashed="$city->trashed()"
                >
                    <x-slot:aside><x-adminv2.master-data.coordinates-mark :lat="$city->lat" :lng="$city->lng" /></x-slot:aside>
                    @if ($city->is_capital || $city->is_regional_capital)
                        <x-slot:badges>
                            @if ($city->is_capital) <flux:badge size="sm" color="blue" inset="top bottom">Hauptstadt</flux:badge> @endif
                            @if ($city->is_regional_capital) <flux:badge size="sm" color="sky" inset="top bottom">Regionshauptstadt</flux:badge> @endif
                        </x-slot:badges>
                    @endif
                    @if (($city->name_translations['en'] ?? '') !== '' && $city->name_translations['en'] !== $city->getName('de'))
                        <x-adminv2.master-data.card-row icon="language" label="Englisch">{{ $city->name_translations['en'] }}</x-adminv2.master-data.card-row>
                    @endif
                    <x-adminv2.master-data.card-row icon="flag" label="Land und Region">
                        @if ($city->country)
                            <a href="{{ route('adminv2.master-data.countries.edit', $city->country->id) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $city->country->getName('de') }}</a>@if ($city->country->trashed()) <span class="text-xs text-zinc-400">(Papierkorb)</span>@endif
                        @else
                            –
                        @endif
                        @if ($city->region)
                            <span class="text-zinc-400">·</span>
                            <a href="{{ route('adminv2.master-data.regions.edit', $city->region->id) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $city->region->getName('de') }}</a>@if ($city->region->trashed()) <span class="text-xs text-zinc-400">(Papierkorb)</span>@endif
                        @endif
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="users" label="Bevölkerung">{{ $city->population === null ? 'Bevölkerung –' : number_format($city->population, 0, ',', '.').' Einwohner' }}</x-adminv2.master-data.card-row>
                    <x-slot:footer>
                        <span class="tabular-nums">geändert {{ $city->updated_at?->format('d.m.Y') ?? '–' }}</span>
                    </x-slot:footer>
                </x-adminv2.master-data.record-card>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</div>
