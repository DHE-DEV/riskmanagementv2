{{-- Aktionen einer Zeile: bearbeiten, loeschen bzw. wiederherstellen / endgueltig loeschen. --}}
@props([
    'id',
    'trashed' => false,
    'editUrl',
    'name' => '',
])

<div class="flex items-center justify-end gap-1">
    @if ($trashed)
        <flux:tooltip content="Wiederherstellen">
            <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="restore({{ $id }})" aria-label="{{ $name }} wiederherstellen" />
        </flux:tooltip>
        <flux:tooltip content="Endgültig löschen">
            <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $id }}, true)" class="!text-red-600" aria-label="{{ $name }} endgültig löschen" />
        </flux:tooltip>
    @else
        <flux:tooltip content="Bearbeiten">
            <flux:button size="sm" variant="ghost" icon="pencil-square" :href="$editUrl" aria-label="{{ $name }} bearbeiten" />
        </flux:tooltip>
        <flux:tooltip content="Löschen">
            <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $id }})" aria-label="{{ $name }} löschen" />
        </flux:tooltip>
    @endif
</div>
