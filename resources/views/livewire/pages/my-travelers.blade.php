@php
    $active = 'my-travelers';
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meine Reisenden - Global Travel Monitor</title>

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
            var fallbackHref = '{{ file_exists(public_path('vendor/fontawesome/css/all.min.css')) ? asset('vendor/fontawesome/css/all.min.css') : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' }}';
            window.addEventListener('DOMContentLoaded', function(){
                setTimeout(function(){ if(!window.__faKitOk){ addCss(fallbackHref); } }, 800);
            });
        })();
        </script>
    @elseif (file_exists(public_path('vendor/fontawesome/css/all.min.css')))
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" />
    @else
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    @endif

    <style>
        /* Basis-Layout */
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

        /* Header - feststehend */
        .header {
            flex-shrink: 0;
            height: 64px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            z-index: 10000;
        }

        /* Footer - feststehend */
        .footer {
            flex-shrink: 0;
            height: 32px;
            background: white;
            color: black;
            z-index: 9999;
            border-top: 1px solid #e5e7eb;
        }

        /* Hauptbereich - dynamisch */
        .main-content {
            flex: 1;
            display: flex;
            min-height: 0;
        }

        /* Navigation - feste Breite */
        .navigation {
            flex-shrink: 0;
            width: 64px;
            background: black;
        }

        /* Sidebar - feste Breite */
        .sidebar {
            flex-shrink: 0;
            width: 380px;
            background: #f9fafb;
            overflow-y: auto;
            height: 100%;
            position: relative;
            border-right: 1px solid #e5e7eb;
        }

        /* Map Container - nimmt restlichen Platz ein */
        .map-container {
            flex: 1;
            position: relative;
            height: 100%;
            overflow: hidden;
        }

        #travelers-map {
            width: 100%;
            height: 100%;
        }

        /* Marker Cluster Custom Styling */
        .marker-cluster div {
            width: 40px;
            height: 40px;
            text-align: center;
            border-radius: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .marker-cluster-small {
            background-color: rgba(34, 197, 94, 0.6);
        }

        .marker-cluster-small div {
            background-color: rgba(34, 197, 94, 0.8);
            color: white;
        }

        .marker-cluster-medium {
            background-color: rgba(59, 130, 246, 0.6);
        }

        .marker-cluster-medium div {
            background-color: rgba(59, 130, 246, 0.8);
            color: white;
        }

        .marker-cluster-large {
            background-color: rgba(239, 68, 68, 0.6);
        }

        .marker-cluster-large div {
            background-color: rgba(239, 68, 68, 0.8);
            color: white;
        }

        /* Traveler Card Styling */
        .traveler-card {
            transition: all 0.2s ease;
        }

        .traveler-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .traveler-card.active {
            border-left: 4px solid #3b82f6;
            background-color: #eff6ff;
        }

        /* Loading Animation */
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
<div class="app-container" x-data="travelersApp()">
    <!-- Header -->
    <x-public-header />

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navigation -->
        <x-public-navigation :active="$active" />

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="p-4">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fa-regular fa-users mr-2"></i>
                    Meine Reisenden
                </h2>

                @if(!$hasValidToken)
                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-4">
                    <div class="flex items-start">
                        <i class="fa-regular fa-triangle-exclamation text-yellow-500 mt-0.5 mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-yellow-800">Keine API-Verbindung</h3>
                            <p class="text-sm text-yellow-700 mt-1">
                                Bitte melden Sie sich erneut via SSO an oder verbinden Sie die Passolution-Integration, um Ihre Reisenden zu sehen.
                            </p>
                            <a href="{{ route('customer.dashboard') }}" class="inline-block mt-2 text-sm text-yellow-800 hover:text-yellow-900 underline">
                                Zum Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-white p-4 rounded-lg border border-gray-200 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">
                                Reisende, die heute unterwegs sind
                            </p>
                            <p class="text-2xl font-bold text-gray-900" x-text="travelers.length"></p>
                        </div>
                        <button @click="loadTravelers()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Aktualisieren">
                            <i class="fa-regular fa-arrows-rotate" :class="{ 'loading-spinner': loading }"></i>
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <template x-if="loading">
                    <div class="flex items-center justify-center py-8">
                        <i class="fa-regular fa-spinner-third fa-spin text-3xl text-blue-500"></i>
                    </div>
                </template>

                <!-- Error State -->
                <template x-if="error && !loading">
                    <div class="bg-red-50 border border-red-200 p-4 rounded-lg mb-4">
                        <div class="flex items-start">
                            <i class="fa-regular fa-circle-exclamation text-red-500 mt-0.5 mr-3"></i>
                            <div>
                                <h3 class="font-semibold text-red-800">Fehler</h3>
                                <p class="text-sm text-red-700 mt-1" x-text="error"></p>
                                <button @click="loadTravelers()" class="inline-block mt-2 text-sm text-red-800 hover:text-red-900 underline">
                                    Erneut versuchen
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <template x-if="!loading && !error && travelers.length === 0">
                    <div class="bg-gray-50 p-6 rounded-lg border border-dashed border-gray-300 text-center">
                        <i class="fa-regular fa-suitcase-rolling text-4xl text-gray-400 mb-3"></i>
                        <h3 class="font-semibold text-gray-700">Keine Reisenden</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Aktuell sind keine Reisenden unterwegs.
                        </p>
                    </div>
                </template>

                <!-- Travelers List -->
                <template x-if="!loading && !error && travelers.length > 0">
                    <div class="space-y-3">
                        <template x-for="traveler in travelers" :key="traveler.id">
                            <div class="traveler-card bg-white p-4 rounded-lg border border-gray-200 cursor-pointer"
                                 :class="{ 'active': selectedTraveler?.id === traveler.id }"
                                 @click="selectTraveler(traveler)">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 text-sm" x-text="traveler.title"></h4>
                                        <template x-if="traveler.destination">
                                            <p class="text-xs text-gray-600 mt-1">
                                                <i class="fa-regular fa-location-dot mr-1"></i>
                                                <span x-text="traveler.destination.name"></span>
                                            </p>
                                        </template>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <i class="fa-regular fa-calendar mr-1"></i>
                                            <span x-text="formatDateRange(traveler.start_date, traveler.end_date)"></span>
                                        </p>
                                        <template x-if="traveler.travelers_count > 1">
                                            <p class="text-xs text-gray-500 mt-1">
                                                <i class="fa-regular fa-users mr-1"></i>
                                                <span x-text="traveler.travelers_count + ' Personen'"></span>
                                            </p>
                                        </template>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              :class="getStatusClass(traveler.status)">
                                            <span x-text="getStatusLabel(traveler.status)"></span>
                                        </span>
                                        <template x-if="traveler.countries && traveler.countries.length > 0">
                                            <div class="flex flex-wrap gap-1 justify-end mt-1">
                                                <template x-for="country in traveler.countries.slice(0, 3)" :key="country.iso2 || country.id">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-600"
                                                          x-text="country.iso2 || country.name?.substring(0, 2)"></span>
                                                </template>
                                                <template x-if="traveler.countries.length > 3">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-600"
                                                          x-text="'+' + (traveler.countries.length - 3)"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                @endif
            </div>
        </div>

        <!-- Map Container -->
        <div class="map-container">
            <div id="travelers-map"></div>
        </div>
    </div>

    <!-- Footer -->
    <x-public-footer />
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
    function travelersApp() {
        return {
            travelers: [],
            selectedTraveler: null,
            loading: false,
            error: null,
            map: null,
            markersLayer: null,
            markers: {},

            init() {
                // Wait for DOM to be ready
                this.$nextTick(() => {
                    this.initMap();
                    @if($hasValidToken)
                    this.loadTravelers();
                    @endif
                });
            },

            initMap() {
                // Initialize the map centered on Europe
                this.map = L.map('travelers-map', {
                    center: [50.0, 10.0],
                    zoom: 4,
                    zoomControl: true,
                    worldCopyJump: false,
                    maxBounds: [[-90, -180], [90, 180]],
                    minZoom: 2
                });

                // Add OpenStreetMap tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(this.map);

                // Window Resize Event-Listener
                let resizeTimeout;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(() => {
                        if (this.map) {
                            this.map.invalidateSize();
                        }
                    }, 250);
                });

                // Initialize marker cluster group
                this.markersLayer = L.markerClusterGroup({
                    chunkedLoading: true,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true,
                    maxClusterRadius: 50
                });

                this.map.addLayer(this.markersLayer);

                // Force map to recalculate size after initialization
                setTimeout(() => {
                    if (this.map) {
                        this.map.invalidateSize();
                    }
                }, 100);
            },

            async loadTravelers() {
                this.loading = true;
                this.error = null;

                try {
                    const response = await fetch('{{ route("my-travelers.active") }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (!data.success) {
                        this.error = data.message || 'Fehler beim Laden der Reisenden';
                        return;
                    }

                    this.travelers = data.travelers || [];
                    this.updateMapMarkers();

                } catch (err) {
                    console.error('Error loading travelers:', err);
                    this.error = 'Verbindungsfehler. Bitte versuchen Sie es erneut.';
                } finally {
                    this.loading = false;
                }
            },

            updateMapMarkers() {
                // Clear existing markers
                this.markersLayer.clearLayers();
                this.markers = {};

                // Add markers for each traveler with destination
                this.travelers.forEach(traveler => {
                    if (traveler.destination && traveler.destination.lat && traveler.destination.lng) {
                        const marker = this.createTravelerMarker(traveler);
                        this.markers[traveler.id] = marker;
                        this.markersLayer.addLayer(marker);
                    }
                });

                // Fit bounds if markers exist
                if (Object.keys(this.markers).length > 0) {
                    const bounds = this.markersLayer.getBounds();
                    if (bounds.isValid()) {
                        this.map.fitBounds(bounds, { padding: [50, 50] });
                    }
                }
            },

            createTravelerMarker(traveler) {
                const icon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div style="background-color: #3b82f6; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                        <i class="fa-solid fa-suitcase" style="font-size: 16px;"></i>
                    </div>`,
                    iconSize: [36, 36],
                    iconAnchor: [18, 18]
                });

                const marker = L.marker([traveler.destination.lat, traveler.destination.lng], { icon: icon });

                // Add popup
                const popupContent = `
                    <div style="min-width: 200px;">
                        <h3 style="font-weight: bold; margin-bottom: 8px; color: #1f2937;">
                            <i class="fa-solid fa-suitcase" style="color: #3b82f6; margin-right: 4px;"></i>
                            ${this.escapeHtml(traveler.title)}
                        </h3>
                        <p style="margin: 4px 0; font-size: 13px;">
                            <i class="fa-regular fa-location-dot" style="margin-right: 4px;"></i>
                            ${this.escapeHtml(traveler.destination.name)}
                        </p>
                        <p style="margin: 4px 0; font-size: 13px;">
                            <i class="fa-regular fa-calendar" style="margin-right: 4px;"></i>
                            ${this.formatDateRange(traveler.start_date, traveler.end_date)}
                        </p>
                        ${traveler.travelers_count > 1 ? `
                        <p style="margin: 4px 0; font-size: 13px;">
                            <i class="fa-regular fa-users" style="margin-right: 4px;"></i>
                            ${traveler.travelers_count} Personen
                        </p>
                        ` : ''}
                    </div>
                `;

                marker.bindPopup(popupContent);

                // Click handler to select traveler
                marker.on('click', () => {
                    this.selectedTraveler = traveler;
                });

                return marker;
            },

            selectTraveler(traveler) {
                this.selectedTraveler = traveler;

                if (traveler.destination && this.markers[traveler.id]) {
                    // Pan to marker and open popup
                    this.map.setView([traveler.destination.lat, traveler.destination.lng], 8);
                    this.markers[traveler.id].openPopup();
                }
            },

            formatDateRange(startDate, endDate) {
                if (!startDate || !endDate) return '-';

                const start = new Date(startDate);
                const end = new Date(endDate);

                const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
                return `${start.toLocaleDateString('de-DE', options)} - ${end.toLocaleDateString('de-DE', options)}`;
            },

            getStatusClass(status) {
                switch (status) {
                    case 'traveling':
                        return 'bg-green-100 text-green-800';
                    case 'upcoming':
                        return 'bg-blue-100 text-blue-800';
                    case 'completed':
                        return 'bg-gray-100 text-gray-600';
                    default:
                        return 'bg-gray-100 text-gray-600';
                }
            },

            getStatusLabel(status) {
                switch (status) {
                    case 'traveling':
                        return 'Unterwegs';
                    case 'upcoming':
                        return 'Geplant';
                    case 'completed':
                        return 'Beendet';
                    default:
                        return 'Unbekannt';
                }
            },

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };
    }
</script>
</body>
</html>
