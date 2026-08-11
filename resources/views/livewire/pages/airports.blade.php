@php
    $active = 'airports';
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Flughäfen - Passolution Travel Information Platform</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Leaflet MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <!-- Font Awesome -->
    @php($faKit = config('services.fontawesome.kit'))
    @if(!empty($faKit))
        <script src="https://kit.fontawesome.com/{{ e($faKit) }}.js" crossorigin="anonymous" onload="window.__faKitOk=true" onerror="window.__faKitOk=false"></script>
        <script>
        (function(){
            function addCss(href){
                var l=document.createElement('link'); l.rel='stylesheet'; l.href=href; document.head.appendChild(l);
            }
            var fallbackHref = '{{ file_exists(public_path('vendor/fontawesome/css/all.min.css')) ? asset('vendor/fontawesome/css/all.min.css') : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css' }}';
            window.addEventListener('DOMContentLoaded', function(){
                setTimeout(function(){ if(!window.__faKitOk){ addCss(fallbackHref); } }, 800);
            });
        })();
        </script>
    @elseif (file_exists(public_path('vendor/fontawesome/css/all.min.css')))
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" />
    @else
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
    @endif

    <style>
        [x-cloak] { display: none !important; }

        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .header {
            flex-shrink: 0;
            height: 64px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            z-index: 10000;
        }

        .footer {
            flex-shrink: 0;
            height: 32px;
            background: white;
            color: black;
            z-index: 9999;
            border-top: 1px solid #e5e7eb;
        }

        .main-content {
            flex: 1;
            display: flex;
            min-height: 0;
        }

        .navigation {
            flex-shrink: 0;
            width: 64px;
            background: black;
        }

        .sidebar {
            flex-shrink: 0;
            width: 350px;
            background: #f9fafb;
            overflow-y: auto;
            height: 100%;
            border-right: 1px solid #e5e7eb;
        }

        .map-container {
            flex: 1;
            position: relative;
            min-height: 0;
        }

        #airports-map {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .airport-marker {
            background: #3b82f6;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        .airport-marker:hover {
            transform: scale(1.15);
            transition: transform 0.15s ease;
        }

        .loading-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="app-container" x-data="airportsApp()">
    <!-- Header -->
    <x-public-header />

    @auth('customer')
        <x-page-tour
            tourKey="airports"
            tourLabel="Flughäfen"
            tourIcon="fa-regular fa-plane"
            :steps='json_encode([
                ["target" => ".sidebar", "title" => "Flughafensuche", "description" => "In der Seitenleiste können Sie nach Flughäfen suchen &ndash; nach Name, IATA-Code oder Stadt. Nutzen Sie die Filter, um die Ergebnisse einzugrenzen."],
                ["target" => ".content-area", "title" => "Flughafeninformationen", "description" => "Im Hauptbereich werden Details zum ausgewählten Flughafen angezeigt: Standort auf der Karte, Kontaktdaten, Terminals und aktuelle Informationen.", "forceBelow" => true],
            ])'
        />
    @endauth

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navigation -->
        <x-public-navigation :active="$active" />

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="p-4">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fa-regular fa-plane mr-2"></i>
                    Flughäfen
                </h2>

                <!-- Search -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Suche</label>
                    <div class="relative">
                        <input type="text"
                               x-model="searchQuery"
                               @input.debounce.300ms="search()"
                               @keydown.enter="search()"
                               placeholder="Name, IATA oder ICAO..."
                               class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <i class="fa-regular fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>

                <!-- Country Filter -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Land</label>
                    <select x-model="countryFilter" @change="search()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Alle Länder</option>
                        <template x-for="country in countries" :key="country.id">
                            <option :value="country.id" x-text="country.name"></option>
                        </template>
                    </select>
                </div>

                <!-- Loading -->
                <template x-if="loading">
                    <div class="flex items-center justify-center py-8">
                        <i class="fa-regular fa-spinner-third fa-spin text-2xl text-blue-500"></i>
                    </div>
                </template>

                <!-- Results -->
                <template x-if="!loading">
                    <div>
                        <div class="text-sm text-gray-500 mb-2" x-show="airports.length > 0">
                            <span x-text="airports.length"></span> Flughafen/Flughäfen gefunden
                        </div>

                        <div class="space-y-2 max-h-[calc(100vh-350px)] overflow-y-auto">
                            <template x-for="airport in airports" :key="airport.id">
                                <div class="bg-white p-3 rounded-lg border border-gray-200 cursor-pointer hover:border-blue-500 hover:shadow-md transition-all"
                                     :class="{ 'border-blue-500 bg-blue-50': selectedAirport?.id === airport.id }"
                                     @click="selectAirport(airport)">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-medium text-gray-900 text-sm truncate" x-text="airport.name"></h3>
                                            <p class="text-xs text-gray-500 mt-0.5" x-text="airport.country?.name || 'Unbekannt'"></p>
                                        </div>
                                        <div class="flex-shrink-0 ml-2 text-right">
                                            <span class="inline-block bg-blue-100 text-blue-800 text-xs font-mono px-2 py-0.5 rounded" x-text="airport.iata_code || '-'"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="airports.length === 0 && searchQuery.length > 0" class="text-center py-8 text-gray-500">
                            <i class="fa-regular fa-plane-slash text-3xl mb-2"></i>
                            <p>Keine Flughäfen gefunden</p>
                        </div>

                        <div x-show="airports.length === 0 && searchQuery.length === 0 && !countryFilter" class="text-center py-8 text-gray-500">
                            <i class="fa-regular fa-search text-3xl mb-2"></i>
                            <p>Suchen Sie nach Flughäfen</p>
                            <p class="text-xs mt-1">Name, IATA-Code oder ICAO-Code eingeben</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Map -->
        <div class="map-container">
            <div id="airports-map"></div>

            <!-- Selected Airport Info -->
            <div x-show="selectedAirport" x-cloak
                 class="absolute bottom-4 left-4 right-4 bg-white rounded-xl shadow-2xl max-w-md z-[1000] overflow-hidden max-h-[calc(100%-2rem)] overflow-y-auto">
                <!-- Header -->
                <div class="p-4 pb-3">
                    <div class="absolute top-3 right-3 flex items-center gap-1.5">
                        <button @click="toggleAllSections()"
                                class="text-gray-400 hover:text-blue-600 transition-colors" :title="allSectionsOpen ? 'Alle zuklappen' : 'Alle aufklappen'">
                            <i class="fa-regular text-sm" :class="allSectionsOpen ? 'fa-compress' : 'fa-expand'"></i>
                        </button>
                        <button @click="selectedAirport = null; airportAirlines = []; airlinesOpen = false; hotelsOpen = false; loungesOpen = false; mobilityOpen = false;" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fa-regular fa-xmark text-lg"></i>
                        </button>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fa-regular fa-plane text-blue-600 text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-900" x-text="selectedAirport?.name"></h3>
                            <p class="text-sm text-gray-500" x-text="selectedAirport?.country?.name"></p>
                            <p x-show="selectedAirport?.type_label" class="text-xs text-gray-500 mt-0.5" x-text="selectedAirport?.type_label"></p>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="inline-flex items-center bg-blue-100 text-blue-800 text-xs font-mono px-2 py-1 rounded">
                                    IATA: <span class="ml-1 font-bold" x-text="selectedAirport?.iata_code || '-'"></span>
                                </span>
                                <span x-show="selectedAirport?.operates_24h"
                                      class="inline-flex items-center gap-1 bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                                    <i class="fa-regular fa-clock text-[10px]"></i> 24/7 - Betrieb
                                </span>
                                <span x-show="selectedAirport && !selectedAirport?.operates_24h"
                                      class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded">
                                    <i class="fa-regular fa-clock text-[10px]"></i> Eingeschränkte Betriebszeit
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 mt-2">
                                <a x-show="selectedAirport?.website" :href="selectedAirport?.website" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 transition-colors">
                                    <i class="fa-regular fa-globe text-[10px]"></i> Website
                                </a>
                                <a x-show="selectedAirport?.security_timeslot_url" :href="selectedAirport?.security_timeslot_url" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 transition-colors">
                                    <i class="fa-regular fa-shield-check text-[10px]"></i> Slot für Sicherheitskontrolle buchen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Airlines Accordion -->
                <div class="border-t border-gray-100">
                    <button @click="toggleAirlines()"
                            class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        <span class="flex items-center gap-2">
                            <i class="fa-regular fa-buildings text-blue-500"></i>
                            Airlines am Flughafen
                            <span x-show="airportAirlines.length > 0"
                                  class="inline-flex items-center justify-center bg-blue-500 text-white text-xs font-bold rounded-full w-5 h-5"
                                  x-text="airportAirlines.length"></span>
                        </span>
                        <i class="fa-regular fa-chevron-down text-gray-400 transition-transform duration-200"
                           :class="{ 'rotate-180': airlinesOpen }"></i>
                    </button>

                    <div x-show="airlinesOpen" x-collapse>
                        <!-- Loading -->
                        <div x-show="airlinesLoading" class="px-4 py-6 text-center">
                            <i class="fa-regular fa-spinner-third fa-spin text-blue-500 text-lg"></i>
                            <p class="text-xs text-gray-400 mt-2">Airlines werden geladen...</p>
                        </div>

                        <!-- No airlines -->
                        <div x-show="!airlinesLoading && airportAirlines.length === 0" class="px-4 py-5 text-center">
                            <i class="fa-regular fa-plane-slash text-gray-300 text-2xl"></i>
                            <p class="text-xs text-gray-400 mt-2">Keine Airlines hinterlegt</p>
                        </div>

                        <!-- Airlines list -->
                        <div x-show="!airlinesLoading && airportAirlines.length > 0"
                             class="divide-y divide-gray-50">
                            <template x-for="airline in airportAirlines" :key="airline.id">
                                <div class="px-4 py-3 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="airline.name"></p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span x-show="airline.iata_code"
                                                      class="inline-flex items-center bg-blue-50 text-blue-700 text-[10px] font-mono font-bold px-1.5 py-0.5 rounded">
                                                    <span x-text="airline.iata_code"></span>
                                                </span>
                                                <span x-show="airline.icao_code"
                                                      class="inline-flex items-center bg-gray-100 text-gray-600 text-[10px] font-mono px-1.5 py-0.5 rounded">
                                                    <span x-text="airline.icao_code"></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div x-show="airline.terminal"
                                             class="flex-shrink-0 text-right">
                                            <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 text-[10px] font-semibold px-2 py-1 rounded-md border border-amber-200">
                                                <i class="fa-regular fa-door-open text-[9px]"></i>
                                                Terminal <span x-text="airline.terminal"></span>
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Cabin classes -->
                                    <div x-show="airline.cabin_classes && airline.cabin_classes.length > 0" class="flex flex-wrap gap-1 mt-2">
                                        <template x-for="cabin in airline.cabin_classes" :key="cabin">
                                            <span class="inline-flex items-center bg-emerald-50 text-emerald-700 text-[10px] px-1.5 py-0.5 rounded border border-emerald-200"
                                                  x-text="cabin"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Hotels Accordion -->
                <div class="border-t border-gray-100">
                    <button @click="hotelsOpen = !hotelsOpen"
                            class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        <span class="flex items-center gap-2">
                            <i class="fa-regular fa-hotel text-purple-500"></i>
                            Hotels in der Nähe
                            <span x-show="(selectedAirport?.nearby_hotels || []).length > 0"
                                  class="inline-flex items-center justify-center bg-purple-500 text-white text-xs font-bold rounded-full w-5 h-5"
                                  x-text="(selectedAirport?.nearby_hotels || []).length"></span>
                        </span>
                        <i class="fa-regular fa-chevron-down text-gray-400 transition-transform duration-200"
                           :class="{ 'rotate-180': hotelsOpen }"></i>
                    </button>

                    <div x-show="hotelsOpen" x-collapse>
                        <div x-show="(selectedAirport?.nearby_hotels || []).length === 0" class="px-4 py-5 text-center">
                            <i class="fa-regular fa-bed-empty text-gray-300 text-2xl"></i>
                            <p class="text-xs text-gray-400 mt-2">Keine Hotels hinterlegt</p>
                        </div>

                        <div x-show="(selectedAirport?.nearby_hotels || []).length > 0"
                             class="max-h-52 overflow-y-auto divide-y divide-gray-50">
                            <template x-for="(hotel, idx) in (selectedAirport?.nearby_hotels || [])" :key="idx">
                                <div class="px-4 py-3 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="hotel.name"></p>
                                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                                <span x-show="hotel.distance_km"
                                                      class="inline-flex items-center gap-1 text-[10px] text-gray-500">
                                                    <i class="fa-regular fa-route text-[9px]"></i>
                                                    <span x-text="hotel.distance_km + ' km'"></span>
                                                </span>
                                                <span x-show="hotel.shuttle"
                                                      class="inline-flex items-center gap-1 bg-green-50 text-green-700 text-[10px] font-semibold px-1.5 py-0.5 rounded border border-green-200">
                                                    <i class="fa-regular fa-shuttle-van text-[9px]"></i>
                                                    Shuttle
                                                </span>
                                                <span x-show="!hotel.shuttle"
                                                      class="inline-flex items-center gap-1 bg-gray-50 text-gray-400 text-[10px] px-1.5 py-0.5 rounded border border-gray-200">
                                                    <i class="fa-regular fa-shuttle-van text-[9px]"></i>
                                                    Kein Shuttle
                                                </span>
                                            </div>
                                        </div>
                                        <div x-show="hotel.booking_url" class="flex-shrink-0">
                                            <a :href="hotel.booking_url" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1 bg-purple-50 text-purple-700 text-[10px] font-semibold px-2 py-1 rounded-md border border-purple-200 hover:bg-purple-100 transition-colors">
                                                <i class="fa-regular fa-external-link text-[9px]"></i>
                                                Buchen
                                            </a>
                                        </div>
                                    </div>
                                    <p x-show="hotel.notes" class="text-[11px] text-gray-500 mt-1.5 leading-relaxed" x-text="hotel.notes"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Lounges Accordion -->
                <div class="border-t border-gray-100">
                    <button @click="loungesOpen = !loungesOpen"
                            class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        <span class="flex items-center gap-2">
                            <i class="fa-regular fa-couch text-amber-500"></i>
                            Lounges
                            <span x-show="(selectedAirport?.lounges || []).length > 0"
                                  class="inline-flex items-center justify-center bg-amber-500 text-white text-xs font-bold rounded-full w-5 h-5"
                                  x-text="(selectedAirport?.lounges || []).length"></span>
                        </span>
                        <i class="fa-regular fa-chevron-down text-gray-400 transition-transform duration-200"
                           :class="{ 'rotate-180': loungesOpen }"></i>
                    </button>

                    <div x-show="loungesOpen" x-collapse>
                        <div x-show="(selectedAirport?.lounges || []).length === 0" class="px-4 py-5 text-center">
                            <i class="fa-regular fa-couch text-gray-300 text-2xl"></i>
                            <p class="text-xs text-gray-400 mt-2">Keine Lounges hinterlegt</p>
                        </div>

                        <div x-show="(selectedAirport?.lounges || []).length > 0"
                             class="max-h-52 overflow-y-auto divide-y divide-gray-50">
                            <template x-for="(lounge, idx) in (selectedAirport?.lounges || [])" :key="idx">
                                <div class="px-4 py-3 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="lounge.name"></p>
                                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                                <span x-show="lounge.location"
                                                      class="inline-flex items-center gap-1 text-[10px] text-gray-500">
                                                    <i class="fa-regular fa-location-dot text-[9px]"></i>
                                                    <span x-text="lounge.location"></span>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                                <span x-show="lounge.price_per_person"
                                                      class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 text-[10px] font-semibold px-1.5 py-0.5 rounded border border-amber-200">
                                                    <i class="fa-regular fa-euro-sign text-[9px]"></i>
                                                    <span x-text="lounge.price_per_person + ' €/Person'"></span>
                                                </span>
                                                <span x-show="lounge.children_welcome"
                                                      class="inline-flex items-center gap-1 bg-green-50 text-green-700 text-[10px] px-1.5 py-0.5 rounded border border-green-200">
                                                    <i class="fa-regular fa-child text-[9px]"></i>
                                                    Kinderfreundlich
                                                </span>
                                            </div>
                                            <p x-show="lounge.access" class="text-[11px] text-gray-500 mt-1 leading-relaxed">
                                                <span class="font-medium text-gray-600">Zugang:</span> <span x-text="lounge.access"></span>
                                            </p>
                                        </div>
                                        <div x-show="lounge.url" class="flex-shrink-0">
                                            <a :href="lounge.url" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 text-[10px] font-semibold px-2 py-1 rounded-md border border-amber-200 hover:bg-amber-100 transition-colors">
                                                <i class="fa-regular fa-external-link text-[9px]"></i>
                                                Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Mobility Accordion (per AIRPORTS_MOBILITY_ENABLED abschaltbar) -->
                @if(config('app.airports_mobility_enabled', false))
                <div class="border-t border-gray-100">
                    <button @click="mobilityOpen = !mobilityOpen"
                            class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        <span class="flex items-center gap-2">
                            <i class="fa-regular fa-car-bus text-teal-500"></i>
                            Mobilität & Transport
                            <span x-show="countMobilityOptions() > 0"
                                  class="inline-flex items-center justify-center bg-teal-500 text-white text-xs font-bold rounded-full w-5 h-5"
                                  x-text="countMobilityOptions()"></span>
                        </span>
                        <i class="fa-regular fa-chevron-down text-gray-400 transition-transform duration-200"
                           :class="{ 'rotate-180': mobilityOpen }"></i>
                    </button>

                    <div x-show="mobilityOpen" x-collapse>
                        <template x-if="!hasMobilityOptions()">
                            <div class="px-4 py-5 text-center">
                                <i class="fa-regular fa-car text-gray-300 text-2xl"></i>
                                <p class="text-xs text-gray-400 mt-2">Keine Mobilitätsoptionen hinterlegt</p>
                            </div>
                        </template>

                        <template x-if="hasMobilityOptions()">
                            <div class="divide-y divide-gray-50">
                                <!-- Car Rental -->
                                <template x-if="selectedAirport?.mobility_options?.car_rental?.available">
                                    <div class="px-4 py-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fa-regular fa-car text-teal-600 text-xs"></i>
                                            <span class="text-xs font-semibold text-gray-700">Mietwagen</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <template x-for="(provider, idx) in (selectedAirport?.mobility_options?.car_rental?.providers || [])" :key="idx">
                                                <a x-show="provider.url" :href="provider.url" target="_blank" rel="noopener"
                                                   class="inline-flex items-center gap-1 bg-teal-50 text-teal-700 text-[10px] font-medium px-2 py-1 rounded-md border border-teal-200 hover:bg-teal-100 transition-colors">
                                                    <span x-text="provider.name"></span>
                                                    <i class="fa-regular fa-external-link text-[8px]"></i>
                                                </a>
                                                <span x-show="!provider.url"
                                                      class="inline-flex items-center bg-teal-50 text-teal-700 text-[10px] font-medium px-2 py-1 rounded-md border border-teal-200"
                                                      x-text="provider.name"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Public Transport -->
                                <template x-if="selectedAirport?.mobility_options?.public_transport?.available">
                                    <div class="px-4 py-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fa-regular fa-train text-teal-600 text-xs"></i>
                                            <span class="text-xs font-semibold text-gray-700">ÖPNV</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <template x-for="(type, idx) in (selectedAirport?.mobility_options?.public_transport?.types || [])" :key="idx">
                                                <a x-show="type.url" :href="type.url" target="_blank" rel="noopener"
                                                   class="inline-flex items-center gap-1 bg-teal-50 text-teal-700 text-[10px] font-medium px-2 py-1 rounded-md border border-teal-200 hover:bg-teal-100 transition-colors">
                                                    <span x-text="type.name"></span>
                                                    <i class="fa-regular fa-external-link text-[8px]"></i>
                                                </a>
                                                <span x-show="!type.url"
                                                      class="inline-flex items-center bg-teal-50 text-teal-700 text-[10px] font-medium px-2 py-1 rounded-md border border-teal-200"
                                                      x-text="type.name"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Airport Shuttle -->
                                <template x-if="selectedAirport?.mobility_options?.airport_shuttle?.available">
                                    <div class="px-4 py-3">
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <i class="fa-regular fa-shuttle-van text-teal-600 text-xs"></i>
                                            <span class="text-xs font-semibold text-gray-700">Shuttle</span>
                                        </div>
                                        <p x-show="selectedAirport?.mobility_options?.airport_shuttle?.info"
                                           class="text-[11px] text-gray-500 leading-relaxed"
                                           x-text="selectedAirport?.mobility_options?.airport_shuttle?.info"></p>
                                        <a x-show="selectedAirport?.mobility_options?.airport_shuttle?.url"
                                           :href="selectedAirport?.mobility_options?.airport_shuttle?.url" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1 text-[10px] text-teal-600 hover:text-teal-800 mt-1">
                                            <i class="fa-regular fa-external-link text-[8px]"></i> Mehr Infos
                                        </a>
                                    </div>
                                </template>

                                <!-- Taxi -->
                                <template x-if="selectedAirport?.mobility_options?.taxi?.available">
                                    <div class="px-4 py-3">
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <i class="fa-regular fa-taxi text-teal-600 text-xs"></i>
                                            <span class="text-xs font-semibold text-gray-700">Taxi</span>
                                        </div>
                                        <p x-show="selectedAirport?.mobility_options?.taxi?.info"
                                           class="text-[11px] text-gray-500 leading-relaxed"
                                           x-text="selectedAirport?.mobility_options?.taxi?.info"></p>
                                        <p x-show="selectedAirport?.mobility_options?.taxi?.approx_cost"
                                           class="text-[11px] text-gray-500 mt-0.5">
                                            <span class="font-medium text-gray-600">Ca. Kosten:</span> <span x-text="selectedAirport?.mobility_options?.taxi?.approx_cost"></span>
                                        </p>
                                    </div>
                                </template>

                                <!-- Parking -->
                                <template x-if="selectedAirport?.mobility_options?.parking?.available">
                                    <div class="px-4 py-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fa-regular fa-square-parking text-teal-600 text-xs"></i>
                                            <span class="text-xs font-semibold text-gray-700">Parken</span>
                                        </div>
                                        <div class="space-y-1.5">
                                            <template x-for="(option, idx) in (selectedAirport?.mobility_options?.parking?.options || [])" :key="idx">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-[11px] font-medium text-gray-700" x-text="option.name"></p>
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <span x-show="option.distance" class="text-[10px] text-gray-400" x-text="option.distance"></span>
                                                            <span x-show="option.price_info" class="text-[10px] text-teal-600 font-medium" x-text="option.price_info"></span>
                                                        </div>
                                                    </div>
                                                    <a x-show="option.url" :href="option.url" target="_blank" rel="noopener"
                                                       class="flex-shrink-0 text-[10px] text-teal-600 hover:text-teal-800">
                                                        <i class="fa-regular fa-external-link"></i>
                                                    </a>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Footer -->
    <x-public-footer />
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Leaflet MarkerCluster JS -->
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
function airportsApp() {
    return {
        searchQuery: '',
        countryFilter: '',
        airports: [],
        countries: [],
        selectedAirport: null,
        loading: false,
        map: null,
        markers: [],
        markerCluster: null,
        airportAirlines: [],
        airlinesOpen: false,
        airlinesLoading: false,
        hotelsOpen: false,
        loungesOpen: false,
        mobilityOpen: false,
        allSectionsOpen: false,

        init() {
            this.$nextTick(() => {
                this.initMap();
                this.loadCountries();
                // Ohne Sucheingabe den kompletten Bestand zeigen
                this.search();
            });
        },

        initMap() {
            this.map = L.map('airports-map', {
                center: [50.0, 10.0],
                zoom: 4,
                zoomControl: true
            });

            L.tileLayer('https://tile.openstreetmap.de/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19
            }).addTo(this.map);

            // Marker werden gebuendelt, sonst ueberdecken sich bei voller Liste
            // die Flughaefen in Ballungsraeumen gegenseitig
            this.markerCluster = L.markerClusterGroup({
                chunkedLoading: true,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                maxClusterRadius: 50,
                disableClusteringAtZoom: 9,
                // Ohne Animation. Beim schnellen Rein-/Rauszoomen wurde die
                // Zoom-Animation des Plugins abgebrochen: Marker behielten ihre
                // alte Pixelposition (sassen also am falschen Ort) und liefen
                // dabei auf einen null-Kartenbezug (_latLngToNewLayerPoint).
                // Ohne Animation werden die Positionen synchron neu gesetzt.
                animate: false,
                animateAddingMarkers: false,
                // Gleiche Darstellung wie die Cluster auf der Weltkarte des
                // Global Travel Monitor (siehe dashboard.blade.php)
                iconCreateFunction: function (cluster) {
                    const childCount = cluster.getChildCount();
                    let size = 40;
                    if (childCount < 10) {
                        size = 35;
                    } else if (childCount >= 100) {
                        size = 45;
                    }

                    return new L.DivIcon({
                        html: '<div style="background: #3B4154; color: white; border-radius: 50%; width: ' + size + 'px; height: ' + size + 'px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);"><span>' + childCount + '</span></div>',
                        className: 'custom-cluster-icon',
                        iconSize: new L.Point(size, size)
                    });
                }
            });
            this.map.addLayer(this.markerCluster);

            setTimeout(() => this.map.invalidateSize(), 100);
        },

        async loadCountries() {
            try {
                const response = await fetch('/api/airports/countries');
                const data = await response.json();
                this.countries = data.data || [];
            } catch (e) {
                console.error('Error loading countries:', e);
            }
        },

        async search() {
            // Weder Suchbegriff noch Landfilter: kompletten Bestand anzeigen
            const showAll = this.searchQuery.length === 0 && !this.countryFilter;

            this.loading = true;

            try {
                const params = new URLSearchParams();
                if (this.searchQuery) params.append('q', this.searchQuery);
                if (this.countryFilter) params.append('country_id', this.countryFilter);
                if (showAll) params.append('all', '1');

                const response = await fetch(`/api/airports/search?${params.toString()}`);
                const data = await response.json();
                this.airports = data.data || [];
            } catch (e) {
                console.error('Error searching airports:', e);
                this.airports = [];
            } finally {
                this.loading = false;
            }

            // Bewusst ausserhalb des try: ein Fehler beim Zeichnen der Karte
            // darf die bereits geladene Trefferliste nicht wieder leeren.
            try {
                this.updateMarkers();
            } catch (e) {
                console.error('Error updating airport markers:', e);
            }
        },

        clearMarkers() {
            if (this.markerCluster) this.markerCluster.clearLayers();
            this.markers = [];
        },

        updateMarkers() {
            this.clearMarkers();

            const bounds = [];

            this.airports.forEach(airport => {
                if (airport.latitude && airport.longitude) {
                    const icon = L.divIcon({
                        className: 'airport-marker',
                        html: '<i class="fa-solid fa-plane"></i>',
                        iconSize: [28, 28],
                        iconAnchor: [14, 14]
                    });

                    const marker = L.marker([airport.latitude, airport.longitude], { icon })
                        .bindTooltip(`${airport.name} (${airport.iata_code || airport.icao_code})`, { direction: 'top' })
                        .on('click', () => this.selectAirport(airport));

                    this.markerCluster.addLayer(marker);
                    this.markers.push(marker);
                    bounds.push([airport.latitude, airport.longitude]);
                }
            });

            if (bounds.length > 0) {
                this.map.fitBounds(bounds, { padding: [50, 50], maxZoom: 10 });
            }
        },

        selectAirport(airport) {
            this.selectedAirport = airport;
            this.airportAirlines = [];
            this.airlinesOpen = false;
            this.airlinesLoading = false;
            this.hotelsOpen = false;
            this.loungesOpen = false;
            this.mobilityOpen = false;

            if (airport.latitude && airport.longitude) {
                this.map.setView([airport.latitude, airport.longitude], 10);
            }
        },

        async toggleAirlines() {
            this.airlinesOpen = !this.airlinesOpen;

            if (this.airlinesOpen && this.airportAirlines.length === 0 && this.selectedAirport) {
                this.airlinesLoading = true;
                try {
                    const response = await fetch(`/api/airports/${this.selectedAirport.id}/airlines`);
                    const data = await response.json();
                    this.airportAirlines = data.data || [];
                } catch (e) {
                    console.error('Error loading airlines:', e);
                } finally {
                    this.airlinesLoading = false;
                }
            }
        },

        hasMobilityOptions() {
            return this.countMobilityOptions() > 0;
        },

        async toggleAllSections() {
            const open = !this.allSectionsOpen;
            this.allSectionsOpen = open;
            this.airlinesOpen = open;
            this.hotelsOpen = open;
            this.loungesOpen = open;
            this.mobilityOpen = open;

            if (open && this.airportAirlines.length === 0 && this.selectedAirport) {
                this.airlinesLoading = true;
                try {
                    const response = await fetch(`/api/airports/${this.selectedAirport.id}/airlines`);
                    const data = await response.json();
                    this.airportAirlines = data.data || [];
                } catch (e) {
                    console.error('Error loading airlines:', e);
                } finally {
                    this.airlinesLoading = false;
                }
            }
        },

        countMobilityOptions() {
            const mo = this.selectedAirport?.mobility_options;
            if (!mo || typeof mo !== 'object') return 0;
            let count = 0;
            if (mo.car_rental?.available) count++;
            if (mo.public_transport?.available) count++;
            if (mo.airport_shuttle?.available) count++;
            if (mo.taxi?.available) count++;
            if (mo.parking?.available) count++;
            return count;
        }
    };
}
</script>
</body>
</html>
