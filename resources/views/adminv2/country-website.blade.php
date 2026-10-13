@php
    /** @var array $c Daten von GET /v1/countries/{code} */
    $name = $c['name'] ?? $country->iso_code;
    $risk = $c['risk_profile'] ?? null;
    $riskEntries = function (string $category, ?array $only = null) use ($risk): array {
        $fields = $risk['categories'][$category]['fields'] ?? [];
        $entries = [];
        foreach ($fields as $key => $field) {
            if ($only !== null && ! in_array($key, $only, true)) {
                continue;
            }
            $value = $field['value'] ?? null;
            $empty = $value === null || $value === '' || $value === [] || ($field['type'] === 'bool' && $value === false && empty($field['note']));
            if ($empty && empty($field['note'])) {
                continue;
            }
            $entries[$key] = $field;
        }

        return $entries;
    };
    $levelClass = fn (?int $level) => match ($level) {
        1 => 'bg-emerald-500', 2 => 'bg-lime-500', 3 => 'bg-amber-500', 4 => 'bg-orange-600', 5 => 'bg-red-600', default => 'bg-zinc-400',
    };
    $tipText = function (?array $tip): ?string {
        if (! $tip) {
            return null;
        }
        $from = $tip['from'] ?? null;
        $to = $tip['to'] ?? null;
        $unit = ($tip['unit'] ?? null) === 'percent' ? ' %' : ' '.($tip['currency'] ?? '');
        if ($from === null && $to === null) {
            return null;
        }
        $range = $from !== null && $to !== null && $from != $to ? $from.'–'.$to : ($to ?? $from);

        return trim($range.$unit);
    };
    $osm = fn ($lat, $lng, $zoom = 12) => 'https://www.openstreetmap.org/?mlat='.$lat.'&mlon='.$lng.'#map='.$zoom.'/'.$lat.'/'.$lng;
    $osmEmbed = function ($lat, $lng, float $span = 0.12) {
        return 'https://www.openstreetmap.org/export/embed.html?bbox='
            .($lng - $span).','.($lat - $span * 0.6).','.($lng + $span).','.($lat + $span * 0.6)
            .'&layer=mapnik&marker='.$lat.','.$lng;
    };
    $weekdays = ['', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
    $emergencyLabels = ['general' => ['Allgemeiner Notruf', 'fa-phone'], 'police' => ['Polizei', 'fa-shield-halved'], 'ambulance' => ['Rettungsdienst', 'fa-truck-medical'], 'fire' => ['Feuerwehr', 'fa-fire-extinguisher']];
    $regionNames = collect($regions)->pluck('name', 'id');
    $sightCategories = collect($sights)->pluck('category_name', 'category')->filter()->sort();
    $highlights = collect($sights)->where('is_highlight', true)->count();
    $facts = array_filter([
        ['Währung', isset($c['currency']['code']) ? trim(($c['currency']['name'] ?? '').' ('.$c['currency']['code'].')') : null, 'fa-coins'],
        ['Vorwahl', $c['phone_prefix'] ?? null, 'fa-phone'],
        ['Zeitzone', $c['timezone'] ?? null, 'fa-clock'],
        ['Sprachen', ! empty($c['languages']) ? collect($c['languages'])->map(fn ($l) => is_array($l) ? ($l['name'] ?? $l['code'] ?? '') : (\Locale::getDisplayLanguage($l, $lang) ?: strtoupper($l)))->implode(', ') : null, 'fa-language'],
        ['Einwohner', ! empty($c['population']) ? number_format($c['population'], 0, ',', '.') : null, 'fa-users'],
        ['Fläche', ! empty($c['area_km2']) ? number_format($c['area_km2'], 0, ',', '.').' km²' : null, 'fa-ruler-combined'],
        ['Verkehr', $c['travel_info']['driving_side_label'] ?? null, 'fa-road'],
        ['Nationalfeiertag', ! empty($c['travel_info']['national_day']['day_month']) ? (function ($nd) { [$m, $d] = explode('-', $nd['day_month']); return $d.'.'.$m.'. · '.($nd['name'] ?? ''); })($c['travel_info']['national_day']) : null, 'fa-flag'],
        ['Religionen', ! empty($c['travel_info']['religions']) ? collect($c['travel_info']['religions'])->pluck('name')->implode(', ') : null, 'fa-place-of-worship'],
    ], fn ($fact) => $fact[1] !== null && $fact[1] !== '');
    $tabs = [
        'general' => ['Allgemeines', 'fa-circle-info'],
        'regions' => ['Regionen', 'fa-map', count($regions)],
        'cities' => ['Städte', 'fa-city', count($cities)],
        'sights' => ['Sehenswert', 'fa-binoculars', count($sights)],
        'transport' => ['Transport', 'fa-car'],
        'money' => ['Geld', 'fa-money-bill-wave'],
        'emergency' => ['Notfall', 'fa-triangle-exclamation'],
        'weather' => ['Wetter', 'fa-cloud-sun'],
        'esim' => ['eSIM', 'fa-sim-card'],
        'visa' => ['Visa', 'fa-passport'],
        'health' => ['Gesundheit', 'fa-briefcase-medical'],
    ];
    // Zeitzone des Landes nur, wenn sie eine gueltige Kennung ist (manche Laender haben Freitext).
    $tz = in_array($c['timezone'] ?? null, timezone_identifiers_list(), true) ? $c['timezone'] : config('app.timezone');
    $currencyCode = $c['currency']['code'] ?? null;
    $rate = $currencyCode && $currencyCode !== 'EUR' ? ($rates['rates'][$currencyCode] ?? null) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" class="scroll-smooth">
<head>
    @include('components.layouts.adminv2.head', ['title' => $name.' · Website-Ansicht'])
    <style>
        .tab-panel { display: none; }
        .tab-panel.is-active { display: block; animation: fade .25s ease-out; }
        @keyframes fade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { scrollbar-width: none; }
    </style>
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-200">

{{-- Leiste oben: zurück, Sprache --}}
<div class="sticky top-0 z-30 border-b border-zinc-200/70 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-2 text-sm">
        <a href="{{ route('adminv2.master-data.countries.edit', $country) }}" class="inline-flex items-center gap-2 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
            <i class="fa-solid fa-arrow-left"></i> Zurück zum Land
        </a>
        <span class="hidden text-xs uppercase tracking-widest text-zinc-400 sm:block">Website-Ansicht · wie in TripData</span>
        <div class="flex items-center gap-1 rounded-full bg-zinc-100 p-1 dark:bg-zinc-900">
            @foreach ($languages as $code => $label)
                <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}" title="{{ $label }}"
                   class="rounded-full px-3 py-1 text-xs font-semibold {{ $lang === $code ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">{{ strtoupper($code) }}</a>
            @endforeach
        </div>
    </div>
</div>

{{-- Titelbild --}}
<header class="relative isolate overflow-hidden">
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-[#0b3250] to-[#13507d]"></div>
    @if (! empty($c['images']['hero_url']))
        <img src="{{ $c['images']['hero_url'] }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover" loading="eager">
    @endif
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/75 via-black/25 to-black/10"></div>
    <div class="mx-auto flex min-h-[22rem] max-w-6xl flex-col justify-end px-4 pb-10 pt-24 text-white sm:min-h-[26rem]">
        <div class="flex items-end gap-4">
            @if (! empty($c['iso_code']))
                <img src="https://flagcdn.com/w160/{{ strtolower($c['iso_code']) }}.png" alt="" class="h-12 w-auto rounded-md shadow-lg ring-1 ring-white/30 sm:h-14">
            @endif
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-white/70">{{ $c['continent']['name'] ?? '' }}</p>
                <h1 class="text-4xl font-bold tracking-tight sm:text-6xl">{{ $name }}</h1>
            </div>
        </div>
        @if (! empty($c['description']['short']))
            <p class="mt-5 max-w-3xl text-base leading-relaxed text-white/90 sm:text-lg">{{ $c['description']['short'] }}</p>
        @endif
        <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold">
            @if (! empty($c['territory']['type_label']))<span class="rounded-full bg-white/15 px-3 py-1 backdrop-blur">{{ $c['territory']['type_label'] }}</span>@endif
            @if (! empty($c['membership']['eu']))<span class="rounded-full bg-white/15 px-3 py-1 backdrop-blur">EU</span>@endif
            @if (! empty($c['membership']['schengen']))<span class="rounded-full bg-white/15 px-3 py-1 backdrop-blur">Schengen</span>@endif
            @if (! empty($risk['overall']['level']))
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 backdrop-blur"><span class="size-2 rounded-full {{ $levelClass($risk['overall']['level']) }}"></span>Risiko: {{ $risk['overall']['label'] }}</span>
            @endif
            @if (! empty($c['capital']['name']))<span class="rounded-full bg-white/15 px-3 py-1 backdrop-blur"><i class="fa-solid fa-landmark-dome me-1"></i>{{ $c['capital']['name'] }}</span>@endif
        </div>
    </div>
</header>

{{-- Reiter --}}
<nav class="sticky top-[45px] z-20 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
    <div class="no-scrollbar mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 py-2">
        @foreach ($tabs as $key => $tab)
            <button type="button" data-tab="{{ $key }}"
                    class="tab-button inline-flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-900">
                <i class="fa-solid {{ $tab[1] }}"></i>{{ $tab[0] }}
                @if (! empty($tab[2]))<span class="rounded-full bg-zinc-200/80 px-1.5 text-[11px] text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">{{ $tab[2] }}</span>@endif
            </button>
        @endforeach
    </div>
</nav>

<main class="mx-auto max-w-6xl px-4 py-8">

    {{-- ===================== Allgemeines ===================== --}}
    <section class="tab-panel" data-panel="general">
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                @if (! empty($c['description']['long']))
                    <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                        <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-white">Über {{ $name }}</h2>
                        <div class="space-y-3 leading-relaxed text-zinc-700 dark:text-zinc-300">{!! nl2br(e($c['description']['long'])) !!}</div>
                    </article>
                @endif
                @if (! empty($c['description']['known_for']))
                    <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                        <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-white">Bekannt für</h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($c['description']['known_for'] as $item)
                                <span class="rounded-full bg-[#0b3250]/8 px-3 py-1.5 text-sm font-medium text-[#0b3250] ring-1 ring-[#0b3250]/15 dark:bg-lime-300/10 dark:text-lime-200 dark:ring-lime-300/20">{{ $item }}</span>
                            @endforeach
                        </div>
                    </article>
                @endif
                @if ($facts)
                    <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Auf einen Blick</h2>
                        <dl class="grid gap-3 sm:grid-cols-2">
                            @foreach ($facts as [$label, $value, $icon])
                                <div class="flex items-start gap-3 rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-800/60">
                                    <i class="fa-solid {{ $icon }} mt-1 w-4 text-center text-[#0b3250] dark:text-lime-300"></i>
                                    <div><dt class="text-xs uppercase tracking-wide text-zinc-500">{{ $label }}</dt><dd class="font-medium text-zinc-900 dark:text-white">{{ $value }}</dd></div>
                                </div>
                            @endforeach
                        </dl>
                    </article>
                @endif
                @if (! empty($c['power']['voltage']) || ! empty($c['power']['plug_types']))
                    <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white"><i class="fa-solid fa-plug me-2 text-zinc-400"></i>Strom &amp; Stecker</h2>
                        <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">{{ collect([($c['power']['voltage'] ?? null) ? $c['power']['voltage'].' V' : null, ($c['power']['frequency'] ?? null) ? $c['power']['frequency'].' Hz' : null])->filter()->implode(' · ') }}</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($c['power']['plug_types'] ?? [] as $plug)
                                <div class="flex w-28 flex-col items-center gap-2 rounded-2xl bg-zinc-50 p-3 text-center dark:bg-zinc-800/60">
                                    <img src="{{ $plug['image'] }}" alt="Typ {{ $plug['type'] }}" class="h-14 w-14 dark:invert">
                                    <div class="text-sm font-semibold">Typ {{ $plug['type'] }}</div>
                                    <div class="text-[11px] leading-tight text-zinc-500">{{ $plug['description'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        @if (! empty($c['power']['notes']))<p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $c['power']['notes'] }}</p>@endif
                    </article>
                @endif
                @if (! empty($c['holidays']['items']))
                    <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white"><i class="fa-regular fa-calendar me-2 text-zinc-400"></i>Feiertage {{ $c['holidays']['year'] }}</h2>
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($c['holidays']['items'] as $holiday)
                                @php $past = $holiday['date'] < now()->toDateString(); @endphp
                                <li class="flex items-center gap-4 py-2.5 {{ $past ? 'opacity-50' : '' }}">
                                    <div class="w-16 shrink-0 text-center">
                                        <div class="text-lg font-bold leading-none text-zinc-900 dark:text-white">{{ \Illuminate\Support\Carbon::parse($holiday['date'])->format('d.m.') }}</div>
                                        <div class="text-[11px] uppercase text-zinc-500">{{ $weekdays[$holiday['weekday'] ?? 0] ?? '' }}</div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium">{{ $holiday['name'] }}</div>
                                        @if (! empty($holiday['comment']))<div class="text-xs text-zinc-500">{{ $holiday['comment'] }}</div>@endif
                                    </div>
                                    @if (! empty($holiday['is_national']))
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">landesweit</span>
                                    @else
                                        <span class="max-w-48 truncate rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400" title="{{ collect($holiday['regions'] ?? [])->map(fn ($r) => is_array($r) ? ($r['name'] ?? '') : $r)->implode(', ') }}">regional</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endif
            </div>
            <aside class="flex flex-col gap-6">
                @if (! empty($c['capital']['lat']))
                    <article class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                        <iframe src="{{ $osmEmbed($c['capital']['lat'], $c['capital']['lng']) }}" class="h-56 w-full border-0" loading="lazy" title="Karte {{ $c['capital']['name'] }}"></iframe>
                        <div class="flex items-center justify-between p-4">
                            <div><div class="text-xs uppercase tracking-wide text-zinc-500">Hauptstadt</div><div class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $c['capital']['name'] }}</div></div>
                            <a href="{{ $osm($c['capital']['lat'], $c['capital']['lng']) }}" target="_blank" rel="noopener" class="rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-semibold hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700">Karte <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i></a>
                        </div>
                    </article>
                @endif
                @if (! empty($c['images']['gallery']))
                    <article class="grid grid-cols-2 gap-2">
                        @foreach (array_slice($c['images']['gallery'], 0, 6) as $image)
                            @php $url = is_array($image) ? ($image['url'] ?? $image['src'] ?? null) : $image; @endphp
                            @if ($url)<img src="{{ $url }}" alt="" class="aspect-square w-full rounded-2xl object-cover" loading="lazy">@endif
                        @endforeach
                    </article>
                @endif
                @if (! empty($c['travel_info']['intro']))
                    <article class="rounded-3xl bg-[#0b3250] p-6 text-white shadow-sm">
                        <h2 class="mb-2 font-semibold">Reiseinfo</h2>
                        <p class="text-sm leading-relaxed text-white/85">{{ $c['travel_info']['intro'] }}</p>
                    </article>
                @endif
                <article class="rounded-3xl bg-white p-5 text-xs text-zinc-500 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    Stand der Daten: {{ ! empty($c['updated_at']) ? \Illuminate\Support\Carbon::parse($c['updated_at'])->timezone(config('app.timezone'))->format('d.m.Y H:i') : '–' }}<br>
                    Quelle: GET /v1/countries/{{ $c['iso_code'] }}?lang={{ $lang }}
                </article>
            </aside>
        </div>
    </section>

    {{-- ===================== Regionen ===================== --}}
    <section class="tab-panel" data-panel="regions">
        @include('adminv2.country-website.filter-bar', ['target' => 'regions', 'filters' => ['all' => 'Alle', 'popular' => 'Beliebt']])
        @php $subregionsByRegion = collect($subregions)->groupBy(fn ($s) => $s['region']['id'] ?? $s['region'] ?? 0); @endphp
        <div class="grid gap-4 md:grid-cols-2" data-list="regions">
            @forelse ($regions as $region)
                @php $subs = $subregionsByRegion->get($region['id'], collect()); @endphp
                <article data-item data-popular="{{ ! empty($region['is_popular']) ? 1 : 0 }}" data-search="{{ mb_strtolower($region['name']) }}"
                         class="flex flex-col rounded-3xl bg-white p-5 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $region['name'] }}</h3>
                        @if (! empty($region['is_popular']))<span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-400/15 dark:text-amber-300"><i class="fa-solid fa-star me-1"></i>Beliebt</span>@endif
                    </div>
                    @if (! empty($region['description']))<p class="mt-2 line-clamp-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $region['description'] }}</p>@endif
                    @if ($subs->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($subs as $sub)
                                <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300" title="{{ $sub['kind_name'] ?? '' }}">
                                    <i class="fa-solid {{ ($sub['kind'] ?? '') === 'island' || ($sub['kind'] ?? '') === 'island_group' ? 'fa-umbrella-beach' : 'fa-location-dot' }} me-1 text-zinc-400"></i>{{ $sub['name'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-auto flex items-center gap-4 pt-4 text-xs text-zinc-500">
                        <span><i class="fa-solid fa-city me-1"></i>{{ $region['cities_count'] ?? 0 }} Städte</span>
                        @if (! empty($region['subregions_count']))<span><i class="fa-solid fa-layer-group me-1"></i>{{ $region['subregions_count'] }} Teilgebiete</span>@endif
                        @if (! empty($region['coordinates']['lat']))<a class="ms-auto hover:text-zinc-900 dark:hover:text-white" href="{{ $osm($region['coordinates']['lat'], $region['coordinates']['lng'], 8) }}" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot me-1"></i>Karte</a>@endif
                    </div>
                </article>
            @empty
                @include('adminv2.country-website.empty', ['text' => 'Für dieses Land sind noch keine Regionen gepflegt.'])
            @endforelse
        </div>
    </section>

    {{-- ===================== Städte ===================== --}}
    <section class="tab-panel" data-panel="cities">
        @include('adminv2.country-website.filter-bar', ['target' => 'cities', 'filters' => ['all' => 'Alle', 'popular' => 'Beliebt'], 'regionOptions' => $regionNames])
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" data-list="cities">
            @forelse (collect($cities)->sortByDesc(fn ($city) => [(int) ! empty($city['is_capital']), (int) ! empty($city['is_popular']), $city['population'] ?? 0]) as $city)
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
            @empty
                @include('adminv2.country-website.empty', ['text' => 'Für dieses Land sind noch keine Städte gepflegt.'])
            @endforelse
        </div>
    </section>

    {{-- ===================== Sehenswert ===================== --}}
    <section class="tab-panel" data-panel="sights">
        @include('adminv2.country-website.filter-bar', ['target' => 'sights', 'filters' => ['all' => 'Alle', 'popular' => 'Highlights ('.$highlights.')'], 'regionOptions' => $regionNames, 'categoryOptions' => $sightCategories])
        <div class="grid gap-4 md:grid-cols-2" data-list="sights">
            @forelse (collect($sights)->sortByDesc(fn ($s) => (int) ! empty($s['is_highlight'])) as $sight)
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
            @empty
                @include('adminv2.country-website.empty', ['text' => 'Für dieses Land sind noch keine Sehenswürdigkeiten gepflegt.'])
            @endforelse
        </div>
    </section>

    {{-- ===================== Transport ===================== --}}
    <section class="tab-panel" data-panel="transport">
        <div class="grid items-start gap-6 lg:grid-cols-2">
            @if (! empty($c['airlines']))
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white"><i class="fa-solid fa-plane me-2 text-zinc-400"></i>Airlines</h2>
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($c['airlines'] as $airline)
                            <li class="flex items-center gap-3 py-2.5">
                                <span class="w-10 shrink-0 rounded-md bg-zinc-100 py-0.5 text-center font-mono text-xs font-semibold dark:bg-zinc-800">{{ $airline['iata_code'] ?? '–' }}</span>
                                <div class="min-w-0 flex-1"><div class="truncate font-medium">{{ $airline['name'] }}</div>@if (! empty($airline['headquarters']))<div class="truncate text-xs text-zinc-500">{{ $airline['headquarters'] }}</div>@endif</div>
                                @if (! empty($airline['booking_url'] ?? $airline['website_url'] ?? null))<a href="{{ $airline['booking_url'] ?? $airline['website_url'] }}" target="_blank" rel="noopener" class="text-zinc-400 hover:text-zinc-900 dark:hover:text-white"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>@endif
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endif
            <div class="flex flex-col gap-6">
                @php $infra = $riskEntries('infrastructure', ['infrastructure_level', 'road_safety', 'public_transport_quality']); @endphp
                @if (! empty($c['travel_info']['driving_side_label']) || $infra)
                    <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white"><i class="fa-solid fa-road me-2 text-zinc-400"></i>Straßenverkehr</h2>
                        @if (! empty($c['travel_info']['driving_side_label']))
                            <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-zinc-100 px-3 py-1.5 text-sm font-medium dark:bg-zinc-800"><i class="fa-solid fa-car-side {{ ($c['travel_info']['driving_side'] ?? '') === 'left' ? '-scale-x-100' : '' }}"></i>{{ $c['travel_info']['driving_side_label'] }}</div>
                        @endif
                        @include('adminv2.country-website.risk-rows', ['entries' => $infra])
                    </article>
                @endif
                @if (! empty($c['taxi_apps']))
                    <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white"><i class="fa-solid fa-taxi me-2 text-zinc-400"></i>Taxi-Apps</h2>
                        @include('adminv2.country-website.app-list', ['items' => $c['taxi_apps']])
                    </article>
                @endif
                @if (! empty($c['airports']))
                    <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white"><i class="fa-solid fa-plane-arrival me-2 text-zinc-400"></i>Flughäfen</h2>
                        <ul class="grid gap-2 sm:grid-cols-2">
                            @foreach ($c['airports'] as $airport)
                                <li class="flex items-center gap-3 rounded-2xl bg-zinc-50 p-2.5 dark:bg-zinc-800/60">
                                    <span class="w-12 shrink-0 rounded-md bg-white py-1 text-center font-mono text-sm font-bold shadow-sm dark:bg-zinc-900">{{ $airport['iata_code'] ?? '–' }}</span>
                                    <div class="min-w-0"><div class="truncate text-sm font-medium">{{ $airport['name'] }}</div><div class="truncate text-xs text-zinc-500">{{ collect([$airport['city'] ?? null, ($airport['type'] ?? '') === 'international' ? 'international' : null])->filter()->implode(' · ') }}</div></div>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endif
            </div>
            @if (empty($c['airlines']) && empty($c['airports']) && empty($c['taxi_apps']) && empty($c['travel_info']['driving_side_label']) && ! $infra)
                @include('adminv2.country-website.empty', ['text' => 'Zum Transport liegen noch keine Angaben vor.'])
            @endif
        </div>
    </section>

    {{-- ===================== Geld ===================== --}}
    <section class="tab-panel" data-panel="money">
        <div class="grid items-start gap-6 lg:grid-cols-2">
            @if (! empty($c['currency']['code']))
                <article class="rounded-3xl bg-gradient-to-br from-[#0b3250] to-[#13507d] p-6 text-white shadow-sm">
                    <div class="text-xs uppercase tracking-widest text-white/60">Währung</div>
                    <div class="mt-1 flex items-baseline gap-3"><span class="text-5xl font-bold">{{ $c['currency']['symbol'] ?? $c['currency']['code'] }}</span><span class="text-lg">{{ $c['currency']['name'] }} ({{ $c['currency']['code'] }})</span></div>
                    @if ($c['currency']['code'] === 'EUR')
                        <p class="mt-4 text-sm text-white/80">Hier wird mit Euro bezahlt – keine Umrechnung nötig.</p>
                    @elseif ($rate)
                        <div class="mt-6 grid grid-cols-[1fr_auto_1fr] items-end gap-3" data-converter data-rate="{{ $rate }}">
                            <label class="text-xs text-white/70">EUR<input type="number" value="100" min="0" step="any" data-from class="mt-1 w-full rounded-xl border-0 bg-white/15 px-3 py-2 text-lg font-semibold text-white focus:ring-2 focus:ring-lime-300"></label>
                            <i class="fa-solid fa-right-left mb-3 text-white/60"></i>
                            <label class="text-xs text-white/70">{{ $c['currency']['code'] }}<input type="number" min="0" step="any" data-to class="mt-1 w-full rounded-xl border-0 bg-white/15 px-3 py-2 text-lg font-semibold text-white focus:ring-2 focus:ring-lime-300"></label>
                        </div>
                        <p class="mt-3 text-xs text-white/60">1 EUR = {{ number_format($rate, 4, ',', '.') }} {{ $c['currency']['code'] }}{{ ! empty($rates['updated_at']) ? ' · Stand '.\Illuminate\Support\Carbon::parse($rates['updated_at'])->format('d.m.Y H:i') : '' }}{{ ! empty($rates['source']) ? ' · '.(is_array($rates['source']) ? ($rates['source']['name'] ?? '') : $rates['source']) : '' }}</p>
                    @else
                        <p class="mt-4 text-sm text-white/80">Für diese Währung liegt gerade kein Wechselkurs vor.</p>
                    @endif
                </article>
            @endif
            @php $tips = array_filter($c['tipping'] ?? []); $tipLabels = ['hotels' => ['Hotel', 'fa-bed'], 'restaurants' => ['Restaurant', 'fa-utensils'], 'taxi' => ['Taxi', 'fa-taxi'], 'guides' => ['Reiseleitung', 'fa-person-hiking']]; @endphp
            @if ($tips)
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white"><i class="fa-solid fa-hand-holding-dollar me-2 text-zinc-400"></i>Trinkgeld</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($tipLabels as $key => [$label, $icon])
                            @continue(empty($tips[$key]))
                            <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/60">
                                <div class="flex items-center justify-between"><span class="text-sm font-semibold"><i class="fa-solid {{ $icon }} me-2 text-zinc-400"></i>{{ $label }}</span>@if ($tipText($tips[$key]))<span class="rounded-full bg-white px-2 py-0.5 text-xs font-bold shadow-sm dark:bg-zinc-900">{{ $tipText($tips[$key]) }}</span>@endif</div>
                                @if (! empty($tips[$key]['description']))<p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $tips[$key]['description'] }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                </article>
            @endif
            @if (empty($c['currency']['code']) && ! $tips)
                @include('adminv2.country-website.empty', ['text' => 'Zum Geld liegen noch keine Angaben vor.'])
            @endif
        </div>
    </section>

    {{-- ===================== Notfall ===================== --}}
    <section class="tab-panel" data-panel="emergency">
        @php $numbers = array_filter($c['travel_info']['emergency'] ?? []); $security = $riskEntries('security'); $hazards = $riskEntries('natural_hazards'); @endphp
        @if ($numbers)
            <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($numbers as $key => $number)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $number) }}" class="group flex items-center gap-4 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-zinc-200/70 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-zinc-900 dark:ring-zinc-800">
                        <div class="flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400"><i class="fa-solid {{ $emergencyLabels[$key][1] ?? 'fa-phone' }} text-lg"></i></div>
                        <div><div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $number }}</div><div class="text-xs text-zinc-500">{{ $emergencyLabels[$key][0] ?? ucfirst($key) }}</div></div>
                    </a>
                @endforeach
            </div>
        @endif
        <div class="grid gap-6 lg:grid-cols-2">
            @if ($security)<article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800"><h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Sicherheit</h2>@include('adminv2.country-website.risk-rows', ['entries' => $security])</article>@endif
            @if ($hazards)<article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800"><h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Naturgefahren</h2>@include('adminv2.country-website.risk-rows', ['entries' => $hazards])</article>@endif
        </div>
        @if (! $numbers && ! $security && ! $hazards)
            @include('adminv2.country-website.empty', ['text' => 'Zu Notfällen liegen noch keine Angaben vor.'])
        @endif
    </section>

    {{-- ===================== Wetter ===================== --}}
    <section class="tab-panel" data-panel="weather">
        @php $now = $weather['current'] ?? null; $climate = $riskEntries('climate'); @endphp
        @if ($now)
            <article class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-sky-500 to-indigo-600 p-6 text-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-6">
                    <div>
                        <div class="text-sm text-white/80"><i class="fa-solid fa-location-dot me-1"></i>{{ $weather['location']['name'] ?? ($c['capital']['name'] ?? $name) }}</div>
                        <div class="mt-1 flex items-center gap-3">
                            <span class="text-6xl font-bold">{{ round($now['temperature']) }}°</span>
                            @if (! empty($now['icon_url']))<img src="{{ $now['icon_url'] }}" alt="" class="size-20">@endif
                        </div>
                        <div class="text-lg capitalize">{{ $now['description'] ?? '' }}</div>
                        <div class="text-sm text-white/80">gefühlt {{ round($now['feels_like'] ?? $now['temperature']) }}° · H {{ round($now['temp_max'] ?? 0) }}° · T {{ round($now['temp_min'] ?? 0) }}°</div>
                    </div>
                    <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                        @foreach (array_filter([
                            ['fa-wind', 'Wind', isset($now['wind_speed']) ? round($now['wind_speed']).' km/h' : null], // kommt schon in km/h
                            ['fa-droplet', 'Feuchte', isset($now['humidity']) ? $now['humidity'].' %' : null],
                            ['fa-gauge', 'Druck', isset($now['pressure']) ? $now['pressure'].' hPa' : null],
                            ['fa-eye', 'Sicht', isset($now['visibility']) ? ($now['visibility'] >= 10 ? 'über 10 km' : number_format($now['visibility'], 1, ',', '.').' km') : null], // kommt schon in km
                            ['fa-sun', 'Aufgang', ! empty($now['sunrise']) ? \Illuminate\Support\Carbon::parse($now['sunrise'])->timezone($tz)->format('H:i') : null],
                            ['fa-moon', 'Untergang', ! empty($now['sunset']) ? \Illuminate\Support\Carbon::parse($now['sunset'])->timezone($tz)->format('H:i') : null],
                        ], fn ($x) => $x[2] !== null) as [$icon, $label, $value])
                            <div class="rounded-2xl bg-white/15 px-3 py-2 backdrop-blur"><dt class="text-[11px] uppercase text-white/70"><i class="fa-solid {{ $icon }} me-1"></i>{{ $label }}</dt><dd class="font-semibold">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </div>
                @if (! empty($weather['hourly']))
                    <div class="no-scrollbar mt-6 flex gap-2 overflow-x-auto">
                        @foreach (array_slice($weather['hourly'], 0, 12) as $hour)
                            <div class="flex w-16 shrink-0 flex-col items-center rounded-2xl bg-white/10 py-2 text-xs">
                                <span>{{ \Illuminate\Support\Carbon::parse($hour['time'])->timezone($tz)->format('H') }} Uhr</span>
                                @if (! empty($hour['icon']))<img src="https://openweathermap.org/img/wn/{{ $hour['icon'] }}.png" alt="" class="size-9">@endif
                                <span class="font-semibold">{{ round($hour['temperature']) }}°</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
            @if (! empty($weather['daily']))
                @php $min = collect($weather['daily'])->min('temp_min'); $max = collect($weather['daily'])->max('temp_max'); $span = max(1, $max - $min); @endphp
                <article class="mb-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-white">Die nächsten Tage</h2>
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($weather['daily'] as $day)
                            <li class="flex items-center gap-4 py-2 text-sm">
                                <span class="w-20 font-medium">{{ \Illuminate\Support\Carbon::parse($day['date'])->locale($lang)->isoFormat('dd, D.M.') }}</span>
                                @if (! empty($day['icon']))<img src="https://openweathermap.org/img/wn/{{ $day['icon'] }}.png" alt="" class="size-8">@endif
                                <span class="w-10 text-right text-zinc-500">{{ round($day['temp_min']) }}°</span>
                                <div class="relative h-1.5 flex-1 rounded-full bg-zinc-100 dark:bg-zinc-800"><div class="absolute h-1.5 rounded-full bg-gradient-to-r from-sky-400 to-amber-400" style="left: {{ ($day['temp_min'] - $min) / $span * 100 }}%; right: {{ 100 - ($day['temp_max'] - $min) / $span * 100 }}%"></div></div>
                                <span class="w-10 font-semibold">{{ round($day['temp_max']) }}°</span>
                                <span class="hidden w-14 text-right text-xs text-sky-600 sm:block">{{ ! empty($day['precipitation_probability']) ? round($day['precipitation_probability'] * (($day['precipitation_probability'] <= 1) ? 100 : 1)).' %' : '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if (! empty($weather['source']))<p class="mt-3 text-xs text-zinc-500">Quelle: {{ is_array($weather['source']) ? ($weather['source']['name'] ?? 'OpenWeatherMap') : $weather['source'] }}</p>@endif
                </article>
            @endif
        @else
            <div class="mb-6">@include('adminv2.country-website.empty', ['text' => 'Aktuelle Wetterdaten sind gerade nicht verfügbar.'])</div>
        @endif
        @if ($climate)
            <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800"><h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Klima</h2>@include('adminv2.country-website.risk-rows', ['entries' => $climate])</article>
        @endif
    </section>

    {{-- ===================== eSIM ===================== --}}
    <section class="tab-panel" data-panel="esim">
        @if (! empty($c['mobile_operators']))
            <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white"><i class="fa-solid fa-signal me-2 text-zinc-400"></i>Mobilfunkanbieter</h2>
                @include('adminv2.country-website.app-list', ['items' => $c['mobile_operators'], 'esim' => true])
            </article>
        @else
            @include('adminv2.country-website.empty', ['text' => 'Zu Mobilfunk und eSIM liegen noch keine Angaben vor.'])
        @endif
    </section>

    {{-- ===================== Visa / Gesundheit ===================== --}}
    @foreach (['visa' => ['entry', 'Einreise & Visum', 'Zu Einreise und Visum liegen noch keine Angaben vor.'], 'health' => ['health', 'Gesundheit', 'Zur Gesundheit liegen noch keine Angaben vor.']] as $panel => [$category, $heading, $emptyText])
        <section class="tab-panel" data-panel="{{ $panel }}">
            @php $entries = $riskEntries($category); @endphp
            @if ($entries)
                <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800"><h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">{{ $heading }}</h2>@include('adminv2.country-website.risk-rows', ['entries' => $entries])</article>
            @else
                @include('adminv2.country-website.empty', ['text' => $emptyText])
            @endif
        </section>
    @endforeach
</main>

<footer class="border-t border-zinc-200 py-6 text-center text-xs text-zinc-500 dark:border-zinc-800">
    Passolution Travel Information Platform · Website-Ansicht von {{ $name }} · Daten wie in der TripData-App
</footer>

<script>
(() => {
    const buttons = [...document.querySelectorAll('.tab-button')];
    const panels = [...document.querySelectorAll('.tab-panel')];
    const activate = (key) => {
        if (!panels.some((p) => p.dataset.panel === key)) key = 'general';
        panels.forEach((p) => p.classList.toggle('is-active', p.dataset.panel === key));
        buttons.forEach((b) => {
            const on = b.dataset.tab === key;
            ['bg-[#0b3250]', 'text-white', 'dark:bg-lime-300', 'dark:text-[#0b3250]'].forEach((c) => b.classList.toggle(c, on));
            ['text-zinc-600', 'dark:text-zinc-400', 'hover:bg-zinc-100', 'dark:hover:bg-zinc-900'].forEach((c) => b.classList.toggle(c, !on));
            if (on) b.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
        });
        history.replaceState(null, '', '#' + key);
    };
    buttons.forEach((b) => b.addEventListener('click', () => activate(b.dataset.tab)));
    activate(location.hash.slice(1) || 'general');

    // Filter: Alle/Beliebt, Region, Kategorie, Suche – je Liste
    document.querySelectorAll('[data-filter-bar]').forEach((bar) => {
        const list = document.querySelector(`[data-list="${bar.dataset.filterBar}"]`);
        const state = { mode: 'all', region: '', category: '', q: '' };
        const apply = () => {
            let shown = 0;
            list.querySelectorAll('[data-item]').forEach((item) => {
                const ok = (state.mode === 'all' || item.dataset.popular === '1')
                    && (!state.region || item.dataset.region === state.region)
                    && (!state.category || item.dataset.category === state.category)
                    && (!state.q || item.dataset.search.includes(state.q));
                item.style.display = ok ? '' : 'none';
                if (ok) shown++;
            });
            bar.querySelector('[data-count]').textContent = shown;
        };
        bar.querySelectorAll('[data-mode]').forEach((btn) => btn.addEventListener('click', () => {
            state.mode = btn.dataset.mode;
            bar.querySelectorAll('[data-mode]').forEach((b) => b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'));
            apply();
        }));
        bar.querySelector('[data-region-select]')?.addEventListener('change', (e) => { state.region = e.target.value; apply(); });
        bar.querySelector('[data-category-select]')?.addEventListener('change', (e) => { state.category = e.target.value; apply(); });
        bar.querySelector('[data-search-input]')?.addEventListener('input', (e) => { state.q = e.target.value.trim().toLowerCase(); apply(); });
        apply();
    });

    // Waehrungsrechner
    document.querySelectorAll('[data-converter]').forEach((box) => {
        const rate = parseFloat(box.dataset.rate), from = box.querySelector('[data-from]'), to = box.querySelector('[data-to]');
        const fmt = (v) => (Math.round(v * 100) / 100).toString();
        from.addEventListener('input', () => { to.value = fmt((parseFloat(from.value) || 0) * rate); });
        to.addEventListener('input', () => { from.value = fmt((parseFloat(to.value) || 0) / rate); });
        to.value = fmt(100 * rate);
    });
})();
</script>
</body>
</html>
