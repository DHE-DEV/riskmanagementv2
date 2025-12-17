@extends('layouts.embed')

@section('title', 'Events - Global Travel Monitor')

@section('content')
<div x-data="embedEventsApp()" x-init="init()" class="h-full flex flex-col bg-gray-50">
    <!-- Filter Bar -->
    <div class="bg-white border-b border-gray-200 px-4 py-3 flex-shrink-0">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <!-- Priority Filter -->
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600 font-medium">Filter:</span>
                <div class="flex gap-1">
                    <button @click="priorityFilter = 'all'"
                            :class="priorityFilter === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1 rounded text-sm font-medium transition-colors">
                        Alle
                    </button>
                    <button @click="priorityFilter = 'critical'"
                            :class="priorityFilter === 'critical' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100'"
                            class="px-3 py-1 rounded text-sm font-medium transition-colors">
                        Kritisch
                    </button>
                    <button @click="priorityFilter = 'high'"
                            :class="priorityFilter === 'high' ? 'bg-orange-500 text-white' : 'bg-orange-50 text-orange-700 hover:bg-orange-100'"
                            class="px-3 py-1 rounded text-sm font-medium transition-colors">
                        Hoch
                    </button>
                </div>
            </div>

            <!-- Search -->
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
                       placeholder="Suchen..."
                       class="pl-8 pr-4 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-48">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            </div>
        </div>
    </div>

    <!-- Events List -->
    <div class="flex-1 overflow-y-auto">
        <!-- Loading State -->
        <template x-if="loading">
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600">Events werden geladen...</span>
            </div>
        </template>

        <!-- Events -->
        <template x-if="!loading">
            <div class="divide-y divide-gray-200">
                <template x-for="event in filteredEvents" :key="event.id">
                    <div class="bg-white hover:bg-gray-50 transition-colors cursor-pointer"
                         @click="openEvent(event)">
                        <div class="px-4 py-3">
                            <div class="flex items-start gap-3">
                                <!-- Priority Indicator -->
                                <div class="flex-shrink-0 mt-1">
                                    <span :class="getPriorityColor(event.priority)"
                                          class="inline-flex items-center justify-center w-8 h-8 rounded-full">
                                        <i :class="getEventIcon(event)" class="text-sm"></i>
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
                        <p>Keine Events gefunden</p>
                    </div>
                </template>
            </div>
        </template>
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
        loading: true,
        searchQuery: '',
        priorityFilter: '{{ request()->query("filter", "all") }}',
        selectedEvent: null,

        async init() {
            await this.loadEvents();
        },

        async loadEvents() {
            this.loading = true;
            try {
                const response = await fetch('/api/custom-events/dashboard-events?limit=100');
                const data = await response.json();
                this.events = data.data || [];
            } catch (error) {
                console.error('Error loading events:', error);
                this.events = [];
            }
            this.loading = false;
        },

        get filteredEvents() {
            let filtered = this.events;

            // Priority filter
            if (this.priorityFilter !== 'all') {
                filtered = filtered.filter(e => e.priority === this.priorityFilter);
            }

            // Search filter
            if (this.searchQuery.trim()) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(e =>
                    e.title?.toLowerCase().includes(query) ||
                    e.description?.toLowerCase().includes(query) ||
                    this.getCountryNames(e).toLowerCase().includes(query)
                );
            }

            return filtered;
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

        getCountryNames(event) {
            if (!event.countries?.length) return 'Global';
            return event.countries.map(c => c.name_de || c.name).join(', ');
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
            if (event.event_type?.icon) return event.event_type.icon;
            const icons = {
                critical: 'fas fa-exclamation-triangle',
                high: 'fas fa-exclamation-circle',
                medium: 'fas fa-info-circle',
                low: 'fas fa-check-circle',
                info: 'fas fa-info'
            };
            return icons[event.priority] || icons.info;
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

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection
