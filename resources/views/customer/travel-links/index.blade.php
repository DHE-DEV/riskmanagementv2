@extends('layouts.dashboard-minimal')

@section('title', 'Travel Links - Global Travel Monitor')

@php
    $active = 'customer-travel-links';
    $customer = auth('customer')->user();
@endphp

@push('styles')
<style>
    .main-content {
        display: flex !important;
        overflow: hidden !important;
        overflow-y: hidden !important;
    }
    .travel-links-sidebar {
        flex-shrink: 0;
        width: 304px;
        background: #f9fafb;
        overflow-y: auto;
        height: 100%;
        border-right: 1px solid #e5e7eb;
    }
    .travel-links-content {
        flex: 1;
        overflow-y: auto;
        height: 100%;
    }
    @media (max-width: 768px) {
        .main-content {
            flex-direction: column !important;
        }
        .travel-links-sidebar {
            width: 100%;
            height: auto;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
        }
    }
</style>
@endpush

@section('content')
<div class="travel-links-sidebar" x-data="travelLinksSidebar()">
    <div class="p-4">
        <h2 class="text-sm font-bold text-gray-900 mb-4">
            <i class="fa-regular fa-link mr-2"></i>
            Travel Links
        </h2>

        {{-- Sync Status --}}
        <div class="mb-4 p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-700">Synchronisierung</span>
                <span class="text-xs" :class="syncEnabled ? 'text-green-600' : 'text-gray-400'" x-text="syncEnabled ? 'Aktiv' : 'Inaktiv'"></span>
            </div>
            <div class="text-xs text-gray-500 mb-3">
                <span x-show="lastSyncedAt">
                    <i class="fas fa-clock mr-1"></i> <span x-text="lastSyncedAt"></span>
                </span>
                <span x-show="!lastSyncedAt">
                    <i class="fas fa-info-circle mr-1"></i> Noch nicht synchronisiert
                </span>
            </div>
            <button @click="syncNow()" :disabled="syncing"
                    class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-medium rounded-lg transition-colors"
                    :class="syncing ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'">
                <i class="fas mr-1.5" :class="syncing ? 'fa-spinner fa-spin' : 'fa-sync-alt'"></i>
                <span x-text="syncing ? 'Synchronisiert...' : 'Jetzt synchronisieren'"></span>
            </button>
            <div x-show="syncResult" x-cloak class="mt-2 p-2 rounded text-xs"
                 :class="syncSuccess ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                <span x-text="syncResult"></span>
            </div>
        </div>

        {{-- Filter --}}
        <div class="mb-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Filter</p>
        </div>

        <button @click="setFilter('all')"
                :class="filter === 'all' ? 'bg-white border-blue-500 text-blue-700 shadow-sm' : 'bg-transparent border-transparent text-gray-600 hover:bg-white hover:shadow-sm'"
                class="w-full flex items-center gap-3 px-3 py-2 text-xs rounded-lg border transition-colors mb-1">
            <i class="fas fa-list w-4 text-center"></i>
            <span>Alle Links</span>
            <span class="ml-auto text-xs text-gray-400" x-text="counts.all ?? ''"></span>
        </button>

        <button @click="setFilter('current')"
                :class="filter === 'current' ? 'bg-white border-blue-500 text-blue-700 shadow-sm' : 'bg-transparent border-transparent text-gray-600 hover:bg-white hover:shadow-sm'"
                class="w-full flex items-center gap-3 px-3 py-2 text-xs rounded-lg border transition-colors mb-1">
            <i class="fas fa-plane-departure w-4 text-center text-green-500"></i>
            <span>Aktive Reisen</span>
            <span class="ml-auto text-xs text-gray-400" x-text="counts.current ?? ''"></span>
        </button>

        <button @click="setFilter('upcoming')"
                :class="filter === 'upcoming' ? 'bg-white border-blue-500 text-blue-700 shadow-sm' : 'bg-transparent border-transparent text-gray-600 hover:bg-white hover:shadow-sm'"
                class="w-full flex items-center gap-3 px-3 py-2 text-xs rounded-lg border transition-colors mb-1">
            <i class="fas fa-calendar-alt w-4 text-center text-blue-500"></i>
            <span>Zukünftige Reisen</span>
            <span class="ml-auto text-xs text-gray-400" x-text="counts.upcoming ?? ''"></span>
        </button>

        <button @click="setFilter('expired')"
                :class="filter === 'expired' ? 'bg-white border-blue-500 text-blue-700 shadow-sm' : 'bg-transparent border-transparent text-gray-600 hover:bg-white hover:shadow-sm'"
                class="w-full flex items-center gap-3 px-3 py-2 text-xs rounded-lg border transition-colors mb-1">
            <i class="fas fa-archive w-4 text-center text-amber-500"></i>
            <span>Abgelaufene Reisen</span>
            <span class="ml-auto text-xs text-gray-400" x-text="counts.expired ?? ''"></span>
        </button>
    </div>
</div>

<div class="travel-links-content" x-data="travelLinksContent()">
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Travel Links</h3>
                <p class="text-sm text-gray-500 mt-1">Personalisierte Reiseinformations-Links für Ihre Reisenden.</p>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="flex items-center justify-center py-16">
            <i class="fas fa-spinner fa-spin text-gray-400 text-xl mr-2"></i>
            <span class="text-sm text-gray-500">Lade Travel Links...</span>
        </div>

        {{-- Leere Liste --}}
        <div x-show="!loading && trips.length === 0" x-cloak class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <i class="fas fa-link text-4xl text-gray-300 mb-4"></i>
            <p class="text-sm text-gray-500 mb-2">Keine Travel Links vorhanden.</p>
            <p class="text-xs text-gray-400">Synchronisieren Sie zuerst Ihre Reisedaten über die Sidebar.</p>
        </div>

        {{-- Link Liste --}}
        <div x-show="!loading && trips.length > 0" x-cloak>
            <template x-for="trip in trips" :key="trip.id">
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-3 hover:border-gray-300 transition-colors">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        {{-- Icon --}}
                        <div class="hidden sm:block w-8 flex-shrink-0 pt-0.5 text-center">
                            <i class="fas fa-link text-lg" :class="trip.pds_share_url ? 'text-blue-500' : 'text-gray-300'"></i>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-4">
                                <p class="text-sm font-medium text-gray-900 truncate" x-text="trip.trip_name || trip.external_trip_id || 'Reise'"></p>
                                <span x-show="trip.pds_tid" class="text-xs text-gray-400 whitespace-nowrap flex-shrink-0">
                                    <i class="fas fa-hashtag mr-0.5"></i><span x-text="trip.pds_tid"></span>
                                </span>
                            </div>

                            {{-- Nationalitäten & Reiseziele --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                {{-- Nationalitäten --}}
                                <div x-show="trip.nationalities && trip.nationalities.length > 0">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Nationalitäten</p>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="nat in trip.nationalities" :key="nat.code">
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 rounded" x-text="nat.name"></span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Reiseziele --}}
                                <div x-show="trip.destinations && trip.destinations.length > 0">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Reiseziele</p>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="dest in trip.destinations" :key="dest.code">
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-blue-50 text-blue-700 rounded" x-text="dest.name"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>


                            {{-- Zeitstrahl --}}
                            <div x-show="trip.computed_start_at && trip.computed_end_at" class="mt-3"
                                 x-data="{ tripProgress: getTripProgress(trip.computed_start_at, trip.computed_end_at) }">
                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                    <span x-text="formatDate(trip.computed_start_at)"></span>
                                    <span x-text="formatDate(trip.computed_end_at)"></span>
                                </div>
                                <div class="flex items-center" :class="tripProgress.started ? 'gap-2' : ''">
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
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

                            {{-- Share Link --}}
                            <div x-show="trip.pds_share_url" class="mt-3 flex flex-col sm:flex-row sm:items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                                        <i class="fas fa-external-link-alt text-xs text-gray-400"></i>
                                        <a :href="trip.pds_share_url + (trip.pds_share_url.includes('?') ? '&' : '?') + 'preview'" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 truncate" x-text="trip.pds_share_url"></a>
                                    </div>
                                </div>
                                <button @click="copyLink(trip.pds_share_url)"
                                        class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 whitespace-nowrap">
                                    <i class="fas fa-copy mr-1.5"></i> Kopieren
                                </button>
                            </div>

                            {{-- Kein Link --}}
                            <div x-show="!trip.pds_share_url" class="mt-3">
                                <span class="text-xs text-gray-400">
                                    <i class="fas fa-info-circle mr-1"></i> Kein Travel Link vorhanden
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Pagination --}}
            <div x-show="lastPage > 1" class="flex items-center justify-between mt-4">
                <p class="text-xs text-gray-500">
                    Seite <span x-text="currentPage"></span> von <span x-text="lastPage"></span>
                    (<span x-text="total"></span> Einträge)
                </p>
                <div class="flex gap-2">
                    <button @click="loadPage(currentPage - 1)" :disabled="currentPage <= 1"
                            :class="currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                            class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button @click="loadPage(currentPage + 1)" :disabled="currentPage >= lastPage"
                            :class="currentPage >= lastPage ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                            class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function getTripProgress(startDate, endDate) {
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
}

function travelLinksSidebar() {
    return {
        syncEnabled: {{ $customer->pds_sync_enabled ? 'true' : 'false' }},
        lastSyncedAt: {!! $customer->pds_last_synced_at ? "'" . $customer->pds_last_synced_at->format('d.m.Y H:i') . "'" : 'null' !!},
        syncing: false,
        syncResult: null,
        syncSuccess: false,
        filter: 'all',
        counts: { all: null, current: null, upcoming: null, expired: null },
        init() {
            this.loadCounts();
        },
        setFilter(f) {
            this.filter = f;
            window.dispatchEvent(new CustomEvent('travel-links-filter', { detail: { filter: f } }));
        },
        async syncNow() {
            this.syncing = true;
            this.syncResult = null;
            try {
                const r = await fetch('{{ route('customer.travel-data.sync-links') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const d = await r.json();
                this.syncSuccess = d.success;
                if (d.stats) {
                    const s = d.stats;
                    let parts = [`${s.trips_synced} Reisen`];
                    if (s.links_created > 0) parts.push(`${s.links_created} neue Links`);
                    if (s.links_refreshed > 0) parts.push(`${s.links_refreshed} aktualisiert`);
                    if (s.links_existing > 0) parts.push(`${s.links_existing} unverändert`);
                    if (s.skipped > 0) parts.push(`${s.skipped} übersprungen`);
                    this.syncResult = d.message + ' (' + parts.join(', ') + ')';
                } else {
                    this.syncResult = d.message;
                }
                if (d.synced_at) this.lastSyncedAt = d.synced_at;
                this.loadCounts();
                window.dispatchEvent(new CustomEvent('travel-links-reload'));
            } catch (e) {
                this.syncSuccess = false;
                this.syncResult = 'Verbindungsfehler';
            }
            this.syncing = false;
        },
        async loadCounts() {
            try {
                for (const f of ['all', 'current', 'upcoming', 'expired']) {
                    const r = await fetch(`{{ route('customer.travel-links') }}/api?filter=${f}&page=1&per_page=1`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const d = await r.json();
                    this.counts[f] = d.total;
                }
            } catch (e) {}
        }
    };
}

function travelLinksContent() {
    return {
        trips: [],
        loading: false,
        currentPage: 1,
        lastPage: 1,
        total: 0,
        filter: 'all',
        init() {
            this.loadTrips();
            window.addEventListener('travel-links-filter', (e) => {
                this.filter = e.detail.filter;
                this.currentPage = 1;
                this.loadTrips();
            });
            window.addEventListener('travel-links-reload', () => {
                this.loadTrips();
            });
        },
        loadPage(page) {
            if (page < 1 || page > this.lastPage) return;
            this.currentPage = page;
            this.loadTrips();
        },
        async loadTrips() {
            this.loading = true;
            try {
                const r = await fetch(`{{ route('customer.travel-links') }}/api?filter=${this.filter}&page=${this.currentPage}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const d = await r.json();
                this.trips = d.data || [];
                this.currentPage = d.current_page;
                this.lastPage = d.last_page;
                this.total = d.total;
            } catch (e) {
                this.trips = [];
            }
            this.loading = false;
        },
        formatDate(d) {
            if (!d) return '';
            const date = new Date(d);
            return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },
        copyLink(url) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url);
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = url;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
        }
    };
}
</script>
@endpush
