@php
    use App\Models\Country;
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
            <flux:select wire:model.live="membership" aria-label="Mitgliedschaft">
                <flux:select.option value="">EU / Schengen: alle</flux:select.option>
                <flux:select.option value="eu">EU-Mitglieder</flux:select.option>
                <flux:select.option value="schengen">Schengen-Mitglieder</flux:select.option>
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
        <div class="w-48">
            <x-adminv2.master-data.trashed-filter />
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.card flush>
        @if ($rows->isEmpty())
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Länder" />
        @else
            <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search, continent, membership, risk, coordinates, trashed, sortBy, resetFilters, gotoPage, nextPage, previousPage">
                <table class="w-full min-w-[1040px] text-left text-sm">
                    <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800">
                        <tr>
                            <x-adminv2.sort-th column="name" :sort="$sort" :direction="$direction" class="ps-5">Name</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="iso_code" :sort="$sort" :direction="$direction">ISO</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="continent" :sort="$sort" :direction="$direction">Kontinent</x-adminv2.sort-th>
                            <th class="px-3 py-3 font-medium uppercase tracking-wide">Mitglied</th>
                            <x-adminv2.sort-th column="risk" :sort="$sort" :direction="$direction">Risiko</x-adminv2.sort-th>
                            <th class="px-3 py-3 font-medium uppercase tracking-wide">Währung</th>
                            <x-adminv2.sort-th column="population" :sort="$sort" :direction="$direction" align="end">Bevölkerung</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="regions_count" :sort="$sort" :direction="$direction" align="end">Regionen</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="cities_count" :sort="$sort" :direction="$direction" align="end">Städte</x-adminv2.sort-th>
                            <th class="px-3 py-3 pe-5"><span class="sr-only">Aktionen</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($rows as $country)
                            @php
                                $editUrl = route('adminv2.master-data.countries.edit', $country->id);
                                $riskLevel = $country->overall_risk_level;
                            @endphp
                            <tr wire:key="country-{{ $country->id }}" @class(['bg-zinc-50/70 text-zinc-500 dark:bg-zinc-900/40' => $country->trashed()])>
                                <td class="px-3 py-3 ps-5">
                                    <a href="{{ $editUrl }}" class="font-medium text-zinc-900 hover:underline dark:text-white">{{ $country->getName('de') }}</a>
                                    @if ($country->trashed())
                                        <flux:badge size="sm" color="zinc" inset="top bottom" class="ms-1">Papierkorb</flux:badge>
                                    @endif
                                    @if ($country->lat === null || $country->lng === null)
                                        <flux:badge size="sm" color="amber" inset="top bottom" class="ms-1">ohne Koordinaten</flux:badge>
                                    @endif
                                    @if (($country->name_translations['en'] ?? '') !== '')
                                        <div class="text-xs text-zinc-500">{{ $country->name_translations['en'] }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 font-mono text-xs whitespace-nowrap text-zinc-700 dark:text-zinc-300">{{ $country->iso_code ?: '–' }} · {{ $country->iso3_code ?: '–' }}</td>
                                <td class="px-3 py-3 text-zinc-700 dark:text-zinc-300">{{ $country->continent?->getName('de') ?? '–' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @if ($country->is_eu_member) <flux:badge size="sm" color="blue" inset="top bottom">EU</flux:badge> @endif
                                    @if ($country->is_schengen_member) <flux:badge size="sm" color="sky" inset="top bottom">Schengen</flux:badge> @endif
                                    @if (! $country->is_eu_member && ! $country->is_schengen_member) <span class="text-zinc-400">–</span> @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @if ($riskLevel)
                                        <flux:badge size="sm" :color="CountryRiskProfile::color($riskLevel)" inset="top bottom">{{ $riskLevel }} – {{ Country::getRiskLevelLabel($riskLevel) }}</flux:badge>
                                    @else
                                        <span class="text-zinc-400">Nicht bewertet</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-zinc-700 dark:text-zinc-300">
                                    {{ $country->currency_code ?: '–' }}
                                    @if ($country->currency_symbol) <span class="text-zinc-400">({{ $country->currency_symbol }})</span> @endif
                                </td>
                                <td class="px-3 py-3 text-end tabular-nums text-zinc-700 dark:text-zinc-300">{{ $number($country->population) }}</td>
                                <td class="px-3 py-3 text-end tabular-nums">
                                    @if ($country->regions_count > 0)
                                        <a href="{{ route('adminv2.master-data.regions.index', ['country' => [$country->id]]) }}" class="text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $number($country->regions_count) }}</a>
                                    @else
                                        <span class="text-zinc-400">0</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-end tabular-nums">
                                    @if ($country->cities_count > 0)
                                        <a href="{{ route('adminv2.master-data.cities.index', ['country' => [$country->id]]) }}" class="text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $number($country->cities_count) }}</a>
                                    @else
                                        <span class="text-zinc-400">0</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 pe-5">
                                    <x-adminv2.master-data.row-actions :id="$country->id" :trashed="$country->trashed()" :edit-url="$editUrl" :name="$country->getName('de')" />
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
