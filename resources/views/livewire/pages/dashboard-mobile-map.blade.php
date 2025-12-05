<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Global Travel Monitor - Karte</title>

    {{-- SEO Meta Tags --}}
    <meta name="description" content="Global Travel Monitor - Weltkarte mit aktuellen Reiseereignissen.">
    <meta name="robots" content="index, follow">

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <!-- Leaflet JS -->
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        /* Map Container */
        #map {
            position: fixed;
            top: 56px;
            left: 0;
            right: 0;
            bottom: 56px;
            z-index: 1;
        }

        /* Priority Colors */
        .priority-low { background-color: #22c55e; }
        .priority-info { background-color: #3b82f6; }
        .priority-medium { background-color: #f97316; }
        .priority-high { background-color: #ef4444; }
        .priority-critical { background-color: #7c2d12; }

        /* Custom Marker */
        .custom-marker {
            background: none;
            border: none;
        }
        .custom-marker i {
            font-size: 24px;
            filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.5));
        }

        /* Marker Popup */
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            padding: 0;
        }
        .leaflet-popup-content {
            margin: 0;
            min-width: 250px;
        }
        .marker-popup {
            padding: 12px;
        }
        .marker-popup h3 {
            font-weight: 600;
            font-size: 14px;
            margin: 0 0 8px 0;
            color: #1f2937;
            line-height: 1.3;
        }
        .marker-popup .priority-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 600;
            color: white;
            margin-bottom: 8px;
        }
        .marker-popup .countries {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .marker-popup .details-btn {
            display: block;
            width: 100%;
            padding: 8px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }
        .marker-popup .details-btn:active {
            background: #2563eb;
        }

        /* Hide elements until Alpine.js initializes */
        [x-cloak] { display: none !important; }

        /* Bottom Sheet */
        .bottom-sheet {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 56px;
            background: white;
            border-radius: 16px 16px 0 0;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(100%);
            transition: transform 0.3s ease;
            z-index: 100;
            max-height: 60vh;
            overflow: hidden;
        }
        .bottom-sheet.open {
            transform: translateY(0);
        }
        .bottom-sheet-handle {
            width: 40px;
            height: 4px;
            background: #d1d5db;
            border-radius: 2px;
            margin: 12px auto;
        }
        .bottom-sheet-content {
            padding: 0 16px 16px;
            overflow-y: auto;
            max-height: calc(60vh - 40px);
        }

        /* FAB Button */
        .fab-button {
            position: fixed;
            right: 16px;
            bottom: 72px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            border: none;
            cursor: pointer;
        }
        .fab-button:active {
            transform: scale(0.95);
        }

        /* Drawer overlay */
        .drawer-overlay {
            background: rgba(0, 0, 0, 0.5);
            transition: opacity 0.3s ease;
        }

        .drawer-content {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .drawer-content.open {
            transform: translateX(0);
        }

        /* Bottom safe area */
        .bottom-safe {
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 56px;
            left: 0;
            right: 0;
            bottom: 56px;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 200;
        }

        /* Spinner */
        .spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Cluster marker customization */
        .marker-cluster-small {
            background-color: rgba(59, 130, 246, 0.6);
        }
        .marker-cluster-small div {
            background-color: rgba(59, 130, 246, 0.8);
        }
        .marker-cluster-medium {
            background-color: rgba(249, 115, 22, 0.6);
        }
        .marker-cluster-medium div {
            background-color: rgba(249, 115, 22, 0.8);
        }
        .marker-cluster-large {
            background-color: rgba(239, 68, 68, 0.6);
        }
        .marker-cluster-large div {
            background-color: rgba(239, 68, 68, 0.8);
        }
    </style>
</head>
<body x-data="mobileMapApp()">

    <!-- Drawer Overlay -->
    <div x-show="drawerOpen"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="drawerOpen = false"
         class="fixed inset-0 z-40 drawer-overlay"
         style="display: none;">
    </div>

    <!-- Drawer Menu -->
    <div x-show="drawerOpen"
         :class="{ 'open': drawerOpen }"
         class="fixed top-0 left-0 h-full w-72 bg-white z-50 drawer-content shadow-xl"
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:leave="transition ease-in duration-200">

        <!-- Drawer Header -->
        <div class="bg-gray-100 border-b border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('android-chrome-192x192.png') }}" alt="GTM" class="h-10 w-10">
                <div>
                    <div class="font-semibold text-gray-900">Global Travel Monitor</div>
                    <div class="text-xs text-gray-500">Passolution GmbH</div>
                </div>
            </div>
        </div>

        <!-- Drawer Items -->
        <nav class="py-2">
            <a href="{{ route('home') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i class="fa-solid fa-rss w-6 text-center"></i>
                <span>Nachrichten Feed</span>
            </a>

            <a href="{{ route('home') }}?view=map" class="flex items-center gap-4 px-4 py-3 text-gray-700 bg-blue-50 border-r-4 border-blue-600">
                <i class="fa-regular fa-map w-6 text-center text-blue-600"></i>
                <span class="font-medium text-blue-600">Karte</span>
            </a>

            @if(config('app.navigation_entry_conditions_enabled', true))
            <a href="{{ route('entry-conditions') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i class="fa-solid fa-earth-europe w-6 text-center"></i>
                <span>Einreisebestimmungen</span>
            </a>
            @endif

            @if(config('app.navigation_booking_enabled', true))
            <a href="{{ route('booking') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i class="fa-regular fa-calendar-check w-6 text-center"></i>
                <span>Buchungsmöglichkeit</span>
            </a>
            @endif

            @if(config('app.navigation_cruise_enabled', true))
            <a href="{{ route('cruise') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                <i class="fa-regular fa-ship w-6 text-center"></i>
                <span>Kreuzfahrt</span>
            </a>
            @endif

            <div class="border-t border-gray-200 my-2"></div>

            @auth('customer')
                @if(auth('customer')->user()->branch_management_active)
                <a href="{{ route('branches') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fa-regular fa-building w-6 text-center"></i>
                    <span>Filialen & Standorte</span>
                </a>
                @endif

                <a href="{{ route('my-travelers') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fa-regular fa-users w-6 text-center"></i>
                    <span>Meine Reisenden</span>
                </a>

                <div class="border-t border-gray-200 my-2"></div>

                <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fa-regular fa-user w-6 text-center"></i>
                    <span>{{ auth('customer')->user()->name }}</span>
                </a>

                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-4 px-4 py-3 text-red-600 hover:bg-red-50">
                        <i class="fa-regular fa-sign-out w-6 text-center"></i>
                        <span>Abmelden</span>
                    </button>
                </form>
            @else
                @if(config('app.customer_login_enabled', true))
                <a href="{{ route('customer.login') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fa-regular fa-sign-in w-6 text-center"></i>
                    <span>Anmelden</span>
                </a>
                @endif

                @if(config('app.customer_registration_enabled', true))
                <a href="{{ route('customer.register') }}" class="flex items-center gap-4 px-4 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fa-regular fa-user-plus w-6 text-center"></i>
                    <span>Registrieren</span>
                </a>
                @endif
            @endauth
        </nav>

        <!-- Drawer Footer -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 text-xs text-gray-500">
            <div class="flex flex-wrap gap-x-4 gap-y-1 mb-2">
                <a href="https://www.passolution.de/datenschutz/" target="_blank" class="text-blue-600 hover:underline">Datenschutz</a>
                <a href="https://www.passolution.de/agb/" target="_blank" class="text-blue-600 hover:underline">AGB</a>
                <a href="https://www.passolution.de/impressum/" target="_blank" class="text-blue-600 hover:underline">Impressum</a>
                <a href="#" onclick="event.preventDefault(); document.getElementById('disclaimerModal')?.classList.remove('hidden');" class="text-blue-600 hover:underline">Haftungsausschluss</a>
            </div>
            <div class="flex justify-between">
                <span>© 2025 Passolution GmbH</span>
                <span>Version 1.0.2</span>
            </div>
        </div>
    </div>

    <!-- App Bar -->
    <header class="fixed top-0 left-0 right-0 bg-white shadow-sm z-30 h-14">
        <div class="flex items-center justify-between h-full px-4">
            <!-- Left: Hamburger -->
            <button @click="drawerOpen = true" class="p-2 -ml-2 text-gray-700">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <!-- Center: Title -->
            <div class="flex items-center gap-2">
                <img src="{{ asset('android-chrome-192x192.png') }}" alt="GTM" class="h-6 w-6">
                <span class="font-semibold text-gray-800">Karte</span>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-1">
                <button @click="centerMap()" class="p-2 text-gray-700" title="Karte zentrieren">
                    <i class="fas fa-crosshairs text-lg"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Map Container -->
    <div id="map"></div>

    <!-- Loading Overlay -->
    <div x-show="loading" class="loading-overlay">
        <div class="text-center">
            <i class="fas fa-spinner spinner text-3xl text-blue-600 mb-3"></i>
            <p class="text-gray-500">Karte wird geladen...</p>
        </div>
    </div>

    <!-- Event Count Badge -->
    <div class="fixed top-16 left-4 bg-white rounded-full shadow-md px-3 py-1 z-20">
        <span class="text-sm text-gray-600">
            <span x-text="eventCount"></span> Ereignisse
        </span>
    </div>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-30 bottom-safe">
        <div class="flex justify-around items-center h-14">
            <a href="{{ route('home') }}" class="flex flex-col items-center justify-center flex-1 py-2 text-gray-500">
                <i class="fa-solid fa-rss text-xl"></i>
                <span class="text-xs mt-1">Feed</span>
            </a>
            <a href="{{ route('home') }}?view=map" class="flex flex-col items-center justify-center flex-1 py-2 text-blue-600">
                <i class="fa-solid fa-map text-xl"></i>
                <span class="text-xs mt-1">Karte</span>
            </a>
            <button @click="drawerOpen = true" class="flex flex-col items-center justify-center flex-1 py-2 text-gray-500">
                <i class="fa-solid fa-ellipsis text-xl"></i>
                <span class="text-xs mt-1">Mehr</span>
            </button>
        </div>
    </nav>

    <!-- Bottom Sheet for Event Details -->
    <div x-show="selectedEvent" x-cloak :class="{ 'open': selectedEvent }" class="bottom-sheet">
        <div class="bottom-sheet-handle"></div>
        <div class="bottom-sheet-content" x-show="selectedEvent">
            <template x-if="selectedEvent">
                <div>
                    <!-- Countries -->
                    <div class="flex flex-wrap gap-2 mb-2" x-show="selectedEvent.countries && selectedEvent.countries.length > 0">
                        <template x-for="country in selectedEvent.countries" :key="country.id">
                            <span class="bg-gray-100 text-gray-700 text-sm px-2 py-1 rounded-full flex items-center gap-2">
                                <span x-text="getCountryFlag(country.iso_code)"></span>
                                <span x-text="country.name"></span>
                            </span>
                        </template>
                    </div>

                    <!-- Priority Badge + Event Types (same row) -->
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-3 py-1 rounded-full text-white text-xs font-semibold"
                              :class="'priority-' + (selectedEvent.priority || 'info')"
                              x-text="getPriorityLabel(selectedEvent.priority)"></span>
                        <template x-for="(eventType, index) in selectedEvent.event_types" :key="index">
                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"
                                  x-text="typeof eventType === 'string' ? eventType : eventType.name"></span>
                        </template>
                    </div>

                    <!-- Title -->
                    <h3 class="text-lg font-bold text-gray-900 mb-2" x-text="selectedEvent.title"></h3>

                    <!-- Date -->
                    <div class="flex items-center gap-1 mb-4 text-sm">
                        <i class="fas fa-clock text-gray-400 w-5"></i>
                        <div>
                            <span class="text-gray-500">Veröffentlicht:</span>
                            <span class="text-gray-900 ml-1" x-text="formatDate(selectedEvent.created_at)"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a :href="'/?event=' + selectedEvent.id"
                           class="flex-1 bg-blue-600 text-white text-center py-3 rounded-lg font-medium">
                            Details anzeigen
                        </a>
                        <button @click="selectedEvent = null"
                                class="px-4 py-3 bg-gray-100 text-gray-700 rounded-lg">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script>
        // Global map variable
        let map;
        let markerClusterGroup;
        let markers = [];

        function mobileMapApp() {
            return {
                drawerOpen: false,
                loading: true,
                events: [],
                eventCount: 0,
                selectedEvent: null,

                init() {
                    this.initializeMap();
                    this.loadEvents();
                },

                initializeMap() {
                    // Create map
                    map = L.map('map', {
                        worldCopyJump: false,
                        maxBounds: [[-90, -180], [90, 180]],
                        minZoom: 2,
                        zoomControl: false
                    }).setView([20, 0], 2);

                    // Add zoom control to bottom-left
                    L.control.zoom({
                        position: 'bottomleft'
                    }).addTo(map);

                    // Add tile layer
                    L.tileLayer('https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap',
                        maxZoom: 19
                    }).addTo(map);

                    // Initialize marker cluster group (matching desktop style)
                    markerClusterGroup = L.markerClusterGroup({
                        maxClusterRadius: 40,
                        spiderfyOnMaxZoom: true,
                        showCoverageOnHover: false,
                        zoomToBoundsOnClick: true,
                        spiderfyDistanceMultiplier: 2.5,
                        animateAddingMarkers: false,
                        iconCreateFunction: function(cluster) {
                            const childCount = cluster.getChildCount();
                            let size = 40;
                            if (childCount < 10) {
                                size = 35;
                            } else if (childCount < 100) {
                                size = 40;
                            } else {
                                size = 45;
                            }
                            return new L.DivIcon({
                                html: '<div style="background: #3B4154; color: white; border-radius: 50%; width: ' + size + 'px; height: ' + size + 'px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);"><span>' + childCount + '</span></div>',
                                className: 'custom-cluster-icon',
                                iconSize: new L.Point(size, size)
                            });
                        }
                    });

                    map.addLayer(markerClusterGroup);
                },

                async loadEvents() {
                    try {
                        const response = await fetch('/api/custom-events/dashboard-events');
                        const data = await response.json();

                        // API returns { data: { events: [...] } }
                        const eventsArray = data.data?.events || data.events || [];
                        this.events = eventsArray.map(event => ({
                            id: event.id,
                            title: event.title,
                            description: event.description || event.popup_content,
                            priority: event.priority || 'info',
                            start_date: event.start_date,
                            end_date: event.end_date,
                            created_at: event.created_at,
                            countries: event.countries || [],
                            event_types: event.event_types || [],
                            latitude: event.latitude,
                            longitude: event.longitude,
                            marker_icon: event.marker_icon,
                            marker_color: event.marker_color
                        }));

                        this.eventCount = this.events.length;
                        this.addMarkersToMap();

                        // Check if there's an event parameter in URL
                        const urlParams = new URLSearchParams(window.location.search);
                        const eventId = urlParams.get('event');
                        if (eventId) {
                            const event = this.events.find(e => e.id == eventId);
                            if (event && event.latitude && event.longitude) {
                                map.setView([event.latitude, event.longitude], 8);
                                this.selectedEvent = event;
                            }
                        }
                    } catch (err) {
                        console.error('Error loading events:', err);
                    } finally {
                        this.loading = false;
                    }
                },

                addMarkersToMap() {
                    const self = this;

                    // Clear existing markers
                    markers = [];
                    markerClusterGroup.clearLayers();

                    this.events.forEach(event => {
                        // Skip events without coordinates
                        if (!event.latitude || !event.longitude) {
                            // Try to get coordinates from countries
                            if (event.countries && event.countries.length > 0) {
                                const country = event.countries[0];
                                if (country.latitude && country.longitude) {
                                    event.latitude = country.latitude;
                                    event.longitude = country.longitude;
                                } else {
                                    return;
                                }
                            } else {
                                return;
                            }
                        }

                        const marker = this.createMarker(event);
                        markers.push(marker);
                        markerClusterGroup.addLayer(marker);
                    });
                },

                createMarker(event) {
                    const self = this;
                    const iconSize = 28;

                    // Determine marker color based on priority (same as desktop)
                    const priorityColors = {
                        low: '#22c55e',
                        info: '#3b82f6',
                        medium: '#f97316',
                        high: '#ef4444',
                        critical: '#7c2d12'
                    };

                    const markerColor = event.marker_color || priorityColors[event.priority] || priorityColors.info;
                    let iconClass = event.marker_icon || 'fa-solid fa-location-pin';

                    // Ensure fa-solid prefix is present
                    if (!iconClass.includes('fa-solid') && !iconClass.includes('fa-regular') && !iconClass.includes('fa-brands')) {
                        iconClass = 'fa-solid ' + iconClass;
                    }

                    // Create icon HTML matching desktop style
                    const iconHtml = `
                        <div style="
                            background-color: ${markerColor};
                            border: 2px solid white;
                            border-radius: 50%;
                            width: ${iconSize}px;
                            height: ${iconSize}px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 2px 6px rgba(0,0,0,0.4);
                        ">
                            <i class="${iconClass}" style="color: #FFFFFF; font-size: ${iconSize * 0.5}px; text-shadow: 0 1px 2px rgba(0,0,0,0.3);"></i>
                        </div>
                    `;

                    // Create custom icon
                    const customIcon = L.divIcon({
                        html: iconHtml,
                        className: 'custom-marker',
                        iconSize: [iconSize, iconSize],
                        iconAnchor: [iconSize / 2, iconSize / 2]
                    });

                    const marker = L.marker([event.latitude, event.longitude], {
                        icon: customIcon
                    });

                    // Click handler to show bottom sheet
                    marker.on('click', function() {
                        self.selectedEvent = event;
                        self.trackEventClick(event.id, 'mobile_map_marker');
                    });

                    return marker;
                },

                trackEventClick(eventId, clickType) {
                    const numericEventId = typeof eventId === 'string' ? parseInt(eventId, 10) : eventId;
                    if (!numericEventId || isNaN(numericEventId)) return;

                    // Use sendBeacon for reliable tracking
                    const data = JSON.stringify({
                        event_id: numericEventId,
                        click_type: clickType
                    });

                    const blob = new Blob([data], { type: 'application/json' });
                    navigator.sendBeacon('/api/custom-events/track-click', blob);
                },

                centerMap() {
                    map.setView([20, 0], 2);
                },

                // Helpers
                getCountryFlag(isoCode) {
                    if (!isoCode) return '🌍';
                    const codePoints = isoCode
                        .toUpperCase()
                        .split('')
                        .map(char => 127397 + char.charCodeAt());
                    return String.fromCodePoint(...codePoints);
                },

                getPriorityLabel(priority) {
                    const labels = {
                        low: 'Niedrig',
                        info: 'Information',
                        medium: 'Mittel',
                        high: 'Wichtig',
                        critical: 'Kritisch'
                    };
                    return labels[priority] || 'Information';
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('de-DE', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            };
        }
    </script>
</body>
</html>
