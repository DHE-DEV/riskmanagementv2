{{-- Leere Liste: nichts vorhanden bzw. nichts zu den Filtern gefunden. --}}
@props([
    'filtered' => false,
    'noun' => 'Einträge',
])

<div class="flex flex-col items-center gap-3 px-4 py-14 text-center">
    <span class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
        <flux:icon.magnifying-glass class="size-6" />
    </span>
    <div>
        <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine {{ $noun }} gefunden</p>
        <p class="mt-1 text-sm text-zinc-500">
            {{ $filtered ? 'Zu Suche und Filtern passt kein Eintrag.' : 'Hier ist noch nichts angelegt.' }}
        </p>
    </div>
    @if ($filtered)
        <flux:button size="sm" icon="x-mark" wire:click="resetFilters">Filter zurücksetzen</flux:button>
    @endif
</div>
