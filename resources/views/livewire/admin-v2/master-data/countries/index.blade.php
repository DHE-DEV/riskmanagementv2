@php
    use App\Support\AdminV2\CountryRiskProfile;

    $rows = $this->rows;
    $number = fn ($value) => $value === null ? '–' : number_format((float) $value, 0, ',', '.');
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.master-data.list-header section="countries" create-label="Neues Land" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name, ISO-Code oder Währungscode suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-52">
            <flux:select wire:model.live="continent" aria-label="Kontinent">
                <flux:select.option value="">Alle Kontinente</flux:select.option>
                @foreach ($this->continents as $option)
                    <flux:select.option value="{{ $option->id }}">{{ $option->getName('de') }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-44">
            <flux:select wire:model.live="membership" aria-label="EU / Schengen">
                <flux:select.option value="">Alle</flux:select.option>
                <flux:select.option value="eu">EU-Länder</flux:select.option>
                <flux:select.option value="schengen">Schengen-Länder</flux:select.option>
                <flux:select.option value="both">EU- und Schengen-Länder</flux:select.option>
                <flux:select.option value="none">Weder EU noch Schengen</flux:select.option>
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="risk" aria-label="Risikostufe">
                <flux:select.option value="">Alle Risikostufen</flux:select.option>
                @foreach (CountryRiskProfile::LEVELS as $level => $label)
                    <flux:select.option value="{{ $level }}">{{ $level }} – {{ $label }}</flux:select.option>
                @endforeach
                <flux:select.option value="none">Nicht bewertet</flux:select.option>
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="coordinates" aria-label="Koordinaten">
                <flux:select.option value="">Koordinaten: alle</flux:select.option>
                <flux:select.option value="missing">Ohne Koordinaten</flux:select.option>
            </flux:select>
        </div>
        <div class="w-52">
            <flux:select wire:model.live="airports" aria-label="Flughäfen">
                <flux:select.option value="">Flughäfen: alle</flux:select.option>
                <flux:select.option value="any">Mit gepflegtem Flughafen</flux:select.option>
                <flux:select.option value="none">Ohne gepflegten Flughafen</flux:select.option>
            </flux:select>
        </div>
        <div class="w-48">
            <x-adminv2.master-data.trashed-filter />
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Land', 'Länder']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Länder" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, continent, membership, risk, coordinates, airports, trashed, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $country)
                <x-adminv2.master-data.record-card
                    wire:key="country-{{ $country->id }}"
                    :id="$country->id"
                    :edit-url="route('adminv2.master-data.countries.edit', $country->id)"
                    :title="$country->getName('de')"
                    :tags="array_filter([$country->iso_code, $country->iso3_code])"
                    :trashed="$country->trashed()"
                >
                    <x-slot:aside><x-adminv2.master-data.coordinates-mark :lat="$country->lat" :lng="$country->lng" /></x-slot:aside>
                    @if ($country->is_eu_member || $country->is_schengen_member)
                        <x-slot:badges>
                            @if ($country->is_eu_member) <flux:badge size="sm" color="blue" inset="top bottom">EU</flux:badge> @endif
                            @if ($country->is_schengen_member) <flux:badge size="sm" color="sky" inset="top bottom">Schengen</flux:badge> @endif
                        </x-slot:badges>
                    @endif
                    @if (($country->name_translations['en'] ?? '') !== '' && $country->name_translations['en'] !== $country->getName('de'))
                        <x-adminv2.master-data.card-row icon="language" label="Englisch">{{ $country->name_translations['en'] }}</x-adminv2.master-data.card-row>
                    @endif
                    <x-adminv2.master-data.card-row icon="globe-europe-africa" label="Kontinent">{{ $country->continent?->getName('de') ?? '–' }}</x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="map" label="Regionen und Städte">
                        @if ($country->regions_count > 0)
                            <a href="{{ route('adminv2.master-data.regions.index', ['country' => [$country->id]]) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $number($country->regions_count) }} {{ $country->regions_count === 1 ? 'Region' : 'Regionen' }}</a>
                        @else
                            Keine Regionen
                        @endif
                        <span class="text-zinc-400">·</span>
                        @if ($country->cities_count > 0)
                            <a href="{{ route('adminv2.master-data.cities.index', ['country' => [$country->id]]) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $number($country->cities_count) }} {{ $country->cities_count === 1 ? 'Stadt' : 'Städte' }}</a>
                        @else
                            keine Städte
                        @endif
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="banknotes" label="Währung und Bevölkerung">
                        {{ $country->currency_code ?: 'Währung –' }}@if ($country->currency_symbol) <span class="text-zinc-400">({{ $country->currency_symbol }})</span>@endif
                        <span class="text-zinc-400">·</span>
                        {{ $country->population === null ? 'Bevölkerung –' : $number($country->population).' Einwohner' }}
                    </x-adminv2.master-data.card-row>
                    <x-slot:footer>
                        <span class="tabular-nums">geändert {{ $country->updated_at?->format('d.m.Y') ?? '–' }}</span>
                    </x-slot:footer>
                </x-adminv2.master-data.record-card>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</div>
