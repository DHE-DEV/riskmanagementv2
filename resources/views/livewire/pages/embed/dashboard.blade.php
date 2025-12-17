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
</style>
@endpush

@section('content')
<div x-data="embedDashboardApp()" x-init="init()" class="h-full flex flex-col lg:flex-row">
    <!-- Sidebar with Events -->
    <div class="w-full lg:w-80 xl:w-96 flex-shrink-0 bg-white border-b lg:border-b-0 lg:border-r border-gray-200 flex flex-col h-1/2 lg:h-full">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 flex-shrink-0">
            <h2 class="font-bold text-gray-800">Aktuelle Ereignisse</h2>
            <div class="flex gap-1 mt-2">
                <button @click="priorityFilter = 'all'; updateMarkers()"
                        :class="priorityFilter === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-2 py-1 rounded text-xs font-medium">Alle</button>
                <button @click="priorityFilter = 'critical'; updateMarkers()"
                        :class="priorityFilter === 'critical' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700'"
                        class="px-2 py-1 rounded text-xs font-medium">Kritisch</button>
                <button @click="priorityFilter = 'high'; updateMarkers()"
                        :class="priorityFilter === 'high' ? 'bg-orange-500 text-white' : 'bg-orange-50 text-orange-700'"
                        class="px-2 py-1 rounded text-xs font-medium">Hoch</button>
            </div>
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
                    <template x-for="event in filteredEvents.slice(0, 20)" :key="event.id">
                        <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors"
                             @click="selectEvent(event)">
                            <div class="flex items-start gap-2">
                                <span :class="getPriorityColor(event.priority)"
                                      class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center mt-0.5">
                                    <i :class="getEventIcon(event)" class="text-xs"></i>
                                </span>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-medium text-gray-900 line-clamp-2" x-text="event.title"></h3>
                                    <p class="text-xs text-gray-500 mt-1" x-text="getCountryNames(event)"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Stats Footer -->
        <div class="px-4 py-2 bg-gray-50 border-t border-gray-200 flex-shrink-0">
            <div class="flex justify-between text-xs text-gray-600">
                <span><strong x-text="filteredEvents.length"></strong> Ereignisse</span>
                <span class="text-gray-400">Letzte Aktualisierung: <span x-text="lastUpdate"></span></span>
            </div>
        </div>
    </div>

    <!-- Map Area -->
    <div class="flex-1 relative h-1/2 lg:h-full">
        <div id="embed-dashboard-map" class="w-full h-full"></div>

        <!-- Loading Overlay -->
        <div x-show="loading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-[1001]">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
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
        loading: true,
        priorityFilter: '{{ request()->query("filter", "all") }}',
        selectedEvent: null,
        lastUpdate: '',

        async init() {
            await this.initMap();
            await this.loadEvents();
            this.lastUpdate = new Date().toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
        },

        initMap() {
            this.map = L.map('embed-dashboard-map', {
                center: [30, 10],
                zoom: 2,
                minZoom: 2,
                maxZoom: 18
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OSM'
            }).addTo(this.map);

            this.markerCluster = L.markerClusterGroup({
                chunkedLoading: true,
                maxClusterRadius: 50
            });

            this.map.addLayer(this.markerCluster);
        },

        async loadEvents() {
            this.loading = true;
            try {
                const response = await fetch('/api/custom-events/dashboard-events?limit=100');
                const data = await response.json();
                this.events = data.data?.events || [];
                this.updateMarkers();
            } catch (error) {
                console.error('Error loading events:', error);
            }
            this.loading = false;
        },

        get filteredEvents() {
            if (this.priorityFilter === 'all') return this.events;
            return this.events.filter(e => e.priority === this.priorityFilter);
        },

        updateMarkers() {
            if (!this.markerCluster) return;
            this.markerCluster.clearLayers();

            this.filteredEvents.forEach(event => {
                // Wenn Event Länder mit Koordinaten hat, erstelle Marker für jedes Land
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
                    // Fallback: Event-eigene Koordinaten
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

            // Verwende übergebene Koordinaten oder erste aus countries Array
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

        createIcon(event) {
            // Verwende marker_color vom Event oder Fallback auf Priority-Farbe
            const priorityColors = {
                critical: '#ef4444',
                high: '#f97316',
                medium: '#eab308',
                low: '#22c55e',
                info: '#3b82f6'
            };
            const markerColor = event.marker_color || priorityColors[event.priority] || priorityColors.info;

            // Icon-Logik wie auf der Hauptseite
            const iconClass = this.getEventIcon(event);
            const iconSize = 28;

            // Einheitliches Kreis-Design wie auf der Hauptseite
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
            // Wenn marker_icon vorhanden, verwende es
            if (event.marker_icon) {
                if (event.marker_icon.startsWith('fa-')) {
                    return event.marker_icon.includes('fa-solid') ? event.marker_icon : `fa-solid ${event.marker_icon}`;
                }
                return event.marker_icon;
            }

            // Fallback auf event_type Icon
            if (event.event_type?.icon) {
                return event.event_type.icon;
            }

            // Fallback auf Event-Typ basierte Icons
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

        getPriorityColor(priority) {
            const colors = {
                critical: 'bg-red-100 text-red-600',
                high: 'bg-orange-100 text-orange-600',
                medium: 'bg-yellow-100 text-yellow-600',
                low: 'bg-green-100 text-green-600',
                info: 'bg-blue-100 text-blue-600'
            };
            return colors[priority] || colors.info;
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
            const labels = { critical: 'Kritisch', high: 'Hoch', medium: 'Mittel', low: 'Niedrig', info: 'Info' };
            return labels[priority] || 'Info';
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
