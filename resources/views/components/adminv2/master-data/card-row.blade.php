{{-- Detailzeile einer Karte: Symbol, Bezeichnung (nur fuer Vorleser) und Inhalt. --}}
@props([
    'icon',
    'label',
])

<div class="flex min-w-0 items-start gap-2">
    <dt class="mt-0.5 shrink-0"><flux:icon :name="$icon" variant="mini" class="text-zinc-400" /><span class="sr-only">{{ $label }}</span></dt>
    <dd class="min-w-0">{{ $slot }}</dd>
</div>
