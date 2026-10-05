{{-- Leere Liste auf der Kundenseite: Symbol, Ueberschrift, Hinweis. --}}
<div class="flex flex-col items-center gap-3 px-4 py-12 text-center">
    <span class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
        <flux:icon :name="$icon" class="size-6" />
    </span>
    <div>
        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $heading }}</p>
        <p class="mt-1 text-sm text-zinc-500">{{ $text }}</p>
    </div>
</div>
