{{-- Sortierung ueber der Kartenliste (Trait ManagesMasterDataList) und die Anzahl der Treffer. --}}
@props([
    'options' => [],
    'sort',
    'direction' => 'asc',
    'total' => 0,
    'noun' => ['Eintrag', 'Einträge'],
])

<div class="-mb-2 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2">
        <span class="text-sm text-zinc-600 dark:text-zinc-400">Sortieren nach</span>
        <div class="w-48">
            <flux:select wire:model.live="sort" aria-label="Sortierung" size="sm">
                @foreach ($options as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:button
            variant="ghost"
            size="sm"
            :icon="$direction === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'"
            wire:click="toggleDirection"
            :aria-label="$direction === 'asc' ? 'Aufsteigend sortiert – umkehren' : 'Absteigend sortiert – umkehren'"
        >
            {{ $direction === 'asc' ? 'Aufsteigend' : 'Absteigend' }}
        </flux:button>
    </div>

    <span class="text-sm text-zinc-500 tabular-nums">
        {{ number_format($total, 0, ',', '.') }} {{ $total === 1 ? $noun[0] : $noun[1] }}
    </span>
</div>
