@extends('layouts.embed')

@section('title', 'Karte - Global Travel Monitor')
@section('badge_position', 'top-right')

@push('styles')
<style>
    #embed-map {
        width: 100%;
        height: 100%;
    }

    .custom-marker {
        background: none;
        border: none;
    }

    .custom-marker i {
        font-size: 24px;
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

    .event-popup {
        min-width: 280px;
        max-width: 320px;
    }

    .event-popup-header {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
    }

    .event-popup-content {
        padding: 12px 16px;
        max-height: 200px;
        overflow-y: auto;
    }

    .event-popup-footer {
        padding: 8px 16px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
    }

    .marker-cluster-small {
        background-color: rgba(181, 226, 140, 0.6);
    }
    .marker-cluster-small div {
        background-color: rgba(110, 204, 57, 0.6);
    }
    .marker-cluster-medium {
        background-color: rgba(241, 211, 87, 0.6);
    }
    .marker-cluster-medium div {
        background-color: rgba(240, 194, 12, 0.6);
    }
    .marker-cluster-large {
        background-color: rgba(253, 156, 115, 0.6);
    }
    .marker-cluster-large div {
        background-color: rgba(241, 128, 23, 0.6);
    }
</style>
@endpush

@section('content')
<div x-data="embedMapApp()" x-init="init()" class="h-full w-full relative">
    <!-- Map Container -->
    <div id="embed-map" class="h-full w-full"></div>

    <!-- Filter Button (Floating - Top Right, below Powered by badge) -->
    <div class="absolute top-16 right-4 z-[1000]">
        <button @click="filterModalOpen = true"
                class="flex items-center gap-2 px-4 py-2 bg-white rounded-lg shadow-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors relative">
            <i class="fas fa-filter"></i>
            <span>Filter</span>
            <span x-show="activeFiltersCount > 0"
                  x-text="activeFiltersCount"
                  class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"></span>
        </button>

        <!-- Active Filter Pills -->
        <div x-show="activeFiltersCount > 0" class="mt-2 flex flex-wrap gap-1 max-w-xs justify-end">
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

    <!-- Loading Overlay -->
    <div x-show="loading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-[1001]">
        <div class="flex items-center gap-3">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="text-gray-600">Karte wird geladen...</span>
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

    <!-- Filter Modal -->
    <div x-show="filterModalOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[1002] flex items-end sm:items-center justify-center bg-black bg-opacity-50"
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
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
function embedMapApp() {
    return {
        map: null,
        markerCluster: null,
        events: [],
        filteredEvents: [],
        loading: true,
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
            'CU': 'NA', 'JM': 'NA', 'HT': 'NA', 'DO': 'NA', 'PR': 'NA', 'BS': 'NA', 'TT': 'NA', 'BB': 'NA', 'LC': 'NA', 'VC': 'NA', 'GD': 'NA', 'AG': 'NA', 'DM': 'NA', 'KN': 'NA',
            // Südamerika
            'BR': 'SA', 'AR': 'SA', 'CL': 'SA', 'PE': 'SA', 'CO': 'SA', 'VE': 'SA', 'EC': 'SA', 'BO': 'SA', 'PY': 'SA', 'UY': 'SA', 'GY': 'SA', 'SR': 'SA', 'GF': 'SA',
            // Ozeanien
            'AU': 'OC', 'NZ': 'OC', 'PG': 'OC', 'FJ': 'OC', 'SB': 'OC', 'VU': 'OC', 'NC': 'OC', 'PF': 'OC', 'WS': 'OC', 'TO': 'OC', 'KI': 'OC', 'MH': 'OC', 'FM': 'OC', 'PW': 'OC', 'NR': 'OC', 'TV': 'OC'
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
            this.initMap();
            await Promise.all([
                this.loadEvents(),
                this.loadEventTypes()
            ]);
        },

        initMap() {
            this.map = L.map('embed-map', {
                center: [30, 10],
                zoom: 2,
                minZoom: 2,
                maxZoom: 18,
                worldCopyJump: true
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            this.markerCluster = L.markerClusterGroup({
                chunkedLoading: true,
                maxClusterRadius: 50,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true
            });

            this.map.addLayer(this.markerCluster);
        },

        async loadEvents() {
            this.loading = true;
            try {
                const response = await fetch('/api/custom-events/map-events');
                const data = await response.json();
                this.events = data.data || [];
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

            // Event Types
            if (this.filters.eventTypes.length > 0) {
                filtered = filtered.filter(e => {
                    const eventTypesList = e.event_types || [];
                    return eventTypesList.some(t => {
                        const typeName = typeof t === 'string' ? t : t.name;
                        const typeId = typeof t === 'object' ? t.id : null;
                        return this.filters.eventTypes.includes(typeId) ||
                               this.eventTypes.some(et =>
                                   this.filters.eventTypes.includes(et.id) && et.name === typeName
                               );
                    });
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

                        marker.bindPopup(this.createPopup(event, country), {
                            maxWidth: 320,
                            className: 'event-popup-wrapper'
                        });

                        this.markerCluster.addLayer(marker);
                    });
                } else {
                    const lat = parseFloat(event.latitude);
                    const lng = parseFloat(event.longitude);

                    if (isNaN(lat) || isNaN(lng)) return;

                    const marker = L.marker([lat, lng], {
                        icon: this.createIcon(event)
                    });

                    marker.bindPopup(this.createPopup(event), {
                        maxWidth: 320,
                        className: 'event-popup-wrapper'
                    });

                    this.markerCluster.addLayer(marker);
                }
            });
        },

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

        // Helper Methods
        getCountryFlag(isoCode) {
            if (!isoCode || isoCode.length !== 2) return '';
            const codePoints = isoCode.toUpperCase().split('').map(char => 127397 + char.charCodeAt(0));
            return String.fromCodePoint(...codePoints);
        },

        getContinentName(code) {
            const continent = this.continents.find(c => c.code === code);
            return continent ? continent.name : code;
        },

        getEventTypeName(typeId) {
            const eventType = this.eventTypes.find(t => t.id === typeId);
            return eventType ? eventType.name : '';
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

        getPriorityLabel(priority) {
            const labels = {
                critical: 'Kritisch',
                high: 'Hoch',
                medium: 'Mittel',
                low: 'Niedrig',
                info: 'Info'
            };
            return labels[priority] || 'Info';
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
                iconAnchor: [iconSize / 2, iconSize / 2],
                popupAnchor: [0, -iconSize / 2]
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

        createPopup(event, country = null) {
            const priorityLabels = {
                critical: 'Kritisch',
                high: 'Hoch',
                medium: 'Mittel',
                low: 'Niedrig',
                info: 'Info'
            };

            const priorityColors = {
                critical: 'bg-red-100 text-red-700',
                high: 'bg-orange-100 text-orange-700',
                medium: 'bg-yellow-100 text-yellow-700',
                low: 'bg-green-100 text-green-700',
                info: 'bg-blue-100 text-blue-700'
            };

            const countryName = country ? (country.name || country.name_de) :
                (event.countries?.map(c => c.name || c.name_de).join(', ') || '');
            const description = event.popup_content || event.description || '';
            const truncatedDesc = description.length > 200 ? description.substring(0, 200) + '...' : description;

            return `
                <div class="event-popup">
                    <div class="event-popup-header">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-xs font-medium rounded ${priorityColors[event.priority] || priorityColors.info}">
                                ${priorityLabels[event.priority] || 'Info'}
                            </span>
                            <span class="text-xs text-gray-500">${this.formatDate(event.created_at)}</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 text-sm">${event.title}</h3>
                        ${countryName ? `<p class="text-xs text-gray-500 mt-1">${countryName}</p>` : ''}
                    </div>
                    <div class="event-popup-content">
                        <div class="text-sm text-gray-700">${truncatedDesc}</div>
                    </div>
                    ${event.source_url ? `
                        <div class="event-popup-footer">
                            <a href="${event.source_url}" target="_blank" rel="noopener"
                               class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                <i class="fas fa-external-link-alt text-xs"></i>
                                Zur Quelle
                            </a>
                        </div>
                    ` : ''}
                </div>
            `;
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }
    };
}
</script>
@endpush
@endsection
