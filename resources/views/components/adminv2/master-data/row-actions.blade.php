{{--
    Drei-Punkte-Menue einer Zeile: bearbeiten, in neuem Tab oeffnen, loeschen
    bzw. wiederherstellen / endgueltig loeschen. Weitere Eintraege kommen ueber
    den Slot dazu.
--}}
@props([
    'id',
    'trashed' => false,
    'editUrl',
    'name' => '',
])

<div class="flex justify-end">
    <flux:dropdown align="end">
        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen{{ $name !== '' ? ' für '.$name : '' }}" />

        <flux:menu>
            <flux:menu.item icon="pencil-square" :href="$editUrl">Bearbeiten</flux:menu.item>
            <flux:menu.item icon="arrow-top-right-on-square" :href="$editUrl" target="_blank">In neuem Tab öffnen</flux:menu.item>

            {{ $slot }}

            <flux:menu.separator />

            @if ($trashed)
                <flux:menu.item icon="arrow-uturn-left" wire:click="restore({{ $id }})">Wiederherstellen</flux:menu.item>
                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $id }}, true)">Endgültig löschen</flux:menu.item>
            @else
                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $id }})">In den Papierkorb</flux:menu.item>
            @endif
        </flux:menu>
    </flux:dropdown>
</div>
