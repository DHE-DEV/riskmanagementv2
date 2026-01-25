@php
    $active = 'my-travelers';
    $version = '1.0.2'; // Cache buster - JS syntax fix
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <!-- Version: {{ $version }} - {{ now()->format('Y-m-d H:i:s') }} -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meine Reisenden - Global Travel Monitor</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Laravel Echo + Pusher for Real-time Updates -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.min.js"></script>

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

        /* Travel Details Sidebar Styles */
        .travel-sidebar {
            position: fixed;
            top: 64px; /* Header height */
            bottom: 32px; /* Footer height */
            right: -400px; /* Start hidden */
            width: 400px;
            background: white;
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
            transition: right 0.3s ease-in-out;
            z-index: 100000;
            display: flex;
            flex-direction: column;
        }

        .travel-sidebar.w-2x { width: 800px; right: -800px; }
        .travel-sidebar.w-3x { width: 1200px; right: -1200px; }

        .travel-sidebar.open { right: 0; }

        .travel-sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .travel-sidebar-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .travel-close-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .travel-close-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .travel-sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
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
                <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-4">
                    <div class="flex items-start">
                        <i class="fa-regular fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-blue-800">Eingeschränkte Ansicht</h3>
                            <p class="text-sm text-blue-700 mt-1">
                                Es werden nur lokal importierte Reisen angezeigt. Verbinden Sie die API, um auch externe Reisedaten zu sehen.
                            </p>
                            <a href="{{ route('customer.dashboard') }}" class="inline-block mt-2 text-sm text-blue-800 hover:text-blue-900 underline">
                                API verbinden
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Stats Box -->
                <div class="bg-white p-4 rounded-lg border border-gray-200 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm text-gray-600">
                                Gefundene Reisen
                            </p>
                            <p class="text-2xl font-bold text-gray-900" x-text="travelers.length"></p>
                        </div>
                        <button @click="loadTravelers()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Aktualisieren">
                            <i class="fa-regular fa-arrows-rotate" :class="{ 'loading-spinner': loading }"></i>
                        </button>
                    </div>
                    <!-- Source breakdown -->
                    <div class="flex gap-2 text-xs" x-show="travelers.length > 0">
                        <div class="flex items-center gap-1">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-gray-600">
                                <span x-text="travelers.filter(t => t.source === 'local').length"></span> Lokal
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="inline-block w-2 h-2 rounded-full bg-blue-500"></span>
                            <span class="text-gray-600">
                                <span x-text="travelers.filter(t => t.source === 'api').length"></span> API
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="bg-white p-4 rounded-lg border border-gray-200 mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">
                        <i class="fa-regular fa-filter mr-2"></i>
                        Filter
                    </h3>

                    <!-- Quick Date Filters -->
                    <div class="mb-4">
                        <label class="text-xs font-medium text-gray-700 mb-2 block">Zeitraum</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button @click="filters.dateFilter = 'today'; loadTravelers()"
                                    class="px-3 py-2 text-xs rounded-lg border transition-colors"
                                    :class="filters.dateFilter === 'today' ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                Heute
                            </button>
                            <button @click="filters.dateFilter = '7days'; loadTravelers()"
                                    class="px-3 py-2 text-xs rounded-lg border transition-colors"
                                    :class="filters.dateFilter === '7days' ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                7 Tage
                            </button>
                            <button @click="filters.dateFilter = '14days'; loadTravelers()"
                                    class="px-3 py-2 text-xs rounded-lg border transition-colors"
                                    :class="filters.dateFilter === '14days' ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                14 Tage
                            </button>
                            <button @click="filters.dateFilter = '30days'; loadTravelers()"
                                    class="px-3 py-2 text-xs rounded-lg border transition-colors"
                                    :class="filters.dateFilter === '30days' ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                30 Tage
                            </button>
                            <button @click="filters.dateFilter = 'all'; loadTravelers()"
                                    class="px-3 py-2 text-xs rounded-lg border transition-colors col-span-2"
                                    :class="filters.dateFilter === 'all' ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                Alle Reisen
                            </button>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="mb-4">
                        <label class="text-xs font-medium text-gray-700 mb-2 block">Status</label>
                        <select x-model="filters.status" @change="loadTravelers()"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">Alle Status</option>
                            <option value="upcoming">Bevorstehend</option>
                            <option value="traveling">Unterwegs</option>
                            <option value="confirmed">Bestätigt</option>
                            <option value="active">Aktiv</option>
                            <option value="completed">Abgeschlossen</option>
                        </select>
                    </div>

                    <!-- Source Filter -->
                    <div class="mb-4">
                        <label class="text-xs font-medium text-gray-700 mb-2 block">Quelle</label>
                        <select x-model="filters.source" @change="loadTravelers()"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">Alle Quellen</option>
                            <option value="local">Nur Lokal</option>
                            <option value="api">Nur API</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="mb-3">
                        <label class="text-xs font-medium text-gray-700 mb-2 block">Suche</label>
                        <div class="relative">
                            <input type="text"
                                   x-model="filters.search"
                                   @input.debounce.500ms="loadTravelers()"
                                   placeholder="Name, Ziel, Folder..."
                                   class="w-full px-3 py-2 pr-8 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <i class="fa-regular fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Reset Filters -->
                    <button @click="resetFilters(); loadTravelers()"
                            class="w-full px-3 py-2 text-xs text-gray-600 hover:text-gray-800 hover:bg-gray-50 rounded-lg border border-gray-300 transition-colors">
                        <i class="fa-regular fa-rotate-left mr-1"></i>
                        Filter zurücksetzen
                    </button>
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
                            <div class="traveler-card bg-white p-4 rounded-lg border border-gray-200"
                                 :class="{ 'active': selectedTraveler?.id === traveler.id }">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 cursor-pointer" @click="selectTraveler(traveler)">
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
                                        <!-- Delete Button (only for local folders) -->
                                        <template x-if="traveler.source === 'local' && traveler.folder_id">
                                            <button @click.stop="confirmDelete(traveler)"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors"
                                                    title="Reise löschen">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </template>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              :class="getStatusClass(traveler.status)">
                                            <span x-text="getStatusLabel(traveler.status)"></span>
                                        </span>
                                        <!-- Source Badge (Local vs API) -->
                                        <template x-if="traveler.source === 'local'">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700" title="Lokal importiert">
                                                <i class="fa-regular fa-database mr-1"></i>
                                                Lokal
                                            </span>
                                        </template>
                                        <template x-if="traveler.source === 'api'">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700" title="PDS API">
                                                <i class="fa-regular fa-cloud mr-1"></i>
                                                API
                                            </span>
                                        </template>
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
            </div>
        </div>

        <!-- Map Container -->
        <div class="map-container">
            <div id="travelers-map"></div>

            <!-- Travel Details Sidebar -->
            <div id="travelSidebar" class="travel-sidebar" x-data="{ sidebarWidth: 1 }">
                <div class="travel-sidebar-header">
                    <div class="flex flex-col gap-1">
                        <h3 id="travelSidebarTitle">Reisedetails</h3>
                        <div class="flex gap-2 text-xs">
                            <button id="travelDecreaseBtn"
                                    class="px-2 py-1 rounded bg-zinc-200 hover:bg-zinc-300"
                                    @click="decreaseTravelSidebarWidth()"
                                    title="Verkleinern">
                                <i class="fa-solid fa-magnifying-glass-minus"></i>
                            </button>
                            <button id="travelIncreaseBtn"
                                    class="px-2 py-1 rounded bg-zinc-200 hover:bg-zinc-300"
                                    @click="increaseTravelSidebarWidth()"
                                    title="Vergrößern">
                                <i class="fa-solid fa-magnifying-glass-plus"></i>
                            </button>
                        </div>
                    </div>
                    <button @click="closeTravelSidebar()" class="travel-close-btn">
                        <i class="fa-regular fa-xmark"></i>
                    </button>
                </div>
                <div id="travelSidebarContent" class="travel-sidebar-content">
                    <div class="flex items-center justify-center py-8">
                        <i class="fa-regular fa-spinner-third fa-spin text-3xl text-blue-500"></i>
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
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
    function travelersApp() {
        return {
            travelers: [],
            selectedTraveler: null,
            loading: false,
            error: null,
            map: null,
            filters: {
                dateFilter: '30days',
                status: 'all',
                source: 'all',
                search: '',
            },
            markersLayer: null,
            markers: {},
            hotelMarkersLayer: null,
            hotelMarkers: [],
            airportMarkersLayer: null,
            airportMarkers: [],

            init() {
                console.log('travelersApp initialized');
                // Wait for DOM to be ready
                this.$nextTick(() => {
                    console.log('DOM ready, initializing map and loading travelers');
                    this.initMap();
                    this.loadTravelers();
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

                // Initialize hotel markers layer (no clustering)
                this.hotelMarkersLayer = L.layerGroup();
                this.map.addLayer(this.hotelMarkersLayer);

                // Initialize airport markers layer (no clustering)
                this.airportMarkersLayer = L.layerGroup();
                this.map.addLayer(this.airportMarkersLayer);

                // Force map to recalculate size after initialization
                setTimeout(() => {
                    if (this.map) {
                        this.map.invalidateSize();
                    }
                }, 100);
            },

            async loadTravelers() {
                console.log('loadTravelers() called with filters:', this.filters);
                this.loading = true;
                this.error = null;

                try {
                    // Build query parameters from filters
                    const params = new URLSearchParams({
                        date_filter: this.filters.dateFilter,
                        status: this.filters.status,
                        source: this.filters.source,
                        search: this.filters.search,
                    });

                    const url = '{{ route("my-travelers.active") }}?' + params.toString();
                    console.log('Fetching travelers from:', url);

                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();
                    console.log('Response data:', data);

                    if (!data.success) {
                        console.error('API returned error:', data.message);
                        this.error = data.message || 'Fehler beim Laden der Reisenden';
                        return;
                    }

                    this.travelers = data.travelers || [];
                    console.log('Loaded travelers:', this.travelers.length);
                    this.updateMapMarkers();

                } catch (err) {
                    console.error('Error loading travelers:', err);
                    this.error = 'Verbindungsfehler. Bitte versuchen Sie es erneut.';
                } finally {
                    this.loading = false;
                }
            },

            resetFilters() {
                this.filters = {
                    dateFilter: 'today',
                    status: 'all',
                    source: 'all',
                    search: '',
                };
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
                // Different colors for local vs API travelers
                const markerColor = traveler.source === 'local' ? '#10b981' : '#3b82f6'; // green for local, blue for API
                const markerIcon = traveler.source === 'local' ? 'database' : 'suitcase';

                const icon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div style="background-color: ${markerColor}; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                        <i class="fa-solid fa-${markerIcon}" style="font-size: 16px;"></i>
                    </div>`,
                    iconSize: [36, 36],
                    iconAnchor: [18, 18]
                });

                const marker = L.marker([traveler.destination.lat, traveler.destination.lng], { icon: icon });

                // Source badge HTML
                const sourceBadge = traveler.source === 'local'
                    ? '<span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; background-color: #d1fae5; color: #065f46; margin-top: 6px;"><i class="fa-regular fa-database" style="margin-right: 4px;"></i>Lokal importiert</span>'
                    : '<span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; background-color: #dbeafe; color: #1e40af; margin-top: 6px;"><i class="fa-regular fa-cloud" style="margin-right: 4px;"></i>PDS API</span>';

                // Add popup
                const popupContent = `
                    <div style="min-width: 200px;">
                        <h3 style="font-weight: bold; margin-bottom: 8px; color: #1f2937;">
                            <i class="fa-solid fa-${markerIcon}" style="color: ${markerColor}; margin-right: 4px;"></i>
                            ${this.escapeHtml(traveler.title)}
                        </h3>
                        ${sourceBadge}
                        ${traveler.booking_reference ? `
                        <p style="margin: 8px 0; font-size: 13px;">
                            <i class="fa-regular fa-ticket" style="margin-right: 4px;"></i>
                            <span
                                onclick="copyToClipboard('${this.escapeHtml(traveler.booking_reference).replace(/'/g, "\\'")}', event)"
                                style="cursor: pointer; color: #2563eb; text-decoration: underline; font-weight: 500;"
                                title="Zum Kopieren anklicken"
                            >${this.escapeHtml(traveler.booking_reference)}</span>
                        </p>
                        ` : ''}
                        <p style="margin: 8px 0 4px 0; font-size: 13px;">
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
                        ${traveler.folder_number ? `
                        <p style="margin: 4px 0; font-size: 13px; color: #6b7280;">
                            <i class="fa-regular fa-folder" style="margin-right: 4px;"></i>
                            ${this.escapeHtml(traveler.folder_number)}
                        </p>
                        ` : ''}
                    </div>
                `;

                marker.bindPopup(popupContent);

                // Click handler to select traveler and open sidebar
                marker.on('click', () => {
                    this.selectTraveler(traveler);
                });

                return marker;
            },

            selectTraveler(traveler) {
                this.selectedTraveler = traveler;

                if (traveler.destination && this.markers[traveler.id]) {
                    // Open travel details sidebar first (if local folder)
                    if (traveler.source === 'local' && traveler.folder_id) {
                        openTravelSidebar(traveler.folder_id);

                        // Wait for sidebar animation to complete (300ms), then pan to marker
                        setTimeout(() => {
                            this.panToMarkerCentered(traveler);
                        }, 350);
                    } else {
                        // No sidebar, pan immediately
                        this.panToMarkerCentered(traveler);
                    }
                } else if (traveler.source === 'local' && traveler.folder_id) {
                    // Open sidebar even if no marker
                    openTravelSidebar(traveler.folder_id);
                }
            },

            panToMarkerCentered(traveler) {
                if (!traveler.destination || !this.markers[traveler.id]) return;

                const marker = this.markers[traveler.id];
                const lat = traveler.destination.lat;
                const lng = traveler.destination.lng;

                // Calculate offset to center in visible map area
                // Sidebar width: 380px left + potentially 400-1200px right
                const leftSidebarWidth = 380;

                // Get current travel sidebar width
                const sidebar = document.getElementById('travelSidebar');
                let rightSidebarWidth = 0;
                if (sidebar && sidebar.classList.contains('open')) {
                    if (sidebar.classList.contains('w-3x')) {
                        rightSidebarWidth = 1200;
                    } else if (sidebar.classList.contains('w-2x')) {
                        rightSidebarWidth = 800;
                    } else {
                        rightSidebarWidth = 400;
                    }
                }

                // Calculate the center point of the visible map area
                const mapContainer = this.map.getContainer();
                const mapWidth = mapContainer.offsetWidth;
                const mapHeight = mapContainer.offsetHeight;

                // Visible map area (excluding sidebars)
                const visibleMapWidth = mapWidth - leftSidebarWidth - rightSidebarWidth;
                const visibleMapCenterX = leftSidebarWidth + (visibleMapWidth / 2);
                const visibleMapCenterY = mapHeight / 2;

                // Calculate offset in pixels from true center
                const offsetX = visibleMapCenterX - (mapWidth / 2);
                const offsetY = visibleMapCenterY - (mapHeight / 2);

                // Convert pixel offset to lat/lng offset
                const point = this.map.project([lat, lng], 8);
                const offsetPoint = L.point(point.x - offsetX, point.y - offsetY);
                const offsetLatLng = this.map.unproject(offsetPoint, 8);

                // Pan to the offset position with animation
                this.map.setView(offsetLatLng, 8, {
                    animate: true,
                    duration: 0.5
                });

                // Open popup after pan
                setTimeout(() => {
                    marker.openPopup();
                }, 500);
            },

            clearHotelMarkers() {
                // Clear all hotel markers
                if (this.hotelMarkersLayer) {
                    this.hotelMarkersLayer.clearLayers();
                }
                this.hotelMarkers = [];
            },

            addHotelMarkers(hotels, folderId) {
                // Clear existing hotel markers
                this.clearHotelMarkers();

                if (!hotels || hotels.length === 0) return;

                // Add marker for each hotel with coordinates
                hotels.forEach(hotel => {
                    if (hotel.lat && hotel.lng) {
                        const marker = this.createHotelMarker(hotel, folderId);
                        this.hotelMarkers.push(marker);
                        this.hotelMarkersLayer.addLayer(marker);
                    }
                });
            },

            createHotelMarker(hotel, folderId) {
                const markerColor = '#f59e0b'; // amber/orange color for hotels

                const icon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div style="background-color: ${markerColor}; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                        <i class="fa-solid fa-hotel" style="font-size: 14px;"></i>
                    </div>`,
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                });

                const marker = L.marker([hotel.lat, hotel.lng], { icon: icon });

                // Add popup
                const popupContent = `
                    <div style="min-width: 200px;">
                        <h3 style="font-weight: bold; margin-bottom: 8px; color: #1f2937;">
                            <i class="fa-solid fa-hotel" style="color: ${markerColor}; margin-right: 4px;"></i>
                            ${this.escapeHtml(hotel.hotel_name)}
                        </h3>
                        ${hotel.booking_reference ? `
                        <p style="margin: 4px 0 8px 0; font-size: 13px;">
                            <i class="fa-regular fa-ticket" style="margin-right: 4px;"></i>
                            <span
                                onclick="copyToClipboard('${this.escapeHtml(hotel.booking_reference).replace(/'/g, "\\'")}', event)"
                                style="cursor: pointer; color: #2563eb; text-decoration: underline; font-weight: 500;"
                                title="Zum Kopieren anklicken"
                            >${this.escapeHtml(hotel.booking_reference)}</span>
                        </p>
                        ` : ''}
                        <p style="margin: 4px 0; font-size: 13px;">
                            <i class="fa-regular fa-location-dot" style="margin-right: 4px;"></i>
                            ${this.escapeHtml(hotel.city || '')}, ${this.escapeHtml(hotel.country_code || '')}
                        </p>
                        ${hotel.check_in_date && hotel.check_out_date ? `
                        <p style="margin: 4px 0; font-size: 13px;">
                            <i class="fa-regular fa-calendar" style="margin-right: 4px;"></i>
                            ${this.formatDate(hotel.check_in_date)} - ${this.formatDate(hotel.check_out_date)}
                        </p>
                        ` : ''}
                        ${hotel.nights ? `
                        <p style="margin: 4px 0; font-size: 13px;">
                            <i class="fa-regular fa-moon" style="margin-right: 4px;"></i>
                            ${hotel.nights} Nächte
                        </p>
                        ` : ''}
                        ${hotel.room_type ? `
                        <p style="margin: 4px 0; font-size: 13px; color: #6b7280;">
                            ${this.escapeHtml(hotel.room_type)}
                        </p>
                        ` : ''}
                    </div>
                `;

                marker.bindPopup(popupContent);

                // Click handler to open sidebar with folder details and center map
                if (folderId) {
                    marker.on('click', () => {
                        // Open the sidebar
                        openTravelSidebar(folderId);

                        // Wait for sidebar animation, then center on hotel
                        setTimeout(() => {
                            this.panToHotelCentered(hotel.lat, hotel.lng);
                        }, 350);
                    });
                }

                return marker;
            },

            clearAirportMarkers() {
                // Clear all airport markers
                if (this.airportMarkersLayer) {
                    this.airportMarkersLayer.clearLayers();
                }
                this.airportMarkers = [];
            },

            showAirportMarker(airport) {
                if (!airport || !airport.lat || !airport.lng) {
                    console.warn('Airport has no coordinates:', airport);
                    return;
                }

                // Clear existing airport markers
                this.clearAirportMarkers();

                const markerColor = '#6366f1'; // indigo color for airports

                const icon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div style="background-color: ${markerColor}; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                        <i class="fa-solid fa-plane" style="font-size: 14px;"></i>
                    </div>`,
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                });

                const marker = L.marker([airport.lat, airport.lng], { icon: icon });

                // Add popup
                const popupContent = `
                    <div style="min-width: 200px;">
                        <h3 style="font-weight: bold; margin-bottom: 8px; color: #1f2937;">
                            <i class="fa-solid fa-plane" style="color: ${markerColor}; margin-right: 4px;"></i>
                            ${this.escapeHtml(airport.name)}
                        </h3>
                        <p style="margin: 4px 0; font-size: 13px;">
                            <i class="fa-regular fa-location-dot" style="margin-right: 4px;"></i>
                            ${this.escapeHtml(airport.city || '')}
                        </p>
                        <p style="margin: 4px 0; font-size: 13px; font-weight: 600;">
                            <i class="fa-regular fa-plane-departure" style="margin-right: 4px;"></i>
                            ${this.escapeHtml(airport.code)}
                        </p>
                    </div>
                `;

                marker.bindPopup(popupContent);
                this.airportMarkers.push(marker);
                this.airportMarkersLayer.addLayer(marker);

                // Center map on airport
                this.panToAirportCentered(airport.lat, airport.lng);

                // Open popup after centering
                setTimeout(() => {
                    marker.openPopup();
                }, 500);
            },

            panToAirportCentered(lat, lng) {
                if (!this.map) return;

                // Calculate offset to center in visible map area
                const leftSidebarWidth = 380;

                // Get current travel sidebar width
                const sidebar = document.getElementById('travelSidebar');
                let rightSidebarWidth = 0;
                if (sidebar && sidebar.classList.contains('open')) {
                    if (sidebar.classList.contains('w-3x')) {
                        rightSidebarWidth = 1200;
                    } else if (sidebar.classList.contains('w-2x')) {
                        rightSidebarWidth = 800;
                    } else {
                        rightSidebarWidth = 400;
                    }
                }

                // Calculate the center point of the visible map area
                const mapContainer = this.map.getContainer();
                const mapWidth = mapContainer.offsetWidth;
                const mapHeight = mapContainer.offsetHeight;

                // Visible map area (excluding sidebars)
                const visibleMapWidth = mapWidth - leftSidebarWidth - rightSidebarWidth;
                const visibleMapCenterX = leftSidebarWidth + (visibleMapWidth / 2);
                const visibleMapCenterY = mapHeight / 2;

                // Calculate offset in pixels from true center
                const offsetX = visibleMapCenterX - (mapWidth / 2);
                const offsetY = visibleMapCenterY - (mapHeight / 2);

                // Convert pixel offset to lat/lng offset
                const zoomLevel = 12; // Zoom level for airports
                const point = this.map.project([lat, lng], zoomLevel);
                const offsetPoint = L.point(point.x - offsetX, point.y - offsetY);
                const offsetLatLng = this.map.unproject(offsetPoint, zoomLevel);

                // Pan to the offset position with animation
                this.map.setView(offsetLatLng, zoomLevel, {
                    animate: true,
                    duration: 0.5
                });
            },

            panToHotelCentered(lat, lng) {
                if (!this.map) return;

                // Calculate offset to center in visible map area
                const leftSidebarWidth = 380;

                // Get current travel sidebar width
                const sidebar = document.getElementById('travelSidebar');
                let rightSidebarWidth = 0;
                if (sidebar && sidebar.classList.contains('open')) {
                    if (sidebar.classList.contains('w-3x')) {
                        rightSidebarWidth = 1200;
                    } else if (sidebar.classList.contains('w-2x')) {
                        rightSidebarWidth = 800;
                    } else {
                        rightSidebarWidth = 400;
                    }
                }

                // Calculate the center point of the visible map area
                const mapContainer = this.map.getContainer();
                const mapWidth = mapContainer.offsetWidth;
                const mapHeight = mapContainer.offsetHeight;

                // Visible map area (excluding sidebars)
                const visibleMapWidth = mapWidth - leftSidebarWidth - rightSidebarWidth;
                const visibleMapCenterX = leftSidebarWidth + (visibleMapWidth / 2);
                const visibleMapCenterY = mapHeight / 2;

                // Calculate offset in pixels from true center
                const offsetX = visibleMapCenterX - (mapWidth / 2);
                const offsetY = visibleMapCenterY - (mapHeight / 2);

                // Convert pixel offset to lat/lng offset
                const zoomLevel = 14; // Zoom closer for hotels
                const point = this.map.project([lat, lng], zoomLevel);
                const offsetPoint = L.point(point.x - offsetX, point.y - offsetY);
                const offsetLatLng = this.map.unproject(offsetPoint, zoomLevel);

                // Pan to the offset position with animation
                this.map.setView(offsetLatLng, zoomLevel, {
                    animate: true,
                    duration: 0.5
                });
            },

            formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
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
            },

            confirmDelete(traveler) {
                if (!traveler.folder_id) {
                    alert('Diese Reise kann nicht gelöscht werden (keine lokale Folder-ID)');
                    return;
                }

                const folderName = traveler.title || traveler.folder_number || 'diese Reise';
                const message = `Möchten Sie "${folderName}" wirklich löschen?\n\nDies wird die Reise und alle zugehörigen Daten (Teilnehmer, Hotels, Flüge, etc.) unwiderruflich löschen.`;

                if (confirm(message)) {
                    this.deleteFolder(traveler);
                }
            },

            async deleteFolder(traveler) {
                if (!traveler.folder_id) return;

                try {
                    const response = await fetch(`/my-travelers/folder/${traveler.folder_id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Remove traveler from list
                        this.travelers = this.travelers.filter(t => t.id !== traveler.id);

                        // Close sidebar if this traveler was selected
                        if (this.selectedTraveler?.id === traveler.id) {
                            closeTravelSidebar();
                            this.selectedTraveler = null;
                        }

                        // Update map markers
                        this.updateMapMarkers();

                        // Show success notification
                        this.showNotification(result.message || 'Reise erfolgreich gelöscht', 'success');
                    } else {
                        this.showNotification(result.message || 'Fehler beim Löschen der Reise', 'error');
                    }
                } catch (error) {
                    console.error('Error deleting folder:', error);
                    this.showNotification('Verbindungsfehler beim Löschen der Reise', 'error');
                }
            },

            showNotification(message, type = 'success') {
                const notification = document.createElement('div');
                notification.textContent = message;
                notification.style.position = 'fixed';
                notification.style.top = '80px';
                notification.style.right = '20px';
                notification.style.padding = '12px 20px';
                notification.style.borderRadius = '8px';
                notification.style.fontSize = '14px';
                notification.style.fontWeight = '500';
                notification.style.zIndex = '100001';
                notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                notification.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                notification.style.transform = 'translateX(400px)';

                if (type === 'error') {
                    notification.style.backgroundColor = '#fee2e2';
                    notification.style.color = '#991b1b';
                    notification.style.border = '2px solid #fca5a5';
                } else {
                    notification.style.backgroundColor = '#dcfce7';
                    notification.style.color = '#166534';
                    notification.style.border = '2px solid #86efac';
                }

                document.body.appendChild(notification);

                // Animate in
                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                }, 10);

                // Fade out and remove after 4 seconds
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(400px)';
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 4000);
            }
        };
    }

    // Travel Details Sidebar Functions
    let currentTravelSidebarWidth = 1;

    function openTravelSidebar(folderId) {
        const sidebar = document.getElementById('travelSidebar');
        const content = document.getElementById('travelSidebarContent');

        if (!sidebar || !content) return;

        // Show loading state
        content.innerHTML = '<div class="flex items-center justify-center py-8"><i class="fa-regular fa-spinner-third fa-spin text-3xl text-blue-500"></i></div>';

        // Open sidebar
        sidebar.classList.add('open');

        // Load folder details
        loadFolderDetails(folderId);
    }

    function closeTravelSidebar() {
        const sidebar = document.getElementById('travelSidebar');
        if (sidebar) {
            sidebar.classList.remove('open');

            // Clear hotel and airport markers
            const alpineData = Alpine.$data(document.querySelector('[x-data]'));
            if (alpineData) {
                if (alpineData.clearHotelMarkers) {
                    alpineData.clearHotelMarkers();
                }
                if (alpineData.clearAirportMarkers) {
                    alpineData.clearAirportMarkers();
                }
            }

            // Re-center the map after sidebar closes
            recenterMapForSidebar();
        }
    }

    function setTravelSidebarWidth(multiplier) {
        const el = document.getElementById('travelSidebar');
        if (!el) return;

        el.classList.remove('w-2x', 'w-3x');
        if (multiplier === 2) {
            el.classList.add('w-2x');
        } else if (multiplier === 3) {
            el.classList.add('w-3x');
        }
        currentTravelSidebarWidth = multiplier;
        updateTravelSidebarButtons();

        // Re-center the map with the new sidebar width
        recenterMapForSidebar();
    }

    function recenterMapForSidebar() {
        // Wait for CSS transition to complete (300ms)
        setTimeout(() => {
            // Get the Alpine.js component
            const mapComponent = document.querySelector('[x-data]').__x?.$data;
            if (mapComponent && mapComponent.selectedTraveler) {
                mapComponent.panToMarkerCentered(mapComponent.selectedTraveler);
            }
        }, 350);
    }

    function showAirportOnMap(airport, event) {
        // Prevent event bubbling
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Get the Alpine.js component
        const alpineData = Alpine.$data(document.querySelector('[x-data]'));
        if (alpineData && alpineData.showAirportMarker) {
            alpineData.showAirportMarker(airport);
        }
    }

    function zoomToHotel(lat, lng, hotelName, event) {
        // Prevent event bubbling
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Get the Alpine.js component
        const alpineData = Alpine.$data(document.querySelector('[x-data]'));
        if (!alpineData || !alpineData.map) return;

        const map = alpineData.map;

        // Calculate offset to center in visible map area (accounting for sidebars)
        const leftSidebarWidth = 380;

        // Get current travel sidebar width
        const sidebar = document.getElementById('travelSidebar');
        let rightSidebarWidth = 0;
        if (sidebar && sidebar.classList.contains('open')) {
            if (sidebar.classList.contains('w-3x')) {
                rightSidebarWidth = 1200;
            } else if (sidebar.classList.contains('w-2x')) {
                rightSidebarWidth = 800;
            } else {
                rightSidebarWidth = 400;
            }
        }

        // Calculate the center point of the visible map area
        const mapContainer = map.getContainer();
        const mapWidth = mapContainer.offsetWidth;
        const mapHeight = mapContainer.offsetHeight;

        // Visible map area (excluding sidebars)
        const visibleMapWidth = mapWidth - leftSidebarWidth - rightSidebarWidth;
        const visibleMapCenterX = leftSidebarWidth + (visibleMapWidth / 2);
        const visibleMapCenterY = mapHeight / 2;

        // Calculate offset in pixels from true center
        const offsetX = visibleMapCenterX - (mapWidth / 2);
        const offsetY = visibleMapCenterY - (mapHeight / 2);

        // Convert pixel offset to lat/lng offset
        const zoomLevel = 14; // Zoom closer for hotels
        const point = map.project([lat, lng], zoomLevel);
        const offsetPoint = L.point(point.x - offsetX, point.y - offsetY);
        const offsetLatLng = map.unproject(offsetPoint, zoomLevel);

        // Pan and zoom to the hotel location
        map.setView(offsetLatLng, zoomLevel, {
            animate: true,
            duration: 0.5
        });

        // Find and open the hotel marker popup
        setTimeout(() => {
            if (alpineData.hotelMarkers && alpineData.hotelMarkers.length > 0) {
                alpineData.hotelMarkers.forEach(marker => {
                    const markerLatLng = marker.getLatLng();
                    if (Math.abs(markerLatLng.lat - lat) < 0.0001 && Math.abs(markerLatLng.lng - lng) < 0.0001) {
                        marker.openPopup();
                    }
                });
            }
        }, 500);
    }

    function copyToClipboard(text, event) {
        // Prevent event bubbling
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Use modern Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showCopyNotification('In Zwischenablage kopiert', event);
            }).catch(err => {
                console.error('Fehler beim Kopieren:', err);
                // Fallback to older method
                fallbackCopyToClipboard(text, event);
            });
        } else {
            // Fallback for older browsers
            fallbackCopyToClipboard(text, event);
        }
    }

    function fallbackCopyToClipboard(text, event) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
            showCopyNotification('In Zwischenablage kopiert', event);
        } catch (err) {
            console.error('Fehler beim Kopieren:', err);
            showCopyNotification('Kopieren fehlgeschlagen', event, true);
        }

        document.body.removeChild(textArea);
    }

    function showCopyNotification(message, event, isError = false) {
        // Create notification element
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.style.position = 'fixed';
        notification.style.padding = '8px 16px';
        notification.style.borderRadius = '6px';
        notification.style.fontSize = '13px';
        notification.style.fontWeight = '500';
        notification.style.zIndex = '99999';
        notification.style.pointerEvents = 'none';
        notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        notification.style.transition = 'opacity 0.3s ease';

        if (isError) {
            notification.style.backgroundColor = '#fee2e2';
            notification.style.color = '#991b1b';
            notification.style.border = '1px solid #fca5a5';
        } else {
            notification.style.backgroundColor = '#dcfce7';
            notification.style.color = '#166534';
            notification.style.border = '1px solid #86efac';
        }

        // Position near the cursor if event is available
        if (event && event.clientX && event.clientY) {
            notification.style.left = (event.clientX + 10) + 'px';
            notification.style.top = (event.clientY - 30) + 'px';
        } else {
            // Center of screen
            notification.style.top = '50%';
            notification.style.left = '50%';
            notification.style.transform = 'translate(-50%, -50%)';
        }

        document.body.appendChild(notification);

        // Fade out and remove after 2 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 2000);
    }

    function updateTravelSidebarButtons() {
        const decreaseBtn = document.getElementById('travelDecreaseBtn');
        const increaseBtn = document.getElementById('travelIncreaseBtn');

        if (!decreaseBtn || !increaseBtn) return;

        // Reset all buttons
        decreaseBtn.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        decreaseBtn.classList.add('bg-zinc-200', 'hover:bg-zinc-300');
        decreaseBtn.style.pointerEvents = '';

        increaseBtn.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        increaseBtn.classList.add('bg-zinc-200', 'hover:bg-zinc-300');
        increaseBtn.style.pointerEvents = '';

        // Disable decrease button at minimum
        if (currentTravelSidebarWidth <= 1) {
            decreaseBtn.classList.remove('bg-zinc-200', 'hover:bg-zinc-300');
            decreaseBtn.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
            decreaseBtn.style.pointerEvents = 'none';
        }

        // Disable increase button at maximum
        if (currentTravelSidebarWidth >= 3) {
            increaseBtn.classList.remove('bg-zinc-200', 'hover:bg-zinc-300');
            increaseBtn.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
            increaseBtn.style.pointerEvents = 'none';
        }
    }

    function decreaseTravelSidebarWidth() {
        if (currentTravelSidebarWidth > 1) {
            currentTravelSidebarWidth--;
            setTravelSidebarWidth(currentTravelSidebarWidth);
        }
    }

    function increaseTravelSidebarWidth() {
        if (currentTravelSidebarWidth < 3) {
            currentTravelSidebarWidth++;
            setTravelSidebarWidth(currentTravelSidebarWidth);
        }
    }

    async function loadFolderDetails(folderId) {
        const content = document.getElementById('travelSidebarContent');
        const title = document.getElementById('travelSidebarTitle');

        try {
            const response = await fetch(`/my-travelers/folder/${folderId}`);
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Fehler beim Laden der Reisedetails');
            }

            const folder = result.data;

            // Update title
            if (title) {
                title.textContent = folder.folder_name || 'Reisedetails';
            }

            // Render folder details
            content.innerHTML = renderFolderDetails(folder);

            // Collect all hotels from all itineraries and add markers to map
            const allHotels = [];
            if (folder.itineraries) {
                folder.itineraries.forEach(itinerary => {
                    if (itinerary.hotels && itinerary.hotels.length > 0) {
                        allHotels.push(...itinerary.hotels);
                    }
                });
            }

            // Add hotel markers using Alpine data
            const alpineData = Alpine.$data(document.querySelector('[x-data]'));
            if (alpineData && alpineData.addHotelMarkers) {
                alpineData.addHotelMarkers(allHotels, folder.id);
            }

        } catch (error) {
            content.innerHTML = `
                <div class="bg-red-50 border border-red-200 p-4 rounded-lg">
                    <div class="flex items-start">
                        <i class="fa-regular fa-circle-exclamation text-red-500 mt-0.5 mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-red-800">Fehler</h3>
                            <p class="text-sm text-red-700 mt-1">${error.message}</p>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    function renderFolderDetails(folder) {
        let html = '';

        // Folder Header
        html += `
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-500">Vorgangsnummer</span>
                    <span class="text-sm font-mono text-gray-700">${escapeHtml(folder.folder_number || '-')}</span>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">${escapeHtml(folder.folder_name || 'Unbenannte Reise')}</h2>
                <div class="flex items-center gap-4 text-sm text-gray-600">
                    <div>
                        <i class="fa-regular fa-calendar mr-1"></i>
                        ${formatDate(folder.travel_start_date)} - ${formatDate(folder.travel_end_date)}
                    </div>
                    <div>
                        <i class="fa-regular fa-location-dot mr-1"></i>
                        ${escapeHtml(folder.primary_destination || '-')}
                    </div>
                </div>
                ${folder.status ? `
                <div class="mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusBadgeClass(folder.status)}">
                        ${getStatusLabel(folder.status)}
                    </span>
                </div>
                ` : ''}
            </div>
        `;

        // Custom Fields
        if (folder.custom_fields && folder.custom_fields.some(field => field.label && field.value)) {
            html += `
                <div class="mb-6 bg-blue-50 border border-blue-200 p-4 rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">
                        <i class="fa-regular fa-link mr-2"></i>
                        Externe Verknüpfungen & Buchungsnummern
                    </h3>
                    <div class="space-y-2 text-sm">
            `;

            folder.custom_fields.forEach(field => {
                if (field.label && field.value) {
                    // Check if value is a URL
                    const isUrl = field.value.startsWith('http://') || field.value.startsWith('https://');

                    html += `
                        <div class="flex justify-between items-start">
                            <span class="text-gray-600 font-medium">${escapeHtml(field.label)}:</span>
                            ${isUrl
                                ? `<a href="${escapeHtml(field.value)}" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1">
                                    ${escapeHtml(field.value)}
                                    <i class="fa-solid fa-external-link text-xs"></i>
                                </a>`
                                : `<span class="font-medium text-gray-900 text-right">${escapeHtml(field.value)}</span>`
                            }
                        </div>
                    `;
                }
            });

            html += `
                    </div>
                </div>
            `;
        }

        // Customer Information
        if (folder.customer) {
            html += `
                <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">
                        <i class="fa-regular fa-user mr-2"></i>
                        Kunde
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Name:</span>
                            <span class="font-medium text-gray-900">
                                ${escapeHtml(folder.customer.salutation || '')}
                                ${escapeHtml(folder.customer.first_name || '')}
                                ${escapeHtml(folder.customer.last_name || '')}
                            </span>
                        </div>
                        ${folder.customer.email ? `
                        <div class="flex justify-between">
                            <span class="text-gray-600">E-Mail:</span>
                            <span class="font-medium text-gray-900">${escapeHtml(folder.customer.email)}</span>
                        </div>
                        ` : ''}
                        ${folder.customer.phone ? `
                        <div class="flex justify-between">
                            <span class="text-gray-600">Telefon:</span>
                            <span class="font-medium text-gray-900">${escapeHtml(folder.customer.phone)}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        // Participants
        if (folder.participants && folder.participants.length > 0) {
            html += `
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">
                        <i class="fa-regular fa-users mr-2"></i>
                        Reiseteilnehmer (${folder.participants.length})
                    </h3>
                    <div class="space-y-2">
            `;

            folder.participants.forEach(participant => {
                html += `
                    <div class="bg-white border border-gray-200 p-3 rounded-lg text-sm">
                        <div class="font-medium text-gray-900">
                            ${escapeHtml(participant.salutation || '')}
                            ${escapeHtml(participant.first_name || '')}
                            ${escapeHtml(participant.last_name || '')}
                            ${participant.is_main_contact ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Hauptkontakt</span>' : ''}
                        </div>
                        <div class="mt-1 text-gray-600 space-y-1">
                            ${participant.birth_date ? `<div><i class="fa-regular fa-cake-candles mr-1"></i>${formatDate(participant.birth_date)}</div>` : ''}
                            ${participant.nationality ? `<div><i class="fa-regular fa-flag mr-1"></i>${escapeHtml(participant.nationality)}</div>` : ''}
                            ${participant.passport_number ? `<div><i class="fa-regular fa-passport mr-1"></i>${escapeHtml(participant.passport_number)}</div>` : ''}
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        }

        // Itineraries
        if (folder.itineraries && folder.itineraries.length > 0) {
            html += `
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">
                        <i class="fa-regular fa-route mr-2"></i>
                        Reiseverlauf (${folder.itineraries.length})
                    </h3>
            `;

            folder.itineraries.forEach(itinerary => {
                html += `
                    <div class="bg-white border border-gray-200 p-4 rounded-lg mb-3">
                        <h4 class="font-semibold text-gray-900 mb-2">${escapeHtml(itinerary.itinerary_name || 'Unbenannt')}</h4>
                        <div class="text-sm text-gray-600 mb-3">
                            <div><i class="fa-regular fa-calendar mr-1"></i>${formatDate(itinerary.start_date)} - ${formatDate(itinerary.end_date)}</div>
                            ${itinerary.booking_reference ? `<div class="mt-1"><i class="fa-regular fa-ticket mr-1"></i>${escapeHtml(itinerary.booking_reference)}</div>` : ''}
                        </div>

                        ${renderItineraryServices(itinerary)}
                    </div>
                `;
            });

            html += `</div>`;
        }

        return html;
    }

    function renderItineraryServices(itinerary) {
        let html = '';

        // Hotels
        if (itinerary.hotels && itinerary.hotels.length > 0) {
            html += '<div class="mb-3"><div class="text-xs font-semibold text-gray-700 mb-2">Hotels</div><div class="space-y-2">';
            itinerary.hotels.forEach(hotel => {
                const hasLocation = hotel.lat && hotel.lng;
                const locationButton = hasLocation ?
                    `<button onclick="zoomToHotel(${hotel.lat}, ${hotel.lng}, '${escapeHtml(hotel.hotel_name || 'Hotel').replace(/'/g, "\\'")}', event)"
                             class="ml-2 text-blue-600 hover:text-blue-800 transition-colors"
                             title="Auf Karte anzeigen">
                        <i class="fa-solid fa-location-dot"></i>
                    </button>` : '';

                html += `
                    <div class="bg-gray-50 p-3 rounded text-sm">
                        <div class="font-medium text-gray-900 flex items-center">
                            <i class="fa-regular fa-hotel mr-1"></i>
                            ${escapeHtml(hotel.hotel_name || '-')}
                            ${locationButton}
                        </div>
                        ${hotel.booking_reference ? `
                        <div class="mt-1 text-xs">
                            <i class="fa-regular fa-ticket mr-1"></i>
                            <span
                                onclick="copyToClipboard('${escapeHtml(hotel.booking_reference).replace(/'/g, "\\'")}', event)"
                                class="cursor-pointer text-blue-600 hover:text-blue-800 hover:underline font-medium"
                                title="Zum Kopieren anklicken"
                            >${escapeHtml(hotel.booking_reference)}</span>
                        </div>
                        ` : ''}
                        <div class="text-gray-600 mt-1">
                            <div>${escapeHtml(hotel.city || '')}, ${escapeHtml(hotel.country_code || '')}</div>
                            <div class="text-xs mt-1">
                                ${formatDate(hotel.check_in_date)} - ${formatDate(hotel.check_out_date)}
                                ${hotel.nights ? ` (${hotel.nights} Nächte)` : ''}
                            </div>
                            ${hotel.room_type ? `<div class="text-xs">${escapeHtml(hotel.room_type)}</div>` : ''}
                        </div>
                    </div>
                `;
            });
            html += '</div></div>';
        }

        // Flights
        if (itinerary.flights && itinerary.flights.length > 0) {
            html += '<div class="mb-3"><div class="text-xs font-semibold text-gray-700 mb-2"><i class="fa-regular fa-plane-departure mr-1"></i>Flüge</div>';

            itinerary.flights.forEach(flight => {
                html += '<div class="bg-white border border-gray-200 p-3 rounded text-sm mb-3">';

                // Flight overview header
                if (flight.booking_reference || flight.service_type) {
                    html += '<div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200">';
                    if (flight.booking_reference) {
                        html += `<div class="text-xs font-medium text-gray-700"><i class="fa-regular fa-ticket mr-1"></i>${escapeHtml(flight.booking_reference)}</div>`;
                    }
                    if (flight.service_type === 'multi_leg') {
                        html += `<span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded">Multi-Leg-Flug</span>`;
                    }
                    html += '</div>';
                }

                // Display segments
                if (flight.segments && flight.segments.length > 0) {
                    flight.segments.forEach((segment, index) => {
                        const depTime = parseDateTime(segment.departure_time);
                        const arrTime = parseDateTime(segment.arrival_time);

                        html += `
                            <div class="relative ${index > 0 ? 'mt-3 pt-3 border-t border-gray-100' : ''}">
                                <!-- Segment Badge -->
                                <div class="absolute -left-1 ${index > 0 ? 'top-3' : 'top-0'} w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs font-bold">
                                    ${segment.segment_number}
                                </div>

                                <div class="pl-8">
                                    <!-- Flight number -->
                                    <div class="font-semibold text-gray-900 mb-1">
                                        ${escapeHtml(segment.airline_code || '')} ${escapeHtml(segment.flight_number || '')}
                                    </div>

                                    <!-- Route -->
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="flex-1">
                                            <div class="font-bold ${segment.departure_airport ? 'text-blue-600 cursor-pointer hover:text-blue-800 hover:underline' : 'text-blue-600'}"
                                                 ${segment.departure_airport ? `onclick="showAirportOnMap(${JSON.stringify(segment.departure_airport).replace(/"/g, '&quot;')}, event)"` : ''}
                                                 ${segment.departure_airport ? 'title="Auf Karte anzeigen"' : ''}>
                                                ${escapeHtml(segment.departure_airport_code || '')}
                                            </div>
                                            ${segment.departure_terminal ? `<div class="text-xs text-gray-500">Terminal ${segment.departure_terminal}</div>` : ''}
                                            ${depTime ? `<div class="text-xs font-medium text-gray-900">${depTime}</div>` : ''}
                                        </div>
                                        <div class="flex-shrink-0 text-gray-400">
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </div>
                                        <div class="flex-1 text-right">
                                            <div class="font-bold ${segment.arrival_airport ? 'text-blue-600 cursor-pointer hover:text-blue-800 hover:underline' : 'text-blue-600'}"
                                                 ${segment.arrival_airport ? `onclick="showAirportOnMap(${JSON.stringify(segment.arrival_airport).replace(/"/g, '&quot;')}, event)"` : ''}
                                                 ${segment.arrival_airport ? 'title="Auf Karte anzeigen"' : ''}>
                                                ${escapeHtml(segment.arrival_airport_code || '')}
                                            </div>
                                            ${segment.arrival_terminal ? `<div class="text-xs text-gray-500">Terminal ${segment.arrival_terminal}</div>` : ''}
                                            ${arrTime ? `<div class="text-xs font-medium text-gray-900">${arrTime}</div>` : ''}
                                        </div>
                                    </div>

                                    <!-- Flight details -->
                                    <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-600">
                                        ${segment.aircraft_type ? `<div><i class="fa-solid fa-plane mr-1"></i>${escapeHtml(segment.aircraft_type)}</div>` : ''}
                                        ${segment.cabin_class ? `<div><i class="fa-solid fa-chair mr-1"></i>${escapeHtml(segment.cabin_class)}</div>` : ''}
                                        ${segment.duration_minutes ? `<div><i class="fa-solid fa-clock mr-1"></i>${formatDuration(segment.duration_minutes)}</div>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    // Fallback if no segments
                    html += `
                        <div class="text-gray-600 text-sm">
                            <i class="fa-regular fa-plane mr-2"></i>
                            ${escapeHtml(flight.origin_airport_code || '')} → ${escapeHtml(flight.destination_airport_code || '')}
                        </div>
                    `;
                }

                html += '</div>';
            });

            html += '</div>';
        }

        // Ships
        if (itinerary.ships && itinerary.ships.length > 0) {
            html += '<div class="mb-3"><div class="text-xs font-semibold text-gray-700 mb-2">Schiffe</div><div class="space-y-2">';
            itinerary.ships.forEach(ship => {
                html += `
                    <div class="bg-gray-50 p-3 rounded text-sm">
                        <div class="font-medium text-gray-900"><i class="fa-regular fa-ship mr-1"></i>${escapeHtml(ship.ship_name || '-')}</div>
                        <div class="text-gray-600 text-xs mt-1">
                            ${formatDate(ship.departure_date)} - ${formatDate(ship.arrival_date)}
                        </div>
                    </div>
                `;
            });
            html += '</div></div>';
        }

        // Car Rentals
        if (itinerary.car_rentals && itinerary.car_rentals.length > 0) {
            html += '<div class="mb-3"><div class="text-xs font-semibold text-gray-700 mb-2">Mietwagen</div><div class="space-y-2">';
            itinerary.car_rentals.forEach(car => {
                html += `
                    <div class="bg-gray-50 p-3 rounded text-sm">
                        <div class="font-medium text-gray-900"><i class="fa-regular fa-car mr-1"></i>${escapeHtml(car.vehicle_type || '-')}</div>
                        <div class="text-gray-600 text-xs mt-1">
                            ${escapeHtml(car.pickup_location || '')} → ${escapeHtml(car.dropoff_location || '')}
                            <div>${formatDate(car.pickup_date)} - ${formatDate(car.dropoff_date)}</div>
                        </div>
                    </div>
                `;
            });
            html += '</div></div>';
        }

        return html;
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function parseDateTime(dateTimeString) {
        if (!dateTimeString) return null;

        // Convert "2025-10-12 22:15" to ISO format "2025-10-12T22:15:00"
        const isoString = dateTimeString.replace(' ', 'T') + ':00';
        const date = new Date(isoString);

        if (isNaN(date.getTime())) return null;

        return date.toLocaleString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatDuration(minutes) {
        if (!minutes) return '-';
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours}h ${mins}min`;
    }

    function getStatusBadgeClass(status) {
        switch (status) {
            case 'confirmed': return 'bg-green-100 text-green-800';
            case 'draft': return 'bg-gray-100 text-gray-800';
            case 'cancelled': return 'bg-red-100 text-red-800';
            default: return 'bg-blue-100 text-blue-800';
        }
    }

    function getStatusLabel(status) {
        switch (status) {
            case 'confirmed': return 'Bestätigt';
            case 'draft': return 'Entwurf';
            case 'cancelled': return 'Storniert';
            default: return status;
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize sidebar buttons on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateTravelSidebarButtons();
    });

    // Initialize Laravel Echo for real-time updates
    @auth('customer')
    document.addEventListener('DOMContentLoaded', function() {
        if (window.Echo === undefined) {
            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: '{{ config('broadcasting.connections.reverb.key') }}',
                wsHost: '{{ config('broadcasting.connections.reverb.host') }}',
                wsPort: {{ config('broadcasting.connections.reverb.port') }},
                wssPort: {{ config('broadcasting.connections.reverb.port') }},
                forceTLS: ('{{ config('broadcasting.connections.reverb.scheme') }}' === 'https'),
                enabledTransports: ['ws', 'wss']
            });
        }

        // Subscribe to customer's private channel for folder updates
        Echo.private('customer.{{ auth('customer')->id() }}')
            .listen('.folder.imported', (event) => {
                console.log('Folder imported/updated:', event);

                // Show notification
                const notificationTitle = event.was_updated ? 'Reise aktualisiert' : 'Neue Reise importiert';
                const notificationMessage = `${notificationTitle}: ${event.folder_name} (${event.folder_number})`;

                // Get Alpine data and show notification
                const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                if (alpineData && alpineData.showNotification) {
                    alpineData.showNotification(notificationMessage, 'success');
                }

                // Reload the travelers list
                if (alpineData && alpineData.loadTravelers) {
                    alpineData.loadTravelers();
                }
            });
    });
    @endauth
</script>
</body>
</html>
