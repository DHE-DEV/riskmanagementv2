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
            {{-- Pagination oben --}}
            <div x-show="lastPage > 1" class="flex items-center justify-between mb-4">
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
                                <div class="flex items-center gap-4 flex-shrink-0">
                                    <span x-show="trip.cruise_compass_cruise_id" class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-50 text-gray-500 rounded border border-gray-200 whitespace-nowrap">
                                        <i class="fas fa-ship mr-1"></i> Cruise&nbsp;<span x-text="trip.cruise_compass_cruise_id"></span>
                                    </span>
                                    <span x-show="trip.booking_reference" class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded border border-gray-200 whitespace-nowrap">
                                        <i class="fas fa-bookmark mr-1"></i><span x-text="trip.booking_reference"></span>
                                    </span>
                                    <span x-show="trip.external_trip_id || trip.pds_tid" class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-50 text-gray-500 rounded border border-gray-200 whitespace-nowrap font-mono">
                                        <i class="fas fa-hashtag mr-1"></i><span x-text="trip.external_trip_id || trip.pds_tid"></span>
                                    </span>
                                    {{-- 3-Punkte-Menü --}}
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open" class="p-1 text-gray-400 hover:text-gray-600 rounded hover:bg-gray-100 transition-colors">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </button>
                                        <div x-show="open" x-cloak @click.away="open = false"
                                             class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20">
                                            <button @click="open = false; $dispatch('edit-trip', { trip: trip })"
                                                    class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <i class="fas fa-pen w-4 text-center"></i> Bearbeiten
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Notiz --}}
                            <div x-show="trip.note" class="mt-2">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-0.5"><i class="fas fa-sticky-note mr-1"></i>Notiz</p>
                                <p class="text-xs text-gray-500" x-text="trip.note"></p>
                            </div>

                            {{-- Reiseziele & Nationalitäten --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                {{-- Reiseziele --}}
                                <div x-show="trip.destinations && trip.destinations.length > 0">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Reiseziele</p>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="dest in trip.destinations" :key="dest.code">
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-blue-50 text-blue-700 rounded" x-text="dest.name"></span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Nationalitäten --}}
                                <div x-show="trip.nationalities_resolved && trip.nationalities_resolved.length > 0">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Nationalitäten</p>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="nat in trip.nationalities_resolved" :key="nat.code">
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-blue-50 text-blue-700 rounded" x-text="nat.name"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Tour Operators & Individual Contents --}}
                            <div x-show="(trip.tour_operators && trip.tour_operators.length > 0) || (trip.individual_contents && trip.individual_contents.length > 0)" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                <div x-show="trip.tour_operators && trip.tour_operators.length > 0">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Reiseveranstalter</p>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="op in trip.tour_operators" :key="op">
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-purple-50 text-purple-700 rounded" x-text="op"></span>
                                        </template>
                                    </div>
                                </div>
                                <div x-show="trip.individual_contents && trip.individual_contents.length > 0">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Individuelle Inhalte</p>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="ic in trip.individual_contents" :key="ic">
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 rounded" x-text="ic"></span>
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
                                        <a :href="travelDetailsUrl(trip) + '&preview'" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 truncate" x-text="travelDetailsUrl(trip)"></a>
                                    </div>
                                </div>
                                <button @click="copyLink(travelDetailsUrl(trip))"
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

                            {{-- Metadaten: Besuche, letzte Änderung --}}
                            <div class="flex flex-wrap items-center gap-3 mt-3 text-[11px] text-gray-400">
                                <span x-show="trip.visits > 0">
                                    <i class="fas fa-eye mr-0.5"></i> <span x-text="trip.visits"></span> Aufrufe
                                </span>
                                <span x-show="trip.last_visited_at">
                                    <i class="fas fa-clock mr-0.5"></i> Zuletzt besucht: <span x-text="formatDateTime(trip.last_visited_at)"></span>
                                </span>
                                <span x-show="trip.last_important_change_at">
                                    <i class="fas fa-pen-to-square mr-0.5"></i> Letzte wichtige Änderung: <span x-text="formatDateTime(trip.last_important_change_at)"></span>
                                </span>
                                <span x-show="trip.cover_media">
                                    <i class="fas fa-image mr-0.5"></i> Cover-Bild vorhanden
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
        {{-- Bearbeiten-Modal --}}
        <div x-show="editTrip" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="editTrip = null" @keydown.enter.window="if(editTrip && !editSaving && document.activeElement.tagName !== 'TEXTAREA') saveEdit()">
            <div class="fixed inset-0 bg-black/50" @click="editTrip = null"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto" style="margin-top: 10px; margin-bottom: 10px;" @click.stop>
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-gray-900">Travel Link bearbeiten</h3>
                    <button @click="editTrip = null" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- Erfolgs-/Fehlermeldung --}}
                <div x-show="editResult" x-cloak class="mb-4 p-3 rounded-lg text-xs"
                     :class="editSuccess ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'">
                    <i class="fas mr-1" :class="editSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                    <span x-text="editResult"></span>
                </div>

                {{-- Debug: API Request anzeigen --}}
                <div x-show="editDebug" x-cloak class="mb-4 p-4 bg-gray-900 rounded-lg text-xs font-mono text-green-400 overflow-x-auto max-h-64 overflow-y-auto">
                    <p class="text-gray-500 mb-2">API Request (nicht ausgeführt):</p>
                    <p class="text-yellow-400" x-text="editDebug ? editDebug.method + ' ' + editDebug.url : ''"></p>
                    <p class="text-gray-500 mt-2 mb-1">Headers:</p>
                    <pre x-text="editDebug ? JSON.stringify(editDebug.headers, null, 2) : ''"></pre>
                    <p class="text-gray-500 mt-2 mb-1">Body:</p>
                    <pre x-text="editDebug ? JSON.stringify(editDebug.body, null, 2) : ''"></pre>
                </div>

                <template x-if="editTrip">
                    <div class="space-y-4">
                        {{-- Reisename --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Reisename</label>
                            <input type="text" x-model="editForm.trip_name"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- Datum --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Startdatum</label>
                                <input type="date" x-model="editForm.start_date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Enddatum</label>
                                <input type="date" x-model="editForm.end_date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        {{-- Reiseziele --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Reiseziele (ISO-Codes, kommagetrennt)</label>
                            <input type="text" x-model="editForm.destinations_input"
                                   placeholder="z.B. DE, ES, FR"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-[10px] text-gray-400 mt-1">2-stellige ISO-Ländercodes, z.B. DE, AT, CH, ES</p>
                        </div>

                        {{-- Nationalitäten --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Nationalitäten (ISO-Codes, kommagetrennt)</label>
                            <input type="text" x-model="editForm.nationalities_input"
                                   placeholder="z.B. DE, AT"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- Referenz-ID --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Referenz-ID</label>
                            <input type="text" x-model="editForm.reference_id"
                                   placeholder="z.B. REF-123"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- Notiz --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Notiz</label>
                            <textarea x-model="editForm.note" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Optionale Notiz zur Reise"></textarea>
                        </div>

                        {{-- Länderinformationen anzeigen --}}
                        <div class="flex items-center gap-3">
                            <button @click="editForm.show_country_info = !editForm.show_country_info"
                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                                    :class="editForm.show_country_info ? 'bg-blue-600' : 'bg-gray-300'">
                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                                      :class="editForm.show_country_info ? 'translate-x-4' : 'translate-x-0.5'"></span>
                            </button>
                            <label class="text-xs text-gray-700">Länderinformationen anzeigen</label>
                        </div>

                        {{-- TID Info --}}
                        <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-500">
                            <i class="fas fa-hashtag mr-1"></i> TID: <span x-text="editTrip.pds_tid" class="font-mono"></span>
                        </div>

                        {{-- Buttons --}}
                        <div class="flex justify-end gap-3 pt-2">
                            <button @click="editTrip = null"
                                    class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                Abbrechen
                            </button>
                            <button @click="saveEdit()" :disabled="editSaving"
                                    :class="editSaving ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                                    class="px-4 py-2 text-sm text-white rounded-lg flex items-center gap-2">
                                <i class="fas" :class="editSaving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                                <span x-text="editSaving ? 'Wird gespeichert...' : 'Speichern'"></span>
                            </button>
                        </div>
                    </div>
                </template>
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
        filter: 'all',
        counts: { all: null, current: null, upcoming: null, expired: null },
        init() {
            this.loadCounts();
        },
        setFilter(f) {
            this.filter = f;
            window.dispatchEvent(new CustomEvent('travel-links-filter', { detail: { filter: f } }));
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
        editTrip: null,
        editForm: {},
        editSaving: false,
        editResult: null,
        editSuccess: false,
        editDebug: null,
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
            window.addEventListener('edit-trip', (e) => {
                this.openEdit(e.detail.trip);
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
        formatDateTime(d) {
            if (!d) return '';
            const date = new Date(d);
            // Timestamps without timezone info (from DB) are parsed as UTC by JS,
            // but are actually in Europe/Berlin. Display them in Europe/Berlin timezone.
            return date.toLocaleDateString('de-DE', { timeZone: '{{ config("app.timezone", "Europe/Berlin") }}', day: '2-digit', month: '2-digit', year: 'numeric' })
                + ' ' + date.toLocaleTimeString('de-DE', { timeZone: '{{ config("app.timezone", "Europe/Berlin") }}', hour: '2-digit', minute: '2-digit' });
        },
        openEdit(trip) {
            this.editTrip = trip;
            this.editResult = null;
            this.editDebug = null;
            this.editForm = {
                trip_name: trip.trip_name || trip.raw_payload?.trip_name || '',
                start_date: trip.computed_start_at ? this.toLocalDate(trip.computed_start_at) : '',
                end_date: trip.computed_end_at ? this.toLocalDate(trip.computed_end_at) : '',
                destinations_input: (trip.countries_visited || []).join(', '),
                nationalities_input: (trip.nationalities_resolved || trip.nationalities || []).map(n => n.code || n).join(', '),
                reference_id: trip.booking_reference || trip.raw_payload?.reference_id || '',
                note: trip.raw_payload?.note || '',
                show_country_info: trip.raw_payload?.show_country_info !== false,
            };
        },
        async saveEdit() {
            if (!this.editTrip?.pds_tid) return;
            this.editSaving = true;
            this.editResult = null;
            try {
                const destinations = this.editForm.destinations_input
                    .split(',').map(s => s.trim().toUpperCase()).filter(s => s.length === 2);
                const nationalities = this.editForm.nationalities_input
                    .split(',').map(s => s.trim().toUpperCase()).filter(s => s.length === 2);

                const r = await fetch('{{ route("customer.travel-data.update-link") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        pds_tid: this.editTrip.pds_tid,
                        trip_id: this.editTrip.id,
                        trip_name: this.editForm.trip_name,
                        start_date: this.editForm.start_date,
                        end_date: this.editForm.end_date,
                        destinations: destinations,
                        nationalities: nationalities,
                        reference_id: this.editForm.reference_id,
                        note: this.editForm.note,
                        show_country_info: this.editForm.show_country_info,
                    })
                });
                const d = await r.json();
                this.editSuccess = d.success;
                if (d.debug && d.api_request) {
                    this.editResult = d.message;
                    this.editDebug = d.api_request;
                } else {
                    this.editResult = d.message;
                    this.editDebug = null;
                    if (d.success) {
                        setTimeout(() => {
                            this.editTrip = null;
                            this.loadTrips();
                        }, 1000);
                    }
                }
            } catch (e) {
                this.editSuccess = false;
                this.editResult = 'Verbindungsfehler. Bitte versuchen Sie es erneut.';
            }
            this.editSaving = false;
        },
        travelDetailsBaseUrl: '{{ env("PASSOLUTION_TRAVEL_DETAILS_LINK", "https://travel-details.eu") }}',
        travelDetailsUrl(trip) {
            const tid = trip.pds_tid || trip.external_trip_id;
            if (!tid) return trip.pds_share_url || '';
            return this.travelDetailsBaseUrl + '/de?tid=' + tid;
        },
        toLocalDate(d) {
            if (!d) return '';
            const date = new Date(d);
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
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
