<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                            <div class="space-y-2">
                                <label class="flex items-center text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-2 rounded">
                                    <input type="radio" name="radius" value="5" class="mr-2 text-blue-600 focus:ring-blue-500" id="radius-5">
                                    <span>+ 5 km</span>
                                </label>
                                <label class="flex items-center text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-2 rounded">
                                    <input type="radio" name="radius" value="10" class="mr-2 text-blue-600 focus:ring-blue-500" id="radius-10" checked>
                                    <span>+ 10 km</span>
                                </label>
                                <label class="flex items-center text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-2 rounded">
                                    <input type="radio" name="radius" value="20" class="mr-2 text-blue-600 focus:ring-blue-500" id="radius-20">
                                    <span>+ 20 km</span>
                                </label>
                            </div>
                        </div>

                        <!-- Online-Buchung -->
                        <div class="bg-gray-100 p-4 rounded">
                            <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500" id="onlineBooking">
                                <span class="font-medium">Nur Online-Buchung</span>
                            </label>
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

    <script>
        // Map initialisieren
        let bookingMap = null;

        document.addEventListener('DOMContentLoaded', function() {
            // Map initialisieren
            bookingMap = L.map('booking-map').setView([51.1657, 10.4515], 6); // Deutschland zentriert

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(bookingMap);
        });

        // Suche nach Buchungsmöglichkeiten
        function searchBookingOptions() {
            const postalCode = document.getElementById('postalCodeInput').value;
            const radius = document.querySelector('input[name="radius"]:checked')?.value || '10';
            const onlineOnly = document.getElementById('onlineBooking').checked;

            console.log('Search booking options:', { postalCode, radius, onlineOnly });

            // TODO: API-Call implementieren
            alert('Suche wird implementiert. PLZ: ' + postalCode + ', Umkreis: ' + radius + 'km, Online: ' + onlineOnly);
        }

        // Filter zurücksetzen
        function resetFilters() {
            document.getElementById('postalCodeInput').value = '';
            document.getElementById('radius-10').checked = true;
            document.getElementById('onlineBooking').checked = false;

            const resultsDiv = document.getElementById('booking-search-results');
            if (resultsDiv) {
                resultsDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>
