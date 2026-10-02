{{-- Fusszeile einer paginierten Liste: "1–25 von 240" und Blaettern. --}}
@props(['paginator'])

<div {{ $attributes->class('flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-500 dark:text-zinc-400') }}>
    <span class="tabular-nums">
        {{ number_format($paginator->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($paginator->lastItem() ?? 0, 0, ',', '.') }}
        von {{ number_format($paginator->total(), 0, ',', '.') }}
    </span>

    @if ($paginator->hasPages())
        <div class="flex items-center gap-2">
            <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previousPage" :disabled="$paginator->onFirstPage()">Zurück</flux:button>
            <span class="tabular-nums">Seite {{ $paginator->currentPage() }} von {{ $paginator->lastPage() }}</span>
            <flux:button size="sm" variant="ghost" icon-trailing="chevron-right" wire:click="nextPage" :disabled="! $paginator->hasMorePages()">Weiter</flux:button>
        </div>
    @endif
</div>
