{{--
    Logo eines Anbieters (Mobilfunk, Taxi-App) – oder, ohne Logo, ein
    Platzhalter mit dem ersten Buchstaben des Namens.

    - url: Adresse des Logos (optional)
    - name: Name des Anbieters
--}}
@props([
    'url' => null,
    'name' => '',
])

<span {{ $attributes->class('flex shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900') }}>
    @if ($url)
        <img src="{{ $url }}" alt="{{ $name }}" class="size-full object-contain p-1" loading="lazy" referrerpolicy="no-referrer" />
    @else
        <span class="text-lg font-semibold text-zinc-400">{{ mb_strtoupper(mb_substr(trim($name) ?: '?', 0, 1)) }}</span>
    @endif
</span>
