{{--
    Schaltflaeche "Alle zuklappen" / "Alle aufklappen" fuer die klappbaren
    Karten einer Seite (x-adminv2.card mit collapsible).
--}}
<div x-data="{ open: true }">
    <flux:button
        variant="ghost"
        x-on:click="open = ! open; $dispatch('adminv2-cards', { open })"
        {{ $attributes }}
    >
        <span class="inline-flex items-center gap-1.5">
            <flux:icon.chevron-double-up variant="micro" x-show="open" />
            <flux:icon.chevron-double-down variant="micro" x-show="! open" x-cloak />
            <span x-text="open ? 'Alle zuklappen' : 'Alle aufklappen'">Alle zuklappen</span>
        </span>
    </flux:button>
</div>
