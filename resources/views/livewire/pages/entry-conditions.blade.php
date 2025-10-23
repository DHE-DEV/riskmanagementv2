<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einreisebestimmungen - Global Travel Monitor</title>

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
            min-width: 0;
        }

        #entry-conditions-map {
            width: 100%;
            height: 100%;
        }

        /* Details Sidebar rechts */
        .details-sidebar {
            flex-shrink: 0;
            width: 400px;
            background: #f9fafb;
            overflow-y: auto;
            height: 100%;
            position: relative;
            z-index: 10;
            border-left: 1px solid #e5e7eb;
        }

        /* Formatierung für API Content */
        .entry-conditions-content-body h1,
        .entry-conditions-content-body h2,
        .entry-conditions-content-body h3,
        .entry-conditions-content-body h4 {
            font-weight: 600;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }

        .entry-conditions-content-body h1 { font-size: 1.25rem; }
        .entry-conditions-content-body h2 { font-size: 1.125rem; }
        .entry-conditions-content-body h3 { font-size: 1rem; }
        .entry-conditions-content-body h4 { font-size: 0.875rem; }

        .entry-conditions-content-body p {
            margin-bottom: 0.75rem;
        }

        .entry-conditions-content-body ul,
        .entry-conditions-content-body ol {
            margin-left: 1.25rem;
            margin-bottom: 0.75rem;
        }

        .entry-conditions-content-body ul {
            list-style-type: disc;
        }

        .entry-conditions-content-body ol {
            list-style-type: decimal;
        }

        .entry-conditions-content-body li {
            margin-bottom: 0.25rem;
        }

        .entry-conditions-content-body a {
            color: #2563eb;
            text-decoration: underline;
        }

        .entry-conditions-content-body a:hover {
            color: #1d4ed8;
        }

        .entry-conditions-content-body table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.75rem;
        }

        .entry-conditions-content-body th,
        .entry-conditions-content-body td {
            border: 1px solid #e5e7eb;
            padding: 0.5rem;
            text-align: left;
        }

        .entry-conditions-content-body th {
            background-color: #f3f4f6;
            font-weight: 600;
        }

        .entry-conditions-content-body strong,
        .entry-conditions-content-body b {
            font-weight: 600;
        }

        .entry-conditions-content-body em,
        .entry-conditions-content-body i {
            font-style: italic;
        }

        /* Passolution API spezifische Styles */
        .entry-conditions-content-body .pds-embed {
            width: 100%;
        }

        .entry-conditions-content-body .pds-embed__info-table-container {
            overflow-x: auto;
        }

        .entry-conditions-content-body .pds-embed__info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .entry-conditions-content-body .pds-embed__info-table thead th {
            background-color: #4b5563;
            color: white;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            border: none;
        }

        /* Schnellübersicht Überschrift */
        .entry-conditions-content-body h2 {
            background-color: #4b5563;
            color: #040404;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            border: none;
            font-size: 1.125rem;
            margin-top: 0;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
        }

        .entry-conditions-content-body .pds-embed__info-table tbody tr {
            border: none;
        }

        .entry-conditions-content-body .pds-embed__info-table tbody tr:hover {
            background-color: #f9fafb;
            cursor: pointer;
        }

        .entry-conditions-content-body .pds-embed__info-table tbody td {
            padding: 0.75rem;
            vertical-align: top !important;
            border: none;
        }

        .entry-conditions-content-body .pds-embed__info-table tbody td:first-child {
            width: 2.5rem;
            text-align: center;
            vertical-align: top !important;
        }

        .entry-conditions-content-body .pds-embed__info-table tbody td:last-child {
            width: 3rem;
            text-align: center;
            vertical-align: top !important;
        }

        .entry-conditions-content-body .pds-embed__info-table svg {
            width: 1.25rem;
            height: 1.25rem;
            display: block;
            margin: 0;
        }

        .entry-conditions-content-body .pds-embed__info-table tbody td:first-child svg {
            color: #6b7280;
        }

        .entry-conditions-content-body .pds-embed__info-table tbody td:last-child svg {
            margin: 0 auto;
        }

        .entry-conditions-content-body .pds-embed__info-table .fa-check {
            color: #10b981;
        }

        .entry-conditions-content-body .pds-embed__info-table .fa-xmark {
            color: #ef4444;
        }

        /* Span-Container für Icons in der Tabelle */
        .entry-conditions-content-body .pds-embed__info-table tbody td span[role="img"] {
            display: block;
            line-height: 1;
        }

        /* Country Flags */
        .country-flag {
            height: 16px;
            width: 24px;
            object-fit: cover;
            border-radius: 2px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            display: inline-block;
            vertical-align: middle;
        }

        .flag-fallback {
            display: inline-block;
            padding: 2px 6px;
            background-color: #e5e7eb;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #4b5563;
        }

        /* PDF Download Button */
        .entry-conditions-content-body .pdf-download-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #2563eb;
            color: white;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            border: none;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .entry-conditions-content-body .pdf-download-btn:hover {
            background-color: #1d4ed8;
        }

        .entry-conditions-content-body .pdf-download-btn:active {
            background-color: #1e40af;
        }

        .entry-conditions-content-body .pdf-download-btn svg {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <div class="flex items-center justify-between h-full px-4">
                <!-- Logo and Title -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <img src="/logo.png" alt="Logo" class="h-8 w-auto" style="margin-left:-5px"/>
                        <span class="text-xl font-semibold text-gray-800" style="margin-left: 30px;">Global Travel Monitor</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-4">
                    <button
                        class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                        title="Daten aktualisieren"
                        onclick="window.location.reload()"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Navigation -->
            <nav class="navigation flex flex-col items-center py-4 space-y-3">
                <a href="/" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Dashboard">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                </a>

                <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Events" onclick="window.location.href='/'">
                    <i class="fa-regular fa-brake-warning text-2xl" aria-hidden="true"></i>
                </button>

                <button class="p-3 bg-gray-800 text-white rounded-lg" title="Einreisebestimmungen">
                    <i class="fa-regular fa-passport text-2xl" aria-hidden="true"></i>
                </button>

                <a href="/booking" class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Buchungsmöglichkeit">
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
                <!-- Einreisebestimmungen Filter -->
                <div class="bg-white shadow-sm">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 cursor-pointer" onclick="toggleSection('entryFilters')">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fa-regular fa-passport text-blue-500"></i>
                            <span>Einreisebestimmungen</span>
                        </h3>
                        <button class="text-gray-500 hover:text-gray-700" onclick="event.stopPropagation(); toggleSection('entryFilters')">
                            <svg id="entryFiltersToggleIcon" class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>

                    <div id="entryFilters" class="p-4 space-y-4" style="display: block;">
                        <!-- Nationalitäten -->
                        <div class="bg-gray-100">
                            <div class="flex items-center justify-between p-3 border-b border-gray-200 cursor-pointer bg-gray-200 hover:bg-gray-300" onclick="toggleFilterSubSection('nationalitySection')">
                                <h4 class="text-sm font-medium text-gray-700">Nationalitäten</h4>
                                <svg id="nationalityToggleIcon" class="w-4 h-4 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(180deg);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div id="selectedNationalityDisplay" class="px-3 pt-3 space-y-1">
                                <!-- Ausgewählte Nationalität wird hier dynamisch eingefügt -->
                            </div>
                            <div id="nationalitySection" class="p-3" style="display: block;">
                                <div class="space-y-2">
                                    <input
                                        type="text"
                                        placeholder="Land suchen (Name oder Code)..."
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        id="nationalityFilterInput"
                                        onkeyup="handleNationalityFilterKeyup(event)"
                                        onkeydown="handleNationalityFilterKeydown(event)"
                                    >
                                    <div id="nationalityFilterResults" class="space-y-1 text-sm text-gray-700 max-h-96 overflow-y-auto transition-all duration-200"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Reiseziele -->
                        <div class="bg-gray-100">
                            <div class="flex items-center justify-between p-3 border-b border-gray-200 cursor-pointer bg-gray-200 hover:bg-gray-300" onclick="toggleFilterSubSection('destinationsSection')">
                                <h4 class="text-sm font-medium text-gray-700">Reiseziele</h4>
                                <svg id="destinationsToggleIcon" class="w-4 h-4 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(180deg);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div id="selectedDestinationsDisplay" class="px-3 pt-3 space-y-1">
                                <!-- Ausgewählte Reiseziele werden hier dynamisch eingefügt -->
                            </div>
                            <div id="destinationsSection" class="p-3" style="display: block;">
                                <div class="space-y-2">
                                    <input
                                        type="text"
                                        placeholder="Land suchen (Name oder Code)..."
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        id="destinationsFilterInput"
                                        onkeyup="handleDestinationsFilterKeyup(event)"
                                        onkeydown="handleDestinationsFilterKeydown(event)"
                                    >
                                    <div id="destinationsFilterResults" class="space-y-1 text-sm text-gray-700 max-h-96 overflow-y-auto transition-all duration-200"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Einreise möglich mit -->
                        <div>
                            <div class="flex items-center justify-between px-2 py-2 mb-2 cursor-pointer bg-gray-200 rounded" onclick="toggleFilterSubSection('entryPossibleSection')">
                                <p class="text-xs text-gray-700 font-medium">Einreise möglich mit</p>
                                <svg id="entryPossibleToggleIcon" class="w-4 h-4 text-gray-700 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(0deg);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div id="selectedEntryPossibleDisplay" class="px-2 pt-2 space-y-1" style="padding-bottom: 0;">
                                <!-- Ausgewählte Filter werden hier dynamisch eingefügt -->
                            </div>
                            <div id="entryPossibleSection" class="px-2 py-2" style="display: none;">
                                <!-- Hidden checkboxes for state management -->
                                <input type="checkbox" id="filter-passport" onchange="applyEntryConditionsFilters()" checked style="display: none;">
                                <input type="checkbox" id="filter-id-card" onchange="applyEntryConditionsFilters()" style="display: none;">
                                <input type="checkbox" id="filter-temp-passport" onchange="applyEntryConditionsFilters()" style="display: none;">
                                <input type="checkbox" id="filter-temp-id-card" onchange="applyEntryConditionsFilters()" style="display: none;">
                                <input type="checkbox" id="filter-child-passport" onchange="applyEntryConditionsFilters()" style="display: none;">

                                <!-- Available filters as badges -->
                                <div id="entryPossibleAvailableFilters" class="flex flex-wrap gap-2">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Visa -->
                        <div>
                            <div class="flex items-center justify-between px-2 py-2 mb-2 cursor-pointer bg-gray-200 rounded" onclick="toggleFilterSubSection('visaSection')">
                                <p class="text-xs text-gray-700 font-medium">Visa</p>
                                <svg id="visaToggleIcon" class="w-4 h-4 text-gray-700 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(0deg);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div id="selectedVisaDisplay" class="px-2 pt-2 space-y-1" style="padding-bottom: 0;">
                                <!-- Ausgewählte Filter werden hier dynamisch eingefügt -->
                            </div>
                            <div id="visaSection" class="px-2 py-2" style="display: none;">
                                <!-- Hidden checkboxes for state management -->
                                <input type="checkbox" id="filter-visa-free" onchange="applyEntryConditionsFilters()" style="display: none;">
                                <input type="checkbox" id="filter-e-visa" onchange="applyEntryConditionsFilters()" style="display: none;">
                                <input type="checkbox" id="filter-visa-on-arrival" onchange="applyEntryConditionsFilters()" style="display: none;">

                                <!-- Available filters as badges -->
                                <div id="visaAvailableFilters" class="flex flex-wrap gap-2">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Weitere Filter -->
                        <div>
                            <div class="flex items-center justify-between px-2 py-2 mb-2 cursor-pointer bg-gray-200 rounded" onclick="toggleFilterSubSection('additionalFiltersSection')">
                                <p class="text-xs text-gray-700 font-medium">Weitere Filter</p>
                                <svg id="additionalFiltersToggleIcon" class="w-4 h-4 text-gray-700 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(0deg);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div id="selectedAdditionalFiltersDisplay" class="px-2 pt-2 space-y-1" style="padding-bottom: 0;">
                                <!-- Ausgewählte Filter werden hier dynamisch eingefügt -->
                            </div>
                            <div id="additionalFiltersSection" class="px-2 py-2" style="display: none;">
                                <!-- Hidden checkboxes for state management -->
                                <input type="checkbox" id="filter-no-insurance" onchange="applyEntryConditionsFilters()" style="display: none;">
                                <input type="checkbox" id="filter-no-entry-form" onchange="applyEntryConditionsFilters()" style="display: none;">

                                <!-- Available filters as badges -->
                                <div id="additionalFiltersAvailableFilters" class="flex flex-wrap gap-2">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-3 space-y-2 border-t border-gray-200">
                            <button onclick="searchEntryConditions()" class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                Suchen
                            </button>
                            <button id="reset-filters-button" onclick="resetEntryConditionsFilters()" class="w-full px-4 py-2.5 text-sm text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors flex items-center justify-center gap-2" style="display: none;">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Filter zurücksetzen
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search Results -->
                <div id="entry-conditions-search-results" class="bg-white shadow-sm" style="display: none;">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fa-regular fa-earth-africa"></i>
                            <span>Suchergebnisse (<span id="results-count">0</span>)</span>
                        </h3>
                    </div>
                    <div id="results-list" class="p-2 space-y-2 bg-white">
                        <!-- Results will be inserted here -->
                    </div>
                </div>
            </aside>

            <!-- Map Container -->
            <div class="map-container">
                <div id="entry-conditions-map"></div>
            </div>

            <!-- Details Sidebar (rechts, anfangs versteckt) -->
            <aside id="details-sidebar" class="details-sidebar" style="display: none;">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="font-semibold text-gray-800">Einreisebestimmungen</h2>
                        <button onclick="hideDetailsSidebar()" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div id="entry-conditions-content" class="space-y-4">
                        <div class="text-center text-gray-500 py-12">
                            <i class="fa-regular fa-passport text-6xl mb-4 text-gray-300"></i>
                            <p class="text-lg">Wählen Sie Nationalitäten und Reiseziele aus</p>
                            <p class="text-sm mt-2">und klicken Sie auf "Suchen" um Einreisebestimmungen anzuzeigen</p>
                        </div>
                    </div>
                </div>
            </aside>
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
        // Map initialisieren - Als window properties für Testbarkeit
        window.entryConditionsMap = null;
        window.entryConditionsMarkers = L.markerClusterGroup();
        window.countryLayersGroup = null;
        window.countriesGeoJSON = null;
        window.countryCoordinates = new Map(); // Map von ISO-Code zu {name, lat, lng, capital_name}

        document.addEventListener('DOMContentLoaded', async function() {
            // Map initialisieren
            window.entryConditionsMap = L.map('entry-conditions-map').setView([20, 0], 2);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(window.entryConditionsMap);

            window.entryConditionsMap.addLayer(window.entryConditionsMarkers);

            // Layer Group für Länder-Highlighting
            window.countryLayersGroup = L.layerGroup().addTo(window.entryConditionsMap);

            // Länder-Koordinaten laden
            await loadCountryCoordinates();

            // Initial Nationalitäten und Reiseziele anzeigen
            renderSelectedNationalities();
            renderSelectedDestinations();

            // Render initial filter badges
            renderSelectedFilters();

            // Prüfe beim Laden, ob Filter aktiv sind und stelle UI entsprechend ein
            updateResetButtonVisibility();

            // Scroll-Position speichern vor automatischer Suche
            const sidebar = document.querySelector('.sidebar');
            const initialScrollTop = sidebar ? sidebar.scrollTop : 0;

            // Automatische Suche beim Laden (mit vorausgewähltem Filter)
            await checkAndAutoSearch();

            // Scroll-Position wiederherstellen nach der Suche
            if (sidebar) {
                sidebar.scrollTop = initialScrollTop;
            }
        });

        // Prüfe ob Filter aktiv sind und führe automatisch Suche aus
        async function checkAndAutoSearch() {
            // Prüfe ob mindestens ein Filter aktiv ist
            const hasActiveFilters =
                document.getElementById('filter-passport')?.checked ||
                document.getElementById('filter-id-card')?.checked ||
                document.getElementById('filter-temp-passport')?.checked ||
                document.getElementById('filter-temp-id-card')?.checked ||
                document.getElementById('filter-child-passport')?.checked ||
                document.getElementById('filter-visa-free')?.checked ||
                document.getElementById('filter-e-visa')?.checked ||
                document.getElementById('filter-visa-on-arrival')?.checked ||
                document.getElementById('filter-no-insurance')?.checked ||
                document.getElementById('filter-no-entry-form')?.checked;

            // Wenn Filter aktiv sind, automatisch Suche ausführen
            if (hasActiveFilters) {
                console.log('Auto-search: Filters detected, executing search automatically');
                searchEntryConditions();
            } else {
                // Wenn keine Filter aktiv sind, alle verfügbaren Länder anzeigen
                console.log('No filters active, displaying all available countries');
                await displayAllCountries();
            }
        }

        // Alle verfügbaren Länder anzeigen (ohne Filter)
        async function displayAllCountries() {
            // Alle Länder aus der Map holen (bereits in loadCountryCoordinates geladen)
            const allCountries = Array.from(window.countryCoordinates.entries()).map(([code, data]) => ({
                code: code,
                name: data.name,
                lat: data.lat,
                lng: data.lng,
                capital_name: data.capital_name
            }));

            // Sortiere Länder alphabetisch nach Name
            allCountries.sort((a, b) => a.name.localeCompare(b.name, 'de'));

            // Zeige Länder in der Liste
            displaySearchResults(allCountries);

            // Zeige Länder auf der Karte
            displayCountriesOnMap(allCountries);
        }

        // Länder-Koordinaten aus der API laden (alle Länder für Reiseziele)
        async function loadCountryCoordinates() {
            try {
                // Use all-coordinates endpoint to get ALL countries, not just available nationalities
                const response = await fetch('/api/entry-conditions/all-coordinates');
                const data = await response.json();

                if (data.success && data.countries) {
                    data.countries.forEach(country => {
                        if (country.lat && country.lng) {
                            window.countryCoordinates.set(country.code, {
                                name: country.name,
                                lat: country.lat,
                                lng: country.lng,
                                capital_name: country.capital_name
                            });
                        }
                    });
                    console.log('Loaded coordinates for', window.countryCoordinates.size, 'countries');
                }
            } catch (error) {
                console.error('Error loading country coordinates:', error);
            }
        }

        // GeoJSON-Daten für Länder laden - NICHT VERWENDET, da keine GeoJSON-Datei vorhanden
        async function loadCountriesGeoJSON() {
            // Deaktiviert - wir verwenden stattdessen Marker-basiertes Highlighting
            console.log('GeoJSON loading skipped - using marker-based highlighting instead');
        }

        // Länder auf der Karte hervorheben mit Markern (Hauptstadt-Koordinaten)
        async function highlightCountriesOnMap(countryCodes) {
            try {
                console.log('highlightCountriesOnMap called with:', countryCodes);

                if (!window.countryLayersGroup) {
                    console.warn('window.countryLayersGroup not initialized');
                    return;
                }

                // Vorherige Layer entfernen
                window.countryLayersGroup.clearLayers();

                const bounds = [];

                // Für jedes ausgewählte Land die Hauptstadt-Koordinaten verwenden
                for (const countryCode of countryCodes) {
                    try {
                        console.log('Looking up coordinates for:', countryCode);

                        // Prüfe ob wir Koordinaten für dieses Land haben
                        const countryData = window.countryCoordinates.get(countryCode);

                        if (countryData && countryData.lat && countryData.lng) {
                            const lat = countryData.lat;
                            const lng = countryData.lng;

                            console.log('Got capital coordinates for', countryCode, ':', lat, lng, '(', countryData.capital_name, ')');

                            // Erstelle einen großen blauen Kreis-Marker um die Hauptstadt
                            const circle = L.circle([lat, lng], {
                                color: '#1d4ed8',
                                fillColor: '#3b82f6',
                                fillOpacity: 0.4,
                                radius: 300000, // 300km Radius
                                weight: 2
                            });

                            circle.addTo(window.countryLayersGroup);

                            // Popup mit Land- und Hauptstadtname
                            const selectedCountry = window.selectedDestinations.get(countryCode);
                            const countryName = selectedCountry ? selectedCountry.name : countryData.name;
                            circle.bindPopup(`<b>${countryName}</b><br>Hauptstadt: ${countryData.capital_name}<br>ISO: ${countryCode}`);

                            // Bounds hinzufügen
                            bounds.push([lat, lng]);

                            console.log('Added circle marker for', countryCode);
                        } else {
                            console.warn('No coordinates found for', countryCode);
                        }
                    } catch (e) {
                        console.error('Error processing country code:', countryCode, e);
                    }
                }

                console.log('Total circles added:', bounds.length);

                // Karte auf alle ausgewählten Länder zoomen
                if (bounds.length > 0) {
                    const latLngBounds = L.latLngBounds(bounds);

                    console.log('Zooming map to bounds:', latLngBounds);

                    window.entryConditionsMap.fitBounds(latLngBounds, {
                        padding: [100, 100],
                        maxZoom: 5
                    });
                } else {
                    console.warn('No bounds to zoom to');
                }
            } catch (error) {
                console.error('Error highlighting countries on map:', error);
                // Fehler nicht nach oben propagieren, damit die Suche weiterläuft
            }
        }

        // Toggle Main Section
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            const icon = document.getElementById(sectionId + 'ToggleIcon');

            if (section.style.display === 'none') {
                section.style.display = 'block';
                icon.style.transform = 'rotate(0deg)';
            } else {
                section.style.display = 'none';
                icon.style.transform = 'rotate(180deg)';
            }
        }

        // Toggle Filter Sub Section
        function toggleFilterSubSection(sectionId) {
            const section = document.getElementById(sectionId);
            const iconId = sectionId.replace('Section', '') + 'ToggleIcon';
            const icon = document.getElementById(iconId);

            if (!section) return;

            if (section.style.display === 'none' || section.style.display === '') {
                section.style.display = 'block';
                if (icon) {
                    // Für die Nationalität-Section (bg-gray-100 style) verwenden wir 180deg für geöffnet
                    if (sectionId === 'nationalitySection') {
                        icon.style.transform = 'rotate(180deg)';
                    } else {
                        // Für die anderen Sections (bg-gray-200 style) verwenden wir 180deg für geöffnet
                        icon.style.transform = 'rotate(180deg)';
                    }
                }
                // Entferne bottom padding wenn Section geöffnet ist
                updateDisplayPadding(sectionId);
            } else {
                section.style.display = 'none';
                if (icon) {
                    // Für alle Sections: 0deg = geschlossen (Pfeil nach unten)
                    icon.style.transform = 'rotate(0deg)';
                }
                // Füge bottom padding hinzu wenn Section geschlossen ist und Inhalte vorhanden sind
                updateDisplayPadding(sectionId);
            }
        }

        // Update padding für Display-Bereiche basierend auf Section-Status und Inhalten
        function updateDisplayPadding(sectionId) {
            let displayId = '';
            if (sectionId === 'nationalitySection') {
                displayId = 'selectedNationalityDisplay';
            } else if (sectionId === 'destinationsSection') {
                displayId = 'selectedDestinationsDisplay';
            } else if (sectionId === 'entryPossibleSection') {
                displayId = 'selectedEntryPossibleDisplay';
            } else if (sectionId === 'visaSection') {
                displayId = 'selectedVisaDisplay';
            } else if (sectionId === 'additionalFiltersSection') {
                displayId = 'selectedAdditionalFiltersDisplay';
            }

            if (!displayId) return;

            const displayElement = document.getElementById(displayId);
            const section = document.getElementById(sectionId);

            if (!displayElement || !section) return;

            // Prüfe ob Section geschlossen ist
            const isClosed = section.style.display === 'none';

            // Prüfe ob Inhalte vorhanden sind (mehr als nur der Kommentar)
            const hasContent = displayElement.children.length > 0;

            // Setze padding-bottom nur wenn Section geschlossen ist UND Inhalte vorhanden sind
            if (isClosed && hasContent) {
                displayElement.style.paddingBottom = '5px';
            } else {
                displayElement.style.paddingBottom = '0';
            }
        }

        // Nationality Filter Variables - Als window properties für Testbarkeit
        window.selectedNationalities = new Map();
        window.selectedNationalities.set('DE', { code: 'DE', name: 'Deutschland' });
        let nationalityFilterActiveIndex = 0;

        // Destinations Filter Variables - Als window properties für Testbarkeit
        window.selectedDestinations = new Map();
        // Standard: "Beliebiges Reiseziel" beim Laden auswählen
        window.selectedDestinations.set('*', { code: '*', name: 'Beliebiges Reiseziel' });
        let destinationsFilterActiveIndex = 0;

        // Helper Functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function escapeForAttr(text) {
            return (text || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // Nationality Filter Handlers
        let debouncedNationalitySearch = debounce(searchNationalityForFilter, 300);

        // Destinations Filter Handlers
        let debouncedDestinationsSearch = debounce(searchDestinationsForFilter, 300);

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function handleNationalityFilterKeyup(event) {
            if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp' && event.key !== 'Enter') {
                debouncedNationalitySearch(event.target.value);
            }
        }

        function handleNationalityFilterKeydown(event) {
            const box = document.getElementById('nationalityFilterResults');
            if (!box) return;

            const items = box.querySelectorAll('.autocomplete-item');
            if (items.length === 0) return;

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                nationalityFilterActiveIndex = Math.min(nationalityFilterActiveIndex + 1, items.length - 1);
                setNationalityFilterActiveIndex(nationalityFilterActiveIndex);
                items[nationalityFilterActiveIndex]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                nationalityFilterActiveIndex = Math.max(nationalityFilterActiveIndex - 1, 0);
                setNationalityFilterActiveIndex(nationalityFilterActiveIndex);
                items[nationalityFilterActiveIndex]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else if (event.key === 'Enter') {
                event.preventDefault();
                const activeItem = items[nationalityFilterActiveIndex];
                if (activeItem) {
                    const countryName = activeItem.getAttribute('data-name');
                    const iso2 = activeItem.getAttribute('data-iso2');
                    if (countryName) {
                        setNationality(countryName, iso2);
                        box.innerHTML = '';
                        event.target.value = '';
                    }
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                box.innerHTML = '';
                nationalityFilterActiveIndex = 0;
            }
        }

        function setNationalityFilterActiveIndex(newIndex) {
            const items = document.querySelectorAll('#nationalityFilterResults .autocomplete-item');
            items.forEach((item, i) => {
                if (i === newIndex) {
                    item.classList.add('border-blue-300');
                    item.classList.remove('border-gray-200');
                } else {
                    item.classList.remove('border-blue-300');
                    item.classList.add('border-gray-200');
                }
            });
            nationalityFilterActiveIndex = newIndex;
        }

        async function searchNationalityForFilter(query) {
            const box = document.getElementById('nationalityFilterResults');
            if (!box) return;
            const q = (query || '').trim();
            if (!q) { box.innerHTML = ''; return; }

            try {
                box.innerHTML = '<div class="text-xs text-gray-500">Suche…</div>';
                // Use entry-conditions endpoint which filters by available nationalities
                const res = await fetch('/api/entry-conditions/countries', {
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) throw new Error('Network error');

                const data = await res.json();
                const allCountries = Array.isArray(data.countries) ? data.countries : [];

                // Filter countries based on search query (name or code)
                const qLower = q.toLowerCase();
                const list = allCountries.filter(c => {
                    const nameLower = (c.name || '').toLowerCase();
                    const codeLower = (c.code || '').toLowerCase();
                    return nameLower.includes(qLower) || codeLower.includes(qLower);
                });

                if (!list.length) {
                    box.innerHTML = '<div class="text-xs text-gray-600 bg-yellow-50 border border-yellow-200 rounded px-3 py-2"><i class="fa-solid fa-lock mr-1 text-yellow-600"></i> Diese Nationalität kann in der kostenlosen Version nicht abgefragt werden.</div>';
                    return;
                }

                box.innerHTML = list.map((c, i) => (
                    `<div class="autocomplete-item px-2 py-1 rounded border hover:bg-gray-50 flex items-center justify-between bg-white ${i === 0 ? 'border-blue-300' : 'border-gray-200'}" data-index="${i}" data-name="${escapeForAttr(c.name)}" data-iso2="${escapeForAttr(c.code || '')}">
                        <div>
                            <div class="font-medium">${escapeHtml(c.name)}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(c.code || '')}</div>
                        </div>
                        <button class="text-xs px-2 py-1 border rounded text-gray-700 bg-gray-300 hover:bg-gray-100">Übernehmen</button>
                    </div>`
                )).join('');

                nationalityFilterActiveIndex = 0;
                box.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mouseenter', () => {
                        const idx = parseInt(el.getAttribute('data-index'));
                        setNationalityFilterActiveIndex(idx);
                    });
                    el.addEventListener('click', (e) => {
                        e.preventDefault();
                        const countryName = el.getAttribute('data-name');
                        const iso2 = el.getAttribute('data-iso2');
                        setNationality(countryName, iso2);
                        box.innerHTML = '';
                        document.getElementById('nationalityFilterInput').value = '';
                    });
                    el.querySelector('button')?.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const countryName = el.getAttribute('data-name');
                        const iso2 = el.getAttribute('data-iso2');
                        setNationality(countryName, iso2);
                        box.innerHTML = '';
                        document.getElementById('nationalityFilterInput').value = '';
                    });
                });
            } catch (e) {
                box.innerHTML = '<div class="text-xs text-red-600">Fehler bei der Suche</div>';
                console.error(e);
            }
        }

        function setNationality(countryName, iso2) {
            const code = iso2 || 'XX';

            // Prüfen ob bereits ausgewählt
            if (window.selectedNationalities.has(code)) {
                return; // Bereits ausgewählt, nichts tun
            }

            // Nationalität hinzufügen
            window.selectedNationalities.set(code, {
                name: countryName,
                code: code
            });

            // Suchfeld und Ergebnisse leeren
            const input = document.getElementById('nationalityFilterInput');
            if (input) input.value = '';

            const resultsBox = document.getElementById('nationalityFilterResults');
            if (resultsBox) resultsBox.innerHTML = '';

            // Ausgewählte Nationalitäten anzeigen
            renderSelectedNationalities();
        }

        function removeNationality(code) {
            // Nationalität entfernen
            window.selectedNationalities.delete(code);

            // Wenn keine Nationalität mehr ausgewählt ist, Deutschland als Standard setzen
            if (window.selectedNationalities.size === 0) {
                window.selectedNationalities.set('DE', { code: 'DE', name: 'Deutschland' });
            }

            renderSelectedNationalities();
        }

        function renderSelectedNationalities() {
            const displayContainer = document.getElementById('selectedNationalityDisplay');
            if (!displayContainer) return;

            if (window.selectedNationalities.size === 0) {
                displayContainer.innerHTML = '';
                updateResetButtonVisibility();
                updateDisplayPadding('nationalitySection');
                return;
            }

            const badges = Array.from(window.selectedNationalities.entries()).map(([code, data]) => `
                <span class="inline-flex items-center gap-2 bg-blue-50 text-blue-800 border border-blue-200 rounded px-2 py-1 text-sm">
                    <span>${escapeHtml(data.name)} (${escapeHtml(data.code)})</span>
                    <button type="button" class="text-blue-700 hover:text-blue-900" onclick="removeNationality('${escapeForAttr(code)}')" style="cursor: pointer;">&times;</button>
                </span>
            `).join('');

            displayContainer.innerHTML = badges;
            updateResetButtonVisibility();
            updateDisplayPadding('nationalitySection');
        }

        // ==================== DESTINATIONS FILTER FUNCTIONS ====================

        function handleDestinationsFilterKeyup(event) {
            if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp' && event.key !== 'Enter') {
                debouncedDestinationsSearch(event.target.value);
            }
        }

        function handleDestinationsFilterKeydown(event) {
            const box = document.getElementById('destinationsFilterResults');
            if (!box) return;

            const items = box.querySelectorAll('.autocomplete-item');
            if (items.length === 0) return;

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                destinationsFilterActiveIndex = Math.min(destinationsFilterActiveIndex + 1, items.length - 1);
                setDestinationsFilterActiveIndex(destinationsFilterActiveIndex);
                items[destinationsFilterActiveIndex]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                destinationsFilterActiveIndex = Math.max(destinationsFilterActiveIndex - 1, 0);
                setDestinationsFilterActiveIndex(destinationsFilterActiveIndex);
                items[destinationsFilterActiveIndex]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else if (event.key === 'Enter') {
                event.preventDefault();
                const activeItem = items[destinationsFilterActiveIndex];
                if (activeItem) {
                    const countryName = activeItem.getAttribute('data-name');
                    const iso2 = activeItem.getAttribute('data-iso2');
                    if (countryName) {
                        setDestination(countryName, iso2);
                        box.innerHTML = '';
                        event.target.value = '';
                    }
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                box.innerHTML = '';
                destinationsFilterActiveIndex = 0;
            }
        }

        function setDestinationsFilterActiveIndex(newIndex) {
            const items = document.querySelectorAll('#destinationsFilterResults .autocomplete-item');
            items.forEach((item, i) => {
                if (i === newIndex) {
                    item.classList.add('border-blue-300');
                    item.classList.remove('border-gray-200');
                } else {
                    item.classList.remove('border-blue-300');
                    item.classList.add('border-gray-200');
                }
            });
            destinationsFilterActiveIndex = newIndex;
        }

        async function searchDestinationsForFilter(query) {
            const box = document.getElementById('destinationsFilterResults');
            if (!box) return;
            const q = (query || '').trim();
            if (!q) { box.innerHTML = ''; return; }

            try {
                box.innerHTML = '<div class="text-xs text-gray-500">Suche…</div>';
                const res = await fetch('/api/countries/search?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) throw new Error('Network error');

                const data = await res.json();
                const list = Array.isArray(data.data) ? data.data : [];

                if (!list.length) {
                    box.innerHTML = '<div class="text-xs text-gray-500">Keine Treffer</div>';
                    return;
                }

                box.innerHTML = list.map((c, i) => (
                    `<div class="autocomplete-item px-2 py-1 rounded border hover:bg-gray-50 flex items-center justify-between bg-white ${i === 0 ? 'border-blue-300' : 'border-gray-200'}" data-index="${i}" data-name="${escapeForAttr(c.name)}" data-iso2="${escapeForAttr(c.iso2 || '')}">
                        <div>
                            <div class="font-medium">${escapeHtml(c.name)}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(c.iso2 || '')}${c.iso3 ? ' / ' + escapeHtml(c.iso3) : ''}</div>
                        </div>
                        <button class="text-xs px-2 py-1 border rounded text-gray-700 bg-gray-300 hover:bg-gray-100">Übernehmen</button>
                    </div>`
                )).join('');

                destinationsFilterActiveIndex = 0;
                box.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('mouseenter', () => {
                        const idx = parseInt(el.getAttribute('data-index'));
                        setDestinationsFilterActiveIndex(idx);
                    });
                    el.addEventListener('click', (e) => {
                        e.preventDefault();
                        const countryName = el.getAttribute('data-name');
                        const iso2 = el.getAttribute('data-iso2');
                        setDestination(countryName, iso2);
                        box.innerHTML = '';
                        document.getElementById('destinationsFilterInput').value = '';
                    });
                    el.querySelector('button')?.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const countryName = el.getAttribute('data-name');
                        const iso2 = el.getAttribute('data-iso2');
                        setDestination(countryName, iso2);
                        box.innerHTML = '';
                        document.getElementById('destinationsFilterInput').value = '';
                    });
                });
            } catch (e) {
                box.innerHTML = '<div class="text-xs text-red-600">Fehler bei der Suche</div>';
                console.error(e);
            }
        }

        function setDestination(countryName, iso2) {
            const code = iso2 || 'XX';

            // Prüfen ob bereits ausgewählt
            if (window.selectedDestinations.has(code)) {
                return; // Bereits ausgewählt, nichts tun
            }

            // Wenn ein spezifisches Land hinzugefügt wird, "Beliebiges Reiseziel" entfernen
            if (window.selectedDestinations.has('*')) {
                window.selectedDestinations.delete('*');
            }

            // Reiseziel hinzufügen
            window.selectedDestinations.set(code, {
                name: countryName,
                code: code
            });

            // Suchfeld und Ergebnisse leeren
            const input = document.getElementById('destinationsFilterInput');
            if (input) input.value = '';

            const resultsBox = document.getElementById('destinationsFilterResults');
            if (resultsBox) resultsBox.innerHTML = '';

            // Ausgewählte Reiseziele anzeigen
            renderSelectedDestinations();
        }

        function removeDestination(code) {
            // Reiseziel entfernen
            window.selectedDestinations.delete(code);

            renderSelectedDestinations();
        }

        function renderSelectedDestinations() {
            const displayContainer = document.getElementById('selectedDestinationsDisplay');
            if (!displayContainer) return;

            if (window.selectedDestinations.size === 0) {
                displayContainer.innerHTML = '';
                updateResetButtonVisibility();
                updateDisplayPadding('destinationsSection');
                return;
            }

            const badges = Array.from(window.selectedDestinations.entries()).map(([code, data]) => {
                // "Beliebiges Reiseziel" (*) ohne X-Button anzeigen (gleiches Design wie Nationalität)
                if (code === '*') {
                    return `
                        <span class="inline-flex items-center gap-2 bg-blue-50 text-blue-800 border border-blue-200 rounded px-2 py-1 text-sm">
                            <span>${escapeHtml(data.name)}</span>
                        </span>
                    `;
                }
                // Andere Reiseziele mit X-Button
                return `
                    <span class="inline-flex items-center gap-2 bg-blue-50 text-blue-800 border border-blue-200 rounded px-2 py-1 text-sm">
                        <span>${escapeHtml(data.name)} (${escapeHtml(data.code)})</span>
                        <button type="button" class="text-blue-700 hover:text-blue-900" onclick="removeDestination('${escapeForAttr(code)}')" style="cursor: pointer;">&times;</button>
                    </span>
                `;
            }).join('');

            displayContainer.innerHTML = badges;
            updateResetButtonVisibility();
            updateDisplayPadding('destinationsSection');
        }

        // Render Filter Badges
        function renderSelectedFilters() {
            renderEntryPossibleFilters();
            renderVisaFilters();
            renderAdditionalFilters();
        }

        function renderEntryPossibleFilters() {
            const displayContainer = document.getElementById('selectedEntryPossibleDisplay');
            const availableContainer = document.getElementById('entryPossibleAvailableFilters');
            if (!displayContainer || !availableContainer) return;

            const filters = [
                { id: 'filter-passport', label: 'Reisepass' },
                { id: 'filter-id-card', label: 'Personalausweis' },
                { id: 'filter-temp-passport', label: 'Vorl. Reisepass' },
                { id: 'filter-temp-id-card', label: 'Vorl. Personalausweis' },
                { id: 'filter-child-passport', label: 'Kinderreisepass' }
            ];

            const activeFilters = filters.filter(f => document.getElementById(f.id)?.checked);
            const inactiveFilters = filters.filter(f => !document.getElementById(f.id)?.checked);

            // Render active filters (green badges)
            if (activeFilters.length === 0) {
                displayContainer.innerHTML = '';
            } else {
                const activeBadges = activeFilters.map(f => `
                    <span class="inline-flex items-center gap-1 bg-green-50 text-green-800 border border-green-200 rounded px-2 py-1 text-xs cursor-pointer hover:bg-green-100" onclick="toggleFilter('${f.id}')">
                        <span>${escapeHtml(f.label)}</span>
                    </span>
                `).join('');
                displayContainer.innerHTML = activeBadges;
            }

            // Render inactive filters (white badges in section)
            if (inactiveFilters.length === 0) {
                availableContainer.innerHTML = '<span class="text-xs text-gray-500">Alle Filter ausgewählt</span>';
            } else {
                const inactiveBadges = inactiveFilters.map(f => `
                    <span class="inline-flex items-center gap-1 bg-white text-gray-700 border border-gray-300 rounded px-2 py-1 text-xs cursor-pointer hover:bg-gray-50" onclick="toggleFilter('${f.id}')">
                        <span>${escapeHtml(f.label)}</span>
                    </span>
                `).join('');
                availableContainer.innerHTML = inactiveBadges;
            }

            updateDisplayPadding('entryPossibleSection');
        }

        function toggleFilter(filterId) {
            const checkbox = document.getElementById(filterId);
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        }

        function renderVisaFilters() {
            const displayContainer = document.getElementById('selectedVisaDisplay');
            const availableContainer = document.getElementById('visaAvailableFilters');
            if (!displayContainer || !availableContainer) return;

            const filters = [
                { id: 'filter-visa-free', label: 'Ohne Visum' },
                { id: 'filter-e-visa', label: 'E-Visum' },
                { id: 'filter-visa-on-arrival', label: 'Visum bei Ankunft' }
            ];

            const activeFilters = filters.filter(f => document.getElementById(f.id)?.checked);
            const inactiveFilters = filters.filter(f => !document.getElementById(f.id)?.checked);

            // Render active filters (green badges)
            if (activeFilters.length === 0) {
                displayContainer.innerHTML = '';
            } else {
                const activeBadges = activeFilters.map(f => `
                    <span class="inline-flex items-center gap-1 bg-green-50 text-green-800 border border-green-200 rounded px-2 py-1 text-xs cursor-pointer hover:bg-green-100" onclick="toggleFilter('${f.id}')">
                        <span>${escapeHtml(f.label)}</span>
                    </span>
                `).join('');
                displayContainer.innerHTML = activeBadges;
            }

            // Render inactive filters (white badges in section)
            if (inactiveFilters.length === 0) {
                availableContainer.innerHTML = '<span class="text-xs text-gray-500">Alle Filter ausgewählt</span>';
            } else {
                const inactiveBadges = inactiveFilters.map(f => `
                    <span class="inline-flex items-center gap-1 bg-white text-gray-700 border border-gray-300 rounded px-2 py-1 text-xs cursor-pointer hover:bg-gray-50" onclick="toggleFilter('${f.id}')">
                        <span>${escapeHtml(f.label)}</span>
                    </span>
                `).join('');
                availableContainer.innerHTML = inactiveBadges;
            }

            updateDisplayPadding('visaSection');
        }

        function renderAdditionalFilters() {
            const displayContainer = document.getElementById('selectedAdditionalFiltersDisplay');
            const availableContainer = document.getElementById('additionalFiltersAvailableFilters');
            if (!displayContainer || !availableContainer) return;

            const filters = [
                { id: 'filter-no-insurance', label: 'Keine Versicherung' },
                { id: 'filter-no-entry-form', label: 'Kein Einreiseformular' }
            ];

            const activeFilters = filters.filter(f => document.getElementById(f.id)?.checked);
            const inactiveFilters = filters.filter(f => !document.getElementById(f.id)?.checked);

            // Render active filters (green badges)
            if (activeFilters.length === 0) {
                displayContainer.innerHTML = '';
            } else {
                const activeBadges = activeFilters.map(f => `
                    <span class="inline-flex items-center gap-1 bg-green-50 text-green-800 border border-green-200 rounded px-2 py-1 text-xs cursor-pointer hover:bg-green-100" onclick="toggleFilter('${f.id}')">
                        <span>${escapeHtml(f.label)}</span>
                    </span>
                `).join('');
                displayContainer.innerHTML = activeBadges;
            }

            // Render inactive filters (white badges in section)
            if (inactiveFilters.length === 0) {
                availableContainer.innerHTML = '<span class="text-xs text-gray-500">Alle Filter ausgewählt</span>';
            } else {
                const inactiveBadges = inactiveFilters.map(f => `
                    <span class="inline-flex items-center gap-1 bg-white text-gray-700 border border-gray-300 rounded px-2 py-1 text-xs cursor-pointer hover:bg-gray-50" onclick="toggleFilter('${f.id}')">
                        <span>${escapeHtml(f.label)}</span>
                    </span>
                `).join('');
                availableContainer.innerHTML = inactiveBadges;
            }

            updateDisplayPadding('additionalFiltersSection');
        }

        // Filter anwenden
        async function applyEntryConditionsFilters() {
            console.log('=== applyEntryConditionsFilters called ===');

            // Prüfe ob mindestens ein Filter aktiv ist in den verschiedenen Kategorien
            const hasEntryFilters =
                document.getElementById('filter-passport')?.checked ||
                document.getElementById('filter-id-card')?.checked ||
                document.getElementById('filter-temp-passport')?.checked ||
                document.getElementById('filter-temp-id-card')?.checked ||
                document.getElementById('filter-child-passport')?.checked;

            const hasVisaFilters =
                document.getElementById('filter-visa-free')?.checked ||
                document.getElementById('filter-e-visa')?.checked ||
                document.getElementById('filter-visa-on-arrival')?.checked;

            const hasAdditionalFilters =
                document.getElementById('filter-no-insurance')?.checked ||
                document.getElementById('filter-no-entry-form')?.checked;

            const hasActiveFilters = hasEntryFilters || hasVisaFilters || hasAdditionalFilters;

            console.log('Filter status:', {
                hasEntryFilters,
                hasVisaFilters,
                hasAdditionalFilters,
                hasActiveFilters
            });

            // Render Filter Badges
            renderSelectedFilters();

            // Update Reset-Button Sichtbarkeit
            updateResetButtonVisibility();

            const destinationsInput = document.getElementById('destinationsFilterInput');
            const selectedDestinationsDisplay = document.getElementById('selectedDestinationsDisplay');

            if (hasActiveFilters) {
                // Filter aktiv: Reiseziele-Feld deaktivieren und ausgrauen
                if (destinationsInput) {
                    destinationsInput.disabled = true;
                    destinationsInput.classList.add('bg-gray-100', 'cursor-not-allowed', 'text-gray-500');
                    destinationsInput.placeholder = 'Filter aktiv - Reiseziel bleibt erhalten';
                }

                // Automatische Suche mit allen aktiven Filtern durchführen
                console.log('=== Triggering automatic search ===');
                await searchWithDestinationValidation();
                console.log('=== Search completed ===');
            } else {
                // Keine Filter: Reiseziele-Feld wieder aktivieren
                if (destinationsInput) {
                    destinationsInput.disabled = false;
                    destinationsInput.classList.remove('bg-gray-100', 'cursor-not-allowed', 'text-gray-500');
                    destinationsInput.placeholder = 'Land suchen (Name oder Code)...';
                }

                // Wenn keine Filter mehr aktiv sind, Ergebnisse ausblenden
                const resultsDiv = document.getElementById('entry-conditions-search-results');
                if (resultsDiv) {
                    resultsDiv.style.display = 'none';
                }
            }
        }

        // Reset-Button Sichtbarkeit aktualisieren
        function updateResetButtonVisibility() {
            const resetButton = document.getElementById('reset-filters-button');
            if (!resetButton) return;

            // Prüfe ob mehrere Nationalitäten ausgewählt sind (mehr als nur Deutschland)
            const hasMultipleNationalities = window.selectedNationalities.size > 1 ||
                (window.selectedNationalities.size === 1 && !window.selectedNationalities.has('DE'));

            // Prüfe ob Reiseziele ausgewählt sind
            const hasDestinations = window.selectedDestinations.size > 0;

            // Prüfe ob Filter aktiv sind
            const hasActiveFilters =
                document.getElementById('filter-passport')?.checked ||
                document.getElementById('filter-id-card')?.checked ||
                document.getElementById('filter-temp-passport')?.checked ||
                document.getElementById('filter-temp-id-card')?.checked ||
                document.getElementById('filter-child-passport')?.checked ||
                document.getElementById('filter-visa-free')?.checked ||
                document.getElementById('filter-e-visa')?.checked ||
                document.getElementById('filter-visa-on-arrival')?.checked ||
                document.getElementById('filter-no-insurance')?.checked ||
                document.getElementById('filter-no-entry-form')?.checked;

            // Button anzeigen wenn eine der Bedingungen erfüllt ist
            if (hasMultipleNationalities || hasDestinations || hasActiveFilters) {
                resetButton.style.display = 'flex';
            } else {
                resetButton.style.display = 'none';
            }
        }

        // Filter zurücksetzen
        function resetEntryConditionsFilters() {
            // Alle Checkboxen zurücksetzen
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);

            // Nationalitäten zurücksetzen (nur Deutschland)
            window.selectedNationalities.clear();
            window.selectedNationalities.set('DE', { code: 'DE', name: 'Deutschland' });
            renderSelectedNationalities();

            // Reiseziele zurücksetzen auf "Beliebiges Reiseziel"
            window.selectedDestinations.clear();
            window.selectedDestinations.set('*', { code: '*', name: 'Beliebiges Reiseziel' });
            renderSelectedDestinations();

            // Suchfelder leeren
            const nationalityInput = document.getElementById('nationalityFilterInput');
            if (nationalityInput) nationalityInput.value = '';

            const destinationsInput = document.getElementById('destinationsFilterInput');
            if (destinationsInput) {
                destinationsInput.value = '';
                // Reiseziele-Feld wieder aktivieren
                destinationsInput.disabled = false;
                destinationsInput.classList.remove('bg-gray-100', 'cursor-not-allowed', 'text-gray-500');
                destinationsInput.placeholder = 'Land suchen (Name oder Code)...';
            }

            // Suchergebnisse ausblenden
            const resultsDiv = document.getElementById('entry-conditions-search-results');
            if (resultsDiv) {
                resultsDiv.style.display = 'none';
            }

            // Details-Sidebar ausblenden
            hideDetailsSidebar();

            // Marker von der Karte entfernen
            window.entryConditionsMarkers.clearLayers();

            // Länder-Layer von der Karte entfernen
            if (window.countryLayersGroup) {
                window.countryLayersGroup.clearLayers();
            }

            // Karte auf Standardansicht zurücksetzen
            if (window.entryConditionsMap) {
                window.entryConditionsMap.setView([20, 0], 2);
            }

            // Filter-Badges neu rendern (zeigt alle Filter als inaktiv/weiß)
            renderSelectedFilters();

            // Reset-Button Sichtbarkeit aktualisieren
            updateResetButtonVisibility();
        }

        // Suche mit Validierung ob Reiseziel in Ergebnissen vorkommt
        async function searchWithDestinationValidation() {
            const nationalityCodes = Array.from(window.selectedNationalities.keys());
            const destinationCodes = Array.from(window.selectedDestinations.keys());

            console.log('searchWithDestinationValidation called:', {
                nationalityCodes,
                destinationCodes,
                nationalitiesCount: nationalityCodes.length,
                destinationsCount: destinationCodes.length
            });

            if (nationalityCodes.length === 0) {
                console.log('No nationalities selected, aborting search');
                return; // Keine Nationalität ausgewählt
            }

            // Wenn keine Reiseziele ausgewählt sind, setze "Beliebiges Reiseziel" als Standard
            if (destinationCodes.length === 0) {
                console.log('No destinations selected, setting default "*"');
                window.selectedDestinations.set('*', { code: '*', name: 'Beliebiges Reiseziel' });
                renderSelectedDestinations();
                destinationCodes.push('*');
            }

            // Wenn "Beliebiges Reiseziel" (*) ausgewählt ist, normale Filtersuche durchführen
            if (destinationCodes.length === 1 && destinationCodes[0] === '*') {
                console.log('Using wildcard destination (*), calling searchEntryConditions()');
                // Führe normale Suche aus und zeige alle Ergebnisse in der Liste
                await searchEntryConditions();
                console.log('searchEntryConditions() completed');
                return;
            }

            // Filter sammeln
            const filters = {
                passport: document.getElementById('filter-passport')?.checked || false,
                idCard: document.getElementById('filter-id-card')?.checked || false,
                tempPassport: document.getElementById('filter-temp-passport')?.checked || false,
                tempIdCard: document.getElementById('filter-temp-id-card')?.checked || false,
                childPassport: document.getElementById('filter-child-passport')?.checked || false,
                visaFree: document.getElementById('filter-visa-free')?.checked || false,
                eVisa: document.getElementById('filter-e-visa')?.checked || false,
                visaOnArrival: document.getElementById('filter-visa-on-arrival')?.checked || false,
                noInsurance: document.getElementById('filter-no-insurance')?.checked || false,
                noEntryForm: document.getElementById('filter-no-entry-form')?.checked || false
            };

            // Sidebar vorbereiten
            const sidebar = document.getElementById('details-sidebar');
            const content = document.getElementById('entry-conditions-content');

            if (!sidebar || !content) return;

            sidebar.style.display = 'block';
            content.innerHTML = `
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="text-gray-600 mt-4">Prüfe Einreisebestimmungen...</p>
                </div>
            `;

            try {
                // Filtersuche durchführen
                const allDestinations = new Map();

                for (const nationality of nationalityCodes) {
                    const response = await fetch('/api/entry-conditions/search', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            nationality: nationality,
                            filters: filters
                        })
                    });

                    const data = await response.json();

                    if (data.success && data.destinations) {
                        data.destinations.forEach(dest => {
                            const key = dest.code || dest.name;
                            if (!allDestinations.has(key)) {
                                allDestinations.set(key, dest);
                            }
                        });
                    }
                }

                // Prüfen ob gewähltes Reiseziel in den Ergebnissen ist
                let foundDestinations = [];
                let notFoundDestinations = [];

                for (const destCode of destinationCodes) {
                    if (allDestinations.has(destCode)) {
                        foundDestinations.push(destCode);
                    } else {
                        const destData = window.selectedDestinations.get(destCode);
                        notFoundDestinations.push(destData ? destData.name : destCode);
                    }
                }

                // Ergebnis anzeigen
                if (foundDestinations.length > 0) {
                    // Gefundene Länder: Details anzeigen
                    await loadEntryConditionsContent(nationalityCodes, foundDestinations);

                    // Auf Karte markieren
                    highlightCountriesOnMap(foundDestinations);
                } else {
                    // Kein Land gefunden: Fehlermeldung anzeigen
                    const destNames = destinationCodes.map(code => {
                        const data = window.selectedDestinations.get(code);
                        return data ? data.name : code;
                    }).join(', ');

                    content.innerHTML = `
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fa-solid fa-exclamation-triangle text-yellow-600 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">
                                        Keine Einreise mit den gewählten Filterkriterien möglich
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>Das gewählte Reiseziel <strong>${escapeHtml(destNames)}</strong> erfüllt nicht die ausgewählten Filterkriterien.</p>
                                        <p class="mt-2">Bitte ändern Sie die Filter oder wählen Sie ein anderes Reiseziel.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

            } catch (error) {
                console.error('Error in searchWithDestinationValidation:', error);
                content.innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <i class="fa-solid fa-circle-exclamation text-4xl mb-4"></i>
                        <p>Fehler bei der Suche. Bitte versuchen Sie es erneut.</p>
                    </div>
                `;
            }
        }

        // Einreisebestimmungen suchen
        async function searchEntryConditions() {
            console.log('Search started');

            // Prüfen ob mindestens eine Nationalität und mindestens ein Reiseziel ausgewählt ist
            const nationalityCodes = Array.from(window.selectedNationalities.keys());
            const destinationCodes = Array.from(window.selectedDestinations.keys());

            console.log('Nationalities:', nationalityCodes, 'Destinations:', destinationCodes);

            // Wenn spezifisches Reiseziel (nicht "*") gesetzt ist, Content API verwenden
            if (nationalityCodes.length > 0 && destinationCodes.length > 0 && !destinationCodes.includes('*')) {
                console.log('Using new content API');

                // Länder auf der Karte hervorheben
                highlightCountriesOnMap(destinationCodes);

                // Neue Content API aufrufen
                await loadEntryConditionsContent(nationalityCodes, destinationCodes);
                return;
            }

            console.log('Using filter-based search (for "Beliebiges Reiseziel" or no destination)');

            // Alte Logik: Filter-basierte Suche wenn keine oder nur eine Auswahl getroffen wurde
            // Filter sammeln (camelCase für API)
            const filters = {
                passport: document.getElementById('filter-passport')?.checked || false,
                idCard: document.getElementById('filter-id-card')?.checked || false,
                tempPassport: document.getElementById('filter-temp-passport')?.checked || false,
                tempIdCard: document.getElementById('filter-temp-id-card')?.checked || false,
                childPassport: document.getElementById('filter-child-passport')?.checked || false,
                visaFree: document.getElementById('filter-visa-free')?.checked || false,
                eVisa: document.getElementById('filter-e-visa')?.checked || false,
                visaOnArrival: document.getElementById('filter-visa-on-arrival')?.checked || false,
                noInsurance: document.getElementById('filter-no-insurance')?.checked || false,
                noEntryForm: document.getElementById('filter-no-entry-form')?.checked || false
            };

            // Loading-Anzeige
            const resultsDiv = document.getElementById('entry-conditions-search-results');
            const resultsList = document.getElementById('results-list');
            const resultsCount = document.getElementById('results-count');

            resultsDiv.style.display = 'block';
            resultsList.innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div><p class="text-gray-600 mt-4">Suche läuft...</p></div>';

            try {
                // Für jede ausgewählte Nationalität die API abfragen
                const allDestinations = new Map(); // Um Duplikate zu vermeiden

                for (const nationality of nationalityCodes) {
                    const response = await fetch('/api/entry-conditions/search', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            nationality: nationality,
                            filters: filters
                        })
                    });

                    const data = await response.json();

                    if (data.success && data.destinations) {
                        // Destinations hinzufügen (Duplikate vermeiden)
                        data.destinations.forEach(dest => {
                            const key = dest.code || dest.name;
                            if (!allDestinations.has(key)) {
                                allDestinations.set(key, dest);
                            }
                        });
                    }
                }

                // Ergebnisse als Array
                let destinationsArray = Array.from(allDestinations.values());

                // Falls spezifische Reiseziele ausgewählt wurden (nicht "*"), nur diese anzeigen
                if (window.selectedDestinations.size > 0 && !window.selectedDestinations.has('*')) {
                    const selectedDestinationCodes = Array.from(window.selectedDestinations.keys());
                    destinationsArray = destinationsArray.filter(dest =>
                        selectedDestinationCodes.includes(dest.code)
                    );
                }

                if (destinationsArray.length > 0) {
                    displaySearchResults(destinationsArray);
                    displayCountriesOnMap(destinationsArray);
                } else {
                    resultsList.innerHTML = '<div class="text-center text-gray-500 py-8"><p>Keine Ergebnisse gefunden. Bitte passen Sie Ihre Filter an.</p></div>';
                }
            } catch (error) {
                console.error('Error searching entry conditions:', error);
                resultsList.innerHTML = '<div class="text-center text-red-500 py-8"><p>Fehler bei der Suche. Bitte versuchen Sie es erneut.</p></div>';
            }
        }

        // Neue Funktion: Entry Conditions Content laden und in rechter Sidebar anzeigen
        async function loadEntryConditionsContent(nationalityCodes, destinationCodes) {
            const sidebar = document.getElementById('details-sidebar');
            const content = document.getElementById('entry-conditions-content');

            if (!sidebar || !content) return;

            // Sidebar anzeigen
            sidebar.style.display = 'block';

            // Loading anzeigen
            content.innerHTML = `
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="text-gray-600 mt-4">Lade Einreisebestimmungen...</p>
                </div>
            `;

            // Karte neu zeichnen
            setTimeout(() => {
                if (window.entryConditionsMap) {
                    window.entryConditionsMap.invalidateSize();
                }
            }, 300);

            try {
                // Container für alle Ergebnisse
                let allResultsHtml = '';

                // Für jede Kombination von Nationalität und Reiseziel die API aufrufen
                for (const natCode of nationalityCodes) {
                    const nationalityData = window.selectedNationalities.get(natCode);
                    const nationalityName = nationalityData ? nationalityData.name : natCode;

                    for (const destCode of destinationCodes) {
                        const destinationData = window.selectedDestinations.get(destCode);
                        const destinationName = destinationData ? destinationData.name : destCode;

                        // API-Aufruf für diese Kombination
                        const response = await fetch('/api/entry-conditions/content', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                countries: [destCode],
                                nationalities: [natCode]
                            })
                        });

                        const data = await response.json();

                        // Ergebnis formatieren mit besserer Fehlerbehandlung
                        let contentHtml = '<p class="text-gray-500 text-sm">Keine Informationen verfügbar</p>';

                        if (data.success && data.content) {
                            // Content ist bereits HTML-String vom Backend
                            contentHtml = data.content;
                        } else if (data.message) {
                            contentHtml = `<p class="text-red-500 text-sm">${escapeHtml(data.message)}</p>`;
                        }

                        // Entferne h2-Überschriften (wie "Schnellübersicht") aus dem Content
                        let cleanedContentHtml = contentHtml.replace(/<h2[^>]*>.*?<\/h2>/gi, '');

                        // Entferne thead-Bereiche mit "Schnellübersicht" aus Tabellen
                        cleanedContentHtml = cleanedContentHtml.replace(/<thead[^>]*>[\s\S]*?<\/thead>/gi, '');

                        allResultsHtml += `
                            <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-4">
                                <div style="background-color: #f4f4f4;" class="px-4 py-3 border-b border-gray-200">
                                    <div class="mb-2">
                                        <span class="text-base font-bold text-gray-800">Schnellübersicht für</span>
                                    </div>
                                    <div class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold text-gray-800 uppercase">Nationalität:</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <img src="https://flagcdn.com/w40/${escapeForAttr(natCode.toLowerCase())}.png"
                                                 alt="${escapeHtml(nationalityName)}"
                                                 class="country-flag"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                                            <span class="flag-fallback" style="display:none;">${escapeHtml(natCode)}</span>
                                            <span class="text-sm font-bold text-gray-800">${escapeHtml(nationalityName)}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold text-gray-800 uppercase">Reiseziel:</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <img src="https://flagcdn.com/w40/${escapeForAttr(destCode.toLowerCase())}.png"
                                                 alt="${escapeHtml(destinationName)}"
                                                 class="country-flag"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                                            <span class="flag-fallback" style="display:none;">${escapeHtml(destCode)}</span>
                                            <span class="text-sm font-bold text-gray-800">${escapeHtml(destinationName)}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="entry-conditions-content-body text-sm text-gray-700 leading-relaxed">
                                        ${cleanedContentHtml}
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                }

                // Alle Ergebnisse anzeigen mit zentraler Warnung
                if (allResultsHtml) {
                    content.innerHTML = `
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fa-solid fa-exclamation-triangle text-yellow-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs text-yellow-800 leading-relaxed">
                                        Bei der Schnellübersicht handelt es sich um eine grobe Übersicht für Pauschalreisen. Die kompletten Einreisebestimmungen für Reisende mit der betreffenden Nationalität erhalten Sie, wenn Sie auf die jeweilige Schaltfläche "PDF Download" klicken. <strong>Alle Informationen sind unverbindlich. Keine Haftung!</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                        ${allResultsHtml}
                    `;
                } else {
                    content.innerHTML = `
                        <div class="text-center text-gray-500 py-8">
                            <p>Keine Informationen verfügbar</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading entry conditions content:', error);
                content.innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <p>Fehler beim Laden der Einreisebestimmungen</p>
                        <p class="text-sm mt-2">${escapeHtml(error.message)}</p>
                    </div>
                `;
            }
        }

        // Suchergebnisse anzeigen
        function displaySearchResults(destinations) {
            const resultsDiv = document.getElementById('entry-conditions-search-results');
            const resultsList = document.getElementById('results-list');
            const resultsCount = document.getElementById('results-count');

            if (!resultsDiv || !resultsList || !resultsCount) return;

            resultsCount.textContent = destinations.length;

            resultsList.innerHTML = '';

            if (destinations.length === 0) {
                resultsList.innerHTML = '<div class="text-center text-gray-500 py-8"><p>Keine Ergebnisse gefunden. Bitte passen Sie Ihre Filter an.</p></div>';
                return;
            }

            destinations.forEach(destination => {
                const item = document.createElement('div');
                item.className = 'p-3 bg-white hover:bg-gray-50 rounded-lg cursor-pointer border border-gray-200 transition-colors';

                // Verwende destination.code für ISO-Code
                const countryCode = destination.code || destination.iso2 || '';
                const countryName = destination.name || 'Unbekannt';

                item.onclick = () => loadEntryConditionsForCountry(countryName, countryCode);

                item.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <img src="https://flagcdn.com/w40/${countryCode.toLowerCase()}.png"
                                 alt="${countryName}"
                                 class="country-flag"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                            <span class="flag-fallback" style="display:none;">${countryCode}</span>
                            <span class="font-medium text-gray-800">${countryName}</span>
                        </div>
                        <i class="fa-regular fa-chevron-right text-gray-400"></i>
                    </div>
                `;

                resultsList.appendChild(item);
            });

            resultsDiv.style.display = 'block';
        }

        // Länder auf Karte anzeigen (mit Hauptstadt-Koordinaten)
        async function displayCountriesOnMap(destinations) {
            window.entryConditionsMarkers.clearLayers();

            // Für jedes Ziel die Hauptstadt-Koordinaten verwenden
            for (const destination of destinations) {
                const countryCode = destination.code || destination.iso2;
                if (!countryCode) continue;

                try {
                    // Prüfe ob wir Koordinaten für dieses Land haben
                    const countryData = window.countryCoordinates.get(countryCode);

                    if (countryData && countryData.lat && countryData.lng) {
                        // Marker an Hauptstadt-Position setzen
                        const marker = L.marker([countryData.lat, countryData.lng]);
                        marker.bindPopup(`<b>${destination.name || countryCode}</b><br>Hauptstadt: ${countryData.capital_name}`);
                        marker.on('click', () => loadEntryConditionsForCountry(destination.name, countryCode));
                        window.entryConditionsMarkers.addLayer(marker);
                    } else {
                        console.warn(`No coordinates found for ${countryCode}`);
                    }
                } catch (error) {
                    console.error(`Error loading coordinates for ${countryCode}:`, error);
                }
            }

            // Karte auf Marker zentrieren
            if (window.entryConditionsMarkers.getLayers().length > 0) {
                window.entryConditionsMap.fitBounds(window.entryConditionsMarkers.getBounds());
            }
        }

        // Einreisebestimmungen für ein Land laden
        async function loadEntryConditionsForCountry(countryName, iso2Code) {
            console.log('loadEntryConditionsForCountry called with:', countryName, iso2Code);

            // Alle bisherigen Reiseziele entfernen
            window.selectedDestinations.clear();

            // Neues Land als Reiseziel auswählen
            window.selectedDestinations.set(iso2Code, {
                name: countryName,
                code: iso2Code
            });
            renderSelectedDestinations();

            // Nationalitäten holen (sollte Deutschland sein)
            const nationalityCodes = Array.from(window.selectedNationalities.keys());
            const destinationCodes = [iso2Code];

            console.log('Loading content for nationalities:', nationalityCodes, 'and destination:', destinationCodes);

            // Länder auf der Karte hervorheben
            highlightCountriesOnMap(destinationCodes);

            // Content API aufrufen
            await loadEntryConditionsContent(nationalityCodes, destinationCodes);
        }

        // Details anzeigen (HTML Content direkt von API)
        function displayEntryConditionsContent(countryName, htmlContent) {
            const content = document.getElementById('entry-conditions-content');
            if (!content) return;

            content.innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-800">${countryName}</h3>
                        <button onclick="hideDetailsSidebar()" class="text-gray-500 hover:text-gray-700">
                            <i class="fa-regular fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="entry-conditions-html-content">
                        ${htmlContent}
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                        <p class="text-sm text-yellow-800">
                            <i class="fa-regular fa-info-circle mr-2"></i>
                            Diese Informationen dienen nur als Orientierung. Bitte überprüfen Sie die aktuellen Einreisebestimmungen beim Auswärtigen Amt oder der Botschaft des Ziellandes.
                        </p>
                    </div>
                </div>
            `;
        }

        // Details anzeigen (Legacy - strukturierte Daten)
        function displayEntryConditionsDetails(countryName, details) {
            const content = document.getElementById('entry-conditions-content');
            if (!content) return;

            content.innerHTML = `
                <div class="space-y-4">
                    <h3 class="text-xl font-bold text-gray-800">${countryName}</h3>

                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <h4 class="font-semibold text-gray-700 mb-2">Reisedokumente</h4>
                        <div class="space-y-2 text-sm">
                            ${details.passport ? '<p>✓ Reisepass: ' + details.passport + '</p>' : ''}
                            ${details.id_card ? '<p>✓ Personalausweis: ' + details.id_card + '</p>' : ''}
                            ${details.visa ? '<p>• Visum: ' + details.visa + '</p>' : ''}
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">Diese Informationen dienen nur als Orientierung. Bitte überprüfen Sie die aktuellen Einreisebestimmungen beim Auswärtigen Amt oder der Botschaft des Ziellandes.</p>
                    </div>
                </div>
            `;
        }

        // Details-Sidebar ausblenden
        function hideDetailsSidebar() {
            const sidebar = document.getElementById('details-sidebar');
            if (sidebar) {
                sidebar.style.display = 'none';

                // Karte neu zeichnen
                setTimeout(() => {
                    if (window.entryConditionsMap) {
                        window.entryConditionsMap.invalidateSize();
                    }
                }, 300);
            }
        }

        // PDF Download Funktion
        function downloadPDF() {
            // Hole die aktuell ausgewählten Nationalitäten und Reiseziele
            const nationalityCodes = Array.from(window.selectedNationalities.keys());
            const destinationCodes = Array.from(window.selectedDestinations.keys());

            if (nationalityCodes.length === 0 || destinationCodes.length === 0) {
                alert('Bitte wählen Sie zuerst eine Nationalität und ein Reiseziel aus.');
                return;
            }

            // Build URL für unsere API (die als Proxy fungiert)
            const queryParams = new URLSearchParams({
                lang: 'de',
                countries: destinationCodes.join(','),
                nat: nationalityCodes.join(',')
            });

            const pdfUrl = `/api/entry-conditions/pdf?${queryParams.toString()}`;

            console.log('PDF Download requested:', pdfUrl);

            // Öffne PDF in neuem Tab
            window.open(pdfUrl, '_blank');
        }
    </script>
</body>
</html>
