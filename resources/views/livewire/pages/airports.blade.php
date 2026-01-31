@php
    $active = 'airports';
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Flughäfen - Global Travel Monitor</title>

    <!-- Alpine.js -->
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
                                            <span class="block text-xs text-gray-400 mt-0.5 font-mono" x-text="airport.icao_code || '-'"></span>
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
                 class="absolute bottom-4 left-4 right-4 bg-white rounded-lg shadow-lg p-4 max-w-md z-[1000]">
                <button @click="selectedAirport = null" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                    <i class="fa-regular fa-xmark"></i>
                </button>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fa-regular fa-plane text-blue-600 text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-gray-900" x-text="selectedAirport?.name"></h3>
                        <p class="text-sm text-gray-500" x-text="selectedAirport?.country?.name"></p>
                        <div class="flex gap-2 mt-2">
                            <span class="inline-flex items-center bg-blue-100 text-blue-800 text-xs font-mono px-2 py-1 rounded">
                                IATA: <span class="ml-1 font-bold" x-text="selectedAirport?.iata_code || '-'"></span>
                            </span>
                            <span class="inline-flex items-center bg-gray-100 text-gray-700 text-xs font-mono px-2 py-1 rounded">
                                ICAO: <span class="ml-1" x-text="selectedAirport?.icao_code || '-'"></span>
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 font-mono" x-show="selectedAirport?.latitude && selectedAirport?.longitude">
                            <span x-text="parseFloat(selectedAirport?.latitude).toFixed(4)"></span>,
                            <span x-text="parseFloat(selectedAirport?.longitude).toFixed(4)"></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <x-public-footer />
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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

        init() {
            this.$nextTick(() => {
                this.initMap();
                this.loadCountries();
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
            if (this.searchQuery.length === 0 && !this.countryFilter) {
                this.airports = [];
                this.clearMarkers();
                return;
            }

            this.loading = true;

            try {
                const params = new URLSearchParams();
                if (this.searchQuery) params.append('q', this.searchQuery);
                if (this.countryFilter) params.append('country_id', this.countryFilter);

                const response = await fetch(`/api/airports/search?${params.toString()}`);
                const data = await response.json();
                this.airports = data.data || [];
                this.updateMarkers();
            } catch (e) {
                console.error('Error searching airports:', e);
                this.airports = [];
            } finally {
                this.loading = false;
            }
        },

        clearMarkers() {
            this.markers.forEach(m => this.map.removeLayer(m));
            this.markers = [];
        },

        updateMarkers() {
            this.clearMarkers();

            const bounds = [];

            this.airports.forEach(airport => {
                if (airport.latitude && airport.longitude) {
                    const icon = L.divIcon({
                        className: 'airport-marker',
                        iconSize: [12, 12],
                        iconAnchor: [6, 6]
                    });

                    const marker = L.marker([airport.latitude, airport.longitude], { icon })
                        .addTo(this.map)
                        .bindTooltip(`${airport.name} (${airport.iata_code || airport.icao_code})`, { direction: 'top' })
                        .on('click', () => this.selectAirport(airport));

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

            if (airport.latitude && airport.longitude) {
                this.map.setView([airport.latitude, airport.longitude], 10);
            }
        }
    };
}
</script>
</body>
</html>
