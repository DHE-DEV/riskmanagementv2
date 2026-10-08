{{--
    Auf- und zuklappbarer Bereich der Navigation. Ob er offen ist, merkt sich
    der Browser je Bereich – auch ueber Seitenwechsel hinweg.

    - heading: Ueberschrift des Bereichs
    - key: Schluessel zum Merken des Zustands
--}}
@props([
    'heading',
    'key',
])

<div
    x-data="{ open: $persist(true).as(@js('adminv2-nav-'.$key)) }"
    {{ $attributes->class('block') }}
>
    <button
        type="button"
        x-on:click="open = ! open"
        :aria-expanded="open"
        class="mb-[2px] flex h-10 w-full items-center rounded-lg text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 lg:h-8 dark:text-white/80 dark:hover:bg-white/[7%] dark:hover:text-white"
    >
        <div class="ps-3 pe-4">
            <flux:icon.chevron-down class="size-3!" x-show="open" />
            <flux:icon.chevron-right class="size-3!" x-show="! open" x-cloak />
        </div>
        <span class="text-sm font-medium leading-none">{{ $heading }}</span>
    </button>

    <div x-show="open" x-cloak class="relative space-y-[2px] ps-7">
        <div class="absolute inset-y-[3px] start-0 ms-4 w-px bg-zinc-200 dark:bg-white/30"></div>
        {{ $slot }}
    </div>
</div>
