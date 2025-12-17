<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $event->title }} - Global Travel Monitor</title>

    {{-- SEO Meta Tags --}}
    <meta name="description" content="{{ Str::limit(strip_tags($event->description ?? $event->popup_content ?? ''), 160) }}">
    <meta name="robots" content="index, follow">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ config('app.url') }}/?event={{ $event->id }}">
    <meta property="og:title" content="{{ $event->title }} - Global Travel Monitor">
    <meta property="og:description" content="{{ Str::limit(strip_tags($event->description ?? $event->popup_content ?? ''), 200) }}">
    @if($event->countries->isNotEmpty())
        @php
            $firstCountry = $event->countries->first();
            $countryImagePath = 'images/countries/' . strtolower($firstCountry->iso_code) . '.jpg';
        @endphp
        <meta property="og:image" content="{{ file_exists(public_path($countryImagePath)) ? asset($countryImagePath) : asset('og-image.png') }}">
    @else
        <meta property="og:image" content="{{ asset('og-image.png') }}">
    @endif
    <meta property="og:site_name" content="Global Travel Monitor">
    <meta property="og:locale" content="de_DE">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $event->title }} - GTM">
    <meta name="twitter:description" content="{{ Str::limit(strip_tags($event->description ?? $event->popup_content ?? ''), 200) }}">

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 0;
        }
        .priority-low { background-color: #22c55e; }
        .priority-info { background-color: #3b82f6; }
        .priority-medium { background-color: #f97316; }
        .priority-high { background-color: #ef4444; }
        .priority-critical { background-color: #7c2d12; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="px-4 py-3 flex items-center justify-between">
            <a href="{{ config('app.url') }}" class="p-2 -ml-2 text-gray-600">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div class="flex items-center gap-2">
                <img src="{{ asset('logo.png') }}" alt="GTM" class="h-6">
                <span class="font-semibold text-gray-800">Details</span>
            </div>
            <button onclick="shareEvent()" class="p-2 -mr-2 text-gray-600">
                <i class="fas fa-share-alt text-xl"></i>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pb-20">
        <!-- Country Image -->
        @if($event->countries->isNotEmpty())
            @php
                $firstCountry = $event->countries->first();
                $countryImagePath = 'images/countries/' . strtolower($firstCountry->iso_code) . '.jpg';
            @endphp
            @if(file_exists(public_path($countryImagePath)))
                <div class="w-full h-48 bg-gray-300">
                    <img src="{{ asset($countryImagePath) }}" alt="{{ $firstCountry->name }}" class="w-full h-full object-cover">
                </div>
            @endif
        @endif

        <div class="px-4 py-4">
            <!-- Countries (directly under image) -->
            @if($event->countries->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach($event->countries as $country)
                        <span class="bg-gray-200 text-gray-700 text-sm px-3 py-1 rounded-full flex items-center gap-2">
                            @if($country->iso_code)
                                @php
                                    $isoCode = strtoupper($country->iso_code);
                                    $flag = implode('', array_map(fn($c) => mb_chr(127397 + ord($c)), str_split($isoCode)));
                                @endphp
                                <span class="text-base">{{ $flag }}</span>
                            @endif
                            {{ $country->getName('de') ?? $country->name }}
                        </span>
                    @endforeach
                </div>
            @endif

            <!-- Priority Badge + Event Types (same row) -->
            <div class="flex flex-wrap items-center gap-2 mb-3">
                @php
                    $priorityClass = match($event->priority) {
                        'low' => 'priority-low',
                        'info' => 'priority-info',
                        'medium' => 'priority-medium',
                        'high' => 'priority-high',
                        'critical' => 'priority-critical',
                        default => 'priority-info'
                    };
                    $priorityText = match($event->priority) {
                        'low' => 'Niedrig',
                        'info' => 'Information',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'critical' => 'Kritisch',
                        default => 'Information'
                    };
                @endphp
                <span class="{{ $priorityClass }} text-white text-xs font-bold px-3 py-1 rounded-full">
                    {{ $priorityText }}
                </span>

                @if($event->eventTypes && $event->eventTypes->isNotEmpty())
                    @foreach($event->eventTypes as $type)
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                            {{ $type->name }}
                        </span>
                    @endforeach
                @elseif($event->eventType)
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                        {{ $event->eventType->name }}
                    </span>
                @endif
            </div>

            <!-- Title -->
            <h1 class="text-xl font-bold text-gray-900 mb-3">
                {{ $event->title }}
            </h1>

            <!-- Description -->
            <div class="bg-white rounded-lg shadow-sm p-4 mb-2">
                <div class="prose prose-sm max-w-none text-gray-700">
                    {!! $event->description ?? $event->popup_content ?? '' !!}
                </div>
            </div>
            <div class="flex items-center gap-1 px-0 mb-4 text-sm">
                <i class="fas fa-clock text-gray-400 w-5"></i>
                <div>
                    <span class="text-gray-500">Veröffentlicht:</span>
                    <span class="text-gray-900 ml-1">{{ $event->created_at->format('d.m.Y H:i') }}</span>
                </div>
            </div>

            {{-- Meta Info
            <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                <div class="space-y-3 text-sm">
                    @if($event->start_date)
                        <div class="flex items-center gap-3">
                            <i class="fas fa-calendar text-gray-400 w-5"></i>
                            <div>
                                <span class="text-gray-500">Gültig ab:</span>
                                <span class="text-gray-900 ml-1">{{ $event->start_date->format('d.m.Y H:i') }}</span>
                            </div>
                        </div>
                    @endif
                    @if($event->end_date)
                        <div class="flex items-center gap-3">
                            <i class="fas fa-calendar-check text-gray-400 w-5"></i>
                            <div>
                                <span class="text-gray-500">Gültig bis:</span>
                                <span class="text-gray-900 ml-1">{{ $event->end_date->format('d.m.Y H:i') }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            --}}

            <!-- Weather Section -->
            @if($event->countries->isNotEmpty())
                @php
                    $firstCountry = $event->countries->first();
                    $lat = null;
                    $lng = null;

                    // Same logic as CustomEventController::getCoordinatesForCountry()
                    if ($firstCountry->pivot->use_default_coordinates ?? true) {
                        // Priority: City > Region > Capital > Country

                        // 1. City coordinates
                        if ($firstCountry->pivot->city_id) {
                            $city = \App\Models\City::find($firstCountry->pivot->city_id);
                            if ($city && $city->lat && $city->lng) {
                                $lat = $city->lat;
                                $lng = $city->lng;
                            }
                        }

                        // 2. Region coordinates
                        if (!$lat && !$lng && $firstCountry->pivot->region_id) {
                            $region = \App\Models\Region::find($firstCountry->pivot->region_id);
                            if ($region && $region->lat && $region->lng) {
                                $lat = $region->lat;
                                $lng = $region->lng;
                            }
                        }

                        // 3. Capital coordinates
                        if (!$lat && !$lng && $firstCountry->capital && $firstCountry->capital->lat && $firstCountry->capital->lng) {
                            $lat = $firstCountry->capital->lat;
                            $lng = $firstCountry->capital->lng;
                        }

                        // 4. Country center (fallback)
                        if (!$lat && !$lng) {
                            $lat = $firstCountry->lat;
                            $lng = $firstCountry->lng;
                        }
                    } else {
                        // Use pivot coordinates
                        $lat = $firstCountry->pivot->latitude;
                        $lng = $firstCountry->pivot->longitude;
                    }

                    // Final fallback: event coordinates
                    if (!$lat && !$lng && $event->latitude && $event->longitude) {
                        $lat = $event->latitude;
                        $lng = $event->longitude;
                    }
                @endphp
                @if($lat && $lng)
                    <div class="bg-white rounded-lg shadow-sm p-4 mb-4" id="weather-section">
                        <h3 class="text-gray-800 mb-3 flex items-center justify-center gap-2">
                            <i class="fas fa-cloud-sun text-blue-500"></i>
                            Wetter vor Ort
                        </h3>
                        <div id="weather-loading" class="flex items-center gap-2 text-gray-500 text-sm">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Wetter wird geladen...</span>
                        </div>
                        <div id="weather-content" class="hidden">
                            <div class="flex items-center justify-center gap-4 mb-3">
                                <div class="text-4xl" id="weather-icon">
                                    <i class="fas fa-cloud-sun text-yellow-500"></i>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-800" id="weather-temp">--°C</div>
                                    <div class="text-sm text-gray-500" id="weather-desc">--</div>
                                </div>
                            </div>
                            <div class="flex justify-center gap-6 text-sm">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-tint text-blue-400 w-4"></i>
                                    <span class="text-gray-500">Luftfeuchtigkeit:</span>
                                    <span class="font-medium" id="weather-humidity">--%</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-wind text-green-400 w-4"></i>
                                    <span class="text-gray-500">Wind:</span>
                                    <span class="font-medium" id="weather-wind">-- km/h</span>
                                </div>
                            </div>
                        </div>
                        <div id="weather-error" class="hidden text-sm text-red-500">
                            <i class="fas fa-exclamation-circle"></i>
                            Wetter konnte nicht geladen werden
                        </div>
                    </div>

                    <!-- Timezone Section -->
                    <div class="bg-white rounded-lg shadow-sm p-4 mb-4" id="timezone-section">
                        <h3 class="text-gray-800 mb-3 flex items-center justify-center gap-2">
                            <i class="fas fa-clock text-blue-500"></i>
                            Uhrzeit vor Ort
                        </h3>
                        <div id="timezone-loading" class="flex items-center justify-center gap-2 text-gray-500 text-sm">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Zeit wird geladen...</span>
                        </div>
                        <div id="timezone-content" class="hidden text-center">
                            <div class="mb-3">
                                <div class="text-3xl font-bold text-gray-800" id="local-time">--:--</div>
                                <div class="text-sm text-gray-500" id="local-date">--.--.----</div>
                            </div>
                            <div class="flex justify-center gap-3 text-xs">
                                <span class="bg-gray-100 px-2 py-1 rounded" id="tz-name">--</span>
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded" id="tz-diff">--</span>
                            </div>
                        </div>
                        <div id="timezone-error" class="hidden text-sm text-red-500">
                            <i class="fas fa-exclamation-circle"></i>
                            Zeit konnte nicht geladen werden
                        </div>
                    </div>
                    <script>
                        (function() {
                            const lat = {{ $lat }};
                            const lng = {{ $lng }};

                            fetch('/api/gdacs/event-details', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ latitude: lat, longitude: lng })
                            })
                            .then(response => response.json())
                            .then(data => {
                                document.getElementById('weather-loading').classList.add('hidden');
                                document.getElementById('timezone-loading').classList.add('hidden');

                                // Weather data
                                if (data.success && data.data && data.data.weather) {
                                    const weather = data.data.weather;
                                    document.getElementById('weather-content').classList.remove('hidden');
                                    document.getElementById('weather-temp').textContent = weather.temperature + '°C';
                                    document.getElementById('weather-desc').textContent = weather.description || '--';
                                    document.getElementById('weather-humidity').textContent = weather.humidity + '%';
                                    document.getElementById('weather-wind').textContent = weather.wind_speed + ' km/h';

                                    // Update weather icon based on condition
                                    const iconEl = document.getElementById('weather-icon');
                                    const main = weather.main?.toLowerCase() || '';
                                    let iconClass = 'fa-cloud-sun text-yellow-500';
                                    if (main.includes('rain')) iconClass = 'fa-cloud-rain text-blue-500';
                                    else if (main.includes('cloud')) iconClass = 'fa-cloud text-gray-400';
                                    else if (main.includes('clear') || main.includes('sun')) iconClass = 'fa-sun text-yellow-500';
                                    else if (main.includes('snow')) iconClass = 'fa-snowflake text-blue-300';
                                    else if (main.includes('thunder')) iconClass = 'fa-bolt text-yellow-600';
                                    iconEl.innerHTML = '<i class="fas ' + iconClass + '"></i>';
                                } else {
                                    document.getElementById('weather-error').classList.remove('hidden');
                                }

                                // Timezone data
                                if (data.success && data.data && data.data.timezone) {
                                    const tz = data.data.timezone;
                                    document.getElementById('timezone-content').classList.remove('hidden');
                                    document.getElementById('local-time').textContent = tz.local_time || '--:--';
                                    document.getElementById('local-date').textContent = tz.local_date || '--.--.----';
                                    document.getElementById('tz-name').textContent = tz.timezone || '--';
                                    document.getElementById('tz-diff').textContent = tz.time_diff_to_berlin || '--';
                                } else {
                                    document.getElementById('timezone-error').classList.remove('hidden');
                                }
                            })
                            .catch(err => {
                                console.error('API error:', err);
                                document.getElementById('weather-loading').classList.add('hidden');
                                document.getElementById('weather-error').classList.remove('hidden');
                                document.getElementById('timezone-loading').classList.add('hidden');
                                document.getElementById('timezone-error').classList.remove('hidden');
                            });
                        })();
                    </script>
                @endif
            @endif

            <!-- Source -->
            <div class="text-center text-gray-500 text-xs mt-6">
                <img src="{{ asset('logo.png') }}" alt="Passolution" class="h-6 mx-auto mb-2">
                <p>Powered by Passolution GmbH</p>
            </div>
        </div>
    </main>

    <!-- Fixed Bottom Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-3 flex gap-2" style="padding-bottom: max(12px, env(safe-area-inset-bottom));">
        {{-- <a href="{{ config('app.url') }}/?view=map&event={{ $event->id }}"
           class="flex-1 bg-blue-600 text-white text-center py-3 rounded-lg font-medium flex items-center justify-center gap-2">
            <i class="fas fa-map-marker-alt"></i>
            Auf Karte
        </a> --}}
        <a href="{{ config('app.url') }}"
           class="flex-1 bg-gray-100 text-gray-700 text-center py-3 rounded-lg font-medium flex items-center justify-center gap-2">
            <i class="fas fa-list"></i>
            Zum Feed
        </a>
        <button onclick="shareEvent()"
                class="bg-gray-100 text-gray-700 px-4 py-3 rounded-lg font-medium flex items-center justify-center gap-2">
            <i class="fas fa-share-alt"></i>
        </button>
    </div>

    <script>
        function shareEvent() {
            if (navigator.share) {
                navigator.share({
                    title: '{{ addslashes($event->title) }}',
                    text: '{{ addslashes(Str::limit(strip_tags($event->description ?? $event->popup_content ?? ''), 100)) }}',
                    url: window.location.href
                });
            } else {
                // Fallback: Copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Link wurde kopiert!');
                });
            }
        }
    </script>
</body>
</html>
