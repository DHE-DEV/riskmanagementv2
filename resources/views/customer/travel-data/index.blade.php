@extends('layouts.dashboard-minimal')

@section('title', 'Travel Data - Global Travel Monitor')

@php
    $active = 'travel-data';
    $customer = auth('customer')->user();
@endphp

@push('styles')
<style>
    .main-content {
        display: flex !important;
        overflow: hidden !important;
        overflow-y: hidden !important;
    }
    .travel-data-sidebar {
        flex-shrink: 0;
        width: 304px;
        background: #f9fafb;
        overflow-y: auto;
        height: 100%;
        border-right: 1px solid #e5e7eb;
    }
    .travel-data-content {
        flex: 1;
        overflow-y: auto;
        height: 100%;
    }
    @media (max-width: 768px) {
        .main-content {
            flex-direction: column !important;
        }
        .travel-data-sidebar {
            width: 100%;
            height: auto;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
        }
    }
</style>
@endpush

@section('content')
<div class="travel-data-sidebar" x-data="travelDataSidebar()">
    <div class="p-4">
        <h2 class="text-sm font-bold text-gray-900 mb-4">
            <i class="fas fa-route mr-2"></i>
            Travel Data
        </h2>

        {{-- Filter --}}
        <div class="mb-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Filter</p>
        </div>

        <button @click="setTab('current')"
                :class="tab === 'current' ? 'bg-white border-blue-500 text-blue-700 shadow-sm' : 'bg-transparent border-transparent text-gray-600 hover:bg-white hover:shadow-sm'"
                class="w-full flex items-center gap-3 px-3 py-2 text-xs rounded-lg border transition-colors mb-1">
            <i class="fas fa-plane-departure w-4 text-center text-green-500"></i>
            <span>Aktive Reisen</span>
            <span class="ml-auto text-xs text-gray-400" x-text="counts.current ?? ''"></span>
        </button>

        <button @click="setTab('upcoming')"
                :class="tab === 'upcoming' ? 'bg-white border-blue-500 text-blue-700 shadow-sm' : 'bg-transparent border-transparent text-gray-600 hover:bg-white hover:shadow-sm'"
                class="w-full flex items-center gap-3 px-3 py-2 text-xs rounded-lg border transition-colors mb-1">
            <i class="fas fa-calendar-alt w-4 text-center text-blue-500"></i>
            <span>Zukünftige Reisen</span>
            <span class="ml-auto text-xs text-gray-400" x-text="counts.upcoming ?? ''"></span>
        </button>

        <button @click="setTab('archive')"
                :class="tab === 'archive' ? 'bg-white border-blue-500 text-blue-700 shadow-sm' : 'bg-transparent border-transparent text-gray-600 hover:bg-white hover:shadow-sm'"
                class="w-full flex items-center gap-3 px-3 py-2 text-xs rounded-lg border transition-colors mb-1">
            <i class="fas fa-archive w-4 text-center text-amber-500"></i>
            <span>Archiv</span>
            <span class="ml-auto text-xs text-gray-400" x-text="counts.archive ?? ''"></span>
        </button>

    </div>
</div>

<div class="travel-data-content" x-data="travelDataContent()">
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Meine Reisen</h3>
                <p class="text-sm text-gray-500 mt-1">Übersicht Ihrer importierten Reisedaten.</p>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="flex items-center justify-center py-16">
            <i class="fas fa-spinner fa-spin text-gray-400 text-xl mr-2"></i>
            <span class="text-sm text-gray-500">Lade Reisedaten...</span>
        </div>

        {{-- Leere Liste --}}
        <div x-show="!loading && folders.length === 0" x-cloak class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <i class="fas fa-route text-4xl text-gray-300 mb-4"></i>
            <p class="text-sm text-gray-500 mb-2">Keine Reisen vorhanden.</p>
            <p class="text-xs text-gray-400">Importieren Sie Reisedaten in den <a href="{{ route('customer.settings', ['section' => 'travel-data']) }}" class="text-blue-600 hover:underline">Einstellungen</a>.</p>
        </div>

        {{-- Reise-Liste --}}
        <div x-show="!loading && folders.length > 0" x-cloak>
            <template x-for="folder in folders" :key="folder.id">
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-3 hover:border-gray-300 transition-colors">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        {{-- Icon --}}
                        <div class="hidden sm:block w-8 flex-shrink-0 pt-0.5 text-center">
                            <i class="fas text-lg" :class="{
                                'fa-plane-departure text-green-500': isCurrentlyTraveling(folder),
                                'fa-calendar-check text-blue-500': !isCurrentlyTraveling(folder) && (folder.status === 'confirmed' || folder.status === 'draft'),
                                'fa-suitcase text-gray-400': folder.status === 'completed',
                                'fa-ban text-red-400': folder.status === 'cancelled'
                            }"></i>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-4">
                                <p class="text-sm font-medium text-gray-900 truncate" x-text="folder.folder_name || ('Reise ' + folder.folder_number)"></p>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded"
                                          :class="{
                                              'bg-green-50 text-green-700': folder.status === 'active' || isCurrentlyTraveling(folder),
                                              'bg-blue-50 text-blue-700': folder.status === 'confirmed',
                                              'bg-gray-100 text-gray-500': folder.status === 'draft',
                                              'bg-gray-100 text-gray-600': folder.status === 'completed',
                                              'bg-red-50 text-red-600': folder.status === 'cancelled'
                                          }"
                                          x-text="statusLabel(folder.status)"></span>
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-50 text-gray-500 rounded border border-gray-200 whitespace-nowrap font-mono">
                                        <i class="fas fa-hashtag mr-1"></i><span x-text="folder.folder_number"></span>
                                    </span>
                                </div>
                            </div>

                            {{-- Reiseziele & Nationalitäten --}}
                            <div x-show="(folder.destinations_resolved && folder.destinations_resolved.length > 0) || (folder.nationalities_resolved && folder.nationalities_resolved.length > 0)"
                                 class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-4">
                                <div>
                                    <template x-if="folder.destinations_resolved && folder.destinations_resolved.length > 0">
                                        <div>
                                            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Reiseziele</p>
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="dest in folder.destinations_resolved" :key="dest.code">
                                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-blue-50 text-blue-700 rounded" x-text="dest.name + ' (' + dest.code + ')'"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <template x-if="folder.nationalities_resolved && folder.nationalities_resolved.length > 0">
                                        <div>
                                            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1 sm:text-right">Nationalitäten</p>
                                            <div class="flex flex-wrap gap-1 sm:justify-end">
                                                <template x-for="nat in folder.nationalities_resolved" :key="nat.code">
                                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-50 text-green-700 rounded" x-text="nat.name + ' (' + nat.code + ')'"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Zeitstrahl --}}
                            <div x-show="folder.travel_start_date && folder.travel_end_date" class="mt-3"
                                 x-data="{ tripProgress: getTripProgress(folder.travel_start_date, folder.travel_end_date) }">
                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                    <span x-text="formatDate(folder.travel_start_date)"></span>
                                    <span x-text="formatDate(folder.travel_end_date)"></span>
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
                            </div>

                            {{-- Services --}}
                            <div class="flex flex-wrap items-center gap-3 mt-3 text-[11px] text-gray-400">
                                <span x-show="folder.total_participants > 0">
                                    <i class="fas fa-users mr-0.5"></i> <span x-text="folder.total_participants"></span> Teilnehmer
                                </span>
                                <span x-show="folder.total_itineraries > 0">
                                    <i class="fas fa-list-check mr-0.5"></i> <span x-text="folder.total_itineraries"></span> Reisebausteine
                                </span>
                                <span x-show="folder.total_value">
                                    <i class="fas fa-coins mr-0.5"></i> <span x-text="parseFloat(folder.total_value).toLocaleString('de-DE', {minimumFractionDigits: 2})"></span> <span x-text="folder.currency || 'EUR'"></span>
                                </span>
                                <span x-show="folder.travel_type">
                                    <i class="fas fa-tag mr-0.5"></i> <span x-text="folder.travel_type === 'business' ? 'Geschäftsreise' : folder.travel_type === 'leisure' ? 'Freizeit' : 'Gemischt'"></span>
                                </span>
                                <span x-show="folder.agent_name">
                                    <i class="fas fa-user-tie mr-0.5"></i> <span x-text="folder.agent_name"></span>
                                </span>
                            </div>

                            {{-- Leistungen (Flüge, Hotels, etc.) --}}
                            <template x-if="getAllServices(folder).length > 0">
                                <div class="mt-3">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1.5">Leistungen</p>
                                    <div class="space-y-1">
                                        <template x-for="svc in getAllServices(folder)" :key="svc._key">
                                            <div class="grid grid-cols-[auto_1fr] gap-x-3 items-center text-xs">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded font-medium whitespace-nowrap justify-self-start"
                                                      :class="svc._type === 'flight' ? 'bg-blue-50 text-blue-700' : svc._type === 'hotel' ? 'bg-amber-50 text-amber-700' : svc._type === 'ship' ? 'bg-cyan-50 text-cyan-700' : 'bg-purple-50 text-purple-700'">
                                                    <i class="fas text-[10px]" :class="svc._type === 'flight' ? 'fa-plane' : svc._type === 'hotel' ? 'fa-hotel' : svc._type === 'ship' ? 'fa-ship' : 'fa-car'"></i>
                                                    <span x-text="svc._label"></span>
                                                </span>
                                                <span class="text-gray-500 truncate text-right" x-text="svc._detail"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- Notiz --}}
                            <div x-show="folder.notes" class="mt-2">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-0.5"><i class="fas fa-sticky-note mr-1"></i>Notiz</p>
                                <p class="text-xs text-gray-500" x-text="folder.notes"></p>
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
function getAllServices(folder) {
    const services = [];
    let idx = 0;

    // Itineraries durchlaufen — sie kommen in der Reihenfolge, wie sie angelegt wurden
    (folder.itineraries || []).forEach(itin => {
        // Flugsegmente dieser Itinerary
        (folder.flight_services || []).filter(f => f.itinerary_id === itin.id).forEach(flight => {
            (flight.segments || []).forEach((seg, i) => {
                const route = (seg.departure_airport_code || '') + ' → ' + (seg.arrival_airport_code || '');
                const carrier = [seg.airline_code, seg.flight_number].filter(Boolean).join(' ');
                const depTime = seg.departure_time ? formatServiceDateTime(seg.departure_time) : '';
                const arrTime = seg.arrival_time ? formatServiceTime(seg.arrival_time) : '';
                const timeRange = depTime && arrTime ? depTime + ' – ' + arrTime : depTime;
                services.push({
                    _key: 'f-' + (seg.id || flight.id + '-' + i),
                    _type: 'flight',
                    _label: route,
                    _detail: [carrier, timeRange].filter(Boolean).join(' · '),
                    _order: idx++,
                });
            });
        });

        // Hotels dieser Itinerary
        (folder.hotel_services || []).filter(h => h.itinerary_id === itin.id).forEach(hotel => {
            const name = hotel.hotel_name || 'Hotel';
            const city = hotel.city_name || '';
            const checkIn = hotel.check_in_date ? formatServiceDate(hotel.check_in_date) : '';
            const checkOut = hotel.check_out_date ? formatServiceDate(hotel.check_out_date) : '';
            const dates = checkIn && checkOut ? checkIn + ' – ' + checkOut : checkIn;
            services.push({
                _key: 'h-' + hotel.id,
                _type: 'hotel',
                _label: name,
                _detail: [city, dates].filter(Boolean).join(' · '),
                _order: idx++,
            });
        });

        // Schiffe dieser Itinerary
        (folder.ship_services || []).filter(s => s.itinerary_id === itin.id).forEach(ship => {
            const ports = [ship.departure_port, ship.arrival_port].filter(Boolean).join(' → ');
            services.push({
                _key: 's-' + ship.id,
                _type: 'ship',
                _label: ship.ship_name || 'Kreuzfahrt',
                _detail: [ports, ship.cabin_type].filter(Boolean).join(' · '),
                _order: idx++,
            });
        });

        // Mietwagen dieser Itinerary
        (folder.car_rental_services || []).filter(c => c.itinerary_id === itin.id).forEach(car => {
            const route = [car.pickup_location, car.dropoff_location].filter(Boolean).join(' → ');
            services.push({
                _key: 'c-' + car.id,
                _type: 'car',
                _label: car.vehicle_type || 'Mietwagen',
                _detail: route,
                _order: idx++,
            });
        });
    });

    return services;
}

function formatServiceDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatServiceDateTime(d) {
    if (!d) return '';
    const date = new Date(d);
    return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' })
        + ' ' + date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
}

function formatServiceTime(d) {
    if (!d) return '';
    return new Date(d).toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
}

function getTripProgress(startDate, endDate) {
    if (!startDate || !endDate) return { started: false, progress: 0, status: 'upcoming' };

    const start = new Date(startDate);
    const end = new Date(endDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (today < start) return { started: false, progress: 0, status: 'upcoming' };
    if (today > end) return { started: true, progress: 100, status: 'completed' };

    const totalDays = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1);
    const elapsedDays = Math.ceil((today - start) / (1000 * 60 * 60 * 24)) + 1;
    const progress = Math.min(100, Math.round((elapsedDays / totalDays) * 100));

    return { started: true, progress, status: 'active' };
}

function travelDataSidebar() {
    return {
        tab: 'current',
        counts: { current: null, upcoming: null, archive: null },
        init() {
            this.loadCounts();
        },
        setTab(t) {
            this.tab = t;
            window.dispatchEvent(new CustomEvent('travel-data-tab', { detail: { tab: t } }));
        },
        async loadCounts() {
            try {
                for (const t of ['current', 'upcoming', 'archive']) {
                    const r = await fetch(`{{ route('customer.travel-data.folders') }}?tab=${t}&page=1`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const d = await r.json();
                    this.counts[t] = d.total;
                }
            } catch (e) {}
        }
    };
}

function travelDataContent() {
    return {
        folders: [],
        loading: false,
        currentPage: 1,
        lastPage: 1,
        total: 0,
        tab: 'current',
        init() {
            this.loadFolders();
            window.addEventListener('travel-data-tab', (e) => {
                this.tab = e.detail.tab;
                this.currentPage = 1;
                this.loadFolders();
            });
        },
        loadPage(page) {
            if (page < 1 || page > this.lastPage) return;
            this.currentPage = page;
            this.loadFolders();
        },
        async loadFolders() {
            this.loading = true;
            try {
                const r = await fetch(`{{ route('customer.travel-data.folders') }}?tab=${this.tab}&page=${this.currentPage}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const d = await r.json();
                this.folders = d.data || [];
                this.currentPage = d.current_page;
                this.lastPage = d.last_page;
                this.total = d.total;
            } catch (e) {
                this.folders = [];
            }
            this.loading = false;
        },
        formatDate(d) {
            if (!d) return '';
            const date = new Date(d);
            return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },
        isCurrentlyTraveling(folder) {
            if (!folder.travel_start_date || !folder.travel_end_date) return false;
            const now = new Date();
            now.setHours(0, 0, 0, 0);
            return new Date(folder.travel_start_date) <= now && new Date(folder.travel_end_date) >= now;
        },
        statusLabel(status) {
            return {
                'draft': 'Entwurf',
                'confirmed': 'Bestätigt',
                'active': 'Aktiv',
                'completed': 'Abgeschlossen',
                'cancelled': 'Storniert',
            }[status] || status;
        }
    };
}
</script>
@endpush
