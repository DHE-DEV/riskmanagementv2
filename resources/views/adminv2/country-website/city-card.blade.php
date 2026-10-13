{{-- Karte einer Stadt. Erwartet $city und $osm. --}}
<article data-item data-popular="{{ ! empty($city['is_popular']) ? 1 : 0 }}" data-region="{{ $city['region']['id'] ?? '' }}" data-search="{{ mb_strtolower($city['name']) }}"
         class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ ! empty($city['is_capital']) ? 'bg-[#0b3250] text-white' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800' }}">
        <i class="fa-solid {{ ! empty($city['is_capital']) ? 'fa-landmark-dome' : 'fa-city' }}"></i>
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-1.5 font-semibold text-zinc-900 dark:text-white">
            <span class="truncate">{{ $city['name'] }}</span>
            @if (! empty($city['is_popular']))<i class="fa-solid fa-star text-[10px] text-amber-500"></i>@endif
        </div>
        <div class="truncate text-xs text-zinc-500">
            {{ collect([! empty($city['is_capital']) ? 'Hauptstadt' : (! empty($city['is_regional_capital']) ? 'Regionalhauptstadt' : null), $city['region']['name'] ?? null, ! empty($city['population']) ? number_format($city['population'], 0, ',', '.').' Einw.' : null])->filter()->implode(' · ') }}
        </div>
    </div>
    @if (! empty($city['coordinates']['lat']))<a href="{{ $osm($city['coordinates']['lat'], $city['coordinates']['lng']) }}" target="_blank" rel="noopener" class="text-zinc-400 hover:text-zinc-800 dark:hover:text-white" title="Karte"><i class="fa-solid fa-map-location-dot"></i></a>@endif
</article>
