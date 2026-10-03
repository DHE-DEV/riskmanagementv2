{{--
    Karte eines Stammdaten-Eintrags – wie die Ereigniskarten: die ganze Karte
    fuehrt zur Bearbeitung, oben rechts das Drei-Punkte-Menue.

    - title / subtitle: Ueberschrift und Zeile darunter (z. B. Codes)
    - tags: statt der Zeile einzelne Tags unter der Ueberschrift; aside: Tag rechts aussen in derselben Zeile
    - badges: Slot fuer Markierungen neben der Ueberschrift
    - Slot: Detailzeilen (dl-Eintraege), footer: Zeile ganz unten
    - Links in der Karte brauchen "relative z-10", damit sie ueber dem Kartenlink liegen.
--}}
@props([
    'id',
    'editUrl',
    'title',
    'subtitle' => null,
    'trashed' => false,
    'inactive' => false,
    'tags' => [],
])

<article
    {{ $attributes->class([
        'group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
        'opacity-70' => $trashed || $inactive,
    ]) }}
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h2 class="min-w-0 text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                <a href="{{ $editUrl }}" class="line-clamp-2 after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $title }}</a>
            </h2>
            @if ($subtitle && $tags === [])
                <p class="mt-0.5 truncate font-mono text-xs text-zinc-500">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
            <x-adminv2.master-data.row-actions :id="$id" :trashed="$trashed" :edit-url="$editUrl" :name="$title" />
        </div>
    </div>

    @if ($tags !== [] || isset($aside))
        <div class="mt-2 flex flex-wrap items-center gap-1.5">
            @foreach ($tags as $tag)
                <flux:badge size="sm" color="zinc" inset="top bottom" class="font-mono">{{ $tag }}</flux:badge>
            @endforeach
            @if (isset($aside))
                <span class="ms-auto">{{ $aside }}</span>
            @endif
        </div>
    @endif

    @if ($trashed || $inactive || isset($badges))
        <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
            @if ($trashed)
                <flux:badge size="sm" color="zinc" inset="top bottom">Papierkorb</flux:badge>
            @endif
            @if ($inactive)
                <flux:badge size="sm" color="zinc" inset="top bottom">inaktiv</flux:badge>
            @endif
            {{ $badges ?? '' }}
        </div>
    @endif

    @if (trim($slot) !== '')
        <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
            {{ $slot }}
        </dl>
    @endif

    @if (isset($footer))
        <div class="mt-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pt-4 text-xs text-zinc-500">
            {{ $footer }}
        </div>
    @endif
</article>
