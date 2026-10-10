@php
    use App\Support\AdminV2\SightInfo;

    $rows = $this->rows;
    $countryOptions = $this->countryOptions->map(fn ($country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => $country->iso_code])->all();
    $regionOptions = $this->regionOptions;
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.master-data.list-header section="sights" create-label="Neue Sehenswürdigkeit" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name oder Adresse suchen …" aria-label="Suche" clearable />
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
        <div class="w-60">
            <flux:select wire:model.live="category" aria-label="Kategorie">
                <flux:select.option value="">Alle Kategorien</flux:select.option>
                @foreach (SightInfo::CATEGORIES as $key => [$label])
                    <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-44">
            <flux:select wire:model.live="highlight" aria-label="Highlights">
                <flux:select.option value="">Alle Einträge</flux:select.option>
                <flux:select.option value="yes">Nur Highlights</flux:select.option>
            </flux:select>
        </div>
        <div class="w-52">
            <flux:select wire:model.live="status" aria-label="Stand">
                <flux:select.option value="">Stand: alle</flux:select.option>
                @foreach (SightInfo::STATUSES as $key => $label)
                    <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                @endforeach
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

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Sehenswürdigkeit', 'Sehenswürdigkeiten']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Sehenswürdigkeiten" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, countryIds, region, category, highlight, status, coordinates, trashed, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $sight)
                @php $status = SightInfo::status($sight->info); @endphp
                <x-adminv2.master-data.record-card
                    wire:key="sight-{{ $sight->id }}"
                    :id="$sight->id"
                    :edit-url="route('adminv2.master-data.sights.edit', $sight->id)"
                    :title="$sight->getName('de')"
                    :tags="array_filter([$sight->is_highlight ? 'Highlight' : null])"
                    :trashed="$sight->trashed()"
                >
                    <x-slot:aside><x-adminv2.master-data.coordinates-mark :lat="$sight->lat" :lng="$sight->lng" /></x-slot:aside>
                    <x-adminv2.master-data.card-row :icon="SightInfo::categoryIcon($sight->category)" label="Kategorie">{{ SightInfo::categoryLabel($sight->category) }}</x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="flag" label="Ort">
                        {{ collect([$sight->city?->getName('de'), $sight->region?->getName('de'), $sight->country?->getName('de')])->filter()->implode(' · ') ?: '–' }}
                    </x-adminv2.master-data.card-row>
                    <x-adminv2.master-data.card-row icon="document-text" label="Stand">
                        <flux:badge size="sm" inset="top bottom" :color="match ($status) { 'ai' => 'amber', 'reviewed' => 'emerald', default => 'sky' }">{{ SightInfo::STATUSES[$status] }}</flux:badge>
                    </x-adminv2.master-data.card-row>
                    <x-slot:footer>
                        <span class="tabular-nums">geändert {{ $sight->updated_at?->format('d.m.Y') ?? '–' }}</span>
                    </x-slot:footer>
                </x-adminv2.master-data.record-card>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</div>
