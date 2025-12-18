@extends('layouts.embed')

@section('title', 'Dashboard - Global Travel Monitor')
@section('badge_position', 'top-right')

@push('styles')
<style>
    #embed-dashboard-map {
        width: 100%;
        height: 100%;
    }

    .custom-marker {
        background: none;
        border: none;
    }

    .custom-marker i {
        font-size: 20px;
        filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.5));
    }

    .leaflet-popup-content {
        margin: 0;
        padding: 0;
    }

    .leaflet-popup-content-wrapper {
        padding: 0;
        border-radius: 8px;
        overflow: hidden;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .leaflet-popup-pane {
        z-index: 2000 !important;
    }

    .leaflet-popup {
        z-index: 2001 !important;
    }
</style>
@endpush

@section('content')
<div x-data="embedDashboardApp()" x-init="init()" class="h-full flex flex-col lg:flex-row">
    <!-- Sidebar with Events -->
    <div class="w-full lg:w-80 xl:w-96 flex-shrink-0 bg-white border-b lg:border-b-0 lg:border-r border-gray-200 flex flex-col h-1/2 lg:h-full">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 flex-shrink-0">
            <h2 class="font-bold text-gray-800">Aktuelle Ereignisse</h2>
            <p class="text-xs text-gray-500 mt-1">
                <span x-text="filteredEvents.length"></span> Ereignisse
                <span x-show="activeFiltersCount > 0" class="text-blue-600">
                    (<span x-text="activeFiltersCount"></span> Filter aktiv)
                </span>
            </p>
        </div>

        <!-- Events List -->
        <div class="flex-1 overflow-y-auto">
            <template x-if="loading">
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                </div>
            </template>

            <template x-if="!loading">
                <div class="divide-y divide-gray-100">
                    <template x-for="event in filteredEvents" :key="event.id">
                        <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors"
                             @click="selectEvent(event)">
                            <div class="flex items-start gap-2">
                                <span :style="'background-color: ' + getMarkerColor(event)"
                                      class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center shadow-sm border-2 border-white">
                                    <i :class="getEventIcon(event)" class="text-sm" style="color: #FFFFFF !important;"></i>
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium uppercase text-gray-800" x-text="getAllCountryNames(event)"></p>
                                    <h3 class="text-sm font-medium text-gray-900 line-clamp-2 mt-1" x-text="event.title"></h3>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <span x-text="formatDate(event.created_at)"></span>
                                        <span class="mx-1">•</span>
                                        <span x-text="getEventTypesDisplay(event)"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Stats Footer -->
        <div class="px-4 py-2 bg-gray-50 border-t border-gray-200 flex-shrink-0">
            <div class="text-xs text-gray-500">
                Letzte Aktualisierung: <span x-text="lastUpdate"></span>
            </div>
        </div>
    </div>

    <!-- Map Area -->
    <div class="flex-1 relative h-1/2 lg:h-full">
        <div id="embed-dashboard-map" class="w-full h-full"></div>

        <!-- Center Map Button (Left side, below zoom controls) -->
        <div class="absolute top-28 left-2 z-[1000]">
            <button @click="centerMap()"
                    title="Karte zentrieren"
                    class="w-8 h-8 bg-white rounded shadow-lg flex items-center justify-center text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-colors border border-gray-300">
                <i class="fas fa-crosshairs text-sm"></i>
            </button>
        </div>

        <!-- Filter Button (Top Right, below Powered by badge) -->
        <div class="absolute top-16 right-4 z-[1000] flex flex-col items-end">
            <button @click="filterModalOpen = true"
                    class="flex items-center gap-2 px-4 py-2 bg-white rounded-lg shadow-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors relative">
                <i class="fas fa-filter"></i>
                <span>Filter</span>
                <span x-show="activeFiltersCount > 0"
                      x-text="activeFiltersCount"
                      class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"></span>
            </button>

            <!-- Active Filter Pills -->
            <div x-show="activeFiltersCount > 0" class="mt-2 flex flex-col items-end gap-1">
                <template x-if="filters.timePeriod !== 'all'">
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-white rounded shadow text-xs">
                        <span x-text="getTimePeriodLabel(filters.timePeriod)"></span>
                        <button @click="filters.timePeriod = 'all'; applyFilters()" class="hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                </template>
                <template x-for="priority in filters.priorities" :key="priority">
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-white rounded shadow text-xs">
                        <span x-text="getPriorityLabel(priority)"></span>
                        <button @click="togglePriority(priority); applyFilters()" class="hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                </template>
                <template x-for="code in filters.continents" :key="code">
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-white rounded shadow text-xs">
                        <span x-text="getContinentName(code)"></span>
                        <button @click="toggleContinent(code); applyFilters()" class="hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                </template>
            </div>
        </div>

        <!-- Legend -->
        <div class="absolute bottom-8 right-4 z-[1000] bg-white rounded-lg shadow-lg p-3">
            <div class="text-xs font-semibold text-gray-700 mb-2">Legende</div>
            <div class="space-y-1 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: #ff0000;"></span>
                    <span>Hoch</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: #e6a50a;"></span>
                    <span>Mittel</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: #0fb67f;"></span>
                    <span>Niedrig</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: #0066cc;"></span>
                    <span>Information</span>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div x-show="loading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-[1001]">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div x-show="filterModalOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[1003] flex items-end sm:items-center justify-center bg-black bg-opacity-50"
         style="display: none;"
         @click.self="filterModalOpen = false">

        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-y-full sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="transform translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-y-0 sm:scale-100"
             x-transition:leave-end="transform translate-y-full sm:translate-y-0 sm:scale-95"
             class="bg-white rounded-t-xl sm:rounded-xl w-full sm:max-w-lg max-h-[85vh] overflow-hidden flex flex-col">

            <!-- Modal Header -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Filter</h2>
                <div class="flex gap-3 items-center">
                    <button @click="resetFilters()" class="text-sm text-blue-600 hover:text-blue-800">Zurücksetzen</button>
                    <button @click="filterModalOpen = false" class="p-1 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-6">
                <!-- Time Period Filter -->
                <div>
                    <h3 class="font-medium text-gray-900 mb-3">Zeitraum</h3>
                    <div class="flex flex-wrap gap-2">
                        <button @click="filters.timePeriod = 'all'"
                                :class="{ 'bg-blue-600 text-white': filters.timePeriod === 'all', 'bg-gray-100 text-gray-700': filters.timePeriod !== 'all' }"
                                class="px-3 py-2 rounded-lg text-sm">Aktuell</button>
                        <button @click="filters.timePeriod = 'future'"
                                :class="{ 'bg-blue-600 text-white': filters.timePeriod === 'future', 'bg-gray-100 text-gray-700': filters.timePeriod !== 'future' }"
                                class="px-3 py-2 rounded-lg text-sm">Zukünftig</button>
                        <button @click="filters.timePeriod = 'today'"
                                :class="{ 'bg-blue-600 text-white': filters.timePeriod === 'today', 'bg-gray-100 text-gray-700': filters.timePeriod !== 'today' }"
                                class="px-3 py-2 rounded-lg text-sm">Heute</button>
                        <button @click="filters.timePeriod = 'week'"
                                :class="{ 'bg-blue-600 text-white': filters.timePeriod === 'week', 'bg-gray-100 text-gray-700': filters.timePeriod !== 'week' }"
                                class="px-3 py-2 rounded-lg text-sm">Diese Woche</button>
                        <button @click="filters.timePeriod = 'month'"
                                :class="{ 'bg-blue-600 text-white': filters.timePeriod === 'month', 'bg-gray-100 text-gray-700': filters.timePeriod !== 'month' }"
                                class="px-3 py-2 rounded-lg text-sm">Dieser Monat</button>
                    </div>
                </div>

                <!-- Priority Filter -->
                <div>
                    <h3 class="font-medium text-gray-900 mb-3">Priorität</h3>
                    <div class="flex flex-wrap gap-2">
                        <button @click="togglePriority('high')"
                                :class="{ 'bg-red-600 text-white': filters.priorities.includes('high'), 'bg-red-100 text-red-700': !filters.priorities.includes('high') }"
                                class="px-3 py-2 rounded-lg text-sm">Hoch</button>
                        <button @click="togglePriority('medium')"
                                :class="{ 'bg-orange-600 text-white': filters.priorities.includes('medium'), 'bg-orange-100 text-orange-700': !filters.priorities.includes('medium') }"
                                class="px-3 py-2 rounded-lg text-sm">Mittel</button>
                        <button @click="togglePriority('low')"
                                :class="{ 'bg-green-600 text-white': filters.priorities.includes('low'), 'bg-green-100 text-green-700': !filters.priorities.includes('low') }"
                                class="px-3 py-2 rounded-lg text-sm">Niedrig</button>
                        <button @click="togglePriority('info')"
                                :class="{ 'bg-blue-600 text-white': filters.priorities.includes('info'), 'bg-blue-100 text-blue-700': !filters.priorities.includes('info') }"
                                class="px-3 py-2 rounded-lg text-sm">Information</button>
                    </div>
                </div>

                <!-- Continent Filter -->
                <div>
                    <h3 class="font-medium text-gray-900 mb-3">Kontinent</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="continent in continents" :key="continent.code">
                            <button @click="toggleContinent(continent.code)"
                                    :class="{ 'bg-blue-600 text-white': filters.continents.includes(continent.code), 'bg-gray-100 text-gray-700': !filters.continents.includes(continent.code) }"
                                    class="px-3 py-2 rounded-lg text-sm"
                                    x-text="continent.name"></button>
                        </template>
                    </div>
                </div>

                <!-- Event Type Filter -->
                <div>
                    <h3 class="font-medium text-gray-900 mb-3">Ereignistyp</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="eventType in eventTypes" :key="eventType.id">
                            <button @click="toggleEventType(eventType.id)"
                                    :class="{ 'bg-blue-600 text-white': filters.eventTypes.includes(eventType.id), 'bg-gray-100 text-gray-700': !filters.eventTypes.includes(eventType.id) }"
                                    class="px-3 py-2 rounded-lg text-sm flex items-center gap-2">
                                <i :class="eventType.icon" class="text-sm"></i>
                                <span x-text="eventType.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Apply Button -->
            <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4">
                <button @click="filterModalOpen = false; applyFilters()"
                        class="w-full py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                    Filter anwenden
                </button>
            </div>
        </div>
    </div>

    <!-- Event Detail Drawer -->
    <div x-show="selectedEvent"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed right-0 top-0 bottom-0 w-full sm:w-96 bg-white shadow-xl z-[1002] flex flex-col"
         style="display: none;">

        <!-- Drawer Header -->
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
            <span x-show="selectedEvent" :class="getPriorityBadgeColor(selectedEvent?.priority)"
                  class="px-2 py-1 text-xs font-medium rounded"
                  x-text="getPriorityLabel(selectedEvent?.priority)"></span>
            <button @click="selectedEvent = null" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Drawer Content -->
        <div class="flex-1 overflow-y-auto p-4">
            <h2 class="text-lg font-bold text-gray-900 mb-3" x-text="selectedEvent?.title"></h2>

            <div class="text-xs text-gray-500 mb-4" x-text="formatDate(selectedEvent?.created_at)"></div>

            <!-- Countries -->
            <div x-show="selectedEvent?.countries?.length" class="mb-4 flex flex-wrap gap-1">
                <template x-for="country in selectedEvent?.countries || []" :key="country.id">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 rounded text-xs">
                        <span x-text="country.flag_emoji"></span>
                        <span x-text="country.name_de || country.name"></span>
                    </span>
                </template>
            </div>

            <!-- Description -->
            <div class="prose prose-sm max-w-none text-gray-700" x-html="selectedEvent?.description || selectedEvent?.popup_content"></div>

            <!-- Source -->
            <div x-show="selectedEvent?.source_url" class="mt-4 pt-4 border-t border-gray-200">
                <a :href="selectedEvent?.source_url" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Zur Quelle</span>
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
function embedDashboardApp() {
    return {
        map: null,
        markerCluster: null,
        events: [],
        filteredEvents: [],
        loading: true,
        selectedEvent: null,
        lastUpdate: '',
        filterModalOpen: false,

        // Filter State
        filters: {
            timePeriod: 'all',
            priorities: [],
            continents: [],
            eventTypes: []
        },

        // Reference Data
        countryToContinentMap: {
            // Europa
            'DE': 'EU', 'AT': 'EU', 'CH': 'EU', 'FR': 'EU', 'IT': 'EU', 'ES': 'EU', 'PT': 'EU', 'GB': 'EU', 'IE': 'EU',
            'NL': 'EU', 'BE': 'EU', 'LU': 'EU', 'PL': 'EU', 'CZ': 'EU', 'SK': 'EU', 'HU': 'EU', 'RO': 'EU', 'BG': 'EU',
            'GR': 'EU', 'HR': 'EU', 'SI': 'EU', 'RS': 'EU', 'BA': 'EU', 'ME': 'EU', 'MK': 'EU', 'AL': 'EU', 'XK': 'EU',
            'SE': 'EU', 'NO': 'EU', 'FI': 'EU', 'DK': 'EU', 'IS': 'EU', 'EE': 'EU', 'LV': 'EU', 'LT': 'EU',
            'UA': 'EU', 'BY': 'EU', 'MD': 'EU', 'RU': 'EU', 'MT': 'EU', 'CY': 'EU', 'TR': 'EU',
            // Asien
            'CN': 'AS', 'JP': 'AS', 'KR': 'AS', 'KP': 'AS', 'MN': 'AS', 'TW': 'AS', 'HK': 'AS', 'MO': 'AS',
            'IN': 'AS', 'PK': 'AS', 'BD': 'AS', 'LK': 'AS', 'NP': 'AS', 'BT': 'AS', 'MV': 'AS', 'AF': 'AS',
            'TH': 'AS', 'VN': 'AS', 'MY': 'AS', 'SG': 'AS', 'ID': 'AS', 'PH': 'AS', 'MM': 'AS', 'KH': 'AS', 'LA': 'AS', 'BN': 'AS', 'TL': 'AS',
            'SA': 'AS', 'AE': 'AS', 'QA': 'AS', 'KW': 'AS', 'BH': 'AS', 'OM': 'AS', 'YE': 'AS', 'IR': 'AS', 'IQ': 'AS', 'SY': 'AS', 'JO': 'AS', 'LB': 'AS', 'IL': 'AS', 'PS': 'AS',
            'KZ': 'AS', 'UZ': 'AS', 'TM': 'AS', 'TJ': 'AS', 'KG': 'AS', 'AZ': 'AS', 'GE': 'AS', 'AM': 'AS',
            // Afrika
            'EG': 'AF', 'LY': 'AF', 'TN': 'AF', 'DZ': 'AF', 'MA': 'AF', 'SD': 'AF', 'SS': 'AF', 'ET': 'AF', 'ER': 'AF', 'DJ': 'AF', 'SO': 'AF',
            'KE': 'AF', 'UG': 'AF', 'TZ': 'AF', 'RW': 'AF', 'BI': 'AF', 'CD': 'AF', 'CG': 'AF', 'GA': 'AF', 'GQ': 'AF', 'CM': 'AF', 'CF': 'AF', 'TD': 'AF',
            'NG': 'AF', 'GH': 'AF', 'CI': 'AF', 'SN': 'AF', 'ML': 'AF', 'BF': 'AF', 'NE': 'AF', 'MR': 'AF', 'GM': 'AF', 'GW': 'AF', 'GN': 'AF', 'SL': 'AF', 'LR': 'AF', 'TG': 'AF', 'BJ': 'AF',
            'ZA': 'AF', 'NA': 'AF', 'BW': 'AF', 'ZW': 'AF', 'ZM': 'AF', 'MW': 'AF', 'MZ': 'AF', 'AO': 'AF', 'SZ': 'AF', 'LS': 'AF', 'MG': 'AF', 'MU': 'AF', 'SC': 'AF', 'KM': 'AF', 'RE': 'AF',
            // Nordamerika
            'US': 'NA', 'CA': 'NA', 'MX': 'NA', 'GT': 'NA', 'BZ': 'NA', 'HN': 'NA', 'SV': 'NA', 'NI': 'NA', 'CR': 'NA', 'PA': 'NA',
            'CU': 'NA', 'JM': 'NA', 'HT': 'NA', 'DO': 'NA', 'PR': 'NA', 'BS': 'NA', 'BB': 'NA', 'TT': 'NA',
            // Südamerika
            'BR': 'SA', 'AR': 'SA', 'CL': 'SA', 'CO': 'SA', 'PE': 'SA', 'VE': 'SA', 'EC': 'SA', 'BO': 'SA', 'PY': 'SA', 'UY': 'SA', 'GY': 'SA', 'SR': 'SA', 'GF': 'SA',
            // Ozeanien
            'AU': 'OC', 'NZ': 'OC', 'PG': 'OC', 'FJ': 'OC', 'SB': 'OC', 'VU': 'OC', 'NC': 'OC', 'PF': 'OC', 'WS': 'OC', 'TO': 'OC', 'FM': 'OC', 'GU': 'OC'
        },
        continents: [
            { code: 'EU', name: 'Europa' },
            { code: 'AS', name: 'Asien' },
            { code: 'AF', name: 'Afrika' },
            { code: 'NA', name: 'Nordamerika' },
            { code: 'SA', name: 'Südamerika' },
            { code: 'OC', name: 'Ozeanien' }
        ],
        eventTypes: [],

        // Computed
        get activeFiltersCount() {
            let count = 0;
            if (this.filters.timePeriod !== 'all') count++;
            count += this.filters.priorities.length;
            count += this.filters.continents.length;
            count += this.filters.eventTypes.length;
            return count;
        },

        async init() {
            this.parseUrlFilters();
            this.initMap();
            await Promise.all([
                this.loadEvents(),
                this.loadEventTypes()
            ]);
            this.lastUpdate = new Date().toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
        },

        parseUrlFilters() {
            const params = new URLSearchParams(window.location.search);

            // Time Period: ?timePeriod=future
            const timePeriod = params.get('timePeriod');
            if (timePeriod && ['all', 'future', 'today', 'week', 'month'].includes(timePeriod)) {
                this.filters.timePeriod = timePeriod;
            }

            // Priorities: ?priorities=high,medium
            const priorities = params.get('priorities');
            if (priorities) {
                const validPriorities = ['critical', 'high', 'medium', 'low', 'info'];
                this.filters.priorities = priorities.split(',')
                    .map(p => p.trim())
                    .filter(p => validPriorities.includes(p));
            }

            // Continents: ?continents=EU,AS
            const continents = params.get('continents');
            if (continents) {
                const validContinents = ['EU', 'AS', 'AF', 'NA', 'SA', 'OC'];
                this.filters.continents = continents.split(',')
                    .map(c => c.trim().toUpperCase())
                    .filter(c => validContinents.includes(c));
            }

            // Event Types: ?eventTypes=1,2,3
            const eventTypes = params.get('eventTypes');
            if (eventTypes) {
                this.filters.eventTypes = eventTypes.split(',')
                    .map(id => parseInt(id.trim()))
                    .filter(id => !isNaN(id));
            }
        },

        initMap() {
            this.map = L.map('embed-dashboard-map', {
                center: [38.1, 13.4], // Italy/Palermo
                zoom: 2,
                minZoom: 2,
                maxZoom: 18
            });

            L.tileLayer('https://tile.openstreetmap.de/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(this.map);

            this.markerCluster = L.markerClusterGroup({
                chunkedLoading: true,
                maxClusterRadius: 50
            });

            this.map.addLayer(this.markerCluster);
        },

        centerMap() {
            // Center on Italy/Palermo with world view
            this.map.setView([38.1, 13.4], 2);
        },

        async loadEvents() {
            this.loading = true;
            try {
                const response = await fetch('/api/custom-events/dashboard-events?limit=100');
                const data = await response.json();
                this.events = data.data?.events || [];
                this.applyFilters();
            } catch (error) {
                console.error('Error loading events:', error);
            }
            this.loading = false;
        },

        async loadEventTypes() {
            try {
                const response = await fetch('/api/custom-events/event-types');
                const data = await response.json();
                this.eventTypes = data.data || data.event_types || [];
            } catch (err) {
                console.error('Error loading event types:', err);
            }
        },

        applyFilters() {
            let filtered = [...this.events];

            // Time Period
            if (this.filters.timePeriod !== 'all') {
                const now = new Date();
                now.setHours(0, 0, 0, 0);
                filtered = filtered.filter(e => {
                    const eventDate = new Date(e.start_date || e.created_at);
                    switch (this.filters.timePeriod) {
                        case 'future':
                            const eventDateOnly = new Date(eventDate);
                            eventDateOnly.setHours(0, 0, 0, 0);
                            return eventDateOnly >= now;
                        case 'today':
                            return eventDate.toDateString() === now.toDateString();
                        case 'week':
                            const weekFromNow = new Date(now);
                            weekFromNow.setDate(weekFromNow.getDate() + 7);
                            return eventDate >= now && eventDate <= weekFromNow;
                        case 'month':
                            const monthFromNow = new Date(now);
                            monthFromNow.setMonth(monthFromNow.getMonth() + 1);
                            return eventDate >= now && eventDate <= monthFromNow;
                        default:
                            return true;
                    }
                });
            }

            // Priority
            if (this.filters.priorities.length > 0) {
                filtered = filtered.filter(e => this.filters.priorities.includes(e.priority));
            }

            // Continent
            if (this.filters.continents.length > 0) {
                filtered = filtered.filter(e =>
                    (e.countries || []).some(c => {
                        const continentCode = this.countryToContinentMap[c.iso_code];
                        return this.filters.continents.includes(continentCode);
                    })
                );
            }

            // Event Type
            if (this.filters.eventTypes.length > 0) {
                filtered = filtered.filter(e => {
                    if (e.event_types && e.event_types.length > 0) {
                        return e.event_types.some(et => this.filters.eventTypes.includes(et.id));
                    }
                    return e.event_type_id && this.filters.eventTypes.includes(e.event_type_id);
                });
            }

            this.filteredEvents = filtered;
            this.updateMarkers();
        },

        updateMarkers() {
            if (!this.markerCluster) return;
            this.markerCluster.clearLayers();

            this.filteredEvents.forEach(event => {
                if (event.countries && event.countries.length > 0) {
                    event.countries.forEach(country => {
                        const lat = parseFloat(country.latitude);
                        const lng = parseFloat(country.longitude);

                        if (isNaN(lat) || isNaN(lng)) return;

                        const marker = L.marker([lat, lng], {
                            icon: this.createIcon(event)
                        });

                        marker.on('click', () => this.selectEvent(event, lat, lng));
                        this.markerCluster.addLayer(marker);
                    });
                } else {
                    const lat = parseFloat(event.latitude);
                    const lng = parseFloat(event.longitude);

                    if (isNaN(lat) || isNaN(lng)) return;

                    const marker = L.marker([lat, lng], {
                        icon: this.createIcon(event)
                    });

                    marker.on('click', () => this.selectEvent(event, lat, lng));
                    this.markerCluster.addLayer(marker);
                }
            });
        },

        selectEvent(event, lat = null, lng = null) {
            this.selectedEvent = event;

            if (!lat || !lng) {
                if (event.countries && event.countries.length > 0) {
                    lat = parseFloat(event.countries[0].latitude);
                    lng = parseFloat(event.countries[0].longitude);
                } else {
                    lat = parseFloat(event.latitude);
                    lng = parseFloat(event.longitude);
                }
            }

            if (!isNaN(lat) && !isNaN(lng)) {
                this.map.setView([lat, lng], 6);
            }
        },

        // Toggle functions
        togglePriority(priority) {
            const idx = this.filters.priorities.indexOf(priority);
            if (idx > -1) {
                this.filters.priorities.splice(idx, 1);
            } else {
                this.filters.priorities.push(priority);
            }
        },

        toggleContinent(code) {
            const idx = this.filters.continents.indexOf(code);
            if (idx > -1) {
                this.filters.continents.splice(idx, 1);
            } else {
                this.filters.continents.push(code);
            }
        },

        toggleEventType(id) {
            const idx = this.filters.eventTypes.indexOf(id);
            if (idx > -1) {
                this.filters.eventTypes.splice(idx, 1);
            } else {
                this.filters.eventTypes.push(id);
            }
        },

        resetFilters() {
            this.filters = {
                timePeriod: 'all',
                priorities: [],
                continents: [],
                eventTypes: []
            };
            this.applyFilters();
        },

        createIcon(event) {
            const priorityColors = {
                'info': '#0066cc',
                'low': '#0fb67f',
                'medium': '#e6a50a',
                'high': '#ff0000',
                'critical': '#dc2626'
            };
            const markerColor = event.marker_color || priorityColors[event.priority] || '#e6a50a';
            const iconClass = this.getEventIcon(event);
            const iconSize = 28;

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
                    cursor: pointer;
                ">
                    <i class="${iconClass}" style="color: #FFFFFF; font-size: ${iconSize * 0.5}px; text-shadow: 0 1px 2px rgba(0,0,0,0.3);"></i>
                </div>
            `;

            return L.divIcon({
                className: 'custom-marker',
                html: iconHtml,
                iconSize: [iconSize, iconSize],
                iconAnchor: [iconSize / 2, iconSize / 2]
            });
        },

        getEventIcon(event) {
            if (event.marker_icon) {
                if (event.marker_icon.startsWith('fa-')) {
                    return event.marker_icon.includes('fa-solid') ? event.marker_icon : `fa-solid ${event.marker_icon}`;
                }
                return event.marker_icon;
            }

            if (event.event_type?.icon) {
                return event.event_type.icon;
            }

            const fallbackIcons = {
                'exercise': 'fa-solid fa-dumbbell',
                'earthquake': 'fa-solid fa-house-crack',
                'flood': 'fa-solid fa-water',
                'volcano': 'fa-solid fa-volcano',
                'storm': 'fa-solid fa-wind',
                'cyclone': 'fa-solid fa-hurricane',
                'drought': 'fa-solid fa-sun',
                'wildfire': 'fa-solid fa-fire',
                'other': 'fa-solid fa-location-pin'
            };

            return fallbackIcons[event.event_type] || 'fa-solid fa-location-pin';
        },

        getCountryNames(event) {
            if (!event.countries?.length) return 'Global';
            return event.countries.slice(0, 2).map(c => c.name_de || c.name).join(', ') +
                   (event.countries.length > 2 ? ` +${event.countries.length - 2}` : '');
        },

        getAllCountryNames(event) {
            if (!event.countries?.length) return 'Global';
            return event.countries.map(c => c.name_de || c.name).join(', ');
        },

        getEventTypesDisplay(event) {
            if (event.event_types && event.event_types.length > 0) {
                return event.event_types.map(t => typeof t === 'string' ? t : t.name).join(', ');
            }
            if (event.event_type_name) {
                return event.event_type_name;
            }
            return 'Ereignis';
        },

        getMarkerColor(event) {
            const priorityColors = {
                'info': '#0066cc',
                'low': '#0fb67f',
                'medium': '#e6a50a',
                'high': '#ff0000',
                'critical': '#dc2626'
            };
            return event.marker_color || priorityColors[event.priority] || '#e6a50a';
        },

        getPriorityBadgeColor(priority) {
            const colors = {
                critical: 'bg-red-100 text-red-700',
                high: 'bg-orange-100 text-orange-700',
                medium: 'bg-yellow-100 text-yellow-700',
                low: 'bg-green-100 text-green-700',
                info: 'bg-blue-100 text-blue-700'
            };
            return colors[priority] || colors.info;
        },

        getPriorityLabel(priority) {
            const labels = { critical: 'Kritisch', high: 'Hoch', medium: 'Mittel', low: 'Niedrig', info: 'Information' };
            return labels[priority] || 'Information';
        },

        getTimePeriodLabel(period) {
            const labels = {
                'all': 'Aktuell',
                'future': 'Zukünftig',
                'today': 'Heute',
                'week': 'Diese Woche',
                'month': 'Dieser Monat'
            };
            return labels[period] || period;
        },

        getContinentName(code) {
            const continent = this.continents.find(c => c.code === code);
            return continent ? continent.name : code;
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
    };
}
</script>
@endpush
@endsection
