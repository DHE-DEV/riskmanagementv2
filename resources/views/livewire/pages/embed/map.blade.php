@extends('layouts.embed')

@section('title', 'Karte - Global Travel Monitor')

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

    <!-- Filter Controls (Floating) -->
    <div class="absolute top-4 left-4 z-[1000] bg-white rounded-lg shadow-lg p-3">
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-700">Filter:</span>
            <select x-model="priorityFilter" @change="updateMarkers()"
                    class="text-sm border border-gray-300 rounded px-2 py-1 focus:ring-2 focus:ring-blue-500">
                <option value="all">Alle Events</option>
                <option value="critical">Kritisch</option>
                <option value="high">Hoch</option>
                <option value="medium">Mittel</option>
            </select>
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
    <div class="absolute bottom-4 right-4 z-[1000] bg-white rounded-lg shadow-lg p-3">
        <div class="text-xs font-semibold text-gray-700 mb-2">Legende</div>
        <div class="space-y-1 text-xs">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                <span>Kritisch</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                <span>Hoch</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                <span>Mittel</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                <span>Info</span>
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
        loading: true,
        priorityFilter: '{{ request()->query("filter", "all") }}',

        async init() {
            await this.initMap();
            await this.loadEvents();
        },

        initMap() {
            // Initialize Leaflet map
            this.map = L.map('embed-map', {
                center: [30, 10],
                zoom: 2,
                minZoom: 2,
                maxZoom: 18,
                worldCopyJump: true
            });

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            // Initialize marker cluster
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
                this.updateMarkers();
            } catch (error) {
                console.error('Error loading events:', error);
            }
            this.loading = false;
        },

        updateMarkers() {
            if (!this.markerCluster) return;

            this.markerCluster.clearLayers();

            let filteredEvents = this.events;
            if (this.priorityFilter !== 'all') {
                filteredEvents = filteredEvents.filter(e => e.priority === this.priorityFilter);
            }

            filteredEvents.forEach(event => {
                const lat = parseFloat(event.latitude || event.lat);
                const lng = parseFloat(event.longitude || event.lng);

                if (isNaN(lat) || isNaN(lng)) return;

                const marker = L.marker([lat, lng], {
                    icon: this.createIcon(event)
                });

                marker.bindPopup(this.createPopup(event), {
                    maxWidth: 320,
                    className: 'event-popup-wrapper'
                });

                this.markerCluster.addLayer(marker);
            });
        },

        createIcon(event) {
            const colors = {
                critical: '#ef4444',
                high: '#f97316',
                medium: '#eab308',
                low: '#22c55e',
                info: '#3b82f6'
            };

            const color = colors[event.priority] || colors.info;
            const iconClass = event.event_type?.icon || 'fas fa-exclamation-circle';

            return L.divIcon({
                className: 'custom-marker',
                html: `<i class="${iconClass}" style="color: ${color};"></i>`,
                iconSize: [24, 24],
                iconAnchor: [12, 24],
                popupAnchor: [0, -24]
            });
        },

        createPopup(event) {
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

            const countries = event.countries?.map(c => c.name_de || c.name).join(', ') || '';
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
                        ${countries ? `<p class="text-xs text-gray-500 mt-1">${countries}</p>` : ''}
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
