{{-- Karte einer Sehenswuerdigkeit (Land, Region, Teilgebiet). Erwartet $sight und $osm. --}}
<article data-item data-popular="{{ ! empty($sight['is_highlight']) ? 1 : 0 }}" data-region="{{ $sight['region']['id'] ?? '' }}" data-category="{{ $sight['category'] ?? '' }}" data-search="{{ mb_strtolower($sight['name'].' '.($sight['city']['name'] ?? '')) }}"
         class="flex flex-col rounded-3xl bg-white p-5 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
    <div class="flex items-start gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#0b3250]/8 text-[#0b3250] dark:bg-lime-300/10 dark:text-lime-300"><i class="fa-solid fa-binoculars"></i></div>
        <div class="min-w-0 flex-1">
            <h3 class="font-semibold leading-snug text-zinc-900 dark:text-white">{{ $sight['name'] }}</h3>
            <div class="text-xs text-zinc-500">{{ collect([$sight['category_name'] ?? null, $sight['city']['name'] ?? ($sight['subregion']['name'] ?? null), $sight['region']['name'] ?? null])->filter()->implode(' · ') }}</div>
        </div>
        @if (! empty($sight['is_highlight']))<span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-400/15 dark:text-amber-300"><i class="fa-solid fa-star me-1"></i>Highlight</span>@endif
    </div>
    @if (! empty($sight['short_description']))<p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $sight['short_description'] }}</p>@endif
    @php
        $sightFacts = array_filter([
            ['fa-hourglass-half', 'Besuchsdauer', ! empty($sight['visit_minutes']) ? ($sight['visit_minutes'] >= 60 ? round($sight['visit_minutes'] / 60, 1).' Std.' : $sight['visit_minutes'].' Min.') : null],
            ['fa-clock', 'Öffnungszeiten', $sight['opening_hours'] ?? null],
            ['fa-ticket', 'Eintritt', $sight['admission'] ?? null],
            ['fa-sun', 'Beste Zeit', $sight['best_time'] ?? null],
            ['fa-lightbulb', 'Tipps', $sight['tips'] ?? null],
            ['fa-wheelchair', 'Barrierefreiheit', $sight['accessibility'] ?? null],
            ['fa-location-dot', 'Adresse', $sight['address'] ?? null],
        ], fn ($fact) => ! empty($fact[2]));
    @endphp
    @if (! empty($sight['description']) || $sightFacts)
        <details class="group mt-3">
            <summary class="cursor-pointer list-none text-sm font-semibold text-[#0b3250] dark:text-lime-300"><span class="group-open:hidden">Mehr anzeigen</span><span class="hidden group-open:inline">Weniger</span> <i class="fa-solid fa-chevron-down ms-1 text-xs transition group-open:rotate-180"></i></summary>
            @if (! empty($sight['description']))<p class="mt-3 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{!! nl2br(e($sight['description'])) !!}</p>@endif
            @if ($sightFacts)
                <dl class="mt-3 grid gap-2 text-sm">
                    @foreach ($sightFacts as [$icon, $label, $value])
                        <div class="flex gap-2"><i class="fa-solid {{ $icon }} mt-1 w-4 text-center text-zinc-400"></i><div><dt class="inline font-medium">{{ $label }}:</dt> <dd class="inline text-zinc-600 dark:text-zinc-400">{{ $value }}</dd></div></div>
                    @endforeach
                </dl>
            @endif
        </details>
    @endif
    <div class="mt-auto flex flex-wrap items-center gap-3 pt-4 text-xs">
        @if (! empty($sight['coordinates']['lat']))<a href="{{ $osm($sight['coordinates']['lat'], $sight['coordinates']['lng'], 15) }}" target="_blank" rel="noopener" class="text-zinc-500 hover:text-zinc-900 dark:hover:text-white"><i class="fa-solid fa-map-location-dot me-1"></i>Karte</a>@endif
        @if (! empty($sight['website_url']))<a href="{{ $sight['website_url'] }}" target="_blank" rel="noopener" class="text-zinc-500 hover:text-zinc-900 dark:hover:text-white"><i class="fa-solid fa-globe me-1"></i>Website</a>@endif
        @if (! empty($sight['ticket_url']))<a href="{{ $sight['ticket_url'] }}" target="_blank" rel="noopener" class="text-zinc-500 hover:text-zinc-900 dark:hover:text-white"><i class="fa-solid fa-ticket me-1"></i>Tickets</a>@endif
        @if (array_key_exists('is_reviewed', $sight) && ! $sight['is_reviewed'])<span class="ms-auto rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] text-zinc-500 dark:bg-zinc-800" title="Von der KI entworfen, noch nicht geprüft">KI-Entwurf</span>@endif
    </div>
</article>
