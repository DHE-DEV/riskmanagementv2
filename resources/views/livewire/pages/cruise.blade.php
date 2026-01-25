<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kreuzfahrt - Global Travel Monitor</title>

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
    <!-- Leaflet MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <!-- Font Awesome Einbindung -->
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
            width: 320px;
            background: #e5e7eb;
            overflow-y: auto;
            height: 100%;
            position: relative;
            z-index: 10;
        }

        /* Map Container - nimmt restlichen Platz ein */
        .map-container {
            flex: 1;
            position: relative;
            height: 100%;
            overflow: hidden;
        }

        #cruise-map {
            width: 100%;
            height: 100%;
        }

        /* Map Legend */
        .map-legend {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            font-size: 12px;
            display: none;
        }

        .map-legend.active {
            display: block;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 4px 0;
        }

        .legend-marker {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: white;
            font-weight: bold;
        }

        /* Marker Cluster Styling */
        .marker-cluster {
            background-clip: padding-box;
            border-radius: 20px;
        }

        .marker-cluster div {
            width: 40px;
            height: 40px;
            margin-left: 0;
            margin-top: 0;
            text-align: center;
            border-radius: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .marker-cluster span {
            line-height: 40px;
            font-size: 14px;
        }

        .marker-cluster-small {
            background-color: rgba(59, 130, 246, 0.6);
        }

        .marker-cluster-small div {
            background-color: rgba(59, 130, 246, 0.8);
            color: white;
        }

        .marker-cluster-medium {
            background-color: rgba(139, 92, 246, 0.6);
        }

        .marker-cluster-medium div {
            background-color: rgba(139, 92, 246, 0.8);
            color: white;
        }

        .marker-cluster-large {
            background-color: rgba(239, 68, 68, 0.6);
        }

        .marker-cluster-large div {
            background-color: rgba(239, 68, 68, 0.8);
            color: white;
        }

        .marker-cluster-roundtrip {
            background-color: rgba(34, 197, 94, 0.6);
        }

        .marker-cluster-roundtrip div {
            background-color: rgba(34, 197, 94, 0.8);
            color: white;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <x-public-header />

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Black Navigation Sidebar -->
            <x-public-navigation active="cruise" />

            <!-- Container Sidebar -->
            <aside class="sidebar">
                <!-- Kreuzfahrt Info -->
                <div class="bg-white shadow-sm">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fa-regular fa-ship text-blue-500"></i>
                            <span>Kreuzfahrt</span>
                        </h3>
                    </div>

                    <div class="p-4 space-y-4">
                        <!-- Suchformular -->
                        <div class="space-y-3">
                            <!-- Reederei -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    <i class="fa-solid fa-building"></i> Reederei
                                </label>
                                <select id="cruise-line-select" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Alle Reedereien</option>
                                </select>
                            </div>

                            <!-- Schiffsname -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    <i class="fa-solid fa-ship"></i> Schiffsname
                                </label>
                                <select id="ship-select" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" disabled>
                                    <option value="">Bitte zuerst Reederei wählen</option>
                                </select>
                            </div>

                            <!-- Routenname -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    <i class="fa-solid fa-route"></i> Routenname
                                </label>
                                <select id="route-select" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" disabled>
                                    <option value="">Bitte zuerst Schiff wählen</option>
                                </select>
                            </div>

                            <!-- Reisetermin -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    <i class="fa-solid fa-calendar-days"></i> Verfügbare Reisetermine
                                </label>
                                <select id="cruise-date-select" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" disabled>
                                    <option value="">Bitte zuerst Route wählen</option>
                                </select>
                            </div>

                            <!-- ODER Trennlinie -->
                            <div class="relative py-2">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center text-xs">
                                    <span class="bg-white px-2 text-gray-500">ODER manuell eingrenzen</span>
                                </div>
                            </div>

                            <!-- Reisedatum Von -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    <i class="fa-solid fa-calendar"></i> Reisedatum Von
                                </label>
                                <input type="date" id="date-from" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="onDateRangeChange()">
                            </div>

                            <!-- Reisedatum Bis -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    <i class="fa-solid fa-calendar"></i> Reisedatum Bis
                                </label>
                                <input type="date" id="date-to" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="onDateRangeChange()">
                            </div>

                            <!-- Suchbuttons -->
                            <div class="pt-2 space-y-2 border-t border-gray-200">
                                <button id="search-cruises-btn" onclick="searchCruises()" class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    Kreuzfahrt suchen
                                </button>
                                <button id="reset-cruise-filters-btn" onclick="resetCruiseFilters()" class="w-full px-4 py-2.5 text-sm text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors flex items-center justify-center gap-2" style="display: none;">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Filter zurücksetzen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suchergebnisse -->
                <div id="cruise-results-section" class="bg-white shadow-sm mt-4" style="display: none;">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-list"></i>
                            <span>Ergebnisse (<span id="cruise-results-count">0</span>)</span>
                        </h3>
                    </div>
                    <div id="cruise-results-list" class="p-2 space-y-2 max-h-96 overflow-y-auto">
                        <!-- Results will be inserted here -->
                    </div>
                </div>

                <!-- Loading Overlay für Sidebar -->
                <div id="sidebar-loading" class="bg-white shadow-sm mt-4" style="display: none;">
                    <div class="p-6 text-center">
                        <div class="inline-block w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                        <p class="mt-2 text-sm text-gray-600">Suche läuft...</p>
                    </div>
                </div>
            </aside>

            <!-- Map Container -->
            <div class="map-container">
                <div id="cruise-map"></div>

                <!-- Map Legend -->
                <div id="map-legend" class="map-legend">
                    <div style="font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px;">
                        Route Legende
                    </div>
                    <div class="legend-item">
                        <div class="legend-marker" style="background-color: #22c55e;">
                            <i class="fa-solid fa-flag-checkered"></i>
                        </div>
                        <span>Starthafen</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-marker" style="background-color: #3b82f6;">
                            1
                        </div>
                        <span>Zwischenstopp (Tag)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-marker" style="background-color: #ef4444;">
                            <i class="fa-solid fa-anchor"></i>
                        </div>
                        <span>Zielhafen</span>
                    </div>
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb;">
                        <div style="font-weight: bold; margin-bottom: 4px; font-size: 11px;">Rundreisen:</div>
                        <div class="legend-item">
                            <div style="width: 24px; height: 24px; border-radius: 50%; background-color: rgba(34, 197, 94, 0.8); color: white; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">
                                2 <i class="fa-solid fa-rotate" style="font-size: 8px; margin-left: 2px;"></i>
                            </div>
                            <span style="font-size: 10px;">Cluster mit Start & Ziel</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin: 4px 0;">
                            <div style="width: 24px; height: 2px; background: repeating-linear-gradient(to right, #9333ea 0, #9333ea 8px, transparent 8px, transparent 16px);"></div>
                            <span style="font-size: 10px;">Verbindungslinie</span>
                        </div>
                        <p style="font-size: 9px; color: #666; margin: 4px 0 0 0; font-style: italic;">
                            Start- und Zielhafen sind nebeneinander platziert
                        </p>
                    </div>
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
        // Map und Marker Layers
        let cruiseMap = null;
        let cruiseMarkersLayer = null;
        let cruiseRouteLinesLayer = null;

        document.addEventListener('DOMContentLoaded', function() {
            // Map initialisieren mit Zoom-Beschränkungen
            cruiseMap = L.map('cruise-map', {
                worldCopyJump: true,
                maxBounds: null,
                minZoom: 2,
                maxZoom: 19
            }).setView([30, 0], 2);

            // OpenStreetMap mit deutschen Namen
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
                minZoom: 2
            }).addTo(cruiseMap);

            // Marker Cluster Layer für Häfen
            cruiseMarkersLayer = L.markerClusterGroup({
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                spiderfyOnMaxZoom: true,
                removeOutsideVisibleBounds: true,
                maxClusterRadius: 80,
                iconCreateFunction: function(cluster) {
                    const count = cluster.getChildCount();
                    const markers = cluster.getAllChildMarkers();

                    // Prüfe ob Cluster Start- und Endhafen einer Rundreise enthält
                    let hasRoundTripStart = false;
                    let hasRoundTripEnd = false;

                    markers.forEach(marker => {
                        if (marker.options.roundTripStart) hasRoundTripStart = true;
                        if (marker.options.roundTripEnd) hasRoundTripEnd = true;
                    });

                    const isRoundTripCluster = hasRoundTripStart && hasRoundTripEnd;

                    let size = 'small';
                    let className = 'marker-cluster-small';

                    // Spezielle Färbung für Rundreise-Cluster
                    if (isRoundTripCluster) {
                        className = 'marker-cluster-roundtrip';
                    } else if (count > 20) {
                        size = 'large';
                        className = 'marker-cluster-large';
                    } else if (count > 10) {
                        size = 'medium';
                        className = 'marker-cluster-medium';
                    }

                    return L.divIcon({
                        html: '<div><span>' + count + (isRoundTripCluster ? ' <i class="fa-solid fa-rotate" style="font-size: 10px;"></i>' : '') + '</span></div>',
                        className: 'marker-cluster ' + className,
                        iconSize: L.point(40, 40)
                    });
                }
            }).addTo(cruiseMap);

            // Routen Layer (nicht geclustert)
            cruiseRouteLinesLayer = L.layerGroup().addTo(cruiseMap);

            // Window Resize Event-Listener
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    if (cruiseMap) {
                        cruiseMap.invalidateSize();
                    }
                }, 250);
            });

            // Reedereien laden
            loadCruiseLines();
        });

        // Reedereien laden
        async function loadCruiseLines() {
            try {
                const response = await fetch('/api/cruise-search/cruise-lines');
                const data = await response.json();

                if (data.success) {
                    const select = document.getElementById('cruise-line-select');
                    select.innerHTML = '<option value="">Alle Reedereien</option>';

                    data.data.forEach(line => {
                        const option = document.createElement('option');
                        option.value = line.id;
                        option.textContent = line.name;
                        select.appendChild(option);
                    });

                    // Event Listener für Reederei-Auswahl
                    select.addEventListener('change', onCruiseLineChange);
                }
            } catch (error) {
                console.error('Fehler beim Laden der Reedereien:', error);
            }
        }

        // Reederei geändert - Schiffe laden
        async function onCruiseLineChange() {
            const lineId = document.getElementById('cruise-line-select').value;
            const shipSelect = document.getElementById('ship-select');
            const routeSelect = document.getElementById('route-select');

            // Reset nachfolgende Felder
            shipSelect.innerHTML = '<option value="">Bitte wählen...</option>';
            routeSelect.innerHTML = '<option value="">Bitte zuerst Schiff wählen</option>';
            routeSelect.disabled = true;

            if (!lineId) {
                shipSelect.innerHTML = '<option value="">Bitte zuerst Reederei wählen</option>';
                shipSelect.disabled = true;
                return;
            }

            shipSelect.disabled = false;

            try {
                const response = await fetch(`/api/cruise-search/ships?line_id=${lineId}`);
                const data = await response.json();

                if (data.success) {
                    shipSelect.innerHTML = '<option value="">Alle Schiffe</option>';
                    data.data.forEach(ship => {
                        const option = document.createElement('option');
                        option.value = ship.id;
                        option.textContent = ship.name;
                        shipSelect.appendChild(option);
                    });

                    shipSelect.addEventListener('change', onShipChange);
                }
            } catch (error) {
                console.error('Fehler beim Laden der Schiffe:', error);
            }
        }

        // Schiff geändert - Routen laden
        async function onShipChange() {
            const shipId = document.getElementById('ship-select').value;
            const routeSelect = document.getElementById('route-select');
            const cruiseDateSelect = document.getElementById('cruise-date-select');

            routeSelect.innerHTML = '<option value="">Bitte wählen...</option>';
            cruiseDateSelect.innerHTML = '<option value="">Bitte zuerst Route wählen</option>';
            cruiseDateSelect.disabled = true;

            if (!shipId) {
                routeSelect.innerHTML = '<option value="">Bitte zuerst Schiff wählen</option>';
                routeSelect.disabled = true;
                return;
            }

            routeSelect.disabled = false;

            try {
                const response = await fetch(`/api/cruise-search/routes?ship_id=${shipId}`);
                const data = await response.json();

                if (data.success) {
                    routeSelect.innerHTML = '<option value="">Alle Routen</option>';
                    data.data.forEach(route => {
                        const option = document.createElement('option');
                        option.value = route.id;
                        option.textContent = route.name;
                        routeSelect.appendChild(option);
                    });

                    routeSelect.addEventListener('change', onRouteChange);
                }
            } catch (error) {
                console.error('Fehler beim Laden der Routen:', error);
            }
        }

        // Route geändert - Reisetermine laden
        async function onRouteChange() {
            const routeId = document.getElementById('route-select').value;
            const cruiseDateSelect = document.getElementById('cruise-date-select');
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;

            cruiseDateSelect.innerHTML = '<option value="">Bitte wählen...</option>';

            if (!routeId) {
                cruiseDateSelect.innerHTML = '<option value="">Bitte zuerst Route wählen</option>';
                cruiseDateSelect.disabled = true;
                return;
            }

            cruiseDateSelect.disabled = false;

            try {
                let url = `/api/cruise-search/cruise-dates?route_id=${routeId}`;

                // Optionale Datumfilter hinzufügen
                if (dateFrom) url += `&date_from=${dateFrom}`;
                if (dateTo) url += `&date_to=${dateTo}`;

                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    cruiseDateSelect.innerHTML = '<option value="">Alle Termine</option>';

                    if (data.data.length === 0) {
                        cruiseDateSelect.innerHTML = '<option value="">Keine Termine verfügbar</option>';
                    } else {
                        data.data.forEach(cruise => {
                            const option = document.createElement('option');
                            option.value = cruise.id;
                            option.textContent = cruise.display_text;
                            cruiseDateSelect.appendChild(option);
                        });
                    }
                }
            } catch (error) {
                console.error('Fehler beim Laden der Reisetermine:', error);
            }
        }

        // Datumsbereich geändert - Reisetermine neu laden wenn Route ausgewählt ist
        function onDateRangeChange() {
            const routeId = document.getElementById('route-select').value;

            // Nur neu laden wenn eine Route ausgewählt ist
            if (routeId) {
                onRouteChange();
            }
        }

        // Kreuzfahrten suchen
        async function searchCruises() {
            const lineId = document.getElementById('cruise-line-select').value;
            const shipId = document.getElementById('ship-select').value;
            const routeId = document.getElementById('route-select').value;
            const cruiseDateId = document.getElementById('cruise-date-select').value;
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;

            // Mindestens ein Filter muss gesetzt sein
            if (!lineId && !shipId && !routeId && !cruiseDateId && !dateFrom && !dateTo) {
                alert('Bitte wählen Sie mindestens ein Suchkriterium aus.');
                return;
            }

            // Loading anzeigen
            document.getElementById('sidebar-loading').style.display = 'block';
            document.getElementById('cruise-results-section').style.display = 'none';

            try {
                const response = await fetch('/api/cruise-search/search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        line_id: lineId || null,
                        ship_id: shipId || null,
                        route_id: routeId || null,
                        cruise_date_id: cruiseDateId || null,
                        date_from: dateFrom || null,
                        date_to: dateTo || null,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    displayCruiseResults(data.data);
                    displayCruiseRoutes(data.data);
                    document.getElementById('reset-cruise-filters-btn').style.display = 'flex';
                } else {
                    alert('Fehler bei der Suche');
                }
            } catch (error) {
                console.error('Fehler bei der Suche:', error);
                alert('Fehler bei der Suche. Bitte versuchen Sie es erneut.');
            } finally {
                document.getElementById('sidebar-loading').style.display = 'none';
            }
        }

        // Ergebnisse in Sidebar anzeigen
        function displayCruiseResults(cruises) {
            const resultsSection = document.getElementById('cruise-results-section');
            const resultsList = document.getElementById('cruise-results-list');
            const resultsCount = document.getElementById('cruise-results-count');

            resultsCount.textContent = cruises.length;
            resultsList.innerHTML = '';

            cruises.forEach(cruise => {
                const card = document.createElement('div');
                card.className = 'bg-white border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow cursor-pointer';

                const portsText = cruise.ports && cruise.ports.length > 0
                    ? cruise.ports.map(p => p.name).slice(0, 3).join(' → ') + (cruise.ports.length > 3 ? '...' : '')
                    : 'Keine Häfen';

                card.innerHTML = `
                    <div class="space-y-2">
                        <h4 class="font-semibold text-sm text-gray-900">${escapeHtml(cruise.route_name)}</h4>
                        <div class="space-y-1 text-xs text-gray-600">
                            <p><i class="fa-solid fa-building"></i> <strong>Reederei:</strong> ${escapeHtml(cruise.line_name)}</p>
                            <p><i class="fa-solid fa-ship"></i> <strong>Schiff:</strong> ${escapeHtml(cruise.ship_name)}</p>
                            <p><i class="fa-solid fa-calendar"></i> <strong>Reisedatum:</strong> ${cruise.start_date} - ${cruise.end_date}</p>
                            <p><i class="fa-solid fa-clock"></i> <strong>Dauer:</strong> ${cruise.duration_days} Tage</p>
                            <p class="text-blue-600"><i class="fa-solid fa-route"></i> ${portsText}</p>
                        </div>
                    </div>
                `;

                // Click handler - zeige Route auf Karte
                card.addEventListener('click', () => {
                    if (cruise.ports && cruise.ports.length > 0) {
                        const bounds = L.latLngBounds(cruise.ports.map(p => [p.lat, p.lng]));
                        cruiseMap.fitBounds(bounds, { padding: [50, 50] });
                    }
                });

                resultsList.appendChild(card);
            });

            resultsSection.style.display = 'block';
        }

        // Routen auf Karte anzeigen
        function displayCruiseRoutes(cruises) {
            // Alte Marker und Routen entfernen
            cruiseMarkersLayer.clearLayers();
            cruiseRouteLinesLayer.clearLayers();

            // Sammle alle Bounds für Zoom
            const allCoords = [];

            // Prüfe ob Routen vorhanden sind
            if (!cruises || cruises.length === 0) {
                document.getElementById('map-legend').classList.remove('active');
                return;
            }

            // Legende anzeigen
            document.getElementById('map-legend').classList.add('active');

            cruises.forEach(cruise => {
                if (cruise.ports && cruise.ports.length > 0) {
                    // Prüfe ob Start- und Zielhafen identisch sind
                    const startPort = cruise.ports[0];
                    const endPort = cruise.ports[cruise.ports.length - 1];
                    const isSameStartEnd = startPort.id === endPort.id ||
                        (Math.abs(startPort.lat - endPort.lat) < 0.0001 &&
                         Math.abs(startPort.lng - endPort.lng) < 0.0001);

                    // Route zeichnen
                    const routeCoords = cruise.ports.map(p => [p.lat, p.lng]);
                    const polyline = L.polyline(routeCoords, {
                        color: '#3b82f6',
                        weight: 3,
                        opacity: 0.7
                    });
                    cruiseRouteLinesLayer.addLayer(polyline);

                    // Bei Rundreise: Verbindungslinie zwischen versetzten Start- und Endmarker
                    if (isSameStartEnd && cruise.ports.length > 1) {
                        const offsetLat = 0.002; // ca. 200m Versatz
                        const startCoord = [parseFloat(startPort.lat) - offsetLat, parseFloat(startPort.lng)];
                        const endCoord = [parseFloat(endPort.lat) + offsetLat, parseFloat(endPort.lng)];

                        const connectLine = L.polyline([startCoord, endCoord], {
                            color: '#9333ea',
                            weight: 2,
                            opacity: 0.6,
                            dashArray: '8, 8'
                        });
                        cruiseRouteLinesLayer.addLayer(connectLine);
                    }

                    // Häfen als nummerierte Marker hinzufügen
                    cruise.ports.forEach((port, index) => {
                        const isStart = index === 0;
                        const isEnd = index === cruise.ports.length - 1;

                        // Koordinaten mit Offset bei identischem Start/Ziel
                        let markerLat = parseFloat(port.lat);
                        let markerLng = parseFloat(port.lng);

                        if (isSameStartEnd && isStart) {
                            // Startmarker nach links oben verschieben
                            markerLat = markerLat - 0.002;
                        } else if (isSameStartEnd && isEnd) {
                            // Endmarker nach rechts unten verschieben
                            markerLat = markerLat + 0.002;
                        }

                        allCoords.push([markerLat, markerLng]);

                        // Farbe basierend auf Position
                        let bgColor = '#3b82f6'; // Blau für Zwischenhäfen
                        let iconHtml = '';

                        if (isStart) {
                            bgColor = '#22c55e'; // Grün für Start
                            iconHtml = '<i class="fa-solid fa-flag-checkered" style="color: white; font-size: 10px;"></i>';
                        } else if (isEnd) {
                            bgColor = '#ef4444'; // Rot für Ende
                            iconHtml = '<i class="fa-solid fa-anchor" style="color: white; font-size: 10px;"></i>';
                        } else {
                            iconHtml = `<span style="color: white; font-size: 11px; font-weight: bold;">${port.day || index + 1}</span>`;
                        }

                        const portIcon = L.divIcon({
                            html: `<div style="
                                background-color: ${bgColor};
                                width: 28px;
                                height: 28px;
                                border-radius: 50%;
                                border: 2px solid white;
                                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">${iconHtml}</div>`,
                            iconSize: [28, 28],
                            className: 'cruise-port-numbered',
                            iconAnchor: [14, 14],
                            popupAnchor: [0, -14]
                        });

                        const marker = L.marker([markerLat, markerLng], {
                            icon: portIcon,
                            roundTripStart: isSameStartEnd && isStart,
                            roundTripEnd: isSameStartEnd && isEnd
                        });

                        // Popup mit detaillierten Informationen
                        const portIconSymbol = isStart ? '<i class="fa-solid fa-flag-checkered"></i>' :
                                              isEnd ? '<i class="fa-solid fa-anchor"></i>' :
                                              '<i class="fa-solid fa-location-dot"></i>';

                        let popupContent = `
                            <div style="min-width: 180px;">
                                <h3 style="font-weight: bold; margin-bottom: 8px; color: ${bgColor}; font-size: 14px;">
                                    ${portIconSymbol} ${escapeHtml(port.name)}
                                </h3>
                                <div style="font-size: 12px; line-height: 1.6;">
                                    <p style="margin: 4px 0;"><strong>Tag:</strong> ${port.day || index + 1}</p>
                                    ${port.arrive_at ? `<p style="margin: 4px 0; color: #059669;"><strong>Ankunft:</strong> ${port.arrive_at} Uhr</p>` : ''}
                                    ${port.depart_at ? `<p style="margin: 4px 0; color: #dc2626;"><strong>Abfahrt:</strong> ${port.depart_at} Uhr</p>` : ''}
                                    <p style="margin: 4px 0; color: #666; font-style: italic;">
                                        ${isStart ? 'Starthafen' : isEnd ? 'Zielhafen' : 'Zwischenstopp'}
                                    </p>
                                    ${isSameStartEnd && (isStart || isEnd) ? '<p style="margin: 4px 0; color: #9333ea; font-weight: bold; font-size: 11px;"><i class="fa-solid fa-rotate"></i> Rundreise</p>' : ''}
                                </div>
                            </div>
                        `;

                        marker.bindPopup(popupContent);
                        cruiseMarkersLayer.addLayer(marker);
                    });
                }
            });

            // Zoom auf alle Routen
            if (allCoords.length > 0) {
                const bounds = L.latLngBounds(allCoords);
                cruiseMap.fitBounds(bounds, { padding: [50, 50] });
            }
        }

        // Filter zurücksetzen
        function resetCruiseFilters() {
            document.getElementById('cruise-line-select').value = '';
            document.getElementById('ship-select').innerHTML = '<option value="">Bitte zuerst Reederei wählen</option>';
            document.getElementById('ship-select').disabled = true;
            document.getElementById('route-select').innerHTML = '<option value="">Bitte zuerst Schiff wählen</option>';
            document.getElementById('route-select').disabled = true;
            document.getElementById('cruise-date-select').innerHTML = '<option value="">Bitte zuerst Route wählen</option>';
            document.getElementById('cruise-date-select').disabled = true;
            document.getElementById('date-from').value = '';
            document.getElementById('date-to').value = '';

            document.getElementById('cruise-results-section').style.display = 'none';
            document.getElementById('reset-cruise-filters-btn').style.display = 'none';

            // Karte und Legende zurücksetzen
            cruiseMarkersLayer.clearLayers();
            cruiseRouteLinesLayer.clearLayers();
            document.getElementById('map-legend').classList.remove('active');
            cruiseMap.setView([30, 0], 2);
        }

        // HTML Escape
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
