@php
    use App\Support\AdminV2\RegionInfo;
    use App\Support\AdminV2\SubregionInfo;

    $rows = $this->rows;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $regionOptions = $this->regionOptions;
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.master-data.list-header section="subregions" create-label="Neues Teilgebiet" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name oder Code suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-60">
            <x-adminv2.multi-select :options="$countryOptions" model="countryIds" :selected="$countryIds" all-label="Alle Länder" noun="Länder" searchable search-placeholder="Land oder ISO-Code …" label="Land" />
        </div>
        @if ($regionOptions->isNotEmpty())
            <div class="w-56">
                <flux:select wire:model.live="region" aria-label="Region">
                    <flux:select.option value="">Alle Regionen</flux:select.option>
                    @foreach ($regionOptions as $option)
                        <flux:select.option value="{{ $option->id }}">{{ $option->getName('de') }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif
        <div class="w-48">
            <flux:select wire:model.live="kind" aria-label="Art">
                <flux:select.option value="">Alle Arten</flux:select.option>
                @foreach (SubregionInfo::KINDS as $key => [$label])
                    <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-52">
            <flux:select wire:model.live="status" aria-label="Stand">
                <flux:select.option value="">Stand: alle</flux:select.option>
                @foreach (RegionInfo::STATUSES as $key => $label)
                    <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-48">
            <x-adminv2.master-data.trashed-filter />
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Teilgebiet', 'Teilgebiete']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Teilgebiete" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, countryIds, region, kind, status, trashed, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $subregion)
                @php $status = RegionInfo::status($subregion->info); @endphp
                <x-adminv2.master-data.record-card
                    wire:key="subregion-{{ $subregion->id }}"
                    :id="$subregion->id"
                    :edit-url="route('adminv2.master-data.subregions.edit', $subregion->id)"
                    :title="$subregion->getName('de')"
                    :tags="array_filter([$subregion->code])"
                    :trashed="$subregion->trashed()"
                >
                    <x-slot:aside><x-adminv2.master-data.coordinates-mark :lat="$subregion->lat" :lng="$subregion->lng" /></x-slot:aside>
                    <x-adminv2.master-data.card-row :icon="SubregionInfo::kindIcon($subregion->kind)" label="Art">{{ SubregionInfo::kindLabel($subregion->kind) }}</x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="map" label="Region">
                        {{ collect([$subregion->region?->getName('de'), $subregion->country?->getName('de')])->filter()->implode(' · ') ?: '–' }}
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="camera" label="Zugeordnet">
                        {{ $subregion->sights_count }} {{ $subregion->sights_count === 1 ? 'Sehenswürdigkeit' : 'Sehenswürdigkeiten' }}, {{ $subregion->cities_count }} {{ $subregion->cities_count === 1 ? 'Stadt' : 'Städte' }}
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="document-text" label="Infos">
                        <flux:badge size="sm" inset="top bottom" :color="match ($status) { 'ai' => 'amber', 'reviewed' => 'emerald', 'manual' => 'sky', default => 'zinc' }">{{ RegionInfo::STATUSES[$status] }}</flux:badge>
                    </x-adminv2.master-data.card-row>
                    <x-slot:footer>
                        <span class="tabular-nums">geändert {{ $subregion->updated_at?->format('d.m.Y') ?? '–' }}</span>
                    </x-slot:footer>
                </x-adminv2.master-data.record-card>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</div>
