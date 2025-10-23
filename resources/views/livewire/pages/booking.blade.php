<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buchungsmöglichkeit - Global Travel Monitor</title>

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
            z-index: 50;
        }

        /* Footer - feststehend */
        .footer {
            flex-shrink: 0;
            height: 32px;
            background: white;
            color: black;
            z-index: 50;
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

        #booking-map {
            width: 100%;
            height: 100%;
        }

        /* Marker Cluster Custom Styling */
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
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <div class="flex items-center justify-between px-6 h-full">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-bold text-gray-800">Global Travel Monitor</h1>
                    <span class="text-sm text-gray-500">Buchungsmöglichkeit</span>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Black Navigation Sidebar -->
            <nav class="navigation flex flex-col items-center py-4 space-y-4">
                <a href="/" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Dashboard">
                    <i class="fa-regular fa-home text-2xl" aria-hidden="true"></i>
                </a>
                <a href="/entry-conditions" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Einreisebestimmungen">
                    <i class="fa-regular fa-passport text-2xl" aria-hidden="true"></i>
                </a>
                <a href="/booking" class="p-3 text-white bg-gray-800 rounded-lg transition-colors" title="Buchungsmöglichkeit">
                    <i class="fa-regular fa-calendar-check text-2xl" aria-hidden="true"></i>
                </a>
                @if(config('app.dashboard_airports_enabled', true))
                <a href="/?airports=1" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Flughäfen">
                    <i class="fa-regular fa-plane text-2xl" aria-hidden="true"></i>
                </a>
                @endif
            </nav>

            <!-- Filter Sidebar -->
            <aside class="sidebar">
                <!-- Buchungsfilter -->
                <div class="bg-white shadow-sm">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fa-regular fa-filter text-blue-500"></i>
                            <span>Suchfilter</span>
                        </h3>
                    </div>

                    <div class="p-4 space-y-4">
                        <!-- Postleitzahl -->
                        <div class="bg-gray-100 p-4 rounded">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Postleitzahl
                            </label>
                            <input
                                type="text"
                                placeholder="z.B. 10115"
                                maxlength="5"
                                pattern="[0-9]{5}"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                id="postalCodeInput"
                            >
                        </div>

                        <!-- Umkreis -->
                        <div class="bg-gray-100 p-4 rounded">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Umkreis
                            </label>
                            <!-- Hidden radio buttons for state management -->
                            <input type="radio" name="radius" value="5" id="radius-5" style="display: none;">
                            <input type="radio" name="radius" value="10" id="radius-10" checked style="display: none;">
                            <input type="radio" name="radius" value="20" id="radius-20" style="display: none;">

                            <!-- Radius badges -->
                            <div class="flex flex-wrap gap-2">
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-2 text-sm cursor-pointer rounded-lg transition-colors radius-badge"
                                    data-radius="5"
                                    onclick="selectRadius(5)">
                                    <span>+ 5 km</span>
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-2 text-sm cursor-pointer rounded-lg transition-colors radius-badge active"
                                    data-radius="10"
                                    onclick="selectRadius(10)">
                                    <span>+ 10 km</span>
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-2 text-sm cursor-pointer rounded-lg transition-colors radius-badge"
                                    data-radius="20"
                                    onclick="selectRadius(20)">
                                    <span>+ 20 km</span>
                                </span>
                            </div>
                        </div>

                        <!-- Buchungsart -->
                        <div class="bg-gray-100 p-4 rounded">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Buchungsart
                            </label>
                            <!-- Hidden radio buttons for state management -->
                            <input type="radio" name="bookingType" value="stationary" id="bookingType-stationary" style="display: none;">
                            <input type="radio" name="bookingType" value="online" id="bookingType-online" style="display: none;">
                            <input type="radio" name="bookingType" value="any" id="bookingType-any" checked style="display: none;">

                            <!-- Booking type badges -->
                            <div class="flex flex-wrap gap-2">
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-2 text-sm cursor-pointer rounded-lg transition-colors bookingtype-badge"
                                    data-bookingtype="stationary"
                                    onclick="selectBookingType('stationary')">
                                    <span>Stationär</span>
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-2 text-sm cursor-pointer rounded-lg transition-colors bookingtype-badge"
                                    data-bookingtype="online"
                                    onclick="selectBookingType('online')">
                                    <span>Online</span>
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-2 text-sm cursor-pointer rounded-lg transition-colors bookingtype-badge active"
                                    data-bookingtype="any"
                                    onclick="selectBookingType('any')">
                                    <span>Egal</span>
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-3 space-y-2 border-t border-gray-200">
                            <button onclick="searchBookingOptions()" class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                Suchen
                            </button>
                            <button id="reset-filters-button" onclick="resetFilters()" class="w-full px-4 py-2.5 text-sm text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors flex items-center justify-center gap-2" style="display: none;">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Filter zurücksetzen
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search Results -->
                <div id="booking-search-results" class="bg-white shadow-sm mt-4" style="display: none;">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fa-regular fa-calendar-check"></i>
                            <span>Ergebnisse (<span id="results-count">0</span>)</span>
                        </h3>
                    </div>
                    <div id="results-list" class="p-2 space-y-2 bg-white">
                        <!-- Results will be inserted here -->
                    </div>
                </div>
            </aside>

            <!-- Map Container -->
            <div class="map-container">
                <div id="booking-map"></div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="flex items-center justify-between px-4 h-full">
                <div class="flex items-center space-x-6 text-sm">
                    <span>© 2025 Passolution GmbH</span>
                    <a href="https://www.passolution.de/impressum/" target="_blank" rel="noopener noreferrer" class="hover:text-blue-300 transition-colors">Impressum</a>
                    <a href="https://www.passolution.de/datenschutz/" target="_blank" rel="noopener noreferrer" class="hover:text-blue-300 transition-colors">Datenschutz</a>
                    <a href="https://www.passolution.de/agb/" target="_blank" rel="noopener noreferrer" class="hover:text-blue-300 transition-colors">AGB</a>
                </div>
                <div class="flex items-center space-x-4 text-sm">
                    <span>Version 1.0.17</span>
                    <span>Build: 2025-09-30</span>
                </div>
            </div>
        </footer>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet MarkerCluster JS -->
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <script>
        // Map initialisieren
        let bookingMap = null;
        let markersLayer = null;

        document.addEventListener('DOMContentLoaded', function() {
            // Map initialisieren
            bookingMap = L.map('booking-map').setView([51.1657, 10.4515], 6); // Deutschland zentriert

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(bookingMap);

            // Marker-Cluster-Layer statt normalem LayerGroup
            markersLayer = L.markerClusterGroup({
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                spiderfyOnMaxZoom: true,
                removeOutsideVisibleBounds: true,
                maxClusterRadius: 80,
                iconCreateFunction: function(cluster) {
                    const count = cluster.getChildCount();
                    let size = 'small';
                    let className = 'marker-cluster-small';

                    if (count > 50) {
                        size = 'large';
                        className = 'marker-cluster-large';
                    } else if (count > 10) {
                        size = 'medium';
                        className = 'marker-cluster-medium';
                    }

                    return L.divIcon({
                        html: '<div><span>' + count + '</span></div>',
                        className: 'marker-cluster ' + className,
                        iconSize: L.point(40, 40)
                    });
                }
            }).addTo(bookingMap);

            // Initial badge styling setzen
            updateRadiusBadges();
            updateBookingTypeBadges();

            // Alle Standorte beim Laden anzeigen
            loadAllLocations();
        });

        // Umkreis-Auswahl
        function selectRadius(radius) {
            // Hidden radio button aktualisieren
            const radioButton = document.getElementById('radius-' + radius);
            if (radioButton) {
                radioButton.checked = true;
            }

            // Badge-Styling aktualisieren
            updateRadiusBadges();
        }

        // Badge-Styling aktualisieren
        function updateRadiusBadges() {
            const badges = document.querySelectorAll('.radius-badge');
            const selectedRadius = document.querySelector('input[name="radius"]:checked')?.value;

            badges.forEach(badge => {
                const badgeRadius = badge.getAttribute('data-radius');
                if (badgeRadius === selectedRadius) {
                    // Aktiv: Grüner Badge
                    badge.className = 'inline-flex items-center gap-1 px-3 py-2 text-sm cursor-pointer rounded-lg transition-colors radius-badge active bg-green-50 text-green-800 border border-green-200 font-medium';
                } else {
                    // Inaktiv: Weißer Badge
                    badge.className = 'inline-flex items-center gap-1 px-3 py-2 text-sm cursor-pointer rounded-lg transition-colors radius-badge bg-white text-gray-700 border border-gray-300 hover:bg-gray-50';
                }
            });
        }

        // Buchungsart-Auswahl
        function selectBookingType(bookingType) {
            // Hidden radio button aktualisieren
            const radioButton = document.getElementById('bookingType-' + bookingType);
            if (radioButton) {
                radioButton.checked = true;
            }

            // Badge-Styling aktualisieren
            updateBookingTypeBadges();
        }

        // Buchungsart Badge-Styling aktualisieren
        function updateBookingTypeBadges() {
            const badges = document.querySelectorAll('.bookingtype-badge');
            const selectedType = document.querySelector('input[name="bookingType"]:checked')?.value;

            badges.forEach(badge => {
                const badgeType = badge.getAttribute('data-bookingtype');
                if (badgeType === selectedType) {
                    // Aktiv: Grüner Badge
                    badge.className = 'inline-flex items-center gap-1 px-3 py-2 text-sm cursor-pointer rounded-lg transition-colors bookingtype-badge active bg-green-50 text-green-800 border border-green-200 font-medium';
                } else {
                    // Inaktiv: Weißer Badge
                    badge.className = 'inline-flex items-center gap-1 px-3 py-2 text-sm cursor-pointer rounded-lg transition-colors bookingtype-badge bg-white text-gray-700 border border-gray-300 hover:bg-gray-50';
                }
            });
        }

        // Alle Standorte laden (Initial)
        async function loadAllLocations() {
            try {
                const response = await fetch('/api/booking-locations');
                const data = await response.json();

                if (data.success) {
                    displayLocations(data.data);
                    console.log(`${data.count} Buchungsstandorte geladen`);
                }
            } catch (error) {
                console.error('Fehler beim Laden der Standorte:', error);
            }
        }

        // Suche nach Buchungsmöglichkeiten
        async function searchBookingOptions() {
            const postalCode = document.getElementById('postalCodeInput').value;
            const radius = document.querySelector('input[name="radius"]:checked')?.value || '10';
            const bookingType = document.querySelector('input[name="bookingType"]:checked')?.value || 'any';

            if (!postalCode || postalCode.length !== 5) {
                alert('Bitte geben Sie eine gültige 5-stellige Postleitzahl ein.');
                return;
            }

            try {
                const response = await fetch('/api/booking-locations/search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        postal_code: postalCode,
                        radius: parseInt(radius),
                        booking_type: bookingType,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    displayLocations(data.data, data.center);
                    displayResults(data);

                    // Zeige Reset-Button
                    document.getElementById('reset-filters-button').style.display = 'block';
                } else {
                    alert(data.message || 'Fehler bei der Suche');
                }
            } catch (error) {
                console.error('Fehler bei der Suche:', error);
                alert('Fehler bei der Suche. Bitte versuchen Sie es erneut.');
            }
        }

        // Standorte auf Karte anzeigen
        function displayLocations(locations, center = null) {
            // Alte Marker entfernen
            markersLayer.clearLayers();

            // Icons für verschiedene Typen - Standard Leaflet Marker
            const blueIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            const greenIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            const redIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            locations.forEach(location => {
                if (location.type === 'online') {
                    // Online-Buchungen haben keine Koordinaten, zeige in Sidebar
                    return;
                }

                if (location.latitude && location.longitude) {
                    const marker = L.marker([location.latitude, location.longitude], {
                        icon: greenIcon,
                    });

                    let popupContent = `
                        <div style="min-width: 200px;">
                            <h3 style="font-weight: bold; margin-bottom: 8px;">${escapeHtml(location.name)}</h3>
                            ${location.description ? `<p style="font-size: 12px; color: #666; margin-bottom: 8px;">${escapeHtml(location.description)}</p>` : ''}
                            ${location.address ? `<p style="font-size: 12px;"><i class="fa-solid fa-location-dot"></i> ${escapeHtml(location.address)}</p>` : ''}
                            ${location.postal_code && location.city ? `<p style="font-size: 12px;">${escapeHtml(location.postal_code)} ${escapeHtml(location.city)}</p>` : ''}
                            ${location.phone ? `<p style="font-size: 12px;"><i class="fa-solid fa-phone"></i> ${escapeHtml(location.phone)}</p>` : ''}
                            ${location.distance ? `<p style="font-size: 12px; color: #059669; font-weight: bold;"><i class="fa-solid fa-route"></i> ${parseFloat(location.distance).toFixed(1)} km</p>` : ''}
                        </div>
                    `;

                    marker.bindPopup(popupContent);
                    markersLayer.addLayer(marker);
                }
            });

            // Karte auf Center zoomen, wenn vorhanden
            if (center && center.lat && center.lng) {
                bookingMap.setView([center.lat, center.lng], 10);

                // Center-Marker (rot für Suchzentrum)
                const centerMarker = L.marker([center.lat, center.lng], {
                    icon: redIcon
                }).bindPopup('Suchzentrum');
                markersLayer.addLayer(centerMarker);
            }
        }

        // Ergebnisse in Sidebar anzeigen
        function displayResults(data) {
            const resultsDiv = document.getElementById('booking-search-results');
            const resultsCount = document.getElementById('results-count');
            const resultsList = document.getElementById('results-list');

            resultsCount.textContent = data.count;
            resultsList.innerHTML = '';

            const locations = data.data || [];

            locations.forEach(location => {
                const card = document.createElement('div');
                card.className = 'bg-white border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow cursor-pointer';

                let cardContent = `
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            ${location.type === 'online'
                                ? '<i class="fa-solid fa-laptop text-blue-600 text-xl"></i>'
                                : '<i class="fa-solid fa-store text-green-600 text-xl"></i>'}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-sm text-gray-900 truncate">${escapeHtml(location.name)}</h4>
                            ${location.description ? `<p class="text-xs text-gray-600 mt-1">${escapeHtml(location.description)}</p>` : ''}
                            ${location.type === 'online' && location.url ? `<a href="${escapeHtml(location.url)}" target="_blank" class="text-xs text-blue-600 hover:underline mt-1 inline-block"><i class="fa-solid fa-external-link"></i> Website öffnen</a>` : ''}
                            ${location.type === 'stationary' ? `
                                <p class="text-xs text-gray-600 mt-1">${escapeHtml(location.address || '')}</p>
                                <p class="text-xs text-gray-600">${escapeHtml(location.postal_code || '')} ${escapeHtml(location.city || '')}</p>
                                ${location.distance ? `<p class="text-xs text-green-600 font-semibold mt-1"><i class="fa-solid fa-route"></i> ${parseFloat(location.distance).toFixed(1)} km</p>` : ''}
                            ` : ''}
                        </div>
                    </div>
                `;

                card.innerHTML = cardContent;

                // Click-Handler für stationäre Standorte
                if (location.type === 'stationary' && location.latitude && location.longitude) {
                    card.addEventListener('click', () => {
                        bookingMap.setView([location.latitude, location.longitude], 15);
                    });
                }

                resultsList.appendChild(card);
            });

            resultsDiv.style.display = 'block';
        }

        // HTML Escape-Funktion
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Filter zurücksetzen
        function resetFilters() {
            document.getElementById('postalCodeInput').value = '';
            document.getElementById('radius-10').checked = true;
            document.getElementById('bookingType-any').checked = true;

            // Badge-Styling aktualisieren
            updateRadiusBadges();
            updateBookingTypeBadges();

            // Ergebnisse und Reset-Button ausblenden
            const resultsDiv = document.getElementById('booking-search-results');
            if (resultsDiv) {
                resultsDiv.style.display = 'none';
            }
            document.getElementById('reset-filters-button').style.display = 'none';

            // Alle Standorte wieder laden
            loadAllLocations();

            // Karte zurück auf Deutschland zentrieren
            bookingMap.setView([51.1657, 10.4515], 6);
        }
    </script>
</body>
</html>
