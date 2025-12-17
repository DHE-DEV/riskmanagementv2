@extends('layouts.embed')

@section('title', 'Ereignisse - Global Travel Monitor')

@push('styles')
<style>
    .filter-chip {
        transition: all 0.2s ease;
    }
    .filter-chip.active {
        background-color: #1976D2;
        color: white;
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush

@section('content')
<div x-data="embedEventsApp()" x-init="init()" class="h-full flex flex-col bg-gray-50">
    <!-- Filter Bar -->
    <div class="bg-white border-b border-gray-200 px-4 py-3 flex-shrink-0">
        <div class="flex items-center justify-between gap-4">
            <!-- Search -->
            <div class="relative flex-1 max-w-xs">
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.300ms="applyFilters()"
                       placeholder="Suchen..."
                       class="w-full pl-8 pr-4 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            </div>

            <!-- Filter Button -->
            <button @click="filterModalOpen = true" class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700 transition-colors relative">
                <i class="fas fa-filter"></i>
                <span>Filter</span>
                <span x-show="activeFiltersCount > 0"
                      x-text="activeFiltersCount"
                      class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"></span>
            </button>
        </div>

        <!-- Active Filter Chips -->
        <div x-show="activeFiltersCount > 0" class="flex flex-wrap gap-2 mt-3">
            <template x-if="filters.timePeriod !== 'all'">
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                    <span x-text="getTimePeriodLabel(filters.timePeriod)"></span>
                    <button @click="filters.timePeriod = 'all'; applyFilters()" class="hover:text-blue-600">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            </template>
            <template x-for="priority in filters.priorities" :key="priority">
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                    <span x-text="getPriorityLabel(priority)"></span>
                    <button @click="togglePriority(priority)" class="hover:text-blue-600">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            </template>
            <template x-for="code in filters.continents" :key="code">
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                    <span x-text="getContinentName(code)"></span>
                    <button @click="toggleContinent(code)" class="hover:text-blue-600">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            </template>
            <template x-for="typeId in filters.eventTypes" :key="typeId">
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                    <span x-text="getEventTypeName(typeId)"></span>
                    <button @click="toggleEventType(typeId)" class="hover:text-blue-600">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            </template>
            <button @click="resetFilters()" class="text-xs text-gray-500 hover:text-gray-700 underline">
                Alle zurücksetzen
            </button>
        </div>
    </div>

    <!-- Events List -->
    <div class="flex-1 overflow-y-auto">
        <!-- Loading State -->
        <template x-if="loading">
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600">Ereignisse werden geladen...</span>
            </div>
        </template>

        <!-- Events -->
        <template x-if="!loading">
            <div>
                <!-- Event Count -->
                <div class="px-4 py-2 text-sm text-gray-500 bg-gray-50 border-b">
                    <span x-text="filteredEvents.length"></span> Ereignisse
                </div>

                <div class="divide-y divide-gray-200">
                    <template x-for="event in filteredEvents" :key="event.id">
                        <div class="bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                             @click="openEvent(event)">
                            <div class="px-4 py-3">
                                <div class="flex items-start gap-3">
                                    <!-- Priority Indicator -->
                                    <div class="flex-shrink-0 mt-1">
                                        <span :style="'background-color: ' + getMarkerColor(event)"
                                              class="inline-flex items-center justify-center w-8 h-8 rounded-full shadow-sm border-2 border-white">
                                            <i :class="getEventIcon(event)" class="text-sm" style="color: #FFFFFF !important;"></i>
                                        </span>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span :class="getPriorityBadgeColor(event.priority)"
                                                  class="px-2 py-0.5 text-xs font-medium rounded"
                                                  x-text="getPriorityLabel(event.priority)"></span>
                                            <span class="text-xs text-gray-500" x-text="formatDate(event.created_at)"></span>
                                        </div>
                                        <h3 class="text-sm font-semibold text-gray-900 line-clamp-2" x-text="event.title"></h3>
                                        <p class="text-xs text-gray-500 mt-1" x-text="getCountryNames(event)"></p>
                                    </div>

                                    <!-- Arrow -->
                                    <div class="flex-shrink-0 text-gray-400">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- No Results -->
                    <template x-if="filteredEvents.length === 0 && !loading">
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>Keine Ereignisse gefunden</p>
                            <p class="text-sm mt-1">Versuchen Sie andere Filtereinstellungen</p>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Filter Modal -->
    <div x-show="filterModalOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black bg-opacity-50"
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
                        <button @click="togglePriority('critical')"
                                :class="{ 'bg-red-600 text-white': filters.priorities.includes('critical'), 'bg-red-100 text-red-700': !filters.priorities.includes('critical') }"
                                class="px-3 py-2 rounded-lg text-sm">Kritisch</button>
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

                <!-- Country Filter -->
                <div>
                    <h3 class="font-medium text-gray-900 mb-3">Land</h3>
                    <input type="text"
                           x-model="countrySearch"
                           @input="filterCountries()"
                           placeholder="Land suchen..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <div x-show="filteredCountriesList.length > 0" class="mt-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg">
                        <template x-for="country in filteredCountriesList" :key="country.id">
                            <button @click="toggleCountry(country.id)"
                                    class="w-full flex items-center justify-between px-3 py-2 hover:bg-gray-50 text-left">
                                <span class="flex items-center gap-2">
                                    <span x-text="country.flag_emoji || getCountryFlag(country.iso_code)"></span>
                                    <span x-text="country.name_de || country.name" class="text-sm"></span>
                                </span>
                                <i x-show="filters.countries.includes(country.id)" class="fas fa-check text-blue-600"></i>
                            </button>
                        </template>
                    </div>
                    <!-- Selected Countries -->
                    <div x-show="filters.countries.length > 0" class="mt-2 flex flex-wrap gap-2">
                        <template x-for="countryId in filters.countries" :key="countryId">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm flex items-center gap-1">
                                <span x-text="getCountryName(countryId)"></span>
                                <button @click="toggleCountry(countryId)" class="hover:text-blue-600">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </span>
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

    <!-- Event Detail Modal -->
    <div x-show="selectedEvent"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
         @click.self="selectedEvent = null"
         style="display: none;">

        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col"
             x-show="selectedEvent"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <span x-show="selectedEvent" :class="getPriorityBadgeColor(selectedEvent?.priority)"
                          class="px-2 py-1 text-xs font-medium rounded"
                          x-text="getPriorityLabel(selectedEvent?.priority)"></span>
                    <span class="text-sm text-gray-500" x-text="formatDate(selectedEvent?.created_at)"></span>
                </div>
                <button @click="selectedEvent = null" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <h2 class="text-xl font-bold text-gray-900 mb-4" x-text="selectedEvent?.title"></h2>

                <!-- Countries -->
                <div x-show="selectedEvent?.countries?.length" class="mb-4">
                    <div class="flex flex-wrap gap-2">
                        <template x-for="country in selectedEvent?.countries || []" :key="country.id">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 rounded text-sm">
                                <span x-text="country.flag_emoji"></span>
                                <span x-text="country.name_de || country.name"></span>
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Description -->
                <div class="prose prose-sm max-w-none text-gray-700" x-html="selectedEvent?.description || selectedEvent?.popup_content"></div>

                <!-- Source Link -->
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
</div>

<script>
function embedEventsApp() {
    return {
        events: [],
        filteredEvents: [],
        loading: true,
        searchQuery: '',
        selectedEvent: null,
        filterModalOpen: false,

        // Filter State
        filters: {
            timePeriod: 'all',
            priorities: [],
            continents: [],
            eventTypes: [],
            countries: []
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
        countries: [],
        countrySearch: '',
        filteredCountriesList: [],

        // Computed
        get activeFiltersCount() {
            let count = 0;
            if (this.filters.timePeriod !== 'all') count++;
            count += this.filters.priorities.length;
            count += this.filters.continents.length;
            count += this.filters.eventTypes.length;
            count += this.filters.countries.length;
            return count;
        },

        // Initialize
        async init() {
            await Promise.all([
                this.loadEvents(),
                this.loadEventTypes(),
                this.loadCountries()
            ]);
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
                this.events = [];
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

        async loadCountries() {
            try {
                const response = await fetch('/api/countries');
                const data = await response.json();
                this.countries = data.countries || data || [];
            } catch (err) {
                console.error('Error loading countries:', err);
            }
        },

        applyFilters() {
            let filtered = [...this.events];

            // Search
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(e =>
                    e.title?.toLowerCase().includes(query) ||
                    e.description?.toLowerCase().includes(query) ||
                    (e.countries || []).some(c => (c.name_de || c.name || '').toLowerCase().includes(query))
                );
            }

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

                if (this.filters.timePeriod === 'future') {
                    filtered.sort((a, b) => {
                        const dateA = new Date(a.start_date || a.created_at);
                        const dateB = new Date(b.start_date || b.created_at);
                        return dateA - dateB;
                    });
                }
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

            // Countries
            if (this.filters.countries.length > 0) {
                filtered = filtered.filter(e =>
                    (e.countries || []).some(c =>
                        this.filters.countries.includes(c.id)
                    )
                );
            }

            this.filteredEvents = filtered;
        },

        filterCountries() {
            if (!this.countrySearch) {
                this.filteredCountriesList = this.countries.slice(0, 10);
            } else {
                const query = this.countrySearch.toLowerCase();
                this.filteredCountriesList = this.countries
                    .filter(c => (c.name_de || c.name || '').toLowerCase().includes(query))
                    .slice(0, 10);
            }
        },

        togglePriority(priority) {
            const idx = this.filters.priorities.indexOf(priority);
            if (idx > -1) {
                this.filters.priorities.splice(idx, 1);
            } else {
                this.filters.priorities.push(priority);
            }
            this.applyFilters();
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

        toggleCountry(id) {
            const idx = this.filters.countries.indexOf(id);
            if (idx > -1) {
                this.filters.countries.splice(idx, 1);
            } else {
                this.filters.countries.push(id);
            }
        },

        resetFilters() {
            this.filters = {
                timePeriod: 'all',
                priorities: [],
                continents: [],
                eventTypes: [],
                countries: []
            };
            this.applyFilters();
        },

        openEvent(event) {
            this.selectedEvent = event;
            this.trackClick(event.id);
        },

        async trackClick(eventId) {
            try {
                await fetch('/api/custom-events/track-click', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        event_id: eventId,
                        click_type: 'embed_view'
                    })
                });
            } catch (e) {}
        },

        // Helper Methods
        getCountryNames(event) {
            if (!event.countries?.length) return 'Global';
            return event.countries.map(c => c.name_de || c.name).join(', ');
        },

        getCountryName(countryId) {
            const country = this.countries.find(c => c.id === countryId);
            return country ? (country.name_de || country.name) : '';
        },

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
            const labels = {
                critical: 'Kritisch',
                high: 'Hoch',
                medium: 'Mittel',
                low: 'Niedrig',
                info: 'Info'
            };
            return labels[priority] || 'Info';
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
@endsection
