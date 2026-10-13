@php
    /**
     * Detailseite einer Region bzw. eines Teilgebiets – Daten von GET /v1/regions/{id} bzw. /v1/subregions/{id},
     * dazu Städte und Sehenswürdigkeiten des Ortes aus den Länderlisten.
     */
    $info = $place['info'] ?? [];
    $countryName = $c['name'] ?? $country->iso_code;
    $isRegion = $type === 'region';
    $parentRegion = $isRegion ? null : ($place['region'] ?? null);
    $kindLabel = $isRegion ? 'Region' : ($place['kind_name'] ?? $place['group_name'] ?? 'Teilgebiet');
    $countryUrl = route('adminv2.master-data.countries.website', ['country' => $country, 'lang' => $lang]);
    $regionUrl = $parentRegion ? route('adminv2.master-data.countries.website.region', ['country' => $country, 'region' => $parentRegion['id'], 'lang' => $lang]) : null;
    $osm = fn ($lat, $lng, $zoom = 12) => 'https://www.openstreetmap.org/?mlat='.$lat.'&mlon='.$lng.'#map='.$zoom.'/'.$lat.'/'.$lng;
    // Karte: eigene Koordinaten, sonst der Mittelpunkt der Städte bzw. Sehenswürdigkeiten des Ortes.
    $points = collect($cities)->concat($sights)->pluck('coordinates')->filter(fn ($p) => ! empty($p['lat']));
    $center = ! empty($place['coordinates']['lat']) ? $place['coordinates'] : ($points->isNotEmpty() ? ['lat' => $points->avg('lat'), 'lng' => $points->avg('lng')] : null);
    $span = $points->count() > 1 ? max(0.08, min(3, max($points->max('lat') - $points->min('lat'), $points->max('lng') - $points->min('lng')) * 0.7)) : 0.25;
    $embed = $center ? 'https://www.openstreetmap.org/export/embed.html?bbox='.($center['lng'] - $span).','.($center['lat'] - $span * 0.7).','.($center['lng'] + $span).','.($center['lat'] + $span * 0.7).'&layer=mapnik'.(! empty($place['coordinates']['lat']) ? '&marker='.$center['lat'].','.$center['lng'] : '') : null;
    $months = ['', 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    $bestMonths = array_map('intval', (array) ($info['best_months'] ?? []));
    $travelInfo = array_filter([
        ['fa-calendar-check', 'Beste Reisezeit', $info['best_time'] ?? null],
        ['fa-temperature-half', 'Klima', $info['climate'] ?? null],
        ['fa-plane-arrival', 'Anreise', $info['getting_there'] ?? null],
        ['fa-route', 'Unterwegs', $info['getting_around'] ?? null],
        ['fa-utensils', 'Küche', $info['cuisine'] ?? null],
        ['fa-lightbulb', 'Gut zu wissen', $info['good_to_know'] ?? null],
    ], fn ($entry) => ! empty($entry[2]));
    $facts = array_filter([
        ['fa-users', 'Einwohner', ! empty($info['population']) ? number_format($info['population'], 0, ',', '.') : null],
        ['fa-ruler-combined', 'Fläche', ! empty($info['area_km2']) ? number_format($info['area_km2'], 0, ',', '.').' km²' : null],
        ['fa-clock', 'Zeitzone', $info['timezone'] ?? null],
        ['fa-city', 'Städte', $place['cities_count'] ?? count($cities) ?: null],
        ['fa-binoculars', 'Sehenswürdigkeiten', count($sights) ?: null],
        ['fa-layer-group', 'Teilgebiete', $isRegion ? ($place['subregions_count'] ?? null) : null],
    ], fn ($fact) => ! empty($fact[2]));
    $subregionGroups = collect($place['subregions'] ?? [])->groupBy(fn ($s) => $s['group_name'] ?? $s['kind_name'] ?? 'Teilgebiete');
    $description = $info['description'] ?? $place['description'] ?? null;
    $short = $info['short_description'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" class="scroll-smooth">
<head>
    @include('components.layouts.adminv2.head', ['title' => $place['name'].' · '.$countryName.' · Website-Ansicht'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-200">

@include('adminv2.country-website.topbar', [
    'backUrl' => $regionUrl ?? $countryUrl.'#regions',
    'backLabel' => $parentRegion ? 'Zurück zu '.$parentRegion['name'] : 'Zurück zu '.$countryName,
])

<header class="relative isolate overflow-hidden">
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-[#0b3250] to-[#13507d]"></div>
    @if (! empty($c['images']['hero_url']))
        <img src="{{ $c['images']['hero_url'] }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover" loading="eager">
    @endif
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/80 via-black/35 to-black/15"></div>
    <div class="mx-auto flex min-h-[19rem] max-w-6xl flex-col justify-end px-4 pb-10 pt-20 text-white">
        <nav class="mb-3 flex flex-wrap items-center gap-2 text-sm text-white/75" aria-label="Brotkrumen">
            @if (! empty($c['iso_code']))<img src="https://flagcdn.com/w40/{{ strtolower($c['iso_code']) }}.png" alt="" class="h-4 w-auto rounded-sm">@endif
            <a href="{{ $countryUrl }}" class="hover:text-white">{{ $countryName }}</a>
            @if ($parentRegion)
                <i class="fa-solid fa-chevron-right text-[10px] opacity-60"></i>
                <a href="{{ $regionUrl }}" class="hover:text-white">{{ $parentRegion['name'] }}</a>
            @endif
        </nav>
        <p class="text-sm font-medium uppercase tracking-[0.2em] text-white/70">{{ $kindLabel }}</p>
        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">{{ $place['name'] }}</h1>
        @if ($short)
            <p class="mt-4 max-w-3xl text-base leading-relaxed text-white/90 sm:text-lg">{{ $short }}</p>
        @endif
        <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold">
            @if (! empty($place['is_popular']))<span class="rounded-full bg-amber-400/90 px-3 py-1 text-amber-950"><i class="fa-solid fa-star me-1"></i>Beliebt</span>@endif
            @if (! empty($place['code']))<span class="rounded-full bg-white/15 px-3 py-1 font-mono backdrop-blur">{{ $place['code'] }}</span>@endif
            @if ($bestMonths)<span class="rounded-full bg-white/15 px-3 py-1 backdrop-blur"><i class="fa-regular fa-calendar me-1"></i>Beste Monate: {{ collect($bestMonths)->map(fn ($m) => $months[$m] ?? $m)->implode(', ') }}</span>@endif
        </div>
    </div>
</header>

<main class="mx-auto flex max-w-6xl flex-col gap-10 px-4 py-8">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            @if ($description)
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-white">Über {{ $place['name'] }}</h2>
                    <div class="space-y-3 leading-relaxed text-zinc-700 dark:text-zinc-300">{!! nl2br(e($description)) !!}</div>
                </article>
            @endif
            @if (! empty($info['known_for']))
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-white">Bekannt für</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($info['known_for'] as $item)
                            <span class="rounded-full bg-[#0b3250]/8 px-3 py-1.5 text-sm font-medium text-[#0b3250] ring-1 ring-[#0b3250]/15 dark:bg-lime-300/10 dark:text-lime-200 dark:ring-lime-300/20">{{ $item }}</span>
                        @endforeach
                    </div>
                </article>
            @endif
            @if ($travelInfo)
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Reiseinfos</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($travelInfo as [$icon, $label, $text])
                            <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/60">
                                <h3 class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white"><i class="fa-solid {{ $icon }} w-4 text-center text-[#0b3250] dark:text-lime-300"></i>{{ $label }}</h3>
                                <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $text }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endif
        </div>
        <aside class="flex flex-col gap-6">
            @if ($embed)
                <article class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    <iframe src="{{ $embed }}" class="h-64 w-full border-0" loading="lazy" title="Karte {{ $place['name'] }}"></iframe>
                    <div class="flex items-center justify-between p-4">
                        <div class="font-semibold text-zinc-900 dark:text-white">{{ $place['name'] }}</div>
                        <a href="{{ $osm($center['lat'], $center['lng'], 8) }}" target="_blank" rel="noopener" class="rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-semibold hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700">Karte <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i></a>
                    </div>
                </article>
            @endif
            @if ($facts)
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Fakten</h2>
                    <dl class="grid grid-cols-2 gap-3">
                        @foreach ($facts as [$icon, $label, $value])
                            <div class="rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-800/60"><dt class="text-[11px] uppercase tracking-wide text-zinc-500"><i class="fa-solid {{ $icon }} me-1"></i>{{ $label }}</dt><dd class="mt-0.5 font-semibold text-zinc-900 dark:text-white">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </article>
            @endif
            @if ($bestMonths)
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Beste Reisemonate</h2>
                    <div class="grid grid-cols-6 gap-1.5">
                        @foreach (range(1, 12) as $month)
                            <span class="rounded-lg py-1.5 text-center text-xs font-semibold {{ in_array($month, $bestMonths, true) ? 'bg-emerald-500 text-white' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800' }}">{{ $months[$month] }}</span>
                        @endforeach
                    </div>
                </article>
            @endif
        </aside>
    </div>

    @foreach ($subregionGroups as $group => $items)
        <section>
            <h2 class="mb-4 text-xl font-semibold text-zinc-900 dark:text-white">{{ $group }} <span class="ms-1 text-sm font-normal text-zinc-500">{{ $items->count() }}</span></h2>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $sub)
                    <a href="{{ route('adminv2.master-data.countries.website.subregion', ['country' => $country, 'subregion' => $sub['id'], 'lang' => $lang]) }}"
                       class="group flex flex-col rounded-3xl bg-white p-5 shadow-sm ring-1 ring-zinc-200/70 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-[#0b3250]/30 dark:bg-zinc-900 dark:ring-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-400/10 dark:text-sky-300"><i class="fa-solid {{ in_array($sub['kind'] ?? '', ['island', 'island_group'], true) ? 'fa-umbrella-beach' : 'fa-mountain-sun' }}"></i></div>
                            <div class="min-w-0 flex-1"><div class="font-semibold text-zinc-900 dark:text-white">{{ $sub['name'] }}</div><div class="text-xs text-zinc-500">{{ $sub['kind_name'] ?? '' }}</div></div>
                            <i class="fa-solid fa-arrow-right text-sm text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-[#0b3250] dark:group-hover:text-lime-300"></i>
                        </div>
                        @if (! empty($sub['description']))<p class="mt-3 line-clamp-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $sub['description'] }}</p>@endif
                        <div class="mt-auto flex gap-4 pt-3 text-xs text-zinc-500">
                            <span><i class="fa-solid fa-city me-1"></i>{{ $sub['cities_count'] ?? 0 }} Städte</span>
                            <span><i class="fa-solid fa-binoculars me-1"></i>{{ $sub['sights_count'] ?? 0 }} Sehenswert</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach

    <section>
        <h2 class="mb-4 text-xl font-semibold text-zinc-900 dark:text-white">Städte <span class="ms-1 text-sm font-normal text-zinc-500">{{ count($cities) }}</span></h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse (collect($cities)->sortByDesc(fn ($city) => [(int) ! empty($city['is_capital']), (int) ! empty($city['is_regional_capital']), (int) ! empty($city['is_popular']), $city['population'] ?? 0]) as $city)
                @include('adminv2.country-website.city-card', ['city' => $city])
            @empty
                @include('adminv2.country-website.empty', ['text' => 'Hier sind noch keine Städte zugeordnet.'])
            @endforelse
        </div>
    </section>

    <section>
        <h2 class="mb-4 text-xl font-semibold text-zinc-900 dark:text-white">Sehenswert <span class="ms-1 text-sm font-normal text-zinc-500">{{ count($sights) }}</span></h2>
        <div class="grid gap-4 md:grid-cols-2">
            @forelse (collect($sights)->sortByDesc(fn ($s) => (int) ! empty($s['is_highlight'])) as $sight)
                @include('adminv2.country-website.sight-card', ['sight' => $sight])
            @empty
                @include('adminv2.country-website.empty', ['text' => 'Hier sind noch keine Sehenswürdigkeiten zugeordnet.'])
            @endforelse
        </div>
    </section>
</main>

<footer class="border-t border-zinc-200 py-6 text-center text-xs text-zinc-500 dark:border-zinc-800">
    Passolution Travel Information Platform · {{ $place['name'] }} ({{ $countryName }}) · Daten wie in der TripData-App
</footer>
</body>
</html>
