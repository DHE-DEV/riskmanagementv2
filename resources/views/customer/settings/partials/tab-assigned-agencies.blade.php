{{-- Liste der zugeordneten Unteraccounts eines Typs (Tab „Agenturen“ bzw. „Reiseberater“):
     Suche, Status-Filter, Tabelle, Detailansicht. Variablen: $tabKey, $locationType, $countEvent, $labelPlural --}}
<div x-show="orgTab === '{{ $tabKey }}'" x-cloak x-data="assignedAgencies(@js($locationType), @js($countEvent))">
    {{-- Suche und Status-Filter --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-56 flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search text-xs"></i></span>
            <input type="text" x-model="search" @input.debounce.300ms="loadAgencies()" @keydown.escape="search = ''; loadAgencies()"
                   placeholder="Suche nach Firma, Code, PLZ, Ort..."
                   class="w-full pl-9 pr-9 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <button x-show="search.length > 0" @click="search = ''; loadAgencies()" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times-circle text-sm"></i>
            </button>
        </div>
        <select x-model="status" aria-label="{{ $labelPlural }} nach Status filtern"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="all">Alle {{ $labelPlural }}</option>
            <option value="active">Nur aktive</option>
            <option value="inactive">Nur inaktive</option>
        </select>
        <button type="button" @click="toggleSort('company_name')" class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50" :aria-label="'Nach Firma sortieren, ' + (sortDir === 'asc' ? 'aufsteigend' : 'absteigend')">
            Firma <i class="fas ml-1" :class="sortDir === 'asc' ? 'fa-arrow-down-a-z' : 'fa-arrow-up-z-a'"></i>
        </button>
    </div>

    {{-- Karten wie in der Übersicht: je nach Breite 1 bis 3 nebeneinander --}}
    <div x-show="loading" class="py-8 text-center text-sm text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Laden...</div>
    <div x-show="!loading && filteredAgencies.length === 0" x-cloak class="bg-white rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">Keine {{ $labelPlural }} gefunden</div>
    <div x-show="!loading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        <template x-for="agency in filteredAgencies" :key="agency.id">
            <div class="rounded-lg border p-3 flex flex-col" :class="isInactive(agency) ? 'bg-red-50 border-red-200' : 'bg-white border-gray-200'">
                <div class="flex items-start justify-between gap-2">
                    <h5 class="text-sm font-semibold text-gray-900 min-w-0 break-words" x-text="agency.company_name || agency.name"></h5>
                    <div class="relative flex-shrink-0" x-data="{ menuOpen: false }">
                        <button type="button" @click="menuOpen = !menuOpen" class="p-1.5 -m-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors" aria-label="Aktionen">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </button>
                        <div x-show="menuOpen" @click.away="menuOpen = false" x-cloak
                             class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 z-50 py-1">
                            <button @click="menuOpen = false; showAgencyDetail(agency)"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <i class="fas fa-eye text-xs text-gray-400"></i> Details anzeigen
                            </button>
                            @if($isSuperAdmin)
                            <form method="POST" :action="'/customer/account/switch/' + agency.id">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button type="submit"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <i class="fas fa-arrows-repeat text-xs text-gray-400"></i> Zum Account wechseln
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1 mt-1">
                    <span x-show="agency.app_code" class="font-mono text-[10px] bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded" x-text="agency.app_code"></span>
                    @if($locationType === 'travel_advisor')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-teal-100 text-teal-800"><i class="fas fa-user-tie mr-1 text-[8px]"></i>Reiseberater</span>
                    @else
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-800"><i class="fas fa-store mr-1 text-[8px]"></i>Agentur</span>
                    @endif
                    <span x-show="isInactive(agency)" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-700"><i class="fas fa-circle-pause mr-1 text-[8px]"></i>Inaktiv</span>
                </div>
                <div class="text-xs text-gray-500 mt-2 space-y-1">
                    <p><i class="fas fa-location-dot mr-1 w-3"></i><span x-text="[((agency.company_street || '') + ' ' + (agency.company_house_number || '')).trim() || '—', ((agency.company_postal_code || '') + ' ' + (agency.company_city || '')).trim() || '—'].join(', ')"></span></p>
                    <p x-show="agency.phone" class="truncate"><i class="fas fa-phone mr-1 w-3"></i><a :href="'tel:' + String(agency.phone).replace(/[^0-9+]/g, '')" class="text-blue-600 hover:underline" x-text="agency.phone"></a></p>
                    <p x-show="agency.email" class="truncate"><i class="fas fa-envelope mr-1 w-3"></i><a :href="'mailto:' + agency.email" class="text-blue-600 hover:underline" x-text="agency.email"></a></p>
                </div>
            </div>
        </template>
    </div>

    {{-- Detail Modal --}}
    <div x-show="detailAgency" x-cloak class="fixed inset-0 z-[10000] flex items-center justify-center" @keydown.escape.window="detailAgency = null">
        <div class="absolute inset-0 bg-black/50" @click="detailAgency = null"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[80vh] overflow-y-auto" x-data="{ detailTab: 'kontakt' }">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-building text-blue-600"></i>
                    <span x-text="detailAgency?.company_name"></span>
                    <span class="font-mono text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded" x-text="detailAgency?.app_code"></span>
                </h3>
                <button @click="detailAgency = null" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-200 px-5">
                <button @click="detailTab = 'kontakt'"
                        :class="detailTab === 'kontakt' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-3 py-2 text-xs font-medium border-b-2 transition-colors">
                    Kontakt & Adresse
                </button>
                @if($isSuperAdmin)
                <button @click="detailTab = 'legacy'"
                        :class="detailTab === 'legacy' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-3 py-2 text-xs font-medium border-b-2 transition-colors">
                    Legacy Optionen
                </button>
                @endif
            </div>

            {{-- Tab: Kontakt & Adresse --}}
            <div x-show="detailTab === 'kontakt'" class="p-5 space-y-4">
                {{-- Kontakt --}}
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Kontakt</h4>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-xs text-gray-500 block">Name</span>
                            <span class="text-gray-900" x-text="detailAgency?.name || '—'"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">E-Mail</span>
                            <a x-show="detailAgency?.email" :href="'mailto:' + detailAgency?.email" class="text-blue-600 hover:text-blue-800 hover:underline break-all" x-text="detailAgency?.email"></a>
                            <span x-show="!detailAgency?.email" class="text-gray-900">—</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Telefon</span>
                            <span class="text-gray-900" x-text="detailAgency?.phone || '—'"></span>
                        </div>
                    </div>
                </div>

                {{-- Firmenadresse --}}
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Firmenadresse</h4>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="col-span-2">
                            <span class="text-xs text-gray-500 block">Firma</span>
                            <span class="text-gray-900" x-text="detailAgency?.company_name || '—'"></span>
                        </div>
                        <div class="col-span-2" x-show="detailAgency?.company_additional">
                            <span class="text-xs text-gray-500 block">Zusatz</span>
                            <span class="text-gray-900" x-text="detailAgency?.company_additional"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Straße</span>
                            <span class="text-gray-900" x-text="(detailAgency?.company_street || '') + ' ' + (detailAgency?.company_house_number || '') || '—'"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">PLZ / Ort</span>
                            <span class="text-gray-900" x-text="(detailAgency?.company_postal_code || '') + ' ' + (detailAgency?.company_city || '') || '—'"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Land</span>
                            <span class="text-gray-900" x-text="detailAgency?.company_country || '—'"></span>
                        </div>
                    </div>
                </div>

                {{-- Rechnungsadresse --}}
                <div x-show="detailAgency?.billing_company_name || detailAgency?.billing_street">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Rechnungsadresse</h4>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="col-span-2">
                            <span class="text-xs text-gray-500 block">Firma</span>
                            <span class="text-gray-900" x-text="detailAgency?.billing_company_name || '—'"></span>
                        </div>
                        <div class="col-span-2" x-show="detailAgency?.billing_additional">
                            <span class="text-xs text-gray-500 block">Zusatz</span>
                            <span class="text-gray-900" x-text="detailAgency?.billing_additional"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Straße</span>
                            <span class="text-gray-900" x-text="(detailAgency?.billing_street || '') + ' ' + (detailAgency?.billing_house_number || '') || '—'"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">PLZ / Ort</span>
                            <span class="text-gray-900" x-text="(detailAgency?.billing_postal_code || '') + ' ' + (detailAgency?.billing_city || '') || '—'"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Land</span>
                            <span class="text-gray-900" x-text="detailAgency?.billing_country || '—'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab: Legacy Optionen (nur für Super-Admins) --}}
            @if($isSuperAdmin)
            <div x-show="detailTab === 'legacy'" class="p-5 space-y-4">
                <template x-if="!detailAgency?.legacy_options">
                    <div class="text-sm text-gray-500 text-center py-4">Keine Legacy-Optionen vorhanden</div>
                </template>
                <template x-if="detailAgency?.legacy_options">
                    {{-- Nur diese Alt-Optionen; myJACK nur, wenn eingetragen --}}
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="col-span-2">
                            <span class="text-xs text-gray-500 block">Account Typ</span>
                            <span class="text-gray-900" x-text="({'1':'Testaccount VA','2':'Testaccount RB','3':'Veranstalter','4':'Reisebüro','5':'Reiseberater'})[detailAgency.legacy_options.account_type] || '—'"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Live ab</span>
                            <span class="text-gray-900" x-text="detailAgency.legacy_options.live_from ? new Date(detailAgency.legacy_options.live_from).toLocaleDateString('de-DE') : '—'"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Nutzungsende</span>
                            <span class="text-gray-900" x-text="detailAgency.legacy_options.end_of_use ? new Date(detailAgency.legacy_options.end_of_use).toLocaleDateString('de-DE') : '—'"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Visa Service</span>
                            <span class="text-gray-900" x-text="detailAgency.legacy_options.show_visa_service ? 'Ja' : 'Nein'"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Visa Orte</span>
                            <span class="text-gray-900" x-text="detailAgency.legacy_options.visa_places || '—'"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Reisewarnungen</span>
                            <span class="text-gray-900" x-text="detailAgency.legacy_options.show_travel_warning ? 'Ja' + (detailAgency.legacy_options.travel_warning_country ? ' (' + detailAgency.legacy_options.travel_warning_country + ')' : '') : 'Nein'"></span>
                        </div>
                        <div x-show="detailAgency.legacy_options.myjack_agency_id">
                            <span class="text-xs text-gray-500 block">myJACK Agency ID</span>
                            <span class="text-gray-900" x-text="detailAgency.legacy_options.myjack_agency_id"></span>
                        </div>
                    </div>
                </template>
            </div>
            @endif
        </div>
    </div>

    {{-- Pagination --}}
    <div class="flex items-center justify-between mt-3">
        <span class="text-xs text-gray-500" x-text="filteredAgencies.length + ' ' + @js($labelPlural)"></span>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500" x-text="'Seite ' + page + ' von ' + lastPage"></span>
            <button @click="prevPage()" :disabled="page <= 1"
                    class="px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button @click="nextPage()" :disabled="page >= lastPage"
                    class="px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>
