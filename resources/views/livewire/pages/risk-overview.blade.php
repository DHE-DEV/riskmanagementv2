@php
$active = 'risk-overview';
$version = '1.0.0';
@endphp
<!DOCTYPE html>
<html lang="de">

<head>
    <!-- Version: {{ $version }} - {{ now()->format('Y-m-d H:i:s') }} -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Risiko-Ubersicht - Global Travel Monitor</title>

    <!-- Alpine.js with Collapse plugin -->
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

    <!-- Font Awesome -->
    @php($faKit = config('services.fontawesome.kit'))
    @if(!empty($faKit))
    <script src="https://kit.fontawesome.com/{{ e($faKit) }}.js" crossorigin="anonymous" onload="window.__faKitOk=true"
        onerror="window.__faKitOk=false"></script>
    <script>
        (function () {
            function addCss(href) {
                var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = href; document.head.appendChild(l);
            }
            var fallbackHref = '{{ file_exists(public_path('vendor/fontawesome/css / all.min.css')) ? asset('vendor / fontawesome / css / all.min.css') : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css' }}';
            window.addEventListener('DOMContentLoaded', function () {
                setTimeout(function () { if (!window.__faKitOk) { addCss(fallbackHref); } }, 800);
            });
        })();
    </script>
    @elseif (file_exists(public_path('vendor/fontawesome/css/all.min.css')))
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" />
    @else
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
    @endif

    <link rel="stylesheet" href="{{ asset('css/risk-overview.css') }}?v={{ $version }}" />
</head>

<body>
    <div class="app-container" x-data="riskOverviewApp()" @open-event-modal.window="openEventModal($event.detail)"
        @open-traveler-modal.window="openTravelerModal($event.detail)">
        <!-- Header -->
        <x-public-header />

        <!-- Main Content -->
        <div class="main-content">
            <!-- Navigation -->
            <x-public-navigation :active="$active" />

            <!-- Sidebar -->
            <div class="sidebar"
                :class="{ 'sidebar-expanded': (showTripFilters && sidebarTab === 'reisen') || (showCountryFilters && sidebarTab === 'laender') }">
                <div class="sidebar-inner">
                    <!-- Main Sidebar Content -->
                    <div class="sidebar-main">
                        <div class="p-4">
                            <h2 class="text-sm font-bold text-gray-900 mb-3">
                                <i class="fa-regular fa-shield-exclamation mr-2"></i>
                                TravelAlert
                            </h2>

                            <!-- Sidebar Tabs -->
                            <div class="flex border-b border-gray-200 mb-4">
                                <button
                                    @click="sidebarTab = 'reisen'; showCountryFilters = false; if (!tripsLoaded) loadTrips();"
                                    class="flex-1 px-3 py-2 text-xs font-medium border-b-2 transition-colors"
                                    :class="sidebarTab === 'reisen' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                                    <i class="fa-regular fa-suitcase-rolling mr-1"></i>
                                    Reisen
                                </button>
                                <button @click="sidebarTab = 'laender'; showTripFilters = false"
                                    class="flex-1 px-3 py-2 text-xs font-medium border-b-2 transition-colors"
                                    :class="sidebarTab === 'laender' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                                    <i class="fa-regular fa-globe mr-1"></i>
                                    Länder
                                </button>
                            </div>

                            <!-- ==================== Tab: Reisen ==================== -->
                            <div x-show="sidebarTab === 'reisen'" x-cloak>
                                <!-- Filter Toggle Button -->
                                <button @click="showTripFilters = !showTripFilters"
                                    class="w-full mb-3 px-3 py-2 text-xs rounded-lg border transition-colors flex items-center gap-2"
                                    :class="showTripFilters ? 'bg-white border-blue-500 text-blue-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                    <span class="flex items-center shrink-0">
                                        <i class="fa-regular fa-filter mr-2"></i>
                                        Filter
                                    </span>
                                    <span class="flex-1 flex flex-wrap gap-1 justify-end">
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                                            :class="filters.customDateRange ? 'bg-blue-100 text-blue-700' : (filters.days !== 30 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600')"
                                            x-text="filters.customDateRange ? (formatDate(filters.dateFrom) + (filters.dateTo ? ' – ' + formatDate(filters.dateTo) : '')) : (filters.days === -1 ? 'Alle' : filters.days === 0 ? 'Heute' : filters.days + ' Tage')"></span>
                                        <template x-if="filters.priority !== null">
                                            <x-risk-overview.priority-badge priority="filters.priority" low-color="yellow"
                                                class="px-1.5 py-0.5 rounded-full text-[10px]" />
                                        </template>
                                        <template x-if="filters.onlyWithEvents">
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-orange-100 text-orange-700">Nur
                                                betroffen</span>
                                        </template>
                                    </span>
                                    <i class="fa-regular fa-chevron-right shrink-0 transition-transform duration-200"
                                        :class="{ 'rotate-180': showTripFilters }"></i>
                                </button>

                                <!-- Loading -->
                                <template x-if="loadingTrips">
                                    <x-risk-overview.loading-spinner />
                                </template>

                                <!-- No trips -->
                                <template x-if="!loadingTrips && filteredTrips.length === 0">
                                    <x-risk-overview.empty-state icon="fa-regular fa-suitcase" title="Keine Reisen"
                                        message="Im ausgewählten Zeitraum sind keine Reisen vorhanden." />
                                </template>

                                <!-- Trips list -->
                                <template x-if="!loadingTrips && filteredTrips.length > 0">
                                    <div>
                                        <!-- Summary -->
                                        <div class="bg-white p-3 rounded-lg border border-gray-200 mb-3">
                                            <div class="grid grid-cols-3 gap-2 text-center">
                                                <div class="rounded-lg p-2 cursor-pointer transition-colors"
                                                    :class="!filters.onlyWithEvents ? 'bg-blue-50 border border-blue-500' : 'bg-gray-50 hover:bg-gray-100'"
                                                    @click="filters.onlyWithEvents = false">
                                                    <p class="text-lg font-bold"
                                                        :class="!filters.onlyWithEvents ? 'text-blue-700' : 'text-gray-900'"
                                                        x-text="filteredTripsSummary.total_trips"></p>
                                                    <p class="text-xs"
                                                        :class="!filters.onlyWithEvents ? 'text-blue-600' : 'text-gray-500'">
                                                        Reisen</p>
                                                </div>
                                                <div class="rounded-lg p-2 cursor-pointer transition-colors"
                                                    :class="filters.onlyWithEvents ? 'bg-blue-50 border border-blue-500' : 'bg-gray-50 hover:bg-gray-100'"
                                                    @click="filters.onlyWithEvents = !filters.onlyWithEvents">
                                                    <p class="text-lg font-bold"
                                                        :class="filters.onlyWithEvents ? 'text-blue-700' : 'text-orange-600'"
                                                        x-text="filteredTripsSummary.trips_with_events"></p>
                                                    <p class="text-xs"
                                                        :class="filters.onlyWithEvents ? 'text-blue-600' : 'text-gray-500'">
                                                        Betroffen</p>
                                                </div>
                                                <div class="bg-gray-50 rounded-lg p-2">
                                                    <p class="text-lg font-bold text-gray-900"
                                                        x-text="filteredTripsSummary.total_events_across_trips"></p>
                                                    <p class="text-xs text-gray-500">Ereignisse</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Trip cards -->
                                        <div class="space-y-2">
                                            <template x-for="trip in filteredTrips" :key="trip.folder_id">
                                                <div class="rounded-lg border-l-4 p-3 cursor-pointer transition-colors"
                                                    @click="selectTrip(trip)" :style="'border-left-color: ' + (trip.total_events > 0
                                            ? (trip.highest_priority === 'high' ? '#ef4444' : trip.highest_priority === 'medium' ? '#f97316' : trip.highest_priority === 'low' ? '#eab308' : '#3b82f6')
                                            : '#d1d5db')" :class="[
                                             trip.total_events === 0 ? 'opacity-60' : '',
                                             selectedTrip?.folder_id === trip.folder_id ? 'bg-blue-50 border border-blue-500 text-blue-700 font-semibold' : 'bg-white border border-gray-200 hover:bg-gray-50'
                                         ]">
                                                    <!-- Trip name -->
                                                    <div class="mb-1">
                                                        <h4 class="text-xs font-semibold text-gray-900 line-clamp-2"
                                                            x-text="trip.folder_name"></h4>
                                                    </div>

                                                    <!-- Dates & participants -->
                                                    <div
                                                        class="flex items-center gap-2 text-[11px] text-gray-500 mb-1.5">
                                                        <span>
                                                            <i class="fa-regular fa-calendar mr-0.5"></i>
                                                            <span
                                                                x-text="formatDate(trip.start_date) + ' - ' + formatDate(trip.end_date)"></span>
                                                        </span>
                                                        <span>
                                                            <i class="fa-regular fa-users mr-0.5"></i>
                                                            <span x-text="trip.participant_count"></span>
                                                        </span>
                                                        <span
                                                            :class="trip.total_events > 0 ? 'text-orange-600' : 'text-green-600'">
                                                            <i class="fa-regular fa-triangle-exclamation mr-0.5"></i>
                                                            <span x-text="trip.total_events"></span>
                                                        </span>
                                                    </div>

                                                    <!-- Progress bar -->
                                                    <div x-data="{ tp: getTripProgress(trip.start_date, trip.end_date) }"
                                                        class="mb-1.5">
                                                        <div class="flex items-center gap-1.5">
                                                            <div
                                                                class="flex-1 h-1 bg-gray-200 rounded-full overflow-hidden">
                                                                <div class="h-full rounded-full transition-all duration-300"
                                                                    :class="tp.started ? 'bg-green-500' : 'bg-gray-300'"
                                                                    :style="'width: ' + (tp.started ? tp.progress : 100) + '%'">
                                                                </div>
                                                            </div>
                                                            <span class="text-[10px] text-gray-400"
                                                                x-text="tp.status === 'upcoming' ? 'Geplant' : tp.status === 'active' ? tp.progress + '%' : 'Beendet'"></span>
                                                        </div>
                                                    </div>

                                                    <!-- Destinations -->
                                                    <div class="flex flex-wrap gap-1 mb-1.5">
                                                        <template x-for="dest in trip.destinations" :key="dest.code">
                                                            <button @click.stop="selectTrip(trip, dest.code)"
                                                                class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] transition-colors hover:opacity-80"
                                                                :class="{
                                                            'bg-red-100 text-red-700': getTripCountryPriority(trip, dest.code) === 'high',
                                                            'bg-orange-100 text-orange-700': getTripCountryPriority(trip, dest.code) === 'medium',
                                                            'bg-green-100 text-green-700': getTripCountryPriority(trip, dest.code) === 'low',
                                                            'bg-blue-100 text-blue-700': getTripCountryPriority(trip, dest.code) === 'info',
                                                            'bg-gray-100 text-gray-600': !getTripCountryPriority(trip, dest.code)
                                                        }" x-text="dest.name"></button>
                                                        </template>
                                                    </div>

                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- ==================== Tab: Länder ==================== -->
                            <div x-show="sidebarTab === 'laender'">

                                <!-- Filter Toggle Button -->
                                <button @click="showCountryFilters = !showCountryFilters"
                                    class="w-full mb-3 px-3 py-2 text-xs rounded-lg border transition-colors flex items-center gap-2"
                                    :class="showCountryFilters ? 'bg-white border-blue-500 text-blue-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                    <span class="flex items-center shrink-0">
                                        <i class="fa-regular fa-filter mr-2"></i>
                                        Filter
                                    </span>
                                    <span class="flex-1 flex flex-wrap gap-1 justify-end">
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                                            :class="filters.customDateRange ? 'bg-blue-100 text-blue-700' : (filters.days !== 30 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600')"
                                            x-text="filters.customDateRange ? (formatDate(filters.dateFrom) + (filters.dateTo ? ' – ' + formatDate(filters.dateTo) : '')) : (filters.days === -1 ? 'Alle' : filters.days === 0 ? 'Heute' : filters.days + ' Tage')"></span>
                                        <template x-if="filters.priority !== null">
                                            <x-risk-overview.priority-badge priority="filters.priority" low-color="yellow"
                                                class="px-1.5 py-0.5 rounded-full text-[10px]" />
                                        </template>
                                        <template x-if="filters.onlyWithTravelers">
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700">Nur
                                                mit Reisen</span>
                                        </template>
                                        <template x-if="filters.country">
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700"
                                                x-text="filters.country"></span>
                                        </template>
                                    </span>
                                    <i class="fa-regular fa-chevron-right shrink-0 transition-transform duration-200"
                                        :class="{ 'rotate-180': showCountryFilters }"></i>
                                </button>

                                <!-- Summary Stats -->
                                <div class="bg-white p-4 rounded-lg border border-gray-200 mb-4">
                                    <div class="grid grid-cols-3 gap-3 text-center">
                                        <div class="bg-gray-50 rounded-lg p-2">
                                            <p class="text-lg font-bold text-gray-900"
                                                x-text="filteredSummary.total_countries"></p>
                                            <p class="text-xs text-gray-500">Länder</p>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-2">
                                            <p class="text-lg font-bold text-gray-900"
                                                x-text="filteredSummary.total_events"></p>
                                            <p class="text-xs text-gray-500">Ereignisse</p>
                                        </div>
                                        <div class="rounded-lg p-2 cursor-pointer transition-colors"
                                            :class="filters.onlyWithTravelers ? 'bg-blue-50 border border-blue-500' : 'bg-gray-50 hover:bg-gray-100'"
                                            @click="filters.onlyWithTravelers = !filters.onlyWithTravelers; loadData()">
                                            <p class="text-lg font-bold"
                                                :class="filters.onlyWithTravelers ? 'text-blue-700' : 'text-gray-900'"
                                                x-text="filteredSummary.total_affected_travelers"></p>
                                            <p class="text-xs"
                                                :class="filters.onlyWithTravelers ? 'text-blue-600' : 'text-gray-500'">
                                                Betroffene Reisen</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loading State -->
                                <template x-if="loading">
                                    <x-risk-overview.loading-spinner />
                                </template>

                                <!-- Error State -->
                                <template x-if="error && !loading">
                                    <div class="bg-red-50 border border-red-200 p-4 rounded-lg mb-4">
                                        <div class="flex items-start">
                                            <i class="fa-regular fa-circle-exclamation text-red-500 mt-0.5 mr-3"></i>
                                            <div>
                                                <h3 class="font-semibold text-red-800">Fehler</h3>
                                                <p class="text-sm text-red-700 mt-1" x-text="error"></p>
                                                <button @click="loadData()"
                                                    class="inline-block mt-2 text-sm text-red-800 hover:text-red-900 underline">
                                                    Erneut versuchen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Empty State -->
                                <template x-if="!loading && !error && filteredCountries.length === 0">
                                    <x-risk-overview.empty-state>
                                        <template x-if="filters.onlyWithTravelers && countries.length > 0">
                                            <div>
                                                <i
                                                    class="fa-regular fa-filter-circle-xmark text-4xl text-blue-500 mb-3"></i>
                                                <h3 class="font-semibold text-gray-700">Keine betroffenen Reisen</h3>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    Im ausgewählten Zeitraum sind keine Reisen in Ländern mit
                                                    Ereignissen.
                                                </p>
                                            </div>
                                        </template>
                                        <template x-if="!filters.onlyWithTravelers || countries.length === 0">
                                            <div>
                                                <i class="fa-regular fa-shield-check text-4xl text-green-500 mb-3"></i>
                                                <h3 class="font-semibold text-gray-700">Keine Ereignisse</h3>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    Aktuell gibt es keine aktiven Ereignisse.
                                                </p>
                                            </div>
                                        </template>
                                    </x-risk-overview.empty-state>
                                </template>

                                <!-- Countries List -->
                                <template x-if="!loading && !error && filteredCountries.length > 0">
                                    <div class="space-y-2">
                                        <template x-for="country in filteredCountries" :key="country.country.code">
                                            <div class="rounded-lg p-3 border-l-4 cursor-pointer transition-colors"
                                                :style="'border-left-color: ' + (country.highest_priority === 'high' ? '#ff0000' : country.highest_priority === 'medium' ? '#e6a50a' : country.highest_priority === 'low' ? '#0fad78' : '#0066cc')"
                                                :class="selectedCountry?.country?.code === country.country.code ? 'bg-blue-50 border border-blue-500 text-blue-700 font-semibold' : 'bg-white border border-gray-200 hover:bg-gray-50'"
                                                @click="selectCountry(country)">
                                                <div class="flex items-start space-x-2">
                                                    <!-- Priority Dot -->
                                                    <x-risk-overview.priority-badge variant="dot" priority="country.highest_priority"
                                                        class="w-2 h-2 mt-1.5 flex-shrink-0" />
                                                    <div class="flex-1 min-w-0">
                                                        <!-- Country Name & Code -->
                                                        <div class="flex items-start justify-between gap-2">
                                                            <span class="text-xs font-medium uppercase text-gray-800"
                                                                x-text="country.country.name"></span>
                                                            <span class="text-xs font-mono text-gray-400 flex-shrink-0"
                                                                x-text="country.country.code"></span>
                                                        </div>
                                                        <!-- Stats -->
                                                        <div class="flex items-center gap-3 text-xs text-gray-600 mt-1">
                                                            <span class="flex items-center gap-1">
                                                                <i class="fa-regular fa-triangle-exclamation"></i>
                                                                <span
                                                                    x-text="country.total_events + ' Ereignis' + (country.total_events !== 1 ? 'se' : '')"></span>
                                                            </span>
                                                            <template x-if="country.affected_travelers > 0">
                                                                <span
                                                                    class="flex items-center gap-1 text-blue-600 font-medium">
                                                                    <i class="fa-regular fa-users"></i>
                                                                    <span
                                                                        x-text="country.affected_travelers + (country.affected_travelers === 1 ? ' Reise' : ' Reisen')"></span>
                                                                </span>
                                                            </template>
                                                        </div>
                                                        <!-- Priority Breakdown -->
                                                        <div class="flex flex-wrap gap-1 mt-2">
                                                            <template x-if="country.events_by_priority.high > 0">
                                                                <span
                                                                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-700">
                                                                    <span
                                                                        x-text="country.events_by_priority.high"></span>
                                                                    Hoch
                                                                </span>
                                                            </template>
                                                            <template x-if="country.events_by_priority.medium > 0">
                                                                <span
                                                                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] bg-orange-100 text-orange-700">
                                                                    <span
                                                                        x-text="country.events_by_priority.medium"></span>
                                                                    Mittel
                                                                </span>
                                                            </template>
                                                            <template x-if="country.events_by_priority.low > 0">
                                                                <span
                                                                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] bg-green-100 text-green-700">
                                                                    <span
                                                                        x-text="country.events_by_priority.low"></span>
                                                                    Niedrig
                                                                </span>
                                                            </template>
                                                            <template x-if="country.events_by_priority.info > 0">
                                                                <span
                                                                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] bg-blue-100 text-blue-700">
                                                                    <span
                                                                        x-text="country.events_by_priority.info"></span>
                                                                    Information
                                                                </span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                            </div>
                            <!-- End Tab: Länder -->

                        </div>
                    </div><!-- /sidebar-main -->

                    <!-- Trip Filter Panel (right side, shown when toggled on Reisen tab) -->
                    <div class="trip-filter-panel" x-show="showTripFilters && sidebarTab === 'reisen'" x-cloak>
                        <h3 class="text-sm font-semibold text-gray-900 flex items-center mb-3">
                            <i class="fa-regular fa-filter mr-2"></i>
                            Filter
                        </h3>

                        <!-- Reset Filters -->
                        <button @click="resetFilters(); applyFilters()"
                            class="w-full mb-4 px-3 py-2 text-xs text-gray-600 hover:text-gray-800 hover:bg-gray-50 rounded-lg border border-gray-300 transition-colors">
                            <i class="fa-regular fa-rotate-left mr-1"></i>
                            Filter zurücksetzen
                        </button>

                        <!-- Priority Filter -->
                        <x-risk-overview.priority-filter-buttons callback="applyFilters()" />

                        <!-- Days Filter -->
                        <x-risk-overview.day-range-buttons callback="applyFilters()"
                            tooltip-text="Wähle einen Zeitraum aus. Der gewählte Zeitraum bestimmt die Darstellung der Reisen, die bis zu diesem Zeitraum stattfinden." />

                        <!-- Only with events filter -->
                        <div class="mb-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="filters.onlyWithEvents"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-xs font-medium text-gray-700">Nur mit Ereignissen</span>
                                <span x-data="{ showTooltip: false }" class="relative inline-flex">
                                    <button type="button" @click.stop="showTooltip = !showTooltip"
                                        class="text-gray-400 hover:text-gray-600 transition-colors">
                                        <i class="fa-regular fa-circle-info text-xs"></i>
                                    </button>
                                    <div x-show="showTooltip" x-cloak @click.outside="showTooltip = false"
                                        class="absolute left-0 top-full mt-1 z-[9999] w-56 p-2 text-[11px] text-gray-600 bg-white border border-gray-200 rounded-lg shadow-lg">
                                        Nur Reisen anzeigen, die von Ereignissen betroffen sind.
                                    </div>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Country Filter Panel (right side, shown when toggled on Länder tab) -->
                    <div class="trip-filter-panel" x-show="showCountryFilters && sidebarTab === 'laender'" x-cloak>
                        <h3 class="text-sm font-semibold text-gray-900 flex items-center mb-3">
                            <i class="fa-regular fa-filter mr-2"></i>
                            Filter
                        </h3>

                        <!-- Reset Filters -->
                        <button @click="resetFilters(); loadData()"
                            class="w-full mb-4 px-3 py-2 text-xs text-gray-600 hover:text-gray-800 hover:bg-gray-50 rounded-lg border border-gray-300 transition-colors">
                            <i class="fa-regular fa-rotate-left mr-1"></i>
                            Filter zurücksetzen
                        </button>

                        <!-- Priority Filter -->
                        <x-risk-overview.priority-filter-buttons callback="loadData()" />

                        <!-- Country Filter -->
                        <div class="mb-4">
                            <label class="text-xs font-medium text-gray-700 mb-2 block">Land</label>
                            <div class="relative">
                                <select x-model="filters.country"
                                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                    <option value="">Alle Länder</option>
                                    <template x-for="country in countries" :key="country.country.code">
                                        <option :value="country.country.code" x-text="country.country.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Days Filter -->
                        <x-risk-overview.day-range-buttons callback="loadData()" label="Zeitraum (Reisende)"
                            :show-extended-range="false"
                            tooltip-text="Wähle einen Zeitraum aus. Der gewählte Zeitraum bestimmt die Darstellung der Reisen, die bis zu diesem Zeitraum stattfinden." />

                        <!-- Only with travelers filter -->
                        <div class="mb-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="filters.onlyWithTravelers"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-xs font-medium text-gray-700">Nur mit betroffenen Reisen</span>
                            </label>
                        </div>
                    </div>

                </div><!-- /sidebar-inner -->
            </div><!-- /sidebar -->

            <!-- Content Container: Länder -->
            <div class="content-container flex flex-col flex-1 min-h-0" x-show="sidebarTab === 'laender'">
                <!-- Tab Navigation -->
                <div class="tab-navigation flex border-b border-gray-200 bg-white px-4">
                    <button @click="activeTab = 'tiles'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'tiles' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fa-regular fa-grid-2 mr-2"></i>
                        Kacheln
                    </button>
                    <button @click="activeTab = 'list'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'list' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fa-regular fa-list mr-2"></i>
                        Liste
                    </button>
                    <button @click="activeTab = 'calendar'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'calendar' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fa-regular fa-calendar-days mr-2"></i>
                        Kalender
                    </button>
                    <button @click="activeTab = 'map'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'map' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fa-regular fa-map mr-2"></i>
                        Karte
                    </button>
                    <button @click="activeTab = 'trips'; if (!tripsLoaded) loadTrips();"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'trips' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fa-regular fa-suitcase-rolling mr-2"></i>
                        Reisen
                    </button>
                    <template x-if="isDebugUser">
                        <button @click="activeTab = 'debug'"
                            class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                            :class="activeTab === 'debug' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                            <i class="fa-regular fa-bug mr-2"></i>
                            Debug
                            <span x-show="debugLogs.length > 0"
                                class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-orange-100 bg-orange-500 rounded-full"
                                x-text="debugLogs.length"></span>
                        </button>
                    </template>
                    <!-- Selected country indicator -->
                    <template
                        x-if="selectedCountry && (activeTab === 'list' || activeTab === 'tiles' || activeTab === 'calendar')">
                        <div class="ml-auto flex items-center gap-2 text-sm text-gray-600">
                            <span class="font-medium" x-text="selectedCountry.country.name"></span>
                            <button @click="selectedCountry = null; countryDetails = null"
                                class="text-gray-400 hover:text-gray-600">
                                <i class="fa-regular fa-xmark"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Tiles View -->
                <div x-show="activeTab === 'tiles'" x-cloak class="list-view flex-1 flex flex-col min-h-0">
                    <!-- No country selected -->
                    <template x-if="!selectedCountry">
                        <x-risk-overview.empty-state variant="centered" icon="fa-regular fa-hand-pointer"
                            title="Land auswählen"
                            message="Wählen Sie ein Land in der linken Sidebar aus, um Details anzuzeigen." />
                    </template>

                    <!-- Country selected - Split view -->
                    <template x-if="selectedCountry">
                        <div class="flex-1 flex flex-col min-h-0">
                            <!-- Loading -->
                            <template x-if="loadingCountryDetails">
                                <x-risk-overview.loading-spinner class="flex-1" />
                            </template>

                            <!-- Content - 50/50 Split -->
                            <template x-if="!loadingCountryDetails && countryDetails">
                                <div class="flex-1 flex flex-col min-h-0">
                                    <!-- Top: Events (50%) -->
                                    <div class="min-h-0 border-b border-gray-200 overflow-hidden flex flex-col transition-all"
                                        :class="maximizedSection === 'events' ? 'flex-1' : maximizedSection === 'travelers' ? 'flex-none h-[52px]' : 'flex-1'">
                                        <x-risk-overview.section-header
                                            icon="fa-regular fa-triangle-exclamation" icon-color="text-orange-500"
                                            title="Ereignisse" count-expression="countryDetails.events.length"
                                            maximize-section="events" />
                                        <div class="flex-1 overflow-y-auto p-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                <template x-for="event in countryDetails.events" :key="event.id">
                                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm border-l-4 cursor-pointer hover:shadow-md transition-shadow"
                                                        @click="openEventModal(event)" :class="{
                                                         'border-l-red-500': event.priority === 'high',
                                                         'border-l-orange-500': event.priority === 'medium',
                                                         'border-l-green-500': event.priority === 'low',
                                                         'border-l-blue-500': event.priority === 'info'
                                                     }">
                                                        <div class="flex items-start justify-between mb-2">
                                                            <h4 class="text-xs font-medium text-gray-800"
                                                                x-text="event.title"></h4>
                                                            <x-risk-overview.priority-badge priority="event.priority" />
                                                        <p class="text-xs text-gray-600 line-clamp-3"
                                                            x-text="event.description"></p>
                                                        <div
                                                            class="flex items-center justify-between mt-3 text-xs text-gray-500">
                                                            <div class="flex items-center gap-2">
                                                                <span x-text="event.event_type"></span>
                                                                <span>&bull;</span>
                                                                <span
                                                                    x-text="formatDate(event.start_date) + (event.end_date ? ' - ' + formatDate(event.end_date) : '')"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <template x-if="countryDetails.events.length === 0">
                                                <div class="text-center py-8 text-gray-500">
                                                    <i
                                                        class="fa-regular fa-check-circle text-3xl text-green-500 mb-2"></i>
                                                    <p>Keine Ereignisse in diesem Land</p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Bottom: Travelers (50%) -->
                                    <div class="min-h-0 overflow-hidden flex flex-col transition-all"
                                        :class="maximizedSection === 'travelers' ? 'flex-1' : maximizedSection === 'events' ? 'flex-none h-[52px]' : 'flex-1'">
                                        <x-risk-overview.section-header
                                            icon="fa-regular fa-users" icon-color="text-blue-500"
                                            title="Betroffene Reisen" count-expression="countryDetails.travelers.length"
                                            maximize-section="travelers"
                                            bg-color="bg-blue-50" border-color="border-blue-200" hover-color="hover:bg-blue-200" />
                                        <div class="flex-1 overflow-y-auto p-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                <template x-for="traveler in countryDetails.travelers"
                                                    :key="traveler.folder_id">
                                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm cursor-pointer hover:shadow-md hover:border-blue-300 transition-all"
                                                        @click="openTravelerModal(traveler)">
                                                        <div class="flex items-start justify-between">
                                                            <h4 class="text-xs font-medium text-gray-800"
                                                                x-text="traveler.folder_name"></h4>
                                                        </div>
                                                        <!-- Trip Progress Bar -->
                                                        <div class="mt-2"
                                                            x-data="{ tripProgress: getTripProgress(traveler.start_date, traveler.end_date) }">
                                                            <div
                                                                class="flex justify-between text-xs text-gray-600 mb-1">
                                                                <span x-text="formatDate(traveler.start_date)"></span>
                                                                <span x-text="formatDate(traveler.end_date)"></span>
                                                            </div>
                                                            <div class="flex items-center"
                                                                :class="tripProgress.started ? 'gap-2' : ''">
                                                                <div
                                                                    class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                    <div class="h-full rounded-full transition-all duration-300"
                                                                        :class="tripProgress.started ? 'bg-green-500' : 'bg-gray-300'"
                                                                        :style="'width: ' + (tripProgress.started ? tripProgress.progress : 100) + '%'">
                                                                    </div>
                                                                </div>
                                                                <span x-show="tripProgress.started"
                                                                    class="text-xs text-gray-500 w-10 text-right"
                                                                    x-text="tripProgress.progress + '%'"></span>
                                                            </div>
                                                            <p class="text-xs mt-1" :class="{
                                                               'text-gray-400': tripProgress.status === 'upcoming',
                                                               'text-green-600': tripProgress.status === 'active',
                                                               'text-gray-500': tripProgress.status === 'completed'
                                                           }"
                                                                x-text="tripProgress.status === 'upcoming' ? 'Noch nicht gestartet' : tripProgress.status === 'active' ? 'Reise aktiv' : 'Abgeschlossen'">
                                                            </p>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <template x-if="countryDetails.travelers.length === 0">
                                                <div class="text-center py-8 text-gray-500">
                                                    <i class="fa-regular fa-suitcase text-3xl text-gray-400 mb-2"></i>
                                                    <p>Keine Reisenden in diesem Land im ausgewählten Zeitraum</p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Calendar View -->
                <div x-show="activeTab === 'calendar'" x-cloak class="list-view flex-1 flex flex-col min-h-0">
                    <!-- No country selected -->
                    <template x-if="!selectedCountry">
                        <x-risk-overview.empty-state variant="centered" icon="fa-regular fa-hand-pointer"
                            title="Land auswählen"
                            message="Wählen Sie ein Land in der linken Sidebar aus, um den Kalender anzuzeigen." />
                    </template>

                    <!-- Country selected - Calendar view -->
                    <template x-if="selectedCountry">
                        <div class="flex-1 flex flex-col min-h-0">
                            <!-- Loading -->
                            <template x-if="loadingCountryDetails">
                                <x-risk-overview.loading-spinner class="flex-1" />
                            </template>

                            <!-- Calendar Content -->
                            <template x-if="!loadingCountryDetails && countryDetails">
                                <div class="flex-1 flex flex-col min-h-0 overflow-hidden" x-data="{
                                     currentMonth: new Date().getMonth(),
                                     currentYear: new Date().getFullYear(),
                                     get monthYearLabel() {
                                         const months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
                                         return months[this.currentMonth] + ' ' + this.currentYear;
                                     },
                                     prevMonth() {
                                         if (this.currentMonth === 0) {
                                             this.currentMonth = 11;
                                             this.currentYear--;
                                         } else {
                                             this.currentMonth--;
                                         }
                                     },
                                     nextMonth() {
                                         if (this.currentMonth === 11) {
                                             this.currentMonth = 0;
                                             this.currentYear++;
                                         } else {
                                             this.currentMonth++;
                                         }
                                     },
                                     goToToday() {
                                         const today = new Date();
                                         this.currentMonth = today.getMonth();
                                         this.currentYear = today.getFullYear();
                                     },
                                     get calendarDays() {
                                         const year = this.currentYear;
                                         const month = this.currentMonth;
                                         const firstDay = new Date(year, month, 1);
                                         const lastDay = new Date(year, month + 1, 0);
                                         const startDate = new Date(firstDay);
                                         const dayOfWeek = startDate.getDay();
                                         const daysToSubtract = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
                                         startDate.setDate(startDate.getDate() - daysToSubtract);
                                         const endDate = new Date(lastDay);
                                         const lastDayOfWeek = endDate.getDay();
                                         const daysToAdd = lastDayOfWeek === 0 ? 0 : 7 - lastDayOfWeek;
                                         endDate.setDate(endDate.getDate() + daysToAdd);
                                         const days = [];
                                         const today = new Date();
                                         today.setHours(0, 0, 0, 0);
                                         const current = new Date(startDate);
                                         while (current <= endDate) {
                                             const dateStr = current.toISOString().split('T')[0];
                                             days.push({
                                                 date: dateStr,
                                                 dayNumber: current.getDate(),
                                                 isCurrentMonth: current.getMonth() === month,
                                                 isToday: current.getTime() === today.getTime()
                                             });
                                             current.setDate(current.getDate() + 1);
                                         }
                                         return days;
                                     },
                                     get events() { return countryDetails?.events || [] },
                                     get travelers() { return countryDetails?.travelers || [] },
                                     getTravelersForDay(dateStr, travelers) {
                                         if (!travelers || !Array.isArray(travelers)) return [];
                                         return travelers.filter(t => {
                                             if (!t.start_date || !t.end_date) return false;
                                             const start = t.start_date.split('T')[0];
                                             const end = t.end_date.split('T')[0];
                                             return dateStr >= start && dateStr <= end;
                                         });
                                     },
                                     getTravelerCountForDay(dateStr, travelers) {
                                         if (!travelers || !Array.isArray(travelers)) return 0;
                                         let count = 0;
                                         travelers.forEach(t => {
                                             if (!t.start_date || !t.end_date) return;
                                             const start = t.start_date.split('T')[0];
                                             const end = t.end_date.split('T')[0];
                                             if (dateStr >= start && dateStr <= end) {
                                                 count += (t.participant_count || 1);
                                             }
                                         });
                                         return count;
                                     },
                                     getEventsForDay(dateStr, events) {
                                         if (!events) return [];
                                         const priorityOrder = { high: 0, medium: 1, low: 2, info: 3 };
                                         return events.filter(e => {
                                             const start = e.start_date?.split('T')[0];
                                             const end = e.end_date?.split('T')[0];
                                             if (!end) {
                                                 const startDate = new Date(start);
                                                 const checkDate = new Date(dateStr);
                                                 const daysDiff = Math.floor((checkDate - startDate) / (1000 * 60 * 60 * 24));
                                                 return dateStr >= start && daysDiff <= 30;
                                             }
                                             return dateStr >= start && dateStr <= end;
                                         }).sort((a, b) => priorityOrder[a.priority] - priorityOrder[b.priority]);
                                     },
                                     isDateInFilterRange(dateStr) {
                                         const checkDate = new Date(dateStr);
                                         checkDate.setHours(0, 0, 0, 0);
                                         const today = new Date();
                                         today.setHours(0, 0, 0, 0);

                                         if (filters.customDateRange && filters.dateFrom) {
                                             const fromDate = new Date(filters.dateFrom);
                                             fromDate.setHours(0, 0, 0, 0);
                                             const toDate = filters.dateTo ? new Date(filters.dateTo) : new Date(fromDate.getTime() + 30 * 24 * 60 * 60 * 1000);
                                             toDate.setHours(23, 59, 59, 999);
                                             return checkDate >= fromDate && checkDate <= toDate;
                                         } else {
                                             const endDate = new Date(today.getTime() + filters.days * 24 * 60 * 60 * 1000);
                                             return checkDate >= today && checkDate <= endDate;
                                         }
                                     }
                                 }">
                                    <!-- Calendar Header -->
                                    <div
                                        class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex-shrink-0 flex items-center justify-between">
                                        <div class="flex items-center gap-1">
                                            <button @click="prevMonth()"
                                                class="p-2 bg-white border border-gray-300 hover:bg-gray-100 rounded-lg transition-colors"
                                                title="Vorheriger Monat">
                                                <i class="fa-regular fa-chevron-left text-gray-700"></i>
                                            </button>
                                            <h3 class="text-sm font-bold text-gray-900 min-w-[160px] text-center px-3"
                                                x-text="monthYearLabel"></h3>
                                            <button @click="nextMonth()"
                                                class="p-2 bg-white border border-gray-300 hover:bg-gray-100 rounded-lg transition-colors"
                                                title="Nächster Monat">
                                                <i class="fa-regular fa-chevron-right text-gray-700"></i>
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <button @click="goToToday()"
                                                class="px-3 py-1.5 text-xs bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium">
                                                Heute
                                            </button>
                                            <div class="flex items-center gap-3 text-xs text-gray-500">
                                                <span><span x-text="events.length"></span> Ereignisse</span>
                                                <span><span x-text="travelers.length"></span> Reisen</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Calendar Grid -->
                                    <div class="flex-1 overflow-y-auto p-4">
                                        <!-- Weekday Headers -->
                                        <div class="grid grid-cols-7 gap-1 mb-2">
                                            <template x-for="day in ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']"
                                                :key="day">
                                                <div class="text-center text-xs font-medium text-gray-500 py-2"
                                                    x-text="day"></div>
                                            </template>
                                        </div>

                                        <!-- Calendar Days -->
                                        <div class="grid grid-cols-7 gap-1">
                                            <template x-for="day in calendarDays" :key="day.date">
                                                <div class="min-h-[120px] border rounded-lg p-1.5 transition-colors"
                                                    :class="{
                                                     'bg-white border-gray-200': day.isCurrentMonth,
                                                     'bg-gray-50 border-gray-100': !day.isCurrentMonth,
                                                     'ring-2 ring-blue-500 ring-inset': day.isToday
                                                 }">
                                                    <!-- Day Header with Number and Traveler Count -->
                                                    <div class="flex items-center justify-between mb-1.5">
                                                        <span class="text-xs font-medium px-1.5 py-0.5 rounded" :class="{
                                                              'bg-blue-500 text-white': day.isToday,
                                                              'text-gray-900': day.isCurrentMonth && !day.isToday,
                                                              'text-gray-400': !day.isCurrentMonth
                                                          }" x-text="day.dayNumber"></span>
                                                        <!-- Traveler count badges (only for days in filter range) -->
                                                        <template
                                                            x-if="isDateInFilterRange(day.date) && getTravelersForDay(day.date, travelers).length > 0">
                                                            <div class="flex items-center gap-1">
                                                                <span
                                                                    class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-700"
                                                                    title="Reisen">
                                                                    <i class="fa-regular fa-suitcase"></i>
                                                                    <span
                                                                        x-text="getTravelersForDay(day.date, travelers).length"></span>
                                                                </span>
                                                                <span
                                                                    class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-700"
                                                                    title="Reisende">
                                                                    <i class="fa-regular fa-users"></i>
                                                                    <span
                                                                        x-text="getTravelerCountForDay(day.date, travelers)"></span>
                                                                </span>
                                                            </div>
                                                        </template>
                                                    </div>

                                                    <!-- Events grouped by priority -->
                                                    <div class="space-y-1 max-h-[85px] overflow-y-auto">
                                                        <!-- High priority events -->
                                                        <template
                                                            x-for="event in getEventsForDay(day.date, events).filter(e => e.priority === 'high')"
                                                            :key="'high-' + event.id">
                                                            <div class="flex items-center gap-1 px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-800 cursor-pointer hover:bg-red-200 transition-colors truncate"
                                                                @click="$dispatch('open-event-modal', event)"
                                                                :title="event.title">
                                                                <span
                                                                    class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                                                <span class="truncate"
                                                                    x-text="event.title.substring(0, 20) + (event.title.length > 20 ? '...' : '')"></span>
                                                            </div>
                                                        </template>
                                                        <!-- Medium priority events -->
                                                        <template
                                                            x-for="event in getEventsForDay(day.date, events).filter(e => e.priority === 'medium')"
                                                            :key="'medium-' + event.id">
                                                            <div class="flex items-center gap-1 px-1.5 py-0.5 rounded text-xs bg-orange-100 text-orange-800 cursor-pointer hover:bg-orange-200 transition-colors truncate"
                                                                @click="$dispatch('open-event-modal', event)"
                                                                :title="event.title">
                                                                <span
                                                                    class="w-1.5 h-1.5 rounded-full bg-orange-500 flex-shrink-0"></span>
                                                                <span class="truncate"
                                                                    x-text="event.title.substring(0, 20) + (event.title.length > 20 ? '...' : '')"></span>
                                                            </div>
                                                        </template>
                                                        <!-- Low priority events -->
                                                        <template
                                                            x-for="event in getEventsForDay(day.date, events).filter(e => e.priority === 'low')"
                                                            :key="'low-' + event.id">
                                                            <div class="flex items-center gap-1 px-1.5 py-0.5 rounded text-xs bg-green-100 text-green-800 cursor-pointer hover:bg-green-200 transition-colors truncate"
                                                                @click="$dispatch('open-event-modal', event)"
                                                                :title="event.title">
                                                                <span
                                                                    class="w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0"></span>
                                                                <span class="truncate"
                                                                    x-text="event.title.substring(0, 20) + (event.title.length > 20 ? '...' : '')"></span>
                                                            </div>
                                                        </template>
                                                        <!-- Info priority events -->
                                                        <template
                                                            x-for="event in getEventsForDay(day.date, events).filter(e => e.priority === 'info')"
                                                            :key="'info-' + event.id">
                                                            <div class="flex items-center gap-1 px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-800 cursor-pointer hover:bg-blue-200 transition-colors truncate"
                                                                @click="$dispatch('open-event-modal', event)"
                                                                :title="event.title">
                                                                <span
                                                                    class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                                                <span class="truncate"
                                                                    x-text="event.title.substring(0, 20) + (event.title.length > 20 ? '...' : '')"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Legend -->
                                        <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-gray-600">
                                            <span class="font-medium text-gray-700">Schweregrad:</span>
                                            <div class="flex items-center gap-1">
                                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                                <span>Hoch</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                                                <span>Mittel</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                                <span>Niedrig</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                                <span>Information</span>
                                            </div>
                                            <div class="flex items-center gap-1 ml-4 pl-4 border-l border-gray-300">
                                                <span
                                                    class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded bg-green-100 text-green-700">
                                                    <i class="fa-regular fa-suitcase text-[10px]"></i>
                                                    <span>n</span>
                                                </span>
                                                <span>Reisen</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span
                                                    class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded bg-blue-100 text-blue-700">
                                                    <i class="fa-regular fa-users text-[10px]"></i>
                                                    <span>n</span>
                                                </span>
                                                <span>Reisende</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Trips View -->
                <div x-show="activeTab === 'trips'" x-cloak class="list-view flex-1 flex flex-col min-h-0">
                    <!-- Loading -->
                    <template x-if="loadingTrips">
                        <x-risk-overview.loading-spinner class="flex-1" />
                    </template>

                    <!-- No trips -->
                    <template x-if="!loadingTrips && trips.length === 0">
                        <x-risk-overview.empty-state variant="centered" icon="fa-regular fa-suitcase"
                            title="Keine Reisen gefunden"
                            message="Im ausgewählten Zeitraum sind keine Reisen vorhanden." />
                    </template>

                    <!-- Trips list -->
                    <template x-if="!loadingTrips && trips.length > 0">
                        <div class="flex-1 flex flex-col min-h-0">
                            <!-- Summary bar -->
                            <div class="px-4 py-3 bg-white border-b border-gray-200 flex-shrink-0">
                                <div class="flex items-center gap-4 text-sm text-gray-600">
                                    <span>
                                        <i class="fa-regular fa-suitcase-rolling mr-1"></i>
                                        <span x-text="tripsSummary.total_trips"></span> Reisen
                                    </span>
                                    <span>
                                        <i class="fa-regular fa-triangle-exclamation mr-1 text-orange-500"></i>
                                        <span x-text="tripsSummary.trips_with_events"></span> mit Ereignissen
                                    </span>
                                    <span>
                                        <i class="fa-regular fa-bell mr-1"></i>
                                        <span x-text="tripsSummary.total_events_across_trips"></span> Ereignisse gesamt
                                    </span>
                                </div>
                            </div>

                            <!-- Trip cards -->
                            <div class="flex-1 overflow-y-auto p-4">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <template x-for="trip in trips" :key="trip.folder_id">
                                        <div class="bg-white rounded-lg border shadow-sm overflow-hidden" :class="trip.total_events > 0
                                            ? (trip.highest_priority === 'high' ? 'border-red-300' : trip.highest_priority === 'medium' ? 'border-orange-300' : trip.highest_priority === 'low' ? 'border-yellow-300' : 'border-blue-300')
                                            : 'border-gray-200 opacity-60'">
                                            <!-- Trip header -->
                                            <div class="p-4 border-b border-gray-100">
                                                <div class="flex items-start justify-between mb-2">
                                                    <h4 class="text-sm font-semibold text-gray-900"
                                                        x-text="trip.folder_name"></h4>
                                                    <div class="flex items-center gap-2 flex-shrink-0">
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                            :class="trip.source === 'api' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'"
                                                            x-text="trip.source_label"></span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 text-xs text-gray-500 mb-2">
                                                    <span>
                                                        <i class="fa-regular fa-calendar mr-1"></i>
                                                        <span
                                                            x-text="formatDate(trip.start_date) + ' - ' + formatDate(trip.end_date)"></span>
                                                    </span>
                                                    <span>
                                                        <i class="fa-regular fa-users mr-1"></i>
                                                        <span x-text="trip.participant_count"></span> Teilnehmer
                                                    </span>
                                                </div>

                                                <!-- Progress bar -->
                                                <div
                                                    x-data="{ tripProgress: getTripProgress(trip.start_date, trip.end_date) }">
                                                    <div class="flex items-center"
                                                        :class="tripProgress.started ? 'gap-2' : ''">
                                                        <div
                                                            class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                                            <div class="h-full rounded-full transition-all duration-300"
                                                                :class="tripProgress.started ? 'bg-green-500' : 'bg-gray-300'"
                                                                :style="'width: ' + (tripProgress.started ? tripProgress.progress : 100) + '%'">
                                                            </div>
                                                        </div>
                                                        <span class="text-xs text-gray-400 w-auto"
                                                            x-text="tripProgress.status === 'upcoming' ? 'Geplant' : tripProgress.status === 'active' ? tripProgress.progress + '%' : 'Beendet'"></span>
                                                    </div>
                                                </div>

                                                <!-- Destination badges -->
                                                <div class="flex flex-wrap gap-1 mt-2">
                                                    <template x-for="dest in trip.destinations" :key="dest.code">
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700"
                                                            x-text="dest.name"></span>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Events section -->
                                            <div class="p-4" x-show="trip.total_events > 0">
                                                <h5 class="text-xs font-medium text-gray-500 mb-2">
                                                    <i class="fa-regular fa-triangle-exclamation mr-1"></i>
                                                    <span x-text="trip.total_events"></span> Ereignis<span
                                                        x-show="trip.total_events !== 1">se</span>
                                                </h5>
                                                <div class="space-y-2">
                                                    <template x-for="event in trip.events" :key="event.id">
                                                        <div class="p-2 rounded border-l-4 cursor-pointer hover:bg-gray-50 transition-colors"
                                                            @click="openEventModal(event)" :class="{
                                                             'border-l-red-500 bg-red-50': event.priority === 'high',
                                                             'border-l-orange-500 bg-orange-50': event.priority === 'medium',
                                                             'border-l-yellow-500 bg-yellow-50': event.priority === 'low',
                                                             'border-l-blue-500 bg-blue-50': event.priority === 'info'
                                                         }">
                                                            <div class="flex items-start justify-between">
                                                                <div class="flex-1 min-w-0">
                                                                    <p class="text-xs font-medium text-gray-800 truncate"
                                                                        x-text="event.title"></p>
                                                                    <div
                                                                        class="flex items-center gap-1 mt-0.5 flex-wrap">
                                                                        <template x-for="mc in event.matched_countries"
                                                                            :key="mc.code">
                                                                            <span
                                                                                class="text-xs text-gray-500 bg-white px-1.5 py-0.5 rounded"
                                                                                x-text="mc.name"></span>
                                                                        </template>
                                                                        <span class="text-xs text-gray-400"
                                                                            x-text="'• ' + (event.event_type || '')"></span>
                                                                    </div>
                                                                </div>
                                                                <x-risk-overview.priority-badge priority="event.priority" low-color="yellow"
                                                                    class="px-1.5 flex-shrink-0 ml-2" />
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- No events -->
                                            <div class="px-4 py-3" x-show="trip.total_events === 0">
                                                <p class="text-xs text-gray-400 flex items-center">
                                                    <i class="fa-regular fa-check-circle mr-1 text-green-400"></i>
                                                    Keine Ereignisse
                                                </p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Map View -->
                <div x-show="activeTab === 'map'" class="map-container flex-1 relative">
                    <div id="risk-map"></div>

                    <!-- Country Details Sidebar (only in Map view) -->
                    <div class="country-sidebar" :class="{ 'open': showCountrySidebar && activeTab === 'map' }">
                        <div class="country-sidebar-header">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900"
                                    x-text="countryDetails?.country?.name || 'Land'"></h3>
                                <p class="text-sm text-gray-500"
                                    x-text="countryDetails?.summary?.total_events + ' Ereignisse, ' + countryDetails?.summary?.total_travelers + (countryDetails?.summary?.total_travelers === 1 ? ' Reise' : ' Reisen')">
                                </p>
                            </div>
                            <button @click="closeCountrySidebar()"
                                class="w-8 h-8 flex items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors">
                                <i class="fa-regular fa-xmark"></i>
                            </button>
                        </div>
                        <div class="country-sidebar-content">
                            <!-- Loading -->
                            <template x-if="loadingCountryDetails">
                                <x-risk-overview.loading-spinner />
                            </template>

                            <!-- Content -->
                            <template x-if="!loadingCountryDetails && countryDetails">
                                <div>
                                    <!-- Events Section -->
                                    <div class="mb-6">
                                        <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                            <i class="fa-regular fa-triangle-exclamation mr-2 text-orange-500"></i>
                                            Aktive Ereignisse
                                        </h4>
                                        <div class="space-y-3">
                                            <template x-for="event in countryDetails.events" :key="event.id">
                                                <div class="bg-gray-50 p-3 rounded-lg border-l-4 cursor-pointer hover:bg-gray-100 transition-colors"
                                                    @click="openEventModal(event)" :class="{
                                                     'border-red-500': event.priority === 'high',
                                                     'border-orange-500': event.priority === 'medium',
                                                     'border-green-500': event.priority === 'low',
                                                     'border-blue-500': event.priority === 'info'
                                                 }">
                                                    <h5 class="text-xs font-medium text-gray-800" x-text="event.title">
                                                    </h5>
                                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2"
                                                        x-text="event.description"></p>
                                                    <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                                        <span x-text="event.event_type"></span>
                                                        <span
                                                            x-text="formatDate(event.start_date) + (event.end_date ? ' - ' + formatDate(event.end_date) : '')"></span>
                                                    </div>
                                                    <template x-if="event.source_url">
                                                        <a :href="event.source_url" target="_blank"
                                                            class="inline-flex items-center gap-1 mt-2 text-xs text-blue-600 hover:text-blue-800">
                                                            <i class="fa-regular fa-external-link"></i>
                                                            Quelle
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="countryDetails.events.length === 0">
                                                <p class="text-sm text-gray-500 text-center py-4">Keine Ereignisse
                                                    gefunden</p>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Travelers Section -->
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                            <i class="fa-regular fa-users mr-2 text-blue-500"></i>
                                            Betroffene Reisende
                                        </h4>
                                        <div class="space-y-3">
                                            <template x-for="traveler in countryDetails.travelers"
                                                :key="traveler.folder_id">
                                                <div class="p-3 rounded-lg cursor-pointer hover:ring-2 hover:ring-blue-300 transition-all"
                                                    :class="traveler.source === 'api' ? 'bg-purple-50' : 'bg-blue-50'"
                                                    @click="openTravelerModal(traveler)">
                                                    <div class="flex items-center justify-between">
                                                        <h5 class="text-xs font-medium text-gray-800"
                                                            x-text="traveler.folder_name"></h5>
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-xs"
                                                                :class="traveler.source === 'api' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                                                x-text="traveler.source === 'api' ? 'API' : 'GTM'"></span>
                                                            <span class="text-xs text-gray-500">
                                                                <i class="fa-regular fa-users mr-1"></i>
                                                                <span x-text="traveler.participant_count"></span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <!-- Trip Progress Bar -->
                                                    <div class="mt-2"
                                                        x-data="{ tripProgress: getTripProgress(traveler.start_date, traveler.end_date) }">
                                                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                            <span x-text="formatDate(traveler.start_date)"></span>
                                                            <span x-text="formatDate(traveler.end_date)"></span>
                                                        </div>
                                                        <div class="flex items-center"
                                                            :class="tripProgress.started ? 'gap-2' : ''">
                                                            <div
                                                                class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                                                <div class="h-full rounded-full transition-all duration-300"
                                                                    :class="tripProgress.started ? 'bg-green-500' : 'bg-gray-300'"
                                                                    :style="'width: ' + (tripProgress.started ? tripProgress.progress : 100) + '%'">
                                                                </div>
                                                            </div>
                                                            <span x-show="tripProgress.started"
                                                                class="text-xs text-gray-500 w-8 text-right"
                                                                x-text="tripProgress.progress + '%'"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="countryDetails.travelers.length === 0">
                                                <p class="text-sm text-gray-500 text-center py-4">Keine Reisenden in
                                                    diesem Land</p>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- List View -->
                <div x-show="activeTab === 'list'" x-cloak class="list-view flex-1 flex flex-col min-h-0">
                    <!-- No country selected -->
                    <template x-if="!selectedCountry">
                        <x-risk-overview.empty-state variant="centered" icon="fa-regular fa-hand-pointer"
                            title="Land auswählen"
                            message="Wählen Sie ein Land in der linken Sidebar aus, um Details anzuzeigen." />
                    </template>

                    <!-- Country selected - Split view -->
                    <template x-if="selectedCountry">
                        <div class="flex-1 flex flex-col min-h-0">
                            <!-- Loading -->
                            <template x-if="loadingCountryDetails">
                                <x-risk-overview.loading-spinner class="flex-1" />
                            </template>

                            <!-- Content - 50/50 Split -->
                            <template x-if="!loadingCountryDetails && countryDetails">
                                <div class="flex-1 flex flex-col min-h-0">
                                    <!-- Top: Events (50%) -->
                                    <div class="min-h-0 border-b border-gray-200 overflow-hidden flex flex-col transition-all"
                                        :class="maximizedSection === 'events' ? 'flex-1' : maximizedSection === 'travelers' ? 'flex-none h-[52px]' : 'flex-1'">
                                        <x-risk-overview.section-header
                                            icon="fa-regular fa-triangle-exclamation" icon-color="text-orange-500"
                                            title="Ereignisse" count-expression="countryDetails.events.length"
                                            maximize-section="events" />
                                        <div class="flex-1 overflow-y-auto">
                                            <div class="divide-y divide-gray-200">
                                                <template x-for="event in countryDetails.events" :key="event.id">
                                                    <div class="px-4 py-3 bg-white hover:bg-gray-50 cursor-pointer transition-colors flex items-center gap-4 border-l-4"
                                                        @click="openEventModal(event)" :class="{
                                                         'border-l-red-500': event.priority === 'high',
                                                         'border-l-orange-500': event.priority === 'medium',
                                                         'border-l-green-500': event.priority === 'low',
                                                         'border-l-blue-500': event.priority === 'info'
                                                     }">
                                                        <div class="flex-1 min-w-0">
                                                            <h4 class="text-xs font-medium text-gray-800 truncate"
                                                                x-text="event.title"></h4>
                                                            <p class="text-xs text-gray-500 mt-0.5"
                                                                x-text="event.event_type + ' • ' + formatDate(event.start_date) + (event.end_date ? ' - ' + formatDate(event.end_date) : '')">
                                                            </p>
                                                        </div>
                                                        <x-risk-overview.priority-badge priority="event.priority"
                                                            class="flex-shrink-0" />
                                                    </div>
                                                </template>
                                            </div>
                                            <template x-if="countryDetails.events.length === 0">
                                                <div class="text-center py-8 text-gray-500">
                                                    <i
                                                        class="fa-regular fa-check-circle text-3xl text-green-500 mb-2"></i>
                                                    <p>Keine Ereignisse in diesem Land</p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Bottom: Travelers (50%) -->
                                    <div class="min-h-0 overflow-hidden flex flex-col transition-all"
                                        :class="maximizedSection === 'travelers' ? 'flex-1' : maximizedSection === 'events' ? 'flex-none h-[52px]' : 'flex-1'">
                                        <x-risk-overview.section-header
                                            icon="fa-regular fa-users" icon-color="text-blue-500"
                                            title="Betroffene Reisen" count-expression="countryDetails.travelers.length"
                                            maximize-section="travelers"
                                            bg-color="bg-blue-50" border-color="border-blue-200" hover-color="hover:bg-blue-200" />
                                        <div class="flex-1 overflow-y-auto">
                                            <div class="divide-y divide-gray-200">
                                                <template x-for="traveler in countryDetails.travelers"
                                                    :key="traveler.folder_id">
                                                    <div class="px-4 py-3 bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                                                        :class="traveler.source === 'api' ? 'border-l-4 border-l-purple-400' : ''"
                                                        @click="openTravelerModal(traveler)"
                                                        x-data="{ tripProgress: getTripProgress(traveler.start_date, traveler.end_date) }">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center gap-2">
                                                                <h4 class="text-xs font-medium text-gray-800"
                                                                    x-text="traveler.folder_name"></h4>
                                                                <span
                                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs"
                                                                    :class="traveler.source === 'api' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600'"
                                                                    x-text="traveler.source === 'api' ? 'API' : 'GTM'"></span>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2 flex items-center gap-4">
                                                            <div class="flex-1">
                                                                <div
                                                                    class="flex justify-between text-xs text-gray-600 mb-1">
                                                                    <span
                                                                        x-text="formatDate(traveler.start_date)"></span>
                                                                    <span x-text="formatDate(traveler.end_date)"></span>
                                                                </div>
                                                                <div class="flex items-center"
                                                                    :class="tripProgress.started ? 'gap-2' : ''">
                                                                    <div
                                                                        class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                                                        <div class="h-full rounded-full transition-all duration-300"
                                                                            :class="tripProgress.started ? 'bg-green-500' : 'bg-gray-300'"
                                                                            :style="'width: ' + (tripProgress.started ? tripProgress.progress : 100) + '%'">
                                                                        </div>
                                                                    </div>
                                                                    <span x-show="tripProgress.started"
                                                                        class="text-xs text-gray-500 w-8 text-right"
                                                                        x-text="tripProgress.progress + '%'"></span>
                                                                </div>
                                                            </div>
                                                            <span class="flex-shrink-0 text-xs px-2 py-0.5 rounded"
                                                                :class="{
                                                                  'bg-gray-100 text-gray-500': tripProgress.status === 'upcoming',
                                                                  'bg-green-100 text-green-700': tripProgress.status === 'active',
                                                                  'bg-gray-100 text-gray-500': tripProgress.status === 'completed'
                                                              }"
                                                                x-text="tripProgress.status === 'upcoming' ? 'Geplant' : tripProgress.status === 'active' ? 'Aktiv' : 'Beendet'"></span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <template x-if="countryDetails.travelers.length === 0">
                                                <div class="text-center py-8 text-gray-500">
                                                    <i class="fa-regular fa-suitcase text-3xl text-gray-400 mb-2"></i>
                                                    <p>Keine Reisenden in diesem Land im ausgewahlten Zeitraum</p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Debug Tab (Länder) -->
                <div x-show="activeTab === 'debug' && isDebugUser" x-cloak
                    class="list-view flex-1 flex flex-col min-h-0">
                    <div class="flex-1 overflow-y-auto p-4 bg-gray-950">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-gray-300">
                                <i class="fa-regular fa-bug mr-2 text-orange-400"></i>
                                API Debug Log
                                <span class="ml-2 text-xs text-gray-500" x-text="debugLogs.length + ' Einträge'"></span>
                            </h3>
                            <button @click="clearDebugLogs()" x-show="debugLogs.length > 0"
                                class="px-3 py-1 text-xs font-medium text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 rounded transition-colors">
                                <i class="fa-regular fa-trash-can mr-1"></i>
                                Alle löschen
                            </button>
                        </div>

                        <!-- Empty state -->
                        <template x-if="debugLogs.length === 0">
                            <div class="text-center py-12">
                                <i class="fa-regular fa-bug text-4xl text-gray-700 mb-3"></i>
                                <p class="text-gray-500 text-sm">Noch keine API-Requests aufgezeichnet.</p>
                                <p class="text-gray-600 text-xs mt-1">Laden Sie Daten oder wählen Sie ein Land aus.</p>
                            </div>
                        </template>

                        <!-- Log entries -->
                        <div class="space-y-3">
                            <template x-for="log in debugLogs" :key="log.id">
                                <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
                                    <!-- Log header -->
                                    <div class="flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-gray-800/50 transition-colors"
                                        @click="log.expanded = !log.expanded">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="text-xs text-gray-500 font-mono whitespace-nowrap"
                                                x-text="log.timestamp"></span>
                                            <span
                                                class="px-2 py-0.5 text-xs font-semibold rounded bg-blue-900/50 text-blue-300 whitespace-nowrap"
                                                x-text="log.endpoint"></span>
                                            <span class="text-xs text-gray-500 truncate"
                                                x-text="JSON.stringify(log.params)"></span>
                                        </div>
                                        <div class="flex items-center gap-3 flex-shrink-0 ml-3">
                                            <template x-if="log.pds_api_calls && log.pds_api_calls.length > 0">
                                                <span
                                                    class="px-1.5 py-0.5 text-xs font-semibold rounded bg-purple-900/60 text-purple-300 whitespace-nowrap"
                                                    x-text="'PDS:' + log.pds_api_calls.length"></span>
                                            </template>
                                            <span class="text-xs font-mono whitespace-nowrap"
                                                :class="log.duration_ms > 1000 ? 'text-red-400' : log.duration_ms > 500 ? 'text-yellow-400' : 'text-green-400'"
                                                x-text="log.duration_ms + 'ms'"></span>
                                            <template x-if="log.server_duration_ms">
                                                <span class="text-xs font-mono text-gray-500 whitespace-nowrap"
                                                    x-text="'(Server: ' + log.server_duration_ms + 'ms)'"></span>
                                            </template>
                                            <i class="fa-regular fa-chevron-down text-gray-500 text-xs transition-transform"
                                                :class="log.expanded ? 'rotate-180' : ''"></i>
                                        </div>
                                    </div>

                                    <!-- Expandable content -->
                                    <div x-show="log.expanded" x-collapse>
                                        <div class="border-t border-gray-800">
                                            <!-- Request -->
                                            <div class="px-4 py-3 border-b border-gray-800">
                                                <div class="flex items-center justify-between mb-2">
                                                    <div class="text-xs font-semibold text-gray-400">Request</div>
                                                    <button
                                                        @click.stop="navigator.clipboard.writeText(JSON.stringify({ url: log.fullUrl, params: log.params }, null, 2)); $el.querySelector('span').textContent = 'Kopiert!'; setTimeout(() => $el.querySelector('span').textContent = 'Kopieren', 1500)"
                                                        class="flex items-center gap-1 text-xs text-gray-500 hover:text-green-400 transition-colors">
                                                        <i class="fa-regular fa-copy"></i>
                                                        <span>Kopieren</span>
                                                    </button>
                                                </div>
                                                <template x-if="log.fullUrl">
                                                    <pre class="text-xs font-mono text-yellow-300 bg-gray-950 rounded p-3 mb-2 overflow-x-auto whitespace-pre-wrap break-all"
                                                        x-text="log.fullUrl"></pre>
                                                </template>
                                                <pre class="text-xs font-mono text-green-400 bg-gray-950 rounded p-3 overflow-x-auto whitespace-pre-wrap"
                                                    x-text="JSON.stringify(log.params, null, 2)"></pre>
                                            </div>
                                            <!-- Response -->
                                            <div class="px-4 py-3 border-b border-gray-800">
                                                <div class="flex items-center justify-between mb-2">
                                                    <div class="text-xs font-semibold text-gray-400">Response</div>
                                                    <button
                                                        @click.stop="navigator.clipboard.writeText(JSON.stringify(log.response, null, 2)); $el.querySelector('span').textContent = 'Kopiert!'; setTimeout(() => $el.querySelector('span').textContent = 'Kopieren', 1500)"
                                                        class="flex items-center gap-1 text-xs text-gray-500 hover:text-blue-400 transition-colors">
                                                        <i class="fa-regular fa-copy"></i>
                                                        <span>Kopieren</span>
                                                    </button>
                                                </div>
                                                <pre class="text-xs font-mono text-green-400 bg-gray-950 rounded p-3 overflow-x-auto whitespace-pre-wrap max-h-96 overflow-y-auto"
                                                    x-text="JSON.stringify(log.response, null, 2)"></pre>
                                            </div>
                                            <!-- PDS API Calls -->
                                            <template x-if="log.pds_api_calls && log.pds_api_calls.length > 0">
                                                <div class="px-4 py-3">
                                                    <div class="text-xs font-semibold text-purple-400 mb-2">
                                                        <i class="fa-regular fa-server mr-1"></i>
                                                        PDS API Calls <span class="text-gray-500 font-normal"
                                                            x-text="'(' + log.pds_api_calls.length + ')'"></span>
                                                    </div>
                                                    <template x-for="(pds, pdsIdx) in log.pds_api_calls" :key="pdsIdx">
                                                        <div
                                                            class="mb-3 bg-gray-950 border border-purple-900/50 rounded-lg p-3">
                                                            <div class="flex items-center justify-between mb-2">
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="px-1.5 py-0.5 text-xs font-bold rounded bg-purple-900/60 text-purple-300"
                                                                        x-text="pds.method"></span>
                                                                    <span
                                                                        class="text-xs font-mono text-purple-200 break-all"
                                                                        x-text="pds.url"></span>
                                                                </div>
                                                                <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                                                    <span class="text-xs font-mono"
                                                                        :class="pds.status === 200 ? 'text-green-400' : 'text-red-400'"
                                                                        x-text="pds.status || 'ERR'"></span>
                                                                    <span class="text-xs font-mono text-gray-500"
                                                                        x-text="pds.duration_ms + 'ms'"></span>
                                                                </div>
                                                            </div>
                                                            <div class="mb-2">
                                                                <div class="flex items-center justify-between mb-1">
                                                                    <div class="text-xs text-gray-500">Request Body
                                                                    </div>
                                                                    <button
                                                                        @click.stop="navigator.clipboard.writeText(JSON.stringify(pds.request_body, null, 2)); $el.querySelector('span').textContent = 'Kopiert!'; setTimeout(() => $el.querySelector('span').textContent = 'Kopieren', 1500)"
                                                                        class="flex items-center gap-1 text-xs text-gray-600 hover:text-purple-400 transition-colors">
                                                                        <i class="fa-regular fa-copy"></i>
                                                                        <span>Kopieren</span>
                                                                    </button>
                                                                </div>
                                                                <pre class="text-xs font-mono text-yellow-300 bg-gray-900 rounded p-2 overflow-x-auto whitespace-pre-wrap max-h-40 overflow-y-auto"
                                                                    x-text="JSON.stringify(pds.request_body, null, 2)"></pre>
                                                            </div>
                                                            <div>
                                                                <div class="flex items-center justify-between mb-1">
                                                                    <div class="text-xs text-gray-500">Response Body
                                                                    </div>
                                                                    <button
                                                                        @click.stop="navigator.clipboard.writeText(JSON.stringify(pds.response_body, null, 2)); $el.querySelector('span').textContent = 'Kopiert!'; setTimeout(() => $el.querySelector('span').textContent = 'Kopieren', 1500)"
                                                                        class="flex items-center gap-1 text-xs text-gray-600 hover:text-purple-400 transition-colors">
                                                                        <i class="fa-regular fa-copy"></i>
                                                                        <span>Kopieren</span>
                                                                    </button>
                                                                </div>
                                                                <pre class="text-xs font-mono text-blue-300 bg-gray-900 rounded p-2 overflow-x-auto whitespace-pre-wrap max-h-60 overflow-y-auto"
                                                                    x-text="JSON.stringify(pds.response_body, null, 2)"></pre>
                                                            </div>
                                                            <template x-if="pds.error">
                                                                <div class="mt-2 text-xs text-red-400 font-mono"
                                                                    x-text="'Error: ' + pds.error"></div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Container: Reisen -->
            <div class="content-container flex flex-col flex-1 min-h-0" x-show="sidebarTab === 'reisen'" x-cloak>
                <!-- Tab Navigation -->
                <div class="tab-navigation flex border-b border-gray-200 bg-white px-4">
                    <button @click="tripActiveTab = 'tiles'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="tripActiveTab === 'tiles' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fa-regular fa-grid-2 mr-2"></i>
                        Kacheln
                    </button>
                    <button @click="tripActiveTab = 'list'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="tripActiveTab === 'list' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fa-regular fa-list mr-2"></i>
                        Liste
                    </button>
                    <button @click="tripActiveTab = 'calendar'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="tripActiveTab === 'calendar' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fa-regular fa-calendar-days mr-2"></i>
                        Kalender
                    </button>
                    <!-- Selected trip indicator -->
                    <template x-if="selectedTrip">
                        <div class="ml-auto flex items-center gap-2 text-sm text-gray-600">
                            <span class="font-medium" x-text="selectedTrip.folder_name"></span>
                            <button
                                @click="selectedTrip = null; selectedTripCountry = null; tripMaximizedSection = null"
                                class="text-gray-400 hover:text-gray-600">
                                <i class="fa-regular fa-xmark"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- No trip selected -->
                <template x-if="!selectedTrip">
                    <x-risk-overview.empty-state variant="centered" icon="fa-regular fa-hand-pointer"
                        title="Reise auswählen"
                        message="Wählen Sie eine Reise in der linken Sidebar aus, um Details anzuzeigen." />
                </template>

                <!-- Trip selected -->
                <template x-if="selectedTrip">
                    <div class="flex-1 flex flex-col min-h-0">

                        <!-- ===== Tiles View ===== -->
                        <div x-show="tripActiveTab === 'tiles'" class="flex-1 flex flex-col min-h-0">
                            <!-- Top: Reisedetails -->
                            <div class="min-h-0 border-b border-gray-200 overflow-hidden flex flex-col transition-all"
                                :class="tripMaximizedSection === 'tripDetails' ? 'flex-1' : tripMaximizedSection === 'tripEvents' ? 'flex-none h-[52px]' : 'flex-1'">
                                <x-risk-overview.section-header
                                    icon="fa-regular fa-suitcase-rolling" icon-color="text-blue-500"
                                    title="Reisedetails" maximize-section="tripDetails"
                                    maximize-var="tripMaximizedSection" toggle-method="toggleTripMaximize"
                                    bg-color="bg-blue-50" border-color="border-blue-200" hover-color="hover:bg-blue-200">
                                    <span class="text-xs font-normal text-gray-500 italic" x-text="selectedTrip.folder_name"></span>
                                </x-risk-overview.section-header>
                                <div class="flex-1 overflow-y-auto p-4">
                                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                                        <!-- Info Grid -->
                                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <p class="text-xs text-gray-500 mb-1">Reisebeginn</p>
                                                <p class="text-sm font-semibold text-gray-900"
                                                    x-text="formatDate(selectedTrip.start_date)"></p>
                                            </div>
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <p class="text-xs text-gray-500 mb-1">Reiseende</p>
                                                <p class="text-sm font-semibold text-gray-900"
                                                    x-text="formatDate(selectedTrip.end_date)"></p>
                                            </div>
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <p class="text-xs text-gray-500 mb-1">Teilnehmer</p>
                                                <p class="text-sm font-semibold text-gray-900"
                                                    x-text="selectedTrip.participant_count"></p>
                                            </div>
                                            <div></div>
                                        </div>

                                        <!-- Progress Bar -->
                                        <div class="mb-4"
                                            x-data="{ tp: getTripProgress(selectedTrip.start_date, selectedTrip.end_date) }">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-medium text-gray-700"
                                                    x-text="formatDate(selectedTrip.start_date)"></span>
                                                <span class="text-xs font-medium text-gray-700"
                                                    x-text="formatDate(selectedTrip.end_date)"></span>
                                            </div>
                                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-300"
                                                    :class="tp.started ? 'bg-green-500' : 'bg-gray-300'"
                                                    :style="'width: ' + (tp.started ? tp.progress : 100) + '%'"></div>
                                            </div>
                                        </div>

                                        <!-- Destinations & Nationalities -->
                                        <div class="grid grid-cols-2 gap-4">
                                            <div
                                                x-show="selectedTrip.destinations && selectedTrip.destinations.length > 0">
                                                <p class="text-xs font-medium text-gray-700 mb-2">Reiseziele</p>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <template x-for="dest in (selectedTrip.destinations || [])"
                                                        :key="dest.code">
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-gray-100 text-gray-700"
                                                            x-text="dest.name"></span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div
                                                x-show="selectedTrip.nationalities && selectedTrip.nationalities.length > 0">
                                                <p class="text-xs font-medium text-gray-700 mb-2">Nationalitäten</p>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <template x-for="nat in (selectedTrip.nationalities || [])"
                                                        :key="nat.code">
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-blue-50 text-blue-700"
                                                            x-text="nat.name"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Labels -->
                                        <div class="mt-4" x-show="selectedTrip.source !== 'api'">
                                            <p class="text-xs font-medium text-gray-700 mb-2">Labels</p>
                                            <div class="flex flex-wrap gap-1.5 mb-2">
                                                <template x-for="label in (selectedTrip.labels || [])" :key="label.id">
                                                    <span
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs text-white"
                                                        :style="'background-color: ' + label.color">
                                                        <i class="fa-regular text-[10px]" :class="label.icon"></i>
                                                        <span x-text="label.name"></span>
                                                        <button @click.stop="detachLabel(label.id)"
                                                            class="ml-0.5 hover:opacity-70">
                                                            <i class="fa-regular fa-xmark text-[10px]"></i>
                                                        </button>
                                                    </span>
                                                </template>
                                            </div>
                                            <div class="relative">
                                                <input type="text" x-model="labelInput" @input="searchLabels()"
                                                    @keydown.enter.prevent="addLabelFromInput()"
                                                    @focus="if (labelInput.trim().length > 0) showLabelSuggestions = true"
                                                    @click.away="showLabelSuggestions = false"
                                                    placeholder="Label hinzufügen..."
                                                    class="w-full text-xs border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                                <div x-show="showLabelSuggestions && (labelSuggestions.length > 0 || labelInput.trim().length > 0)"
                                                    x-cloak
                                                    class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                                    <template x-for="suggestion in labelSuggestions"
                                                        :key="suggestion.id">
                                                        <button @click="attachLabel(suggestion.id, null)"
                                                            class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                                            <span class="w-3 h-3 rounded-full flex-shrink-0"
                                                                :style="'background-color: ' + suggestion.color"></span>
                                                            <span x-text="suggestion.name"></span>
                                                        </button>
                                                    </template>
                                                    <template
                                                        x-if="labelInput.trim().length > 0 && !labelSuggestions.some(s => s.name.toLowerCase() === labelInput.trim().toLowerCase())">
                                                        <button @click="addLabelFromInput()"
                                                            class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2 border-t border-gray-100 text-blue-600">
                                                            <i class="fa-regular fa-plus text-[10px]"></i>
                                                            <span>"<span x-text="labelInput.trim()"></span>"
                                                                erstellen</span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom: Ereignisse -->
                            <div class="min-h-0 overflow-hidden flex flex-col transition-all"
                                :class="tripMaximizedSection === 'tripEvents' ? 'flex-1' : tripMaximizedSection === 'tripDetails' ? 'flex-none h-[52px]' : 'flex-1'">
                                <x-risk-overview.section-header
                                    icon="fa-regular fa-triangle-exclamation" icon-color="text-orange-500"
                                    title="Ereignisse" count-expression="filteredTripEvents.length"
                                    maximize-section="tripEvents"
                                    maximize-var="tripMaximizedSection" toggle-method="toggleTripMaximize">
                                    <template x-if="selectedTripCountry">
                                        <span
                                            class="flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full"
                                            :class="{
                                            'bg-red-100 text-red-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'high',
                                            'bg-orange-100 text-orange-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'medium',
                                            'bg-green-100 text-green-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'low',
                                            'bg-blue-100 text-blue-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'info',
                                            'bg-gray-100 text-gray-600': !getTripCountryPriority(selectedTrip, selectedTripCountry)
                                        }">
                                            <span
                                                x-text="selectedTrip.destinations?.find(d => d.code === selectedTripCountry)?.name || selectedTripCountry"></span>
                                            <button @click="selectedTripCountry = null"
                                                class="hover:opacity-70">
                                                <i class="fa-regular fa-xmark text-[10px]"></i>
                                            </button>
                                        </span>
                                    </template>
                                </x-risk-overview.section-header>
                                <div class="flex-1 overflow-y-auto p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <template x-for="event in filteredTripEvents" :key="event.id">
                                            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm border-l-4 cursor-pointer hover:shadow-md transition-shadow"
                                                @click="openEventModal(event)" :class="{
                                                 'border-l-red-500': event.priority === 'high',
                                                 'border-l-orange-500': event.priority === 'medium',
                                                 'border-l-green-500': event.priority === 'low',
                                                 'border-l-blue-500': event.priority === 'info'
                                             }">
                                                <div class="flex items-start justify-between mb-2">
                                                    <h4 class="text-xs font-medium text-gray-800" x-text="event.title">
                                                    </h4>
                                                    <x-risk-overview.priority-badge priority="event.priority"
                                                        class="flex-shrink-0 ml-2" />
                                                </div>
                                                <p class="text-xs text-gray-600 line-clamp-3"
                                                    x-text="event.description"></p>
                                                <div class="flex items-center gap-2 mt-3 text-xs text-gray-500">
                                                    <span x-text="event.event_type"></span>
                                                    <span>&bull;</span>
                                                    <span
                                                        x-text="formatDate(event.start_date) + (event.end_date ? ' - ' + formatDate(event.end_date) : '')"></span>
                                                </div>
                                                <div class="flex flex-wrap gap-1 mt-2">
                                                    <template x-for="mc in (event.matched_countries || [])"
                                                        :key="mc.code">
                                                        <span
                                                            class="text-[10px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded"
                                                            x-text="mc.name"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <template x-if="filteredTripEvents.length === 0">
                                        <div class="text-center py-8 text-gray-500">
                                            <i class="fa-regular fa-check-circle text-3xl text-green-500 mb-2"></i>
                                            <p>Keine Ereignisse für diese Reise</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- ===== List View ===== -->
                        <div x-show="tripActiveTab === 'list'" x-cloak class="flex-1 flex flex-col min-h-0">
                            <!-- Top: Reisedetails -->
                            <div class="min-h-0 border-b border-gray-200 overflow-hidden flex flex-col transition-all"
                                :class="tripMaximizedSection === 'tripDetails' ? 'flex-1' : tripMaximizedSection === 'tripEvents' ? 'flex-none h-[52px]' : 'flex-1'">
                                <x-risk-overview.section-header
                                    icon="fa-regular fa-suitcase-rolling" icon-color="text-blue-500"
                                    title="Reisedetails" maximize-section="tripDetails"
                                    maximize-var="tripMaximizedSection" toggle-method="toggleTripMaximize"
                                    bg-color="bg-blue-50" border-color="border-blue-200" hover-color="hover:bg-blue-200">
                                    <span class="text-xs font-normal text-gray-500 italic" x-text="selectedTrip.folder_name"></span>
                                </x-risk-overview.section-header>
                                <div class="flex-1 overflow-y-auto">
                                    <div class="divide-y divide-gray-200">
                                        <!-- Trip info row -->
                                        <div class="px-4 py-3 bg-white">
                                            <div class="flex items-center gap-4">
                                                <div class="flex-1"
                                                    x-data="{ tp: getTripProgress(selectedTrip.start_date, selectedTrip.end_date) }">
                                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                        <span x-text="formatDate(selectedTrip.start_date)"></span>
                                                        <span x-text="formatDate(selectedTrip.end_date)"></span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <div
                                                            class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                                            <div class="h-full rounded-full transition-all duration-300"
                                                                :class="tp.started ? 'bg-green-500' : 'bg-gray-300'"
                                                                :style="'width: ' + (tp.started ? tp.progress : 100) + '%'">
                                                            </div>
                                                        </div>
                                                        <span class="text-xs text-gray-500 w-auto"
                                                            x-text="tp.status === 'upcoming' ? 'Geplant' : tp.status === 'active' ? tp.progress + '%' : 'Beendet'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                                <span><i class="fa-regular fa-users mr-1"></i> <span
                                                        x-text="selectedTrip.participant_count"></span>
                                                    Teilnehmer</span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4 mt-2">
                                                <div
                                                    x-show="selectedTrip.destinations && selectedTrip.destinations.length > 0">
                                                    <p class="text-[10px] font-medium text-gray-500 mb-1">Reiseziele</p>
                                                    <div class="flex flex-wrap gap-1">
                                                        <template x-for="dest in (selectedTrip.destinations || [])"
                                                            :key="dest.code">
                                                            <span
                                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700"
                                                                x-text="dest.name"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                                <div
                                                    x-show="selectedTrip.nationalities && selectedTrip.nationalities.length > 0">
                                                    <p class="text-[10px] font-medium text-gray-500 mb-1">Nationalitäten
                                                    </p>
                                                    <div class="flex flex-wrap gap-1">
                                                        <template x-for="nat in (selectedTrip.nationalities || [])"
                                                            :key="nat.code">
                                                            <span
                                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-50 text-blue-700"
                                                                x-text="nat.name"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Labels -->
                                            <div class="mt-3" x-show="selectedTrip.source !== 'api'">
                                                <p class="text-[10px] font-medium text-gray-500 mb-1">Labels</p>
                                                <div class="flex flex-wrap gap-1 mb-1.5">
                                                    <template x-for="label in (selectedTrip.labels || [])"
                                                        :key="label.id">
                                                        <span
                                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] text-white"
                                                            :style="'background-color: ' + label.color">
                                                            <i class="fa-regular text-[9px]" :class="label.icon"></i>
                                                            <span x-text="label.name"></span>
                                                            <button @click.stop="detachLabel(label.id)"
                                                                class="ml-0.5 hover:opacity-70">
                                                                <i class="fa-regular fa-xmark text-[9px]"></i>
                                                            </button>
                                                        </span>
                                                    </template>
                                                </div>
                                                <div class="relative">
                                                    <input type="text" x-model="labelInput" @input="searchLabels()"
                                                        @keydown.enter.prevent="addLabelFromInput()"
                                                        @focus="if (labelInput.trim().length > 0) showLabelSuggestions = true"
                                                        @click.away="showLabelSuggestions = false"
                                                        placeholder="Label hinzufügen..."
                                                        class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                                    <div x-show="showLabelSuggestions && (labelSuggestions.length > 0 || labelInput.trim().length > 0)"
                                                        x-cloak
                                                        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                                        <template x-for="suggestion in labelSuggestions"
                                                            :key="suggestion.id">
                                                            <button @click="attachLabel(suggestion.id, null)"
                                                                class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                                                <span class="w-3 h-3 rounded-full flex-shrink-0"
                                                                    :style="'background-color: ' + suggestion.color"></span>
                                                                <span x-text="suggestion.name"></span>
                                                            </button>
                                                        </template>
                                                        <template
                                                            x-if="labelInput.trim().length > 0 && !labelSuggestions.some(s => s.name.toLowerCase() === labelInput.trim().toLowerCase())">
                                                            <button @click="addLabelFromInput()"
                                                                class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2 border-t border-gray-100 text-blue-600">
                                                                <i class="fa-regular fa-plus text-[10px]"></i>
                                                                <span>"<span x-text="labelInput.trim()"></span>"
                                                                    erstellen</span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom: Ereignisse -->
                            <div class="min-h-0 overflow-hidden flex flex-col transition-all"
                                :class="tripMaximizedSection === 'tripEvents' ? 'flex-1' : tripMaximizedSection === 'tripDetails' ? 'flex-none h-[52px]' : 'flex-1'">
                                <x-risk-overview.section-header
                                    icon="fa-regular fa-triangle-exclamation" icon-color="text-orange-500"
                                    title="Ereignisse" count-expression="filteredTripEvents.length"
                                    maximize-section="tripEvents"
                                    maximize-var="tripMaximizedSection" toggle-method="toggleTripMaximize">
                                    <template x-if="selectedTripCountry">
                                        <span
                                            class="flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full"
                                            :class="{
                                            'bg-red-100 text-red-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'high',
                                            'bg-orange-100 text-orange-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'medium',
                                            'bg-green-100 text-green-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'low',
                                            'bg-blue-100 text-blue-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'info',
                                            'bg-gray-100 text-gray-600': !getTripCountryPriority(selectedTrip, selectedTripCountry)
                                        }">
                                            <span
                                                x-text="selectedTrip.destinations?.find(d => d.code === selectedTripCountry)?.name || selectedTripCountry"></span>
                                            <button @click="selectedTripCountry = null"
                                                class="hover:opacity-70">
                                                <i class="fa-regular fa-xmark text-[10px]"></i>
                                            </button>
                                        </span>
                                    </template>
                                </x-risk-overview.section-header>
                                <div class="flex-1 overflow-y-auto">
                                    <div class="divide-y divide-gray-200">
                                        <template x-for="event in filteredTripEvents" :key="event.id">
                                            <div class="px-4 py-3 bg-white hover:bg-gray-50 cursor-pointer transition-colors flex items-center gap-4 border-l-4"
                                                @click="openEventModal(event)" :class="{
                                                 'border-l-red-500': event.priority === 'high',
                                                 'border-l-orange-500': event.priority === 'medium',
                                                 'border-l-green-500': event.priority === 'low',
                                                 'border-l-blue-500': event.priority === 'info'
                                             }">
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="text-xs font-medium text-gray-800 truncate"
                                                        x-text="event.title"></h4>
                                                    <p class="text-xs text-gray-500 mt-0.5"
                                                        x-text="event.event_type + ' • ' + formatDate(event.start_date) + (event.end_date ? ' - ' + formatDate(event.end_date) : '')">
                                                    </p>
                                                    <div class="flex flex-wrap gap-1 mt-1">
                                                        <template x-for="mc in (event.matched_countries || [])"
                                                            :key="mc.code">
                                                            <span
                                                                class="text-[10px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded"
                                                                x-text="mc.name"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                                <x-risk-overview.priority-badge priority="event.priority"
                                                    class="flex-shrink-0" />
                                            </div>
                                        </template>
                                    </div>
                                    <template x-if="filteredTripEvents.length === 0">
                                        <div class="text-center py-8 text-gray-500">
                                            <i class="fa-regular fa-check-circle text-3xl text-green-500 mb-2"></i>
                                            <p>Keine Ereignisse für diese Reise</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- ===== Calendar View ===== -->
                        <div x-show="tripActiveTab === 'calendar'" x-cloak
                            class="flex-1 flex flex-col min-h-0 overflow-hidden" x-data="{
                             currentMonth: new Date().getMonth(),
                             currentYear: new Date().getFullYear(),
                             get monthYearLabel() {
                                 const months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
                                 return months[this.currentMonth] + ' ' + this.currentYear;
                             },
                             prevMonth() {
                                 if (this.currentMonth === 0) { this.currentMonth = 11; this.currentYear--; } else { this.currentMonth--; }
                             },
                             nextMonth() {
                                 if (this.currentMonth === 11) { this.currentMonth = 0; this.currentYear++; } else { this.currentMonth++; }
                             },
                             goToToday() {
                                 const today = new Date();
                                 this.currentMonth = today.getMonth();
                                 this.currentYear = today.getFullYear();
                             },
                             goToTripStart() {
                                 if (selectedTrip?.start_date) {
                                     const d = new Date(selectedTrip.start_date);
                                     this.currentMonth = d.getMonth();
                                     this.currentYear = d.getFullYear();
                                 }
                             },
                             get calendarDays() {
                                 const year = this.currentYear;
                                 const month = this.currentMonth;
                                 const firstDay = new Date(year, month, 1);
                                 const lastDay = new Date(year, month + 1, 0);
                                 const startDate = new Date(firstDay);
                                 const dayOfWeek = startDate.getDay();
                                 const daysToSubtract = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
                                 startDate.setDate(startDate.getDate() - daysToSubtract);
                                 const endDate = new Date(lastDay);
                                 const lastDayOfWeek = endDate.getDay();
                                 const daysToAdd = lastDayOfWeek === 0 ? 0 : 7 - lastDayOfWeek;
                                 endDate.setDate(endDate.getDate() + daysToAdd);
                                 const days = [];
                                 const today = new Date();
                                 today.setHours(0, 0, 0, 0);
                                 const current = new Date(startDate);
                                 while (current <= endDate) {
                                     const dateStr = current.toISOString().split('T')[0];
                                     days.push({
                                         date: dateStr,
                                         dayNumber: current.getDate(),
                                         isCurrentMonth: current.getMonth() === month,
                                         isToday: current.getTime() === today.getTime()
                                     });
                                     current.setDate(current.getDate() + 1);
                                 }
                                 return days;
                             },
                             isDateInTrip(dateStr) {
                                 if (!selectedTrip) return false;
                                 const start = selectedTrip.start_date?.split('T')[0];
                                 const end = selectedTrip.end_date?.split('T')[0];
                                 return dateStr >= start && dateStr <= end;
                             },
                             getEventsForDay(dateStr) {
                                 const events = filteredTripEvents;
                                 if (!events || events.length === 0) return [];
                                 const priorityOrder = { high: 0, medium: 1, low: 2, info: 3 };
                                 return events.filter(e => {
                                     const start = e.start_date?.split('T')[0];
                                     const end = e.end_date?.split('T')[0];
                                     if (!end) {
                                         const startDate = new Date(start);
                                         const checkDate = new Date(dateStr);
                                         const daysDiff = Math.floor((checkDate - startDate) / (1000 * 60 * 60 * 24));
                                         return dateStr >= start && daysDiff <= 30;
                                     }
                                     return dateStr >= start && dateStr <= end;
                                 }).sort((a, b) => priorityOrder[a.priority] - priorityOrder[b.priority]);
                             }
                         }">
                            <!-- Calendar Header -->
                            <div
                                class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex-shrink-0 flex items-center justify-between">
                                <div class="flex items-center gap-1">
                                    <button @click="prevMonth()"
                                        class="p-2 bg-white border border-gray-300 hover:bg-gray-100 rounded-lg transition-colors"
                                        title="Vorheriger Monat">
                                        <i class="fa-regular fa-chevron-left text-gray-700"></i>
                                    </button>
                                    <h3 class="text-sm font-bold text-gray-900 min-w-[160px] text-center px-3"
                                        x-text="monthYearLabel"></h3>
                                    <button @click="nextMonth()"
                                        class="p-2 bg-white border border-gray-300 hover:bg-gray-100 rounded-lg transition-colors"
                                        title="Nächster Monat">
                                        <i class="fa-regular fa-chevron-right text-gray-700"></i>
                                    </button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="goToToday()"
                                        class="px-3 py-1.5 text-xs bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium">
                                        Heute
                                    </button>
                                    <button @click="goToTripStart()"
                                        class="px-3 py-1.5 text-xs bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                                        Reisestart
                                    </button>
                                    <div class="flex items-center gap-3 text-xs text-gray-500 ml-2">
                                        <span><span x-text="filteredTripEvents.length"></span> Ereignisse</span>
                                    </div>
                                    <template x-if="selectedTripCountry">
                                        <span class="flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full ml-2"
                                            :class="{
                                            'bg-red-100 text-red-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'high',
                                            'bg-orange-100 text-orange-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'medium',
                                            'bg-green-100 text-green-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'low',
                                            'bg-blue-100 text-blue-700': getTripCountryPriority(selectedTrip, selectedTripCountry) === 'info',
                                            'bg-gray-100 text-gray-600': !getTripCountryPriority(selectedTrip, selectedTripCountry)
                                        }">
                                            <span
                                                x-text="selectedTrip.destinations?.find(d => d.code === selectedTripCountry)?.name || selectedTripCountry"></span>
                                            <button @click="selectedTripCountry = null" class="hover:opacity-70">
                                                <i class="fa-regular fa-xmark text-[10px]"></i>
                                            </button>
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- Calendar Grid -->
                            <div class="flex-1 overflow-y-auto p-4">
                                <!-- Weekday Headers -->
                                <div class="grid grid-cols-7 gap-1 mb-2">
                                    <template x-for="day in ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']" :key="day">
                                        <div class="text-center text-xs font-medium text-gray-500 py-2" x-text="day">
                                        </div>
                                    </template>
                                </div>

                                <!-- Calendar Days -->
                                <div class="grid grid-cols-7 gap-1">
                                    <template x-for="day in calendarDays" :key="day.date">
                                        <div class="min-h-[120px] border rounded-lg p-1.5 transition-colors" :class="{
                                             'bg-green-50 border-green-200': day.isCurrentMonth && isDateInTrip(day.date),
                                             'bg-white border-gray-200': day.isCurrentMonth && !isDateInTrip(day.date),
                                             'bg-gray-50 border-gray-100': !day.isCurrentMonth,
                                             'ring-2 ring-blue-500 ring-inset': day.isToday
                                         }">
                                            <!-- Day Number -->
                                            <div class="flex items-center justify-between mb-1.5">
                                                <span class="text-xs font-medium px-1.5 py-0.5 rounded" :class="{
                                                      'bg-blue-500 text-white': day.isToday,
                                                      'text-gray-900': day.isCurrentMonth && !day.isToday,
                                                      'text-gray-400': !day.isCurrentMonth
                                                  }" x-text="day.dayNumber"></span>
                                                <template x-if="isDateInTrip(day.date)">
                                                    <span class="text-[10px] text-green-600"><i
                                                            class="fa-regular fa-suitcase"></i></span>
                                                </template>
                                            </div>

                                            <!-- Events -->
                                            <div class="space-y-1 max-h-[85px] overflow-y-auto">
                                                <template x-for="event in getEventsForDay(day.date)"
                                                    :key="event.id + '-' + day.date">
                                                    <div class="flex items-center gap-1 px-1.5 py-0.5 rounded text-xs cursor-pointer transition-colors truncate"
                                                        @click="$dispatch('open-event-modal', event)"
                                                        :title="event.title" :class="{
                                                         'bg-red-100 text-red-800 hover:bg-red-200': event.priority === 'high',
                                                         'bg-orange-100 text-orange-800 hover:bg-orange-200': event.priority === 'medium',
                                                         'bg-green-100 text-green-800 hover:bg-green-200': event.priority === 'low',
                                                         'bg-blue-100 text-blue-800 hover:bg-blue-200': event.priority === 'info'
                                                     }">
                                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="{
                                                              'bg-red-500': event.priority === 'high',
                                                              'bg-orange-500': event.priority === 'medium',
                                                              'bg-green-500': event.priority === 'low',
                                                              'bg-blue-500': event.priority === 'info'
                                                          }"></span>
                                                        <span class="truncate"
                                                            x-text="event.title.substring(0, 20) + (event.title.length > 20 ? '...' : '')"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Legend -->
                                <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-gray-600">
                                    <span class="font-medium text-gray-700">Schweregrad:</span>
                                    <div class="flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span> <span>Hoch</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-orange-500"></span> <span>Mittel</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-green-500"></span> <span>Niedrig</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span> <span>Information</span>
                                    </div>
                                    <div class="flex items-center gap-1 ml-4 pl-4 border-l border-gray-300">
                                        <span class="w-3 h-3 rounded bg-green-50 border border-green-200"></span>
                                        <span>Reisetage</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </template>
            </div>

        </div>

        <!-- Footer -->
        <x-public-footer />

        <!-- Event Detail Modal -->
        <div x-show="showEventModal" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-[200000] overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeEventModal()"></div>

            <!-- Modal Container -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="showEventModal" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    @click.stop
                    class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-hidden">

                    <!-- Modal Header -->
                    <div
                        class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-start justify-between z-10">
                        <div class="pr-10">
                            <h3 id="modal-title" class="text-lg font-semibold text-gray-900"
                                x-text="selectedEvent?.title"></h3>
                            <div class="flex items-center gap-2 mt-1">
                                <x-risk-overview.priority-badge priority="selectedEvent?.priority" class="rounded-full px-2.5" />
                                <span class="text-sm text-gray-500" x-text="selectedEvent?.event_type"></span>
                            </div>
                        </div>
                        <!-- Close Button -->
                        <button @click="closeEventModal()"
                            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 transition-colors">
                            <i class="fa-regular fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-5 overflow-y-auto max-h-[calc(85vh-140px)]">
                        <!-- Description -->
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Beschreibung
                            </h4>
                            <div class="prose prose-sm max-w-none text-gray-700"
                                x-html="selectedEvent?.description || selectedEvent?.popup_content || '<p class=\'text-gray-400 italic\'>Keine Beschreibung verfügbar</p>'">
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <!-- Start Date -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center gap-2 text-gray-500 mb-1">
                                    <i class="fa-regular fa-calendar"></i>
                                    <span class="text-xs font-medium uppercase tracking-wider">Startdatum</span>
                                </div>
                                <p class="text-gray-900 font-medium" x-text="formatDate(selectedEvent?.start_date)"></p>
                            </div>

                            <!-- End Date -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center gap-2 text-gray-500 mb-1">
                                    <i class="fa-regular fa-calendar-check"></i>
                                    <span class="text-xs font-medium uppercase tracking-wider">Enddatum</span>
                                </div>
                                <p class="text-gray-900 font-medium"
                                    x-text="selectedEvent?.end_date ? formatDate(selectedEvent?.end_date) : 'Unbestimmt'">
                                </p>
                            </div>

                            <!-- Event Category -->
                            <template x-if="selectedEvent?.event_category">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center gap-2 text-gray-500 mb-1">
                                        <i class="fa-regular fa-folder"></i>
                                        <span class="text-xs font-medium uppercase tracking-wider">Kategorie</span>
                                    </div>
                                    <p class="text-gray-900 font-medium" x-text="selectedEvent?.event_category"></p>
                                </div>
                            </template>

                            <!-- Coordinates -->
                            <template x-if="selectedEvent?.latitude && selectedEvent?.longitude">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center gap-2 text-gray-500 mb-1">
                                        <i class="fa-regular fa-map-pin"></i>
                                        <span class="text-xs font-medium uppercase tracking-wider">Koordinaten</span>
                                    </div>
                                    <p class="text-gray-900 font-medium text-sm font-mono"
                                        x-text="parseFloat(selectedEvent?.latitude).toFixed(4) + ', ' + parseFloat(selectedEvent?.longitude).toFixed(4)">
                                    </p>
                                </div>
                            </template>

                            <!-- Radius -->
                            <template x-if="selectedEvent?.radius_km">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center gap-2 text-gray-500 mb-1">
                                        <i class="fa-regular fa-circle-dashed"></i>
                                        <span class="text-xs font-medium uppercase tracking-wider">Radius</span>
                                    </div>
                                    <p class="text-gray-900 font-medium" x-text="selectedEvent?.radius_km + ' km'"></p>
                                </div>
                            </template>

                        </div>

                        <!-- Tags -->
                        <template x-if="selectedEvent?.tags && selectedEvent?.tags.length > 0">
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Tags</h4>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="tag in selectedEvent.tags" :key="tag">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm">
                                            <i class="fa-regular fa-tag mr-1 text-xs"></i>
                                            <span x-text="tag"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex items-center justify-between">
                        <div class="text-xs text-gray-500 space-x-3">
                            <template x-if="selectedEvent?.created_at">
                                <span>Erstellt: <span x-text="formatDate(selectedEvent?.created_at)"></span></span>
                            </template>
                            <template
                                x-if="selectedEvent?.updated_at && selectedEvent?.updated_at !== selectedEvent?.created_at">
                                <span>Aktualisiert: <span x-text="formatDate(selectedEvent?.updated_at)"></span></span>
                            </template>
                        </div>
                        <button @click="closeEventModal()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 rounded-lg text-sm font-medium text-white hover:bg-gray-800 transition-colors">
                            Schließen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Traveler Detail Modal -->
        <div x-show="showTravelerModal" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-[200000] overflow-y-auto"
            aria-labelledby="traveler-modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeTravelerModal()"></div>

            <!-- Modal Container -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="showTravelerModal" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    @click.stop
                    class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-hidden">

                    <!-- Modal Header -->
                    <div
                        class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-start justify-between z-10">
                        <div class="pr-10">
                            <h3 id="traveler-modal-title" class="text-lg font-semibold text-gray-900"
                                x-text="selectedTraveler?.folder_name"></h3>
                        </div>
                        <!-- Close Button -->
                        <button @click="closeTravelerModal()"
                            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 transition-colors">
                            <i class="fa-regular fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-5 overflow-y-auto max-h-[calc(85vh-140px)]">
                        <!-- Trip Progress -->
                        <div class="mb-6"
                            x-data="{ get tripProgress() { return selectedTraveler ? getTripProgress(selectedTraveler.start_date, selectedTraveler.end_date) : { started: false, progress: 0, status: 'upcoming' }; } }">
                            <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Reisestatus</h4>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium" :class="{
                                          'text-gray-500': tripProgress.status === 'upcoming',
                                          'text-green-600': tripProgress.status === 'active',
                                          'text-gray-500': tripProgress.status === 'completed'
                                      }"
                                        x-text="tripProgress.status === 'upcoming' ? 'Noch nicht gestartet' : tripProgress.status === 'active' ? 'Reise aktiv' : 'Abgeschlossen'"></span>
                                    <span x-show="tripProgress.started" class="text-sm font-bold text-gray-700"
                                        x-text="tripProgress.progress + '%'"></span>
                                </div>
                                <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500" :class="{
                                         'bg-gray-300': !tripProgress.started,
                                         'bg-green-500': tripProgress.status === 'active',
                                         'bg-gray-400': tripProgress.status === 'completed'
                                     }"
                                        :style="'width: ' + (tripProgress.started ? tripProgress.progress : 100) + '%'">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <!-- Folder Number -->
                            <template
                                x-if="selectedTraveler?.folder_number && String(selectedTraveler.folder_number).trim() !== ''">
                                <div class="bg-gray-50 rounded-lg p-4" x-data="{ copied: false }">
                                    <div class="flex items-center gap-2 text-gray-500 mb-1">
                                        <i class="fa-regular fa-hashtag"></i>
                                        <span class="text-xs font-medium uppercase tracking-wider">Vorgangsnummer</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-gray-900 font-medium font-mono cursor-pointer hover:text-blue-600 transition-colors"
                                            @click="navigator.clipboard.writeText(selectedTraveler?.folder_number); copied = true; setTimeout(() => copied = false, 2000)"
                                            :title="copied ? 'Kopiert!' : 'Klicken zum Kopieren'"
                                            x-text="selectedTraveler?.folder_number"></p>
                                        <i class="fa-regular text-sm transition-all"
                                            :class="copied ? 'fa-check text-green-500' : 'fa-copy text-gray-400 hover:text-blue-500 cursor-pointer'"
                                            @click="navigator.clipboard.writeText(selectedTraveler?.folder_number); copied = true; setTimeout(() => copied = false, 2000)"></i>
                                    </div>
                                </div>
                            </template>

                            <!-- Endkundenlink (for API travelers) -->
                            <template x-if="selectedTraveler?.trip_id">
                                <div class="bg-gray-50 rounded-lg p-4" x-data="{ copied: false }">
                                    <div class="flex items-center gap-2 text-gray-500 mb-1">
                                        <i class="fa-regular fa-fingerprint"></i>
                                        <span class="text-xs font-medium uppercase tracking-wider">Endkundenlink</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-gray-900 font-medium font-mono text-xs truncate"
                                            x-text="'https://travel-details.eu/de?tid=' + selectedTraveler?.trip_id + '&preview'">
                                        </p>
                                        <i class="fa-regular text-sm cursor-pointer transition-all"
                                            :class="copied ? 'fa-check text-green-500' : 'fa-copy text-gray-400 hover:text-blue-500'"
                                            :title="copied ? 'Kopiert!' : 'Link kopieren'"
                                            @click="navigator.clipboard.writeText('https://travel-details.eu/de?tid=' + selectedTraveler?.trip_id + '&preview'); copied = true; setTimeout(() => copied = false, 2000)"></i>
                                        <a :href="'https://travel-details.eu/de?tid=' + selectedTraveler?.trip_id + '&preview'"
                                            target="_blank" class="text-blue-500 hover:text-blue-700 transition-colors"
                                            title="In neuem Tab öffnen">
                                            <i class="fa-regular fa-arrow-up-right-from-square text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                            </template>

                        </div>

                        <!-- Reisezeitraum -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center gap-2 text-gray-500 mb-1">
                                    <i class="fa-regular fa-plane-departure"></i>
                                    <span class="text-xs font-medium uppercase tracking-wider">Reisebeginn</span>
                                </div>
                                <p class="text-gray-900 font-medium" x-text="formatDate(selectedTraveler?.start_date)">
                                </p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center gap-2 text-gray-500 mb-1">
                                    <i class="fa-regular fa-plane-arrival"></i>
                                    <span class="text-xs font-medium uppercase tracking-wider">Reiseende</span>
                                </div>
                                <p class="text-gray-900 font-medium" x-text="formatDate(selectedTraveler?.end_date)">
                                </p>
                            </div>
                        </div>

                        <!-- Nationalities -->
                        <template x-if="selectedTraveler?.nationalities && selectedTraveler.nationalities.length > 0">
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">
                                    Nationalitäten</h4>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="nat in selectedTraveler.nationalities" :key="nat.code">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200"
                                            x-text="nat.name"></span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Destinations -->
                        <template x-if="selectedTraveler?.destinations && selectedTraveler.destinations.length > 0">
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Reiseziele
                                </h4>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="dest in selectedTraveler.destinations" :key="dest.code">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700 border border-gray-200"
                                            x-text="dest.name"></span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Participants List -->
                        <template x-if="selectedTraveler?.participants && selectedTraveler?.participants.length > 0">
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Teilnehmer
                                </h4>
                                <div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
                                    <template x-for="(participant, idx) in selectedTraveler.participants" :key="idx">
                                        <div class="p-3 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <i class="fa-regular fa-user text-blue-600 text-sm"></i>
                                                </div>
                                                <span class="text-sm font-medium text-gray-900"
                                                    x-text="participant.name || 'Unbenannt'"></span>
                                            </div>
                                            <template x-if="participant.is_main_contact">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                                    <i class="fa-regular fa-star mr-1"></i>
                                                    Hauptkontakt
                                                </span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Trip Duration Info -->
                        <div class="mb-6" x-data="{
                        get duration() {
                            if (!selectedTraveler?.start_date || !selectedTraveler?.end_date) return null;
                            const start = new Date(selectedTraveler.start_date);
                            const end = new Date(selectedTraveler.end_date);
                            const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
                            return days;
                        }
                    }">
                            <template x-if="duration">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">
                                        Reisedauer</h4>
                                    <div class="bg-blue-50 rounded-lg p-4 flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="fa-regular fa-calendar-days text-blue-600 text-xl"></i>
                                        </div>
                                        <div>
                                            <p class="text-2xl font-bold text-blue-700" x-text="duration"></p>
                                            <p class="text-sm text-blue-600" x-text="duration === 1 ? 'Tag' : 'Tage'">
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex items-center justify-end">
                        <button @click="closeTravelerModal()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 rounded-lg text-sm font-medium text-white hover:bg-gray-800 transition-colors">
                            Schließen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        window.__riskOverviewConfig = {
            isDebugUser: {{ isset($isDebugUser) && $isDebugUser ? 'true' : 'false' }},
            routes: {
                labelsSearch: '{{ route("risk-overview.labels.search") }}',
                data: '{{ route("risk-overview.data") }}',
                trips: '{{ route("risk-overview.trips") }}',
                country: '{{ url("/risk-overview/country") }}',
            }
        };
    </script>
    <script src="{{ asset('js/risk-overview.js') }}?v={{ $version }}"></script>

    <x-debug-panel :isDebugUser="$isDebugUser ?? false" />
</body>

</html>