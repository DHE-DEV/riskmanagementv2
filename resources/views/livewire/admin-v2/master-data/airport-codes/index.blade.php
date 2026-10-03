@php
    use App\Models\AirportCode;
    use App\Support\AdminV2\Coordinates;

    $rows = $this->rows;
    $types = AirportCode::getTypeOptions();
    $continents = AirportCode::getContinentOptions();
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.master-data.list-header section="airport-codes" create-label="Neuer Flughafen-Code" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name, Ort oder Code suchen – bis 4 Zeichen gilt die Eingabe als Code …" aria-label="Suche" clearable />
        </div>
        <div class="w-28">
            <flux:input wire:model.live.debounce.300ms="isoCountry" placeholder="Land" aria-label="Land (ISO-Code)" maxlength="2" class="font-mono uppercase" />
        </div>
        <div class="w-44">
            <flux:select wire:model.live="continent" aria-label="Kontinent">
                <flux:select.option value="">Alle Kontinente</flux:select.option>
                @foreach ($continents as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-52">
            <flux:select wire:model.live="type" aria-label="Typ">
                <flux:select.option value="">Alle Typen</flux:select.option>
                @foreach ($types as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-44">
            <flux:select wire:model.live="codes" aria-label="Codes">
                <flux:select.option value="">Codes: alle</flux:select.option>
                <flux:select.option value="iata">Mit IATA-Code</flux:select.option>
                <flux:select.option value="icao">Mit ICAO-Code</flux:select.option>
                <flux:select.option value="none">Ohne IATA und ICAO</flux:select.option>
            </flux:select>
        </div>
        <div class="w-44">
            <flux:select wire:model.live="scheduled" aria-label="Linienflugverkehr">
                <flux:select.option value="">Linienflug: alle</flux:select.option>
                <flux:select.option value="yes">Mit Linienflügen</flux:select.option>
                <flux:select.option value="no">Ohne Linienflüge</flux:select.option>
            </flux:select>
        </div>
        <div class="w-40">
            <flux:select wire:model.live="active" aria-label="Status">
                <flux:select.option value="">Aktiv und inaktiv</flux:select.option>
                <flux:select.option value="active">Nur aktive</flux:select.option>
                <flux:select.option value="inactive">Nur inaktive</flux:select.option>
            </flux:select>
        </div>
        <div class="w-64">
            <flux:select wire:model.live="managed" aria-label="Gepflegt">
                <flux:select.option value="">Gepflegt: alle</flux:select.option>
                <flux:select.option value="unmanaged">Große Flughäfen, noch nicht gepflegt</flux:select.option>
            </flux:select>
        </div>
        <div class="w-48">
            <x-adminv2.master-data.trashed-filter />
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Flughafen-Code', 'Flughafen-Codes']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Flughafen-Codes" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, isoCountry, continent, type, codes, scheduled, active, managed, trashed, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $code)
                <x-adminv2.master-data.record-card
                    wire:key="code-{{ $code->id }}"
                    :id="$code->id"
                    :edit-url="route('adminv2.master-data.airport-codes.edit', $code->id)"
                    :title="$code->name"
                    :tags="array_filter(['Ident '.($code->ident ?: '–'), $code->iata_code ? 'IATA '.$code->iata_code : null, $code->icao_code ? 'ICAO '.$code->icao_code : null])"
                    :trashed="$code->trashed()"
                    :inactive="! $code->is_active"
                >
                    <x-slot:aside>
                        <span class="inline-flex items-center gap-1.5">
                            @if ($code->scheduled_service === 'yes') <flux:badge size="sm" color="green" inset="top bottom">Linienflug</flux:badge> @endif
                            <x-adminv2.master-data.coordinates-mark :lat="$code->latitude_deg" :lng="$code->longitude_deg" />
                        </span>
                    </x-slot:aside>
                    <x-adminv2.master-data.card-row icon="map-pin" label="Ort und Land">
                        {{ $code->municipality ?: '–' }}
                        <span class="text-zinc-400">·</span>
                        <span class="font-mono text-xs">{{ $code->iso_country ?: '–' }}@if ($code->iso_region) <span class="text-zinc-400">{{ $code->iso_region }}</span>@endif</span>
                        @if ($code->continent) <span class="text-zinc-400">·</span> {{ $continents[$code->continent] ?? $code->continent }} @endif
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="tag" label="Typ">{{ $types[$code->type] ?? ($code->type ?: '–') }}</x-adminv2.master-data.card-row>
                    @if ($code->elevation_ft !== null)
                        <x-adminv2.master-data.card-row icon="arrow-trending-up" label="Höhe">{{ number_format($code->elevation_ft, 0, ',', '.') }} ft</x-adminv2.master-data.card-row>
                    @endif
                </x-adminv2.master-data.record-card>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</div>
