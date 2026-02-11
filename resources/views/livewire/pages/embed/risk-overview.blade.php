@extends('layouts.embed')

@section('title', 'TravelAlert - Global Travel Monitor')
@section('hide_default_badge', true)

@push('head-scripts')
    <!-- Alpine.js Collapse Plugin (used by sidebar filters) -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
@endpush

@section('additional-styles')
    /* Alpine.js cloak */
    [x-cloak] { display: none !important; }

    body {
        margin: 0;
        padding: 0;
        height: 100vh;
        overflow: hidden;
    }

    .embed-container {
        height: 100vh;
    }

    .embed-content {
        overflow: hidden;
    }

    .app-container {
        display: flex;
        flex-direction: column;
        height: 100vh;
    }

    /* Main content - fills entire viewport (no header/footer offset) */
    .main-content {
        flex: 1;
        display: flex;
        min-height: 0;
    }

    /* Sidebar */
    .sidebar {
        flex-shrink: 0;
        width: 304px;
        background: #f9fafb;
        overflow-y: auto;
        height: 100%;
        position: relative;
        border-right: 1px solid #e5e7eb;
    }

    /* Map Container */
    .map-container {
        flex: 1;
        position: relative;
        min-height: 0;
        overflow: hidden;
    }

    #risk-map {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
    }

    /* Content Container with Tabs */
    .content-container {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
    }

    .tab-navigation {
        flex-shrink: 0;
    }

    /* List View */
    .list-view {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        background: #f9fafb;
    }

    /* Country Details Sidebar - no header/footer offset */
    .country-sidebar {
        position: fixed;
        top: 0;
        bottom: 0;
        right: -450px;
        width: 450px;
        background: white;
        box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
        transition: right 0.3s ease-in-out;
        z-index: 100000;
        display: flex;
        flex-direction: column;
    }

    .country-sidebar.open { right: 0; }

    .country-sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .country-sidebar-content {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    /* Loading Animation */
    .loading-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Custom Leaflet Marker */
    .event-marker {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        font-weight: bold;
        font-size: 12px;
        color: white;
    }

    .event-marker-high { background-color: #ef4444; }
    .event-marker-medium { background-color: #f97316; }
    .event-marker-low { background-color: #eab308; }
    .event-marker-info { background-color: #3b82f6; }

    /* Prose styling for event descriptions */
    .prose p { margin-bottom: 0.75rem; }
    .prose p:last-child { margin-bottom: 0; }
    .prose ul, .prose ol { margin: 0.75rem 0; padding-left: 1.5rem; }
    .prose li { margin-bottom: 0.25rem; }
    .prose a { color: #2563eb; text-decoration: underline; }
    .prose a:hover { color: #1d4ed8; }
    .prose strong { font-weight: 600; }
    .prose h1, .prose h2, .prose h3, .prose h4 { font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; }
    .prose blockquote { border-left: 3px solid #e5e7eb; padding-left: 1rem; margin: 0.75rem 0; font-style: italic; color: #6b7280; }
@endsection

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="app-container" x-data="riskOverviewApp()" @open-event-modal.window="openEventModal($event.detail)" @open-traveler-modal.window="openTravelerModal($event.detail)">
    <!-- Main Content (no header, no navigation) -->
    <div class="main-content">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="p-4">
                <h2 class="text-sm font-bold text-gray-900 mb-4">
                    <i class="fa-regular fa-shield-exclamation mr-2"></i>
                    TravelAlert
                </h2>

                <!-- Filter Section -->
                <div class="bg-white rounded-lg border border-gray-200 mb-4">
                    <button @click="filterOpen = !filterOpen"
                            class="w-full p-4 flex items-center justify-between text-left hover:bg-gray-50 transition-colors rounded-lg">
                        <h3 class="text-sm font-semibold text-gray-900 flex items-center">
                            <i class="fa-regular fa-filter mr-2"></i>
                            Filter
                        </h3>
                        <i class="fa-regular fa-chevron-down text-gray-500 transition-transform duration-200"
                           :class="{ 'rotate-180': filterOpen }"></i>
                    </button>

                    <div x-show="filterOpen" x-collapse class="px-4 pb-4">
                        <!-- Priority Filter -->
                        <div class="mb-4">
                            <label class="text-xs font-medium text-gray-700 mb-2 block">Risikostufe</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button @click="filters.priority = null; loadData()"
                                        class="px-3 py-2 text-xs rounded-lg border transition-colors"
                                        :class="filters.priority === null ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                    Alle
                                </button>
                                <button @click="filters.priority = 'high'; loadData()"
                                        class="px-3 py-2 text-xs rounded-lg border transition-colors flex items-center justify-center gap-1"
                                        :class="filters.priority === 'high' ? 'bg-red-50 border-red-500 text-red-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    Hoch
                                </button>
                                <button @click="filters.priority = 'medium'; loadData()"
                                        class="px-3 py-2 text-xs rounded-lg border transition-colors flex items-center justify-center gap-1"
                                        :class="filters.priority === 'medium' ? 'bg-orange-50 border-orange-500 text-orange-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                                    Mittel
                                </button>
                                <button @click="filters.priority = 'low'; loadData()"
                                        class="px-3 py-2 text-xs rounded-lg border transition-colors flex items-center justify-center gap-1"
                                        :class="filters.priority === 'low' ? 'bg-green-50 border-green-500 text-green-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                    Niedrig
                                </button>
                                <button @click="filters.priority = 'info'; loadData()"
                                        class="px-3 py-2 text-xs rounded-lg border transition-colors col-span-2 flex items-center justify-center gap-1"
                                        :class="filters.priority === 'info' ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    Information
                                </button>
                            </div>
                        </div>

                        <!-- Country Filter -->
                        <div class="mb-4">
                            <label class="text-xs font-medium text-gray-700 mb-2 block">Land</label>
                            <div class="relative">
                                <select x-model="filters.country"
                                        class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                    <option value="">Alle Lander</option>
                                    <template x-for="country in countries" :key="country.country.code">
                                        <option :value="country.country.code" x-text="country.country.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Days Filter -->
                        <div class="mb-4">
                            <label class="text-xs font-medium text-gray-700 mb-2 block">Zeitraum (Reisende)</label>
                            <div class="grid grid-cols-3 gap-2 mb-2">
                                <button @click="filters.days = 7; filters.customDateRange = false; loadData()"
                                        class="px-3 py-2 text-xs rounded-lg border transition-colors flex flex-col items-center"
                                        :class="filters.days === 7 && !filters.customDateRange ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                    <span class="text-sm font-bold">7</span>
                                    <span>Tage</span>
                                </button>
                                <button @click="filters.days = 14; filters.customDateRange = false; loadData()"
                                        class="px-3 py-2 text-xs rounded-lg border transition-colors flex flex-col items-center"
                                        :class="filters.days === 14 && !filters.customDateRange ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                    <span class="text-sm font-bold">14</span>
                                    <span>Tage</span>
                                </button>
                                <button @click="filters.days = 30; filters.customDateRange = false; loadData()"
                                        class="px-3 py-2 text-xs rounded-lg border transition-colors flex flex-col items-center"
                                        :class="filters.days === 30 && !filters.customDateRange ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                    <span class="text-sm font-bold">30</span>
                                    <span>Tage</span>
                                </button>
                            </div>
                            <!-- Custom date range toggle -->
                            <button @click="filters.customDateRange = !filters.customDateRange"
                                    class="w-full px-3 py-2 text-xs rounded-lg border transition-colors flex items-center justify-center gap-1"
                                    :class="filters.customDateRange ? 'bg-blue-50 border-blue-500 text-blue-700 font-semibold' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'">
                                <i class="fa-regular fa-calendar-range mr-1"></i>
                                Eigener Zeitraum
                            </button>
                            <!-- Custom date inputs -->
                            <div x-show="filters.customDateRange" x-collapse class="mt-2 space-y-2">
                                <div>
                                    <label class="text-xs text-gray-500 block mb-1">Von</label>
                                    <input type="date"
                                           x-model="filters.dateFrom"
                                           @change="loadData()"
                                           class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-1">Bis</label>
                                    <input type="date"
                                           x-model="filters.dateTo"
                                           @change="loadData()"
                                           class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Only with travelers filter -->
                        <div class="mb-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox"
                                       x-model="filters.onlyWithTravelers"
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-xs font-medium text-gray-700">Nur mit betroffenen Reisen</span>
                            </label>
                        </div>

                        <!-- Reset Filters -->
                        <button @click="resetFilters(); loadData()"
                                class="w-full px-3 py-2 text-xs text-gray-600 hover:text-gray-800 hover:bg-gray-50 rounded-lg border border-gray-300 transition-colors">
                            <i class="fa-regular fa-rotate-left mr-1"></i>
                            Filter zurucksetzen
                        </button>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="bg-white p-4 rounded-lg border border-gray-200 mb-4">
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="bg-gray-50 rounded-lg p-2">
                            <p class="text-lg font-bold text-gray-900" x-text="filteredSummary.total_countries"></p>
                            <p class="text-xs text-gray-500">Länder</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-2">
                            <p class="text-lg font-bold text-gray-900" x-text="filteredSummary.total_events"></p>
                            <p class="text-xs text-gray-500">Ereignisse</p>
                        </div>
                        <div class="rounded-lg p-2 cursor-pointer transition-colors"
                             :class="filters.onlyWithTravelers ? 'bg-blue-50 border border-blue-500' : 'bg-gray-50 hover:bg-gray-100'"
                             @click="filters.onlyWithTravelers = !filters.onlyWithTravelers; loadData()">
                            <p class="text-lg font-bold" :class="filters.onlyWithTravelers ? 'text-blue-700' : 'text-gray-900'" x-text="filteredSummary.total_affected_travelers"></p>
                            <p class="text-xs" :class="filters.onlyWithTravelers ? 'text-blue-600' : 'text-gray-500'">Betroffene Reisen</p>
                        </div>
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
                                <button @click="loadData()" class="inline-block mt-2 text-sm text-red-800 hover:text-red-900 underline">
                                    Erneut versuchen
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <template x-if="!loading && !error && filteredCountries.length === 0">
                    <div class="bg-gray-50 p-6 rounded-lg border border-dashed border-gray-300 text-center">
                        <template x-if="filters.onlyWithTravelers && countries.length > 0">
                            <div>
                                <i class="fa-regular fa-filter-circle-xmark text-4xl text-blue-500 mb-3"></i>
                                <h3 class="font-semibold text-gray-700">Keine betroffenen Reisen</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Im ausgewählten Zeitraum sind keine Reisen in Ländern mit Ereignissen.
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
                    </div>
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
                                    <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0"
                                         :class="{
                                             'bg-red-500': country.highest_priority === 'high',
                                             'bg-orange-500': country.highest_priority === 'medium',
                                             'bg-green-500': country.highest_priority === 'low',
                                             'bg-blue-500': country.highest_priority === 'info'
                                         }"></div>
                                    <div class="flex-1 min-w-0">
                                        <!-- Country Name & Code -->
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-xs font-medium uppercase text-gray-800" x-text="country.country.name"></span>
                                            <span class="text-xs font-mono text-gray-400 flex-shrink-0" x-text="country.country.code"></span>
                                        </div>
                                        <!-- Stats -->
                                        <div class="flex items-center gap-3 text-xs text-gray-600 mt-1">
                                            <span class="flex items-center gap-1">
                                                <i class="fa-regular fa-triangle-exclamation"></i>
                                                <span x-text="country.total_events + ' Ereignis' + (country.total_events !== 1 ? 'se' : '')"></span>
                                            </span>
                                            <template x-if="country.affected_travelers > 0">
                                                <span class="flex items-center gap-1 text-blue-600 font-medium">
                                                    <i class="fa-regular fa-users"></i>
                                                    <span x-text="country.affected_travelers + (country.affected_travelers === 1 ? ' Reise' : ' Reisen')"></span>
                                                </span>
                                            </template>
                                        </div>
                                        <!-- Priority Breakdown -->
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            <template x-if="country.events_by_priority.high > 0">
                                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-700">
                                                    <span x-text="country.events_by_priority.high"></span> Hoch
                                                </span>
                                            </template>
                                            <template x-if="country.events_by_priority.medium > 0">
                                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] bg-orange-100 text-orange-700">
                                                    <span x-text="country.events_by_priority.medium"></span> Mittel
                                                </span>
                                            </template>
                                            <template x-if="country.events_by_priority.low > 0">
                                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] bg-green-100 text-green-700">
                                                    <span x-text="country.events_by_priority.low"></span> Niedrig
                                                </span>
                                            </template>
                                            <template x-if="country.events_by_priority.info > 0">
                                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] bg-blue-100 text-blue-700">
                                                    <span x-text="country.events_by_priority.info"></span> Information
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
        </div>

        <!-- Content Container with Tabs -->
        <div class="content-container flex flex-col flex-1 min-h-0">
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
                <!-- Selected country indicator -->
                <template x-if="selectedCountry && (activeTab === 'list' || activeTab === 'tiles' || activeTab === 'calendar')">
                    <div class="ml-auto flex items-center gap-2 text-sm text-gray-600">
                        <span class="font-medium" x-text="selectedCountry.country.name"></span>
                        <button @click="selectedCountry = null; countryDetails = null" class="text-gray-400 hover:text-gray-600">
                            <i class="fa-regular fa-xmark"></i>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Tiles View -->
            <div x-show="activeTab === 'tiles'" x-cloak class="list-view flex-1 flex flex-col min-h-0">
                <!-- No country selected -->
                <template x-if="!selectedCountry">
                    <div class="flex-1 flex items-center justify-center bg-gray-50">
                        <div class="text-center">
                            <i class="fa-regular fa-hand-pointer text-4xl text-gray-400 mb-3"></i>
                            <h3 class="font-semibold text-gray-700">Land auswählen</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Wählen Sie ein Land in der linken Sidebar aus, um Details anzuzeigen.
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Country selected - Split view -->
                <template x-if="selectedCountry">
                    <div class="flex-1 flex flex-col min-h-0">
                        <!-- Loading -->
                        <template x-if="loadingCountryDetails">
                            <div class="flex-1 flex items-center justify-center">
                                <i class="fa-regular fa-spinner-third fa-spin text-3xl text-blue-500"></i>
                            </div>
                        </template>

                        <!-- Content - 50/50 Split -->
                        <template x-if="!loadingCountryDetails && countryDetails">
                            <div class="flex-1 flex flex-col min-h-0">
                                <!-- Top: Events (50%) -->
                                <div class="min-h-0 border-b border-gray-200 overflow-hidden flex flex-col transition-all"
                                     :class="maximizedSection === 'events' ? 'flex-1' : maximizedSection === 'travelers' ? 'flex-none h-[52px]' : 'flex-1'">
                                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex-shrink-0">
                                        <h3 class="text-sm font-semibold text-gray-900 flex items-center justify-between">
                                            <span class="flex items-center">
                                                <i class="fa-regular fa-triangle-exclamation mr-2 text-orange-500"></i>
                                                Ereignisse
                                                <span class="ml-2 text-gray-500 font-normal" x-text="'(' + countryDetails.events.length + ')'"></span>
                                            </span>
                                            <button @click="toggleMaximize('events')"
                                                    class="p-1.5 hover:bg-gray-200 rounded transition-colors"
                                                    :title="maximizedSection === 'events' ? 'Ansicht wiederherstellen' : 'Maximieren'">
                                                <i class="fa-regular text-xs transition-all" :class="maximizedSection === 'events' ? 'fa-compress' : 'fa-expand'"></i>
                                            </button>
                                        </h3>
                                    </div>
                                    <div class="flex-1 overflow-y-auto p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <template x-for="event in countryDetails.events" :key="event.id">
                                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm border-l-4 cursor-pointer hover:shadow-md transition-shadow"
                                                     @click="openEventModal(event)"
                                                     :class="{
                                                         'border-l-red-500': event.priority === 'high',
                                                         'border-l-orange-500': event.priority === 'medium',
                                                         'border-l-green-500': event.priority === 'low',
                                                         'border-l-blue-500': event.priority === 'info'
                                                     }">
                                                    <div class="flex items-start justify-between mb-2">
                                                        <h4 class="text-xs font-medium text-gray-800" x-text="event.title"></h4>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                              :class="{
                                                                  'bg-red-100 text-red-700': event.priority === 'high',
                                                                  'bg-orange-100 text-orange-700': event.priority === 'medium',
                                                                  'bg-green-100 text-green-700': event.priority === 'low',
                                                                  'bg-blue-100 text-blue-700': event.priority === 'info'
                                                              }"
                                                              x-text="event.priority === 'high' ? 'Hoch' : event.priority === 'medium' ? 'Mittel' : event.priority === 'low' ? 'Niedrig' : 'Information'"></span>
                                                    </div>
                                                    <p class="text-xs text-gray-600 line-clamp-3" x-text="event.description"></p>
                                                    <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                                                        <div class="flex items-center gap-2">
                                                            <span x-text="event.event_type"></span>
                                                            <span>&bull;</span>
                                                            <span x-text="formatDate(event.start_date)"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <template x-if="countryDetails.events.length === 0">
                                            <div class="text-center py-8 text-gray-500">
                                                <i class="fa-regular fa-check-circle text-3xl text-green-500 mb-2"></i>
                                                <p>Keine Ereignisse in diesem Land</p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Bottom: Travelers (50%) -->
                                <div class="min-h-0 overflow-hidden flex flex-col transition-all"
                                     :class="maximizedSection === 'travelers' ? 'flex-1' : maximizedSection === 'events' ? 'flex-none h-[52px]' : 'flex-1'">
                                    <div class="px-4 py-3 bg-blue-50 border-b border-blue-200 flex-shrink-0">
                                        <h3 class="text-sm font-semibold text-gray-900 flex items-center justify-between">
                                            <span class="flex items-center">
                                                <i class="fa-regular fa-users mr-2 text-blue-500"></i>
                                                Betroffene Reisen
                                                <span class="ml-2 text-gray-500 font-normal" x-text="'(' + countryDetails.travelers.length + ')'"></span>
                                            </span>
                                            <button @click="toggleMaximize('travelers')"
                                                    class="p-1.5 hover:bg-blue-200 rounded transition-colors"
                                                    :title="maximizedSection === 'travelers' ? 'Ansicht wiederherstellen' : 'Maximieren'">
                                                <i class="fa-regular text-xs transition-all" :class="maximizedSection === 'travelers' ? 'fa-compress' : 'fa-expand'"></i>
                                            </button>
                                        </h3>
                                    </div>
                                    <div class="flex-1 overflow-y-auto p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <template x-for="traveler in countryDetails.travelers" :key="traveler.folder_id">
                                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm cursor-pointer hover:shadow-md hover:border-blue-300 transition-all"
                                                     @click="openTravelerModal(traveler)">
                                                    <div class="flex items-start justify-between">
                                                        <h4 class="text-xs font-medium text-gray-800" x-text="traveler.folder_name"></h4>
                                                    </div>
                                                    <!-- Trip Progress Bar -->
                                                    <div class="mt-2" x-data="{ tripProgress: getTripProgress(traveler.start_date, traveler.end_date) }">
                                                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                            <span x-text="formatDate(traveler.start_date)"></span>
                                                            <span x-text="formatDate(traveler.end_date)"></span>
                                                        </div>
                                                        <div class="flex items-center" :class="tripProgress.started ? 'gap-2' : ''">
                                                            <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                <div class="h-full rounded-full transition-all duration-300"
                                                                     :class="tripProgress.started ? 'bg-green-500' : 'bg-gray-300'"
                                                                     :style="'width: ' + (tripProgress.started ? tripProgress.progress : 100) + '%'"></div>
                                                            </div>
                                                            <span x-show="tripProgress.started" class="text-xs text-gray-500 w-10 text-right"
                                                                  x-text="tripProgress.progress + '%'"></span>
                                                        </div>
                                                        <p class="text-xs mt-1"
                                                           :class="{
                                                               'text-gray-400': tripProgress.status === 'upcoming',
                                                               'text-green-600': tripProgress.status === 'active',
                                                               'text-gray-500': tripProgress.status === 'completed'
                                                           }"
                                                           x-text="tripProgress.status === 'upcoming' ? 'Noch nicht gestartet' : tripProgress.status === 'active' ? 'Reise aktiv' : 'Abgeschlossen'"></p>
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
                    <div class="flex-1 flex items-center justify-center bg-gray-50">
                        <div class="text-center">
                            <i class="fa-regular fa-hand-pointer text-4xl text-gray-400 mb-3"></i>
                            <h3 class="font-semibold text-gray-700">Land auswählen</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Wählen Sie ein Land in der linken Sidebar aus, um den Kalender anzuzeigen.
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Country selected - Calendar view -->
                <template x-if="selectedCountry">
                    <div class="flex-1 flex flex-col min-h-0">
                        <!-- Loading -->
                        <template x-if="loadingCountryDetails">
                            <div class="flex-1 flex items-center justify-center">
                                <i class="fa-regular fa-spinner-third fa-spin text-3xl text-blue-500"></i>
                            </div>
                        </template>

                        <!-- Calendar Content -->
                        <template x-if="!loadingCountryDetails && countryDetails">
                            <div class="flex-1 flex flex-col min-h-0 overflow-hidden"
                                 x-data="{
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
                                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex-shrink-0 flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <button @click="prevMonth()" class="p-2 bg-white border border-gray-300 hover:bg-gray-100 rounded-lg transition-colors" title="Vorheriger Monat">
                                            <i class="fa-regular fa-chevron-left text-gray-700"></i>
                                        </button>
                                        <h3 class="text-sm font-bold text-gray-900 min-w-[160px] text-center px-3" x-text="monthYearLabel"></h3>
                                        <button @click="nextMonth()" class="p-2 bg-white border border-gray-300 hover:bg-gray-100 rounded-lg transition-colors" title="Nächster Monat">
                                            <i class="fa-regular fa-chevron-right text-gray-700"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <button @click="goToToday()" class="px-3 py-1.5 text-xs bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium">
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
                                        <template x-for="day in ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']" :key="day">
                                            <div class="text-center text-xs font-medium text-gray-500 py-2" x-text="day"></div>
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
                                                    <span class="text-xs font-medium px-1.5 py-0.5 rounded"
                                                          :class="{
                                                              'bg-blue-500 text-white': day.isToday,
                                                              'text-gray-900': day.isCurrentMonth && !day.isToday,
                                                              'text-gray-400': !day.isCurrentMonth
                                                          }"
                                                          x-text="day.dayNumber"></span>
                                                    <!-- Traveler count badges -->
                                                    <template x-if="isDateInFilterRange(day.date) && getTravelersForDay(day.date, travelers).length > 0">
                                                        <div class="flex items-center gap-1">
                                                            <span class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-700" title="Reisen">
                                                                <i class="fa-regular fa-suitcase"></i>
                                                                <span x-text="getTravelersForDay(day.date, travelers).length"></span>
                                                            </span>
                                                            <span class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-700" title="Reisende">
                                                                <i class="fa-regular fa-users"></i>
                                                                <span x-text="getTravelerCountForDay(day.date, travelers)"></span>
                                                            </span>
                                                        </div>
                                                    </template>
                                                </div>

                                                <!-- Events grouped by priority -->
                                                <div class="space-y-1 max-h-[85px] overflow-y-auto">
                                                    <template x-for="event in getEventsForDay(day.date, events).filter(e => e.priority === 'high')" :key="'high-' + event.id">
                                                        <div class="flex items-center gap-1 px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-800 cursor-pointer hover:bg-red-200 transition-colors truncate"
                                                             @click="$dispatch('open-event-modal', event)"
                                                             :title="event.title">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                                            <span class="truncate" x-text="event.title.substring(0, 20) + (event.title.length > 20 ? '...' : '')"></span>
                                                        </div>
                                                    </template>
                                                    <template x-for="event in getEventsForDay(day.date, events).filter(e => e.priority === 'medium')" :key="'medium-' + event.id">
                                                        <div class="flex items-center gap-1 px-1.5 py-0.5 rounded text-xs bg-orange-100 text-orange-800 cursor-pointer hover:bg-orange-200 transition-colors truncate"
                                                             @click="$dispatch('open-event-modal', event)"
                                                             :title="event.title">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 flex-shrink-0"></span>
                                                            <span class="truncate" x-text="event.title.substring(0, 20) + (event.title.length > 20 ? '...' : '')"></span>
                                                        </div>
                                                    </template>
                                                    <template x-for="event in getEventsForDay(day.date, events).filter(e => e.priority === 'low')" :key="'low-' + event.id">
                                                        <div class="flex items-center gap-1 px-1.5 py-0.5 rounded text-xs bg-green-100 text-green-800 cursor-pointer hover:bg-green-200 transition-colors truncate"
                                                             @click="$dispatch('open-event-modal', event)"
                                                             :title="event.title">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0"></span>
                                                            <span class="truncate" x-text="event.title.substring(0, 20) + (event.title.length > 20 ? '...' : '')"></span>
                                                        </div>
                                                    </template>
                                                    <template x-for="event in getEventsForDay(day.date, events).filter(e => e.priority === 'info')" :key="'info-' + event.id">
                                                        <div class="flex items-center gap-1 px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-800 cursor-pointer hover:bg-blue-200 transition-colors truncate"
                                                             @click="$dispatch('open-event-modal', event)"
                                                             :title="event.title">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                                            <span class="truncate" x-text="event.title.substring(0, 20) + (event.title.length > 20 ? '...' : '')"></span>
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
                                            <span class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded bg-green-100 text-green-700">
                                                <i class="fa-regular fa-suitcase text-[10px]"></i>
                                                <span>n</span>
                                            </span>
                                            <span>Reisen</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded bg-blue-100 text-blue-700">
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

            <!-- Map View -->
            <div x-show="activeTab === 'map'" class="map-container flex-1 relative">
                <div id="risk-map"></div>

                <!-- Country Details Sidebar (only in Map view) -->
                <div class="country-sidebar" :class="{ 'open': showCountrySidebar && activeTab === 'map' }">
                    <div class="country-sidebar-header">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900" x-text="countryDetails?.country?.name || 'Land'"></h3>
                            <p class="text-sm text-gray-500" x-text="countryDetails?.summary?.total_events + ' Ereignisse, ' + countryDetails?.summary?.total_travelers + (countryDetails?.summary?.total_travelers === 1 ? ' Reise' : ' Reisen')"></p>
                        </div>
                        <button @click="closeCountrySidebar()" class="w-8 h-8 flex items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors">
                            <i class="fa-regular fa-xmark"></i>
                        </button>
                    </div>
                    <div class="country-sidebar-content">
                        <!-- Loading -->
                        <template x-if="loadingCountryDetails">
                            <div class="flex items-center justify-center py-8">
                                <i class="fa-regular fa-spinner-third fa-spin text-3xl text-blue-500"></i>
                            </div>
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
                                                 @click="openEventModal(event)"
                                                 :class="{
                                                     'border-red-500': event.priority === 'high',
                                                     'border-orange-500': event.priority === 'medium',
                                                     'border-green-500': event.priority === 'low',
                                                     'border-blue-500': event.priority === 'info'
                                                 }">
                                                <h5 class="text-xs font-medium text-gray-800" x-text="event.title"></h5>
                                                <p class="text-xs text-gray-600 mt-1 line-clamp-2" x-text="event.description"></p>
                                                <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                                    <span x-text="event.event_type"></span>
                                                    <span x-text="formatDate(event.start_date)"></span>
                                                </div>
                                                <template x-if="event.source_url">
                                                    <a :href="event.source_url" target="_blank" class="inline-flex items-center gap-1 mt-2 text-xs text-blue-600 hover:text-blue-800">
                                                        <i class="fa-regular fa-external-link"></i>
                                                        Quelle
                                                    </a>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="countryDetails.events.length === 0">
                                            <p class="text-sm text-gray-500 text-center py-4">Keine Ereignisse gefunden</p>
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
                                        <template x-for="traveler in countryDetails.travelers" :key="traveler.folder_id">
                                            <div class="p-3 rounded-lg cursor-pointer hover:ring-2 hover:ring-blue-300 transition-all"
                                                 :class="traveler.source === 'api' ? 'bg-purple-50' : 'bg-blue-50'"
                                                 @click="openTravelerModal(traveler)">
                                                <div class="flex items-center justify-between">
                                                    <h5 class="text-xs font-medium text-gray-800" x-text="traveler.folder_name"></h5>
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs"
                                                              :class="traveler.source === 'api' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                                              x-text="traveler.source === 'api' ? 'API' : 'GTM'"></span>
                                                        <span class="text-xs text-gray-500">
                                                            <i class="fa-regular fa-users mr-1"></i>
                                                            <span x-text="traveler.participant_count"></span>
                                                        </span>
                                                    </div>
                                                </div>
                                                <!-- Trip Progress Bar -->
                                                <div class="mt-2" x-data="{ tripProgress: getTripProgress(traveler.start_date, traveler.end_date) }">
                                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                        <span x-text="formatDate(traveler.start_date)"></span>
                                                        <span x-text="formatDate(traveler.end_date)"></span>
                                                    </div>
                                                    <div class="flex items-center" :class="tripProgress.started ? 'gap-2' : ''">
                                                        <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                                            <div class="h-full rounded-full transition-all duration-300"
                                                                 :class="tripProgress.started ? 'bg-green-500' : 'bg-gray-300'"
                                                                 :style="'width: ' + (tripProgress.started ? tripProgress.progress : 100) + '%'"></div>
                                                        </div>
                                                        <span x-show="tripProgress.started" class="text-xs text-gray-500 w-8 text-right"
                                                              x-text="tripProgress.progress + '%'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="countryDetails.travelers.length === 0">
                                            <p class="text-sm text-gray-500 text-center py-4">Keine Reisenden in diesem Land</p>
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
                    <div class="flex-1 flex items-center justify-center bg-gray-50">
                        <div class="text-center">
                            <i class="fa-regular fa-hand-pointer text-4xl text-gray-400 mb-3"></i>
                            <h3 class="font-semibold text-gray-700">Land auswählen</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Wählen Sie ein Land in der linken Sidebar aus, um Details anzuzeigen.
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Country selected - Split view -->
                <template x-if="selectedCountry">
                    <div class="flex-1 flex flex-col min-h-0">
                        <!-- Loading -->
                        <template x-if="loadingCountryDetails">
                            <div class="flex-1 flex items-center justify-center">
                                <i class="fa-regular fa-spinner-third fa-spin text-3xl text-blue-500"></i>
                            </div>
                        </template>

                        <!-- Content - 50/50 Split -->
                        <template x-if="!loadingCountryDetails && countryDetails">
                            <div class="flex-1 flex flex-col min-h-0">
                                <!-- Top: Events (50%) -->
                                <div class="min-h-0 border-b border-gray-200 overflow-hidden flex flex-col transition-all"
                                     :class="maximizedSection === 'events' ? 'flex-1' : maximizedSection === 'travelers' ? 'flex-none h-[52px]' : 'flex-1'">
                                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex-shrink-0">
                                        <h3 class="text-sm font-semibold text-gray-900 flex items-center justify-between">
                                            <span class="flex items-center">
                                                <i class="fa-regular fa-triangle-exclamation mr-2 text-orange-500"></i>
                                                Ereignisse
                                                <span class="ml-2 text-gray-500 font-normal" x-text="'(' + countryDetails.events.length + ')'"></span>
                                            </span>
                                            <button @click="toggleMaximize('events')"
                                                    class="p-1.5 hover:bg-gray-200 rounded transition-colors"
                                                    :title="maximizedSection === 'events' ? 'Ansicht wiederherstellen' : 'Maximieren'">
                                                <i class="fa-regular text-xs transition-all" :class="maximizedSection === 'events' ? 'fa-compress' : 'fa-expand'"></i>
                                            </button>
                                        </h3>
                                    </div>
                                    <div class="flex-1 overflow-y-auto">
                                        <div class="divide-y divide-gray-200">
                                            <template x-for="event in countryDetails.events" :key="event.id">
                                                <div class="px-4 py-3 bg-white hover:bg-gray-50 cursor-pointer transition-colors flex items-center gap-4 border-l-4"
                                                     @click="openEventModal(event)"
                                                     :class="{
                                                         'border-l-red-500': event.priority === 'high',
                                                         'border-l-orange-500': event.priority === 'medium',
                                                         'border-l-green-500': event.priority === 'low',
                                                         'border-l-blue-500': event.priority === 'info'
                                                     }">
                                                    <div class="flex-1 min-w-0">
                                                        <h4 class="text-xs font-medium text-gray-800 truncate" x-text="event.title"></h4>
                                                        <p class="text-xs text-gray-500 mt-0.5" x-text="event.event_type + ' • ' + formatDate(event.start_date)"></p>
                                                    </div>
                                                    <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                          :class="{
                                                              'bg-red-100 text-red-700': event.priority === 'high',
                                                              'bg-orange-100 text-orange-700': event.priority === 'medium',
                                                              'bg-green-100 text-green-700': event.priority === 'low',
                                                              'bg-blue-100 text-blue-700': event.priority === 'info'
                                                          }"
                                                          x-text="event.priority === 'high' ? 'Hoch' : event.priority === 'medium' ? 'Mittel' : event.priority === 'low' ? 'Niedrig' : 'Information'"></span>
                                                </div>
                                            </template>
                                        </div>
                                        <template x-if="countryDetails.events.length === 0">
                                            <div class="text-center py-8 text-gray-500">
                                                <i class="fa-regular fa-check-circle text-3xl text-green-500 mb-2"></i>
                                                <p>Keine Ereignisse in diesem Land</p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Bottom: Travelers (50%) -->
                                <div class="min-h-0 overflow-hidden flex flex-col transition-all"
                                     :class="maximizedSection === 'travelers' ? 'flex-1' : maximizedSection === 'events' ? 'flex-none h-[52px]' : 'flex-1'">
                                    <div class="px-4 py-3 bg-blue-50 border-b border-blue-200 flex-shrink-0">
                                        <h3 class="text-sm font-semibold text-gray-900 flex items-center justify-between">
                                            <span class="flex items-center">
                                                <i class="fa-regular fa-users mr-2 text-blue-500"></i>
                                                Betroffene Reisen
                                                <span class="ml-2 text-gray-500 font-normal" x-text="'(' + countryDetails.travelers.length + ')'"></span>
                                            </span>
                                            <button @click="toggleMaximize('travelers')"
                                                    class="p-1.5 hover:bg-blue-200 rounded transition-colors"
                                                    :title="maximizedSection === 'travelers' ? 'Ansicht wiederherstellen' : 'Maximieren'">
                                                <i class="fa-regular text-xs transition-all" :class="maximizedSection === 'travelers' ? 'fa-compress' : 'fa-expand'"></i>
                                            </button>
                                        </h3>
                                    </div>
                                    <div class="flex-1 overflow-y-auto">
                                        <div class="divide-y divide-gray-200">
                                            <template x-for="traveler in countryDetails.travelers" :key="traveler.folder_id">
                                                <div class="px-4 py-3 bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                                                     :class="traveler.source === 'api' ? 'border-l-4 border-l-purple-400' : ''"
                                                     @click="openTravelerModal(traveler)"
                                                     x-data="{ tripProgress: getTripProgress(traveler.start_date, traveler.end_date) }">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <h4 class="text-xs font-medium text-gray-800" x-text="traveler.folder_name"></h4>
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs"
                                                                  :class="traveler.source === 'api' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600'"
                                                                  x-text="traveler.source === 'api' ? 'API' : 'GTM'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2 flex items-center gap-4">
                                                        <div class="flex-1">
                                                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                                <span x-text="formatDate(traveler.start_date)"></span>
                                                                <span x-text="formatDate(traveler.end_date)"></span>
                                                            </div>
                                                            <div class="flex items-center" :class="tripProgress.started ? 'gap-2' : ''">
                                                                <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                                                    <div class="h-full rounded-full transition-all duration-300"
                                                                         :class="tripProgress.started ? 'bg-green-500' : 'bg-gray-300'"
                                                                         :style="'width: ' + (tripProgress.started ? tripProgress.progress : 100) + '%'"></div>
                                                                </div>
                                                                <span x-show="tripProgress.started" class="text-xs text-gray-500 w-8 text-right"
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
        </div>
    </div>

    <!-- Event Detail Modal -->
    <div x-show="showEventModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[200000] overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeEventModal()"></div>

        <!-- Modal Container -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showEventModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.stop
                 class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-hidden">

                <!-- Modal Header -->
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-start justify-between z-10">
                    <div class="pr-10">
                        <h3 id="modal-title" class="text-lg font-semibold text-gray-900" x-text="selectedEvent?.title"></h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-red-100 text-red-800': selectedEvent?.priority === 'high',
                                      'bg-orange-100 text-orange-800': selectedEvent?.priority === 'medium',
                                      'bg-green-100 text-green-800': selectedEvent?.priority === 'low',
                                      'bg-blue-100 text-blue-800': selectedEvent?.priority === 'info'
                                  }"
                                  x-text="selectedEvent?.priority === 'high' ? 'Hoch' : selectedEvent?.priority === 'medium' ? 'Mittel' : selectedEvent?.priority === 'low' ? 'Niedrig' : 'Information'"></span>
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
                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Beschreibung</h4>
                        <div class="prose prose-sm max-w-none text-gray-700"
                             x-html="selectedEvent?.description || selectedEvent?.popup_content || '<p class=\'text-gray-400 italic\'>Keine Beschreibung verfügbar</p>'"></div>
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
                            <p class="text-gray-900 font-medium" x-text="selectedEvent?.end_date ? formatDate(selectedEvent?.end_date) : 'Unbestimmt'"></p>
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
                                <p class="text-gray-900 font-medium text-sm font-mono" x-text="parseFloat(selectedEvent?.latitude).toFixed(4) + ', ' + parseFloat(selectedEvent?.longitude).toFixed(4)"></p>
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
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm">
                                        <i class="fa-regular fa-tag mr-1 text-xs"></i>
                                        <span x-text="tag"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex items-center justify-between">
                    <div class="text-xs text-gray-500 space-x-3">
                        <template x-if="selectedEvent?.created_at">
                            <span>Erstellt: <span x-text="formatDate(selectedEvent?.created_at)"></span></span>
                        </template>
                        <template x-if="selectedEvent?.updated_at && selectedEvent?.updated_at !== selectedEvent?.created_at">
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
    <div x-show="showTravelerModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[200000] overflow-y-auto"
         aria-labelledby="traveler-modal-title"
         role="dialog"
         aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeTravelerModal()"></div>

        <!-- Modal Container -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showTravelerModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.stop
                 class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-hidden">

                <!-- Modal Header -->
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-start justify-between z-10">
                    <div class="pr-10">
                        <h3 id="traveler-modal-title" class="text-lg font-semibold text-gray-900" x-text="selectedTraveler?.folder_name"></h3>
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
                    <div class="mb-6" x-data="{ get tripProgress() { return selectedTraveler ? getTripProgress(selectedTraveler.start_date, selectedTraveler.end_date) : { started: false, progress: 0, status: 'upcoming' }; } }">
                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Reisestatus</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium"
                                      :class="{
                                          'text-gray-500': tripProgress.status === 'upcoming',
                                          'text-green-600': tripProgress.status === 'active',
                                          'text-gray-500': tripProgress.status === 'completed'
                                      }"
                                      x-text="tripProgress.status === 'upcoming' ? 'Noch nicht gestartet' : tripProgress.status === 'active' ? 'Reise aktiv' : 'Abgeschlossen'"></span>
                                <span x-show="tripProgress.started" class="text-sm font-bold text-gray-700"
                                      x-text="tripProgress.progress + '%'"></span>
                            </div>
                            <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                     :class="{
                                         'bg-gray-300': !tripProgress.started,
                                         'bg-green-500': tripProgress.status === 'active',
                                         'bg-gray-400': tripProgress.status === 'completed'
                                     }"
                                     :style="'width: ' + (tripProgress.started ? tripProgress.progress : 100) + '%'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <!-- Folder Number -->
                        <template x-if="selectedTraveler?.folder_number && String(selectedTraveler.folder_number).trim() !== ''">
                            <div class="bg-gray-50 rounded-lg p-4"
                                 x-data="{ copied: false }">
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
                            <div class="bg-gray-50 rounded-lg p-4"
                                 x-data="{ copied: false }">
                                <div class="flex items-center gap-2 text-gray-500 mb-1">
                                    <i class="fa-regular fa-fingerprint"></i>
                                    <span class="text-xs font-medium uppercase tracking-wider">Endkundenlink</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <p class="text-gray-900 font-medium font-mono text-xs truncate" x-text="'https://travel-details.eu/de?tid=' + selectedTraveler?.trip_id + '&preview'"></p>
                                    <i class="fa-regular text-sm cursor-pointer transition-all"
                                       :class="copied ? 'fa-check text-green-500' : 'fa-copy text-gray-400 hover:text-blue-500'"
                                       :title="copied ? 'Kopiert!' : 'Link kopieren'"
                                       @click="navigator.clipboard.writeText('https://travel-details.eu/de?tid=' + selectedTraveler?.trip_id + '&preview'); copied = true; setTimeout(() => copied = false, 2000)"></i>
                                    <a :href="'https://travel-details.eu/de?tid=' + selectedTraveler?.trip_id + '&preview'"
                                       target="_blank"
                                       class="text-blue-500 hover:text-blue-700 transition-colors"
                                       title="In neuem Tab öffnen">
                                        <i class="fa-regular fa-arrow-up-right-from-square text-sm"></i>
                                    </a>
                                </div>
                            </div>
                        </template>

                    </div>

                    <!-- Reisebeginn / Reiseende (always in one row) -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <!-- Start Date -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center gap-2 text-gray-500 mb-1">
                                <i class="fa-regular fa-plane-departure"></i>
                                <span class="text-xs font-medium uppercase tracking-wider">Reisebeginn</span>
                            </div>
                            <p class="text-gray-900 font-medium" x-text="formatDate(selectedTraveler?.start_date)"></p>
                        </div>

                        <!-- End Date -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center gap-2 text-gray-500 mb-1">
                                <i class="fa-regular fa-plane-arrival"></i>
                                <span class="text-xs font-medium uppercase tracking-wider">Reiseende</span>
                            </div>
                            <p class="text-gray-900 font-medium" x-text="formatDate(selectedTraveler?.end_date)"></p>
                        </div>
                    </div>

                    <!-- Nationalities -->
                    <template x-if="selectedTraveler?.nationalities && selectedTraveler.nationalities.length > 0">
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Nationalitäten</h4>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="nat in selectedTraveler.nationalities" :key="nat.code">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200" x-text="nat.name"></span>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Destinations -->
                    <template x-if="selectedTraveler?.destinations && selectedTraveler.destinations.length > 0">
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Reiseziele</h4>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="dest in selectedTraveler.destinations" :key="dest.code">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700 border border-gray-200" x-text="dest.name"></span>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Participants List -->
                    <template x-if="selectedTraveler?.participants && selectedTraveler?.participants.length > 0">
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Teilnehmer</h4>
                            <div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
                                <template x-for="(participant, idx) in selectedTraveler.participants" :key="idx">
                                    <div class="p-3 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fa-regular fa-user text-blue-600 text-sm"></i>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900" x-text="participant.name || 'Unbenannt'"></span>
                                        </div>
                                        <template x-if="participant.is_main_contact">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
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
                                <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Reisedauer</h4>
                                <div class="bg-blue-50 rounded-lg p-4 flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fa-regular fa-calendar-days text-blue-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-bold text-blue-700" x-text="duration"></p>
                                        <p class="text-sm text-blue-600" x-text="duration === 1 ? 'Tag' : 'Tage'"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex items-center justify-end">
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
    function riskOverviewApp() {
        return {
            countries: [],
            summary: {
                total_countries: 0,
                total_events: 0,
                total_affected_travelers: 0,
            },
            selectedCountry: null,
            countryDetails: null,
            selectedEvent: null,
            showEventModal: false,
            selectedTraveler: null,
            showTravelerModal: false,
            loading: false,
            loadingCountryDetails: false,
            error: null,
            map: null,
            markers: [],
            filters: {
                priority: null,
                days: 30,
                onlyWithTravelers: false,
                country: '',
                customDateRange: false,
                dateFrom: '',
                dateTo: '',
            },
            filterOpen: false,
            showCountrySidebar: false,
            activeTab: 'tiles',
            maximizedSection: null,

            toggleMaximize(section) {
                if (this.maximizedSection === section) {
                    this.maximizedSection = null;
                } else {
                    this.maximizedSection = section;
                }
            },

            get filteredCountries() {
                let result = this.countries;

                // Filter by country
                if (this.filters.country) {
                    result = result.filter(c => c.country.code === this.filters.country);
                }

                // Filter by travelers
                if (this.filters.onlyWithTravelers) {
                    result = result.filter(c => c.affected_travelers > 0);
                }

                return result;
            },

            get filteredSummary() {
                const filtered = this.filteredCountries;
                let totalEvents = 0;
                let totalAffectedTravelers = 0;
                filtered.forEach(c => {
                    totalEvents += c.total_events;
                    totalAffectedTravelers += c.affected_travelers;
                });
                return {
                    total_countries: filtered.length,
                    total_events: totalEvents,
                    total_affected_travelers: totalAffectedTravelers,
                };
            },

            init() {
                this.$nextTick(() => {
                    this.initMap();
                    this.loadData();

                    // ESC key to close modals
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            if (this.showTravelerModal) {
                                this.closeTravelerModal();
                            } else if (this.showEventModal) {
                                this.closeEventModal();
                            }
                        }
                    });

                    // Watch for client-side filter changes
                    this.$watch('filters.onlyWithTravelers', () => {
                        this.updateMapMarkers();
                        this.reselectCountryIfNeeded();
                    });

                    this.$watch('filters.country', () => {
                        this.updateMapMarkers();
                        this.reselectCountryIfNeeded();
                    });

                    // Watch for tab changes to invalidate map size
                    this.$watch('activeTab', (newTab) => {
                        if (newTab === 'map' && this.map) {
                            setTimeout(() => {
                                this.map.invalidateSize();
                            }, 100);
                        }
                        if (newTab === 'list') {
                            this.showCountrySidebar = false;
                        }
                    });
                });
            },

            initMap() {
                this.map = L.map('risk-map', {
                    center: [30.0, 10.0],
                    zoom: 3,
                    zoomControl: true,
                    worldCopyJump: false,
                    maxBounds: [[-90, -180], [90, 180]],
                    minZoom: 2
                });

                L.tileLayer('https://tile.openstreetmap.de/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(this.map);

                let resizeTimeout;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(() => {
                        if (this.map) {
                            this.map.invalidateSize();
                        }
                    }, 250);
                });

                setTimeout(() => {
                    if (this.map) {
                        this.map.invalidateSize();
                    }
                }, 100);
            },

            async loadData() {
                this.loading = true;
                this.error = null;

                try {
                    const params = new URLSearchParams();
                    if (this.filters.priority) {
                        params.append('priority', this.filters.priority);
                    }

                    if (this.filters.customDateRange && this.filters.dateFrom) {
                        params.append('date_from', this.filters.dateFrom);
                        if (this.filters.dateTo) {
                            params.append('date_to', this.filters.dateTo);
                        }
                    } else {
                        params.append('days', this.filters.days);
                    }

                    const response = await fetch(`{{ route('embed.risk-overview.data') }}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    // Session expired - reload to show login
                    if (response.status === 401) {
                        window.location.reload();
                        return;
                    }

                    if (!response.ok) {
                        throw new Error('Fehler beim Laden der Daten');
                    }

                    const result = await response.json();

                    if (result.success) {
                        this.countries = result.data.countries;
                        this.summary = result.data.summary;
                        this.updateMapMarkers();
                        this.reselectCountryIfNeeded();
                    } else {
                        throw new Error(result.message || 'Unbekannter Fehler');
                    }
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.loading = false;
                }
            },

            updateMapMarkers() {
                this.markers.forEach(marker => this.map.removeLayer(marker));
                this.markers = [];

                this.filteredCountries.forEach(country => {
                    if (country.country.lat && country.country.lng) {
                        const markerColor = this.getPriorityColor(country.highest_priority);
                        const markerSize = Math.min(30, 20 + country.total_events * 2);

                        const icon = L.divIcon({
                            className: 'event-marker event-marker-' + country.highest_priority,
                            html: `<span>${country.total_events}</span>`,
                            iconSize: [markerSize, markerSize],
                            iconAnchor: [markerSize / 2, markerSize / 2],
                        });

                        const marker = L.marker([country.country.lat, country.country.lng], { icon })
                            .addTo(this.map)
                            .on('click', () => this.selectCountry(country));

                        marker.bindTooltip(`
                            <strong>${country.country.name}</strong><br>
                            ${country.total_events} Ereignis${country.total_events !== 1 ? 'se' : ''}<br>
                            ${country.affected_travelers} ${country.affected_travelers === 1 ? 'Reise' : 'Reisen'}
                        `, {
                            direction: 'top',
                            offset: [0, -markerSize / 2]
                        });

                        this.markers.push(marker);
                    }
                });

                if (this.markers.length > 0) {
                    const group = L.featureGroup(this.markers);
                    this.map.fitBounds(group.getBounds().pad(0.1));
                }
            },

            getPriorityColor(priority) {
                const colors = {
                    high: '#ef4444',
                    medium: '#f97316',
                    low: '#eab308',
                    info: '#3b82f6',
                };
                return colors[priority] || colors.info;
            },

            async selectCountry(country) {
                this.selectedCountry = country;
                this.loadingCountryDetails = true;
                this.countryDetails = null;

                if (this.activeTab === 'map') {
                    this.showCountrySidebar = true;
                    if (country.country.lat && country.country.lng) {
                        this.map.setView([country.country.lat, country.country.lng], 5);
                    }
                }

                try {
                    const params = new URLSearchParams();

                    if (this.filters.customDateRange && this.filters.dateFrom) {
                        params.append('date_from', this.filters.dateFrom);
                        if (this.filters.dateTo) {
                            params.append('date_to', this.filters.dateTo);
                        }
                    } else {
                        params.append('days', this.filters.days);
                    }

                    const response = await fetch(`{{ url('/embed/risk-overview/country') }}/${country.country.code}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    // Session expired - reload to show login
                    if (response.status === 401) {
                        window.location.reload();
                        return;
                    }

                    if (!response.ok) {
                        throw new Error('Fehler beim Laden der Details');
                    }

                    const result = await response.json();

                    if (result.success) {
                        this.countryDetails = result.data;
                    }
                } catch (e) {
                    console.error('Error loading country details:', e);
                } finally {
                    this.loadingCountryDetails = false;
                }
            },

            closeCountrySidebar() {
                this.showCountrySidebar = false;
                this.selectedCountry = null;
                this.countryDetails = null;
            },

            openEventModal(event) {
                this.selectedEvent = event;
                this.showEventModal = true;
                document.body.style.overflow = 'hidden';
            },

            closeEventModal() {
                this.showEventModal = false;
                this.selectedEvent = null;
                document.body.style.overflow = '';
            },

            openTravelerModal(traveler) {
                this.selectedTraveler = traveler;
                this.showTravelerModal = true;
                document.body.style.overflow = 'hidden';
            },

            closeTravelerModal() {
                this.showTravelerModal = false;
                this.selectedTraveler = null;
                document.body.style.overflow = '';
            },

            reselectCountryIfNeeded() {
                if (!this.selectedCountry) {
                    return;
                }

                const stillVisible = this.filteredCountries.find(
                    c => c.country.code === this.selectedCountry.country.code
                );

                if (stillVisible) {
                    this.selectCountry(stillVisible);
                } else {
                    this.selectedCountry = null;
                    this.countryDetails = null;
                }
            },

            resetFilters() {
                this.filters = {
                    priority: null,
                    days: 30,
                    onlyWithTravelers: false,
                    country: '',
                    customDateRange: false,
                    dateFrom: '',
                    dateTo: '',
                };
            },

            formatDate(dateStr) {
                if (!dateStr) return '-';
                const date = new Date(dateStr);
                return date.toLocaleDateString('de-DE', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                });
            },

            formatDateRange(start, end) {
                const startStr = this.formatDate(start);
                const endStr = this.formatDate(end);
                if (startStr === endStr) return startStr;
                return `${startStr} - ${endStr}`;
            },

            getTripProgress(startDate, endDate) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                const start = new Date(startDate);
                start.setHours(0, 0, 0, 0);

                const end = new Date(endDate);
                end.setHours(0, 0, 0, 0);

                if (today < start) {
                    return { started: false, progress: 0, status: 'upcoming' };
                }

                if (today > end) {
                    return { started: true, progress: 100, status: 'completed' };
                }

                const totalDays = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1);
                const elapsedDays = Math.ceil((today - start) / (1000 * 60 * 60 * 24)) + 1;
                const progress = Math.min(100, Math.round((elapsedDays / totalDays) * 100));

                return { started: true, progress, status: 'active' };
            },
        };
    }
</script>
@endsection
