{{--
    Travel Requirements Service v2 – Results renderer.

    Fully DECOUPLED from the search form. Communicates only via window events:
      Listens:
        - trs:searching                -> loading=true, clear, scroll into view
        - trs:results  (event.detail)  -> store + normalize response, render
        - trs:error    (event.detail.message) -> show error box
      Dispatches:
        - trs:pdf      detail { destination, nationality }
        - trs:open-abo detail { destinations: [...codes], titles: {code:title} }

    Mirrors the pds-homepage result layout (white destination cards, accordions).
--}}
<div id="trs-results"
     x-data="trsResults()"
     x-init="init()"
     class="mt-6 scroll-mt-24">

    {{-- ===================== Loading ===================== --}}
    <template x-if="loading">
        <div class="flex flex-col items-center justify-center gap-3 py-16 bg-white rounded-2xl shadow"
             style="border-top: 4px solid #3973b9;">
            <i class="fa-solid fa-spinner fa-spin text-3xl text-pds-link"></i>
            <span class="text-sm text-gray-500">{{ __('trs.Searching') }}</span>
        </div>
    </template>

    {{-- ===================== Error ===================== --}}
    <template x-if="!loading && errorMessage">
        <div class="flex items-start gap-3 p-5 bg-red-50 border border-red-200 rounded-2xl shadow-sm">
            <i class="fa-solid fa-triangle-exclamation text-red-500 text-xl mt-0.5"></i>
            <div class="text-sm text-red-700" x-text="errorMessage"></div>
        </div>
    </template>

    {{-- ===================== No result ===================== --}}
    <template x-if="!loading && !errorMessage && response && response.ok && groups.length === 0">
        <div class="flex items-center gap-3 p-6 bg-white rounded-2xl shadow"
             style="border-top: 4px solid #3973b9;">
            <i class="fa-regular fa-circle-xmark text-gray-400 text-xl"></i>
            <span class="text-gray-600">{{ __('trs.NoResultForQuery') }}</span>
        </div>
    </template>

    {{-- ===================== Results ===================== --}}
    <template x-if="!loading && !errorMessage && groups.length > 0">
        <div>
            {{-- Heading + global Subscribe button --}}
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-pds-blue flex items-center gap-2">
                    <i class="fa-solid fa-clipboard-list text-pds-link"></i>
                    {{ __('trs.SearchResult') }}
                </h2>
                <button type="button" @click="openAbo()"
                        class="inline-flex items-center gap-2 rounded-lg bg-pds-green/80 text-pds-blue font-semibold text-sm px-4 py-2 hover:bg-pds-green transition">
                    <i class="fa-solid fa-bell"></i>
                    <span>{{ __('trs.Subscribe') }}</span>
                </button>
            </div>

            {{-- Destination cards --}}
            <div class="space-y-6">
                <template x-for="(group, gi) in groups" :key="group._key">
                    <div class="bg-white rounded-2xl shadow overflow-hidden"
                         style="border-top: 4px solid #3973b9;">

                        {{-- Card header --}}
                        <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-gray-100">
                            <div class="flex items-center gap-3 min-w-0">
                                <template x-if="group.flag">
                                    <img :src="group.flag" :alt="group.title" class="h-6 w-auto rounded shadow-sm shrink-0">
                                </template>
                                <h3 class="text-2xl font-semibold text-pds-blue truncate" x-text="group.title"></h3>
                                <template x-if="group.destination_type === 'transit'">
                                    <span class="text-sm font-normal text-gray-400 shrink-0">({{ __('trs.TransitCountry') }})</span>
                                </template>
                            </div>

                            {{-- Show all / Hide all --}}
                            <button type="button" @click="toggleAll(gi)"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 text-pds-link text-sm font-medium px-3 py-1.5 hover:bg-gray-50 transition shrink-0">
                                <i class="fa-solid" :class="group._open ? 'fa-eye-slash' : 'fa-eye'"></i>
                                <span x-text="group._open ? '{{ __('trs.HideAllContent') }}' : '{{ __('trs.ShowAllContent') }}'"></span>
                            </button>
                        </div>

                        {{-- Records (nationalities) --}}
                        <div class="divide-y divide-gray-100">
                            <template x-for="(record, ri) in group.records" :key="record._key">
                                <div>
                                    {{-- Record header (accordion) --}}
                                    <button type="button" @click="toggleRecord(gi, ri)"
                                            class="w-full flex items-center justify-between gap-3 px-6 py-3 text-left transition hover:brightness-95"
                                            style="background:#ececec;">
                                        <span class="flex items-center gap-2 min-w-0">
                                            <span class="text-sm text-gray-500 shrink-0">{{ __('trs.Nationality') }}:</span>
                                            <template x-if="record.nationality_flag">
                                                <img :src="record.nationality_flag" :alt="record.nationality_title" class="h-5 w-auto rounded shrink-0">
                                            </template>
                                            <span class="font-semibold text-pds-blue truncate" x-text="record.nationality_title"></span>
                                            <template x-if="record.entry_stopped_temporarily">
                                                <span class="ml-1 inline-flex items-center gap-1 text-xs font-medium text-red-600 shrink-0">
                                                    <i class="fa-solid fa-ban"></i>
                                                </span>
                                            </template>
                                        </span>

                                        <span class="flex items-center gap-3 shrink-0">
                                            {{-- Per-record PDF --}}
                                            <span role="button"
                                                  @click.stop="emitPdf(group, record)"
                                                  class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white text-pds-blue text-xs font-medium px-2.5 py-1 hover:bg-gray-50 transition">
                                                <i class="fa-solid fa-file-pdf text-red-600"></i>
                                                <span>{{ __('trs.ShowPDFFile') }}</span>
                                            </span>
                                            <i class="fa-solid fa-chevron-down text-gray-400 transition-transform"
                                               :class="record._open ? 'rotate-180' : ''"></i>
                                        </span>
                                    </button>

                                    {{-- Record body --}}
                                    <div x-show="record._open" x-collapse x-cloak class="bg-white">
                                        {{-- Entry stopped temporarily --}}
                                        <template x-if="record.entry_stopped_temporarily">
                                            <div class="px-6 py-4">
                                                <div class="pds-content" x-html="record.entry_stopped_content || ''"></div>
                                            </div>
                                        </template>

                                        {{-- Sections (sub-accordions) --}}
                                        <template x-if="!record.entry_stopped_temporarily">
                                            <div class="divide-y divide-gray-100">
                                                <template x-for="(section, si) in record.sections" :key="section._key">
                                                    <div>
                                                        <button type="button" @click="toggleSection(gi, ri, si)"
                                                                class="w-full flex items-center justify-between gap-3 px-6 py-2.5 text-left transition hover:brightness-95"
                                                                style="background:#f3f4f6;">
                                                            <span class="font-medium text-pds-blue truncate" x-text="section.title"></span>
                                                            <i class="fa-solid fa-chevron-down text-gray-400 transition-transform shrink-0"
                                                               :class="section._open ? 'rotate-180' : ''"></i>
                                                        </button>
                                                        <div x-show="section._open" x-collapse x-cloak class="px-6 py-4">
                                                            <div class="pds-content" x-html="section.content || ''"></div>
                                                            <template x-if="section.updated_at">
                                                                <p class="mt-3 text-xs text-gray-400">
                                                                    {{ __('trs.UpdatedAt') }}: <span x-text="fmtDate(section.updated_at)"></span>
                                                                </p>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="!record.sections || record.sections.length === 0">
                                                    <div class="px-6 py-4 text-sm text-gray-400">{{ __('trs.NoResultForQuery') }}</div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Country information (optional) --}}
                        <template x-if="group.country_information && group.country_information.topics && group.country_information.topics.length">
                            <div class="border-t border-gray-200">
                                <div class="px-6 py-3 bg-pds-blue/5">
                                    <h4 class="text-sm font-semibold text-pds-blue flex items-center gap-2">
                                        <i class="fa-solid fa-circle-info text-pds-link"></i>
                                        {{ __('trs.CountryInformation') }}
                                    </h4>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    <template x-for="(topic, ti) in group.country_information.topics" :key="topic._key">
                                        <div>
                                            <button type="button" @click="toggleTopic(gi, ti)"
                                                    class="w-full flex items-center justify-between gap-3 px-6 py-2.5 text-left transition hover:brightness-95"
                                                    style="background:#f3f4f6;">
                                                <span class="font-medium text-pds-blue truncate" x-text="topic.title"></span>
                                                <i class="fa-solid fa-chevron-down text-gray-400 transition-transform shrink-0"
                                                   :class="topic._open ? 'rotate-180' : ''"></i>
                                            </button>
                                            <div x-show="topic._open" x-collapse x-cloak class="px-6 py-4">
                                                <div class="pds-content" x-html="topic.content || ''"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Per-card Subscribe (bottom) --}}
                        <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                            <button type="button" @click="openAbo()"
                                    class="inline-flex items-center gap-2 rounded-lg border border-pds-link/40 text-pds-link text-sm font-medium px-4 py-2 hover:bg-pds-link/5 transition">
                                <i class="fa-solid fa-bell"></i>
                                <span>{{ __('trs.Subscribe') }}</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
    function trsResults() {
        return {
            loading: false,
            errorMessage: null,
            response: null,
            groups: [],

            init() {
                window.addEventListener('trs:searching', () => {
                    this.loading = true;
                    this.errorMessage = null;
                    this.response = null;
                    this.groups = [];
                    this.scrollIntoView();
                });

                window.addEventListener('trs:results', (e) => {
                    this.loading = false;
                    this.errorMessage = null;
                    this.response = (e && e.detail) ? e.detail : null;

                    if (this.response && this.response.ok === false) {
                        this.errorMessage = this.response.error || 'Error';
                        this.groups = [];
                        this.scrollIntoView();
                        return;
                    }

                    this.groups = this.normalize(this.response ? (this.response.groups || []) : []);
                    this.scrollIntoView();
                });

                window.addEventListener('trs:error', (e) => {
                    this.loading = false;
                    this.groups = [];
                    this.errorMessage = (e && e.detail && e.detail.message) ? e.detail.message : 'Error';
                    this.scrollIntoView();
                });
            },

            /* ---------- normalize: add stable keys + collapsed _open flags ---------- */
            normalize(groups) {
                return (groups || []).map((g, gi) => {
                    const records = (g.records || []).map((r, ri) => {
                        const sections = (r.sections || []).map((s, si) => ({
                            ...s,
                            _key: 'g' + gi + 'r' + ri + 's' + si + '-' + (s.key || si),
                            _open: false,
                        }));
                        return {
                            ...r,
                            sections,
                            _key: 'g' + gi + 'r' + ri + '-' + (r.nationality || ri),
                            _open: false,
                        };
                    });

                    let country_information = g.country_information || null;
                    if (country_information && Array.isArray(country_information.topics)) {
                        country_information = {
                            ...country_information,
                            topics: country_information.topics.map((t, ti) => ({
                                ...t,
                                _key: 'g' + gi + 'ci' + ti,
                                _open: false,
                            })),
                        };
                    }

                    return {
                        ...g,
                        records,
                        country_information,
                        _key: 'g' + gi + '-' + (g.destination || gi),
                        _open: false,
                    };
                });
            },

            /* ---------- toggles ---------- */
            toggleGroup(gi) {
                const g = this.groups[gi];
                if (g) g._open = !g._open;
            },

            toggleRecord(gi, ri) {
                const r = this.groups[gi] && this.groups[gi].records[ri];
                if (r) r._open = !r._open;
            },

            toggleSection(gi, ri, si) {
                const r = this.groups[gi] && this.groups[gi].records[ri];
                const s = r && r.sections[si];
                if (s) s._open = !s._open;
            },

            toggleTopic(gi, ti) {
                const ci = this.groups[gi] && this.groups[gi].country_information;
                const t = ci && ci.topics && ci.topics[ti];
                if (t) t._open = !t._open;
            },

            /* Expand/collapse everything inside one group */
            toggleAll(gi) {
                const g = this.groups[gi];
                if (!g) return;
                const next = !g._open;
                g._open = next;
                (g.records || []).forEach((r) => {
                    r._open = next;
                    (r.sections || []).forEach((s) => { s._open = next; });
                });
                if (g.country_information && Array.isArray(g.country_information.topics)) {
                    g.country_information.topics.forEach((t) => { t._open = next; });
                }
            },

            /* ---------- outbound events ---------- */
            emitPdf(group, record) {
                window.dispatchEvent(new CustomEvent('trs:pdf', {
                    detail: {
                        destination: group ? group.destination : null,
                        nationality: record ? record.nationality : null,
                    },
                }));
            },

            openAbo() {
                const destinations = [];
                const titles = {};
                this.groups.forEach((g) => {
                    if (g.destination && destinations.indexOf(g.destination) === -1) {
                        destinations.push(g.destination);
                        titles[g.destination] = g.title || g.destination;
                    }
                });
                window.dispatchEvent(new CustomEvent('trs:open-abo', {
                    detail: { destinations, titles },
                }));
            },

            /* ---------- helpers ---------- */
            scrollIntoView() {
                this.$nextTick(() => {
                    const el = this.$root || document.getElementById('trs-results');
                    if (el && typeof el.scrollIntoView === 'function') {
                        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            },

            fmtDate(value) {
                if (!value) return '';
                const d = new Date(value);
                if (isNaN(d.getTime())) return value;
                try {
                    return d.toLocaleDateString(document.documentElement.lang || undefined, {
                        year: 'numeric', month: '2-digit', day: '2-digit',
                    });
                } catch (e) {
                    return value;
                }
            },
        };
    }
</script>
@endpush
