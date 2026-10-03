@php
    use App\Support\AdminV2\Coordinates;

    $rows = $this->rows;
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.master-data.list-header section="continents" create-label="Neuer Kontinent" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name oder Code suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-48">
            <x-adminv2.master-data.trashed-filter />
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Kontinent', 'Kontinente']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Kontinente" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, trashed, sort, toggleDirection, resetFilters, gotoPage, nextPage, previousPage">
            @foreach ($rows as $continent)
                <x-adminv2.master-data.record-card
                    wire:key="continent-{{ $continent->id }}"
                    :id="$continent->id"
                    :edit-url="route('adminv2.master-data.continents.edit', $continent->id)"
                    :title="$continent->getName('de')"
                    :tags="array_filter([$continent->code, 'Sortierung '.$continent->sort_order])"
                    :trashed="$continent->trashed()"
                >
                    <x-slot:aside><x-adminv2.master-data.coordinates-mark :lat="$continent->lat" :lng="$continent->lng" /></x-slot:aside>
                    @if (($continent->name_translations['en'] ?? '') !== '' && $continent->name_translations['en'] !== $continent->getName('de'))
                        <x-adminv2.master-data.card-row icon="language" label="Englisch">{{ $continent->name_translations['en'] }}</x-adminv2.master-data.card-row>
                    @endif
                    <x-adminv2.master-data.card-row icon="flag" label="Länder">
                        @if ($continent->countries_count > 0)
                            <a href="{{ route('adminv2.master-data.countries.index', ['continent' => $continent->id]) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $continent->countries_count }} {{ $continent->countries_count === 1 ? 'Land' : 'Länder' }}</a>
                        @else
                            Keine Länder
                        @endif
                    </x-adminv2.master-data.card-row>
                    <x-slot:footer>
                        <span class="tabular-nums">geändert {{ $continent->updated_at?->format('d.m.Y') ?? '–' }}</span>
                    </x-slot:footer>
                </x-adminv2.master-data.record-card>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    <x-adminv2.master-data.delete-modal :pending="$this->pendingDelete" />
</div>
