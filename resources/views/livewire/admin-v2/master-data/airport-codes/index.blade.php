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
        <div class="w-48">
            <x-adminv2.master-data.trashed-filter />
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.card flush>
        @if ($rows->isEmpty())
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Flughafen-Codes" />
        @else
            <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search, isoCountry, continent, type, codes, scheduled, active, trashed, sortBy, resetFilters, gotoPage, nextPage, previousPage">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800">
                        <tr>
                            <x-adminv2.sort-th column="name" :sort="$sort" :direction="$direction" class="ps-5">Name</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="ident" :sort="$sort" :direction="$direction">Ident</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="iata_code" :sort="$sort" :direction="$direction">IATA</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="icao_code" :sort="$sort" :direction="$direction">ICAO</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="municipality" :sort="$sort" :direction="$direction">Ort</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="iso_country" :sort="$sort" :direction="$direction">Land</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="type" :sort="$sort" :direction="$direction">Typ</x-adminv2.sort-th>
                            <th class="px-3 py-3 font-medium uppercase tracking-wide">Linienflug</th>
                            <th class="px-3 py-3 font-medium uppercase tracking-wide">Koordinaten</th>
                            <th class="px-3 py-3 pe-5"><span class="sr-only">Aktionen</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($rows as $code)
                            @php $editUrl = route('adminv2.master-data.airport-codes.edit', $code->id); @endphp
                            <tr wire:key="code-{{ $code->id }}" @class(['bg-zinc-50/70 text-zinc-500 dark:bg-zinc-900/40' => $code->trashed()])>
                                <td class="px-3 py-3 ps-5">
                                    <a href="{{ $editUrl }}" class="font-medium text-zinc-900 hover:underline dark:text-white">{{ $code->name }}</a>
                                    @unless ($code->is_active) <flux:badge size="sm" color="zinc" inset="top bottom" class="ms-1">inaktiv</flux:badge> @endunless
                                    @if ($code->trashed()) <flux:badge size="sm" color="zinc" inset="top bottom" class="ms-1">Papierkorb</flux:badge> @endif
                                </td>
                                <td class="px-3 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $code->ident ?: '–' }}</td>
                                <td class="px-3 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $code->iata_code ?: '–' }}</td>
                                <td class="px-3 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $code->icao_code ?: '–' }}</td>
                                <td class="px-3 py-3 text-zinc-700 dark:text-zinc-300">{{ $code->municipality ?: '–' }}</td>
                                <td class="px-3 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $code->iso_country ?: '–' }}@if ($code->iso_region) <span class="text-zinc-400">{{ $code->iso_region }}</span>@endif</td>
                                <td class="px-3 py-3 whitespace-nowrap text-zinc-700 dark:text-zinc-300">{{ $types[$code->type] ?? ($code->type ?: '–') }}</td>
                                <td class="px-3 py-3">
                                    @if ($code->scheduled_service === 'yes')
                                        <flux:badge size="sm" color="green" inset="top bottom">Ja</flux:badge>
                                    @else
                                        <span class="text-zinc-400">Nein</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap tabular-nums text-zinc-600 dark:text-zinc-400">
                                    {{ $code->latitude_deg !== null && $code->longitude_deg !== null ? Coordinates::format($code->latitude_deg).', '.Coordinates::format($code->longitude_deg) : '–' }}
                                </td>
                                <td class="px-3 py-2 pe-5">
                                    <x-adminv2.master-data.row-actions :id="$code->id" :trashed="$code->trashed()" :edit-url="$editUrl" :name="$code->name" />
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
