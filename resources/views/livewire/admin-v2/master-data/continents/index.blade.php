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

    <x-adminv2.card flush>
        @if ($rows->isEmpty())
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Kontinente" />
        @else
            <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search, trashed, sortBy, resetFilters, gotoPage, nextPage, previousPage">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800">
                        <tr>
                            <x-adminv2.sort-th column="sort_order" :sort="$sort" :direction="$direction" class="w-20 ps-5">#</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="name" :sort="$sort" :direction="$direction">Name</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="code" :sort="$sort" :direction="$direction">Code</x-adminv2.sort-th>
                            <x-adminv2.sort-th column="countries_count" :sort="$sort" :direction="$direction" align="end">Länder</x-adminv2.sort-th>
                            <th class="px-3 py-3 font-medium uppercase tracking-wide">Koordinaten</th>
                            <th class="px-3 py-3 pe-5"><span class="sr-only">Aktionen</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($rows as $continent)
                            @php $editUrl = route('adminv2.master-data.continents.edit', $continent->id); @endphp
                            <tr wire:key="continent-{{ $continent->id }}" @class(['bg-zinc-50/70 text-zinc-500 dark:bg-zinc-900/40' => $continent->trashed()])>
                                <td class="px-3 py-3 ps-5 tabular-nums text-zinc-500">{{ $continent->sort_order }}</td>
                                <td class="px-3 py-3">
                                    <a href="{{ $editUrl }}" class="font-medium text-zinc-900 hover:underline dark:text-white">{{ $continent->getName('de') }}</a>
                                    @if ($continent->trashed())
                                        <flux:badge size="sm" color="zinc" inset="top bottom" class="ms-1">Papierkorb</flux:badge>
                                    @endif
                                    @if (($continent->name_translations['en'] ?? '') !== '')
                                        <div class="text-xs text-zinc-500">{{ $continent->name_translations['en'] }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $continent->code ?: '–' }}</td>
                                <td class="px-3 py-3 text-end tabular-nums">
                                    @if ($continent->countries_count > 0)
                                        <a href="{{ route('adminv2.master-data.countries.index', ['continent' => $continent->id]) }}" class="text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $continent->countries_count }}</a>
                                    @else
                                        <span class="text-zinc-400">0</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap tabular-nums text-zinc-600 dark:text-zinc-400">
                                    {{ $continent->lat !== null && $continent->lng !== null ? Coordinates::format($continent->lat).', '.Coordinates::format($continent->lng) : '–' }}
                                </td>
                                <td class="px-3 py-2 pe-5">
                                    <x-adminv2.master-data.row-actions :id="$continent->id" :trashed="$continent->trashed()" :edit-url="$editUrl" :name="$continent->getName('de')" />
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
