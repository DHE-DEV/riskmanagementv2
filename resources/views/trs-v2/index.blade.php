@extends('trs-v2.layout')

@section('title', __('trs.GeneralEntryRequirements'))

@section('content')
<main>
    <section class="relative">
        {{-- Hero band --}}
        <div class="h-[260px] sm:h-[320px] bg-cover bg-center" style="background-image: url('{{ asset('img/trs/header_one.jpg') }}');"></div>

        {{-- Search card overlapping the hero --}}
        <div class="relative z-10 -mt-[220px] sm:-mt-[270px] px-3 pb-8">
            <div class="mx-auto w-full max-w-card" x-data="trsSearch()" x-cloak>
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">

                    {{-- ===== Tabs ===== --}}
                    <div class="flex">
                        @php
                            $tabs = [
                                'ptd' => __('trs.GeneralEntryRequirements'),
                                'business' => __('trs.BusinessVisa'),
                                'cruise' => __('trs.CruiseRoutes'),
                            ];
                        @endphp
                        @foreach($tabs as $key => $title)
                            <button type="button" @click="tab = '{{ $key }}'"
                                    class="flex-1 text-center px-2 py-3 text-xs sm:text-sm leading-tight transition {{ !$loop->first ? 'border-l border-white/30' : '' }}"
                                    :class="tab === '{{ $key }}' ? 'bg-white text-pds-blue font-bold' : 'bg-pds-tab text-white hover:bg-gray-500'">
                                {{ $title }}
                            </button>
                        @endforeach
                    </div>

                    {{-- ===== Body ===== --}}
                    <div class="p-4 sm:p-6">

                        {{-- ========== TAB 1: General entry requirements ========== --}}
                        <div x-show="tab === 'ptd'">
                            <div class="md:flex md:gap-6">
                                {{-- left column --}}
                                <div class="flex-1 space-y-4 md:pr-6 md:border-r md:border-gray-200">
                                    @include('trs-v2.partials.select', ['uid'=>'ptd-dest','label'=>__('trs.Destinations'),'path'=>'ptd.destinations','optionsKey'=>'countries','multiple'=>true,'help'=>__('trs.SelectDestinationsHelpText')])
                                    @include('trs-v2.partials.select', ['uid'=>'ptd-transit','label'=>__('trs.TransitDestinations'),'path'=>'ptd.transit','optionsKey'=>'countries','multiple'=>true])
                                    @include('trs-v2.partials.select', ['uid'=>'ptd-nat','label'=>__('trs.Nationalities'),'path'=>'ptd.nationalities','optionsKey'=>'nationalities','multiple'=>true,'help'=>__('trs.SelectNationalitiesHelpText')])
                                </div>
                                {{-- right column --}}
                                <div class="flex-1 space-y-4 mt-4 md:mt-0 md:min-w-[300px]">
                                    @include('trs-v2.partials.select', ['uid'=>'ptd-lang','label'=>__('trs.Language'),'path'=>'ptd.language','optionsKey'=>'languages','multiple'=>false,'help'=>__('trs.SelectLanguageHelpText')])

                                    <div x-show="tourOperators.length">
                                        @include('trs-v2.partials.select', ['uid'=>'ptd-to','label'=>__('trs.TourOperators'),'path'=>'ptd.tourOperators','optionsKey'=>'tourOperators','multiple'=>true,'help'=>__('trs.SelectTourOperatorsHelpText')])
                                    </div>

                                    {{-- transport modes --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('trs.TransportModes') }}</label>
                                        <div class="flex flex-wrap gap-3">
                                            @foreach(['air'=>['fa-plane', __('trs.PossibleByAir')], 'land'=>['fa-car', __('trs.PossibleByLand')], 'sea'=>['fa-ship', __('trs.PossibleBySea')]] as $mode => $cfg)
                                                <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border cursor-pointer text-sm transition"
                                                       :class="form.ptd.modes.{{ $mode }} ? 'border-pds-blue bg-pds-blue/5 text-pds-blue' : 'border-gray-300 text-gray-600'">
                                                    <input type="checkbox" class="sr-only" x-model="form.ptd.modes.{{ $mode }}">
                                                    <i class="fa-solid {{ $cfg[0] }}"></i>
                                                    <span>{{ $cfg[1] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- toggles --}}
                                    <div class="space-y-2 pt-1">
                                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" x-model="form.ptd.withMinors" class="w-4 h-4 rounded border-gray-300 text-pds-blue focus:ring-pds-green">
                                            {{ __('trs.TravellingWithMinors') }}
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" x-model="form.ptd.showCountryInfo" class="w-4 h-4 rounded border-gray-300 text-pds-blue focus:ring-pds-green">
                                            {{ __('trs.IncludeCountryInformation') }}
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" x-model="form.ptd.showReturn" class="w-4 h-4 rounded border-gray-300 text-pds-blue focus:ring-pds-green">
                                            {{ __('trs.ShowReturnTravelRequirements') }}
                                        </label>
                                    </div>

                                    <div x-show="form.ptd.showReturn" x-cloak>
                                        @include('trs-v2.partials.select', ['uid'=>'ptd-return','label'=>__('trs.ReturnCountry'),'path'=>'ptd.returnCountry','optionsKey'=>'countries','multiple'=>false])
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ========== TAB 2: Business visa ========== --}}
                        <div x-show="tab === 'business'" x-cloak>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @include('trs-v2.partials.select', ['uid'=>'bv-dest','label'=>__('trs.Destinations'),'path'=>'business.destinations','optionsKey'=>'countries','multiple'=>true,'help'=>__('trs.SelectDestinationsHelpText')])
                                    @include('trs-v2.partials.select', ['uid'=>'bv-lang','label'=>__('trs.Language'),'path'=>'business.language','optionsKey'=>'languages','multiple'=>false])
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('trs.TripStartDate') }}</label>
                                        <input type="date" x-model="form.business.tripStart" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pds-green">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('trs.TripEndDate') }}</label>
                                        <input type="date" x-model="form.business.tripEnd" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pds-green">
                                    </div>
                                </div>

                                {{-- travellers --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('trs.Travellers') }}</label>
                                    <div class="space-y-3">
                                        <template x-for="(t, idx) in form.business.travellers" :key="idx">
                                            <div class="rounded-lg border border-gray-200 p-3 bg-gray-50">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs font-semibold text-gray-500" x-text="'{{ __('trs.Traveller') }} ' + (idx + 1)"></span>
                                                    <button type="button" x-show="form.business.travellers.length > 1" @click="removeTraveller(idx)" class="text-xs text-red-600 hover:underline">
                                                        <i class="fa-solid fa-xmark"></i> {{ __('trs.RemoveTraveller') }}
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">{{ __('trs.Nationality') }}</label>
                                                        <select x-model="t.nationality" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm bg-white">
                                                            <option value="">{{ __('trs.SelectPlaceholder') }}</option>
                                                            <template x-for="n in nationalities" :key="'bvn'+idx+n.code"><option :value="n.code" x-text="n.name"></option></template>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">{{ __('trs.ResidenceCountry') }}</label>
                                                        <select x-model="t.residence" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm bg-white">
                                                            <option value="">{{ __('trs.SelectPlaceholder') }}</option>
                                                            <template x-for="c in countries" :key="'bvr'+idx+c.code"><option :value="c.code" x-text="c.name"></option></template>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">{{ __('trs.SecondaryNationality') }} <span class="text-gray-400">({{ __('trs.Optional') }})</span></label>
                                                        <select x-model="t.secondary" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm bg-white">
                                                            <option value="">{{ __('trs.SelectPlaceholder') }}</option>
                                                            <template x-for="n in nationalities" :key="'bvs'+idx+n.code"><option :value="n.code" x-text="n.name"></option></template>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">{{ __('trs.Purpose') }}</label>
                                                        <select x-model="t.purpose" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm bg-white">
                                                            <option value="">{{ __('trs.SelectPlaceholder') }}</option>
                                                            <template x-for="p in purposes" :key="'bvp'+idx+p.code"><option :value="p.code" x-text="p.label"></option></template>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button" @click="addTraveller()" class="mt-2 text-sm text-pds-blue hover:underline">
                                        <i class="fa-solid fa-plus"></i> {{ __('trs.AddTraveller') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- ========== TAB 3: Cruise routes ========== --}}
                        <div x-show="tab === 'cruise'" x-cloak>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('trs.ShippingLine') }}</label>
                                    <select x-model="form.cruise.line" @change="onCruiseLineChange()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white">
                                        <option value="">{{ __('trs.SelectPlaceholder') }}</option>
                                        <template x-for="l in cruiseLines" :key="l.id"><option :value="l.id" x-text="l.name"></option></template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('trs.ShipName') }}</label>
                                    <select x-model="form.cruise.ship" @change="onCruiseShipChange()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white disabled:bg-gray-100" :disabled="!form.cruise.line || cruiseLoading.ships">
                                        <option value="">{{ __('trs.SelectPlaceholder') }}</option>
                                        <template x-for="s in cruiseShips" :key="s.id"><option :value="s.id" x-text="s.name"></option></template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('trs.RouteName') }}</label>
                                    <select x-model="form.cruise.route" @change="onCruiseRouteChange()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white disabled:bg-gray-100" :disabled="!form.cruise.ship || cruiseLoading.routes">
                                        <option value="">{{ __('trs.SelectPlaceholder') }}</option>
                                        <template x-for="r in cruiseRoutes" :key="r.id"><option :value="r.id" x-text="r.name"></option></template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('trs.CruiseDate') }}</label>
                                    <select x-model="form.cruise.date" @change="onCruiseDateChange()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white disabled:bg-gray-100" :disabled="!form.cruise.route || cruiseLoading.cruises">
                                        <option value="">{{ __('trs.SelectPlaceholder') }}</option>
                                        <template x-for="c in cruiseDates" :key="c.date">
                                            <option :value="c.date" x-text="c.date + ' · ' + c.duration_in_days + ' {{ __('trs.Days') }}'"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('trs.CruiseLength') }} ({{ __('trs.Days') }})</label>
                                    <input type="number" min="1" x-model.number="form.cruise.days" readonly class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-gray-100 text-gray-600">
                                </div>
                                @include('trs-v2.partials.select', ['uid'=>'cr-lang','label'=>__('trs.Language'),'path'=>'cruise.language','optionsKey'=>'languages','multiple'=>false])
                            </div>
                            <div class="mt-4">
                                @include('trs-v2.partials.select', ['uid'=>'cr-nat','label'=>__('trs.Nationalities'),'path'=>'cruise.nationalities','optionsKey'=>'nationalities','multiple'=>true,'help'=>__('trs.SelectNationalitiesHelpText')])
                            </div>
                        </div>

                        {{-- ===== Search button ===== --}}
                        <div class="mt-6">
                            <button type="button" @click="submit()" :disabled="loading"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-pds-blue text-white font-semibold py-3 px-6 hover:bg-pds-blue2 disabled:opacity-60 transition">
                                <i class="fa-solid fa-magnifying-glass" x-show="!loading"></i>
                                <i class="fa-solid fa-spinner fa-spin" x-show="loading" x-cloak></i>
                                <span x-text="loading ? '{{ __('trs.Searching') }}' : '{{ __('trs.Search') }}'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ===== Results (decoupled trsResults component, listens on window events) ===== --}}
                @include('trs-v2.partials.results')
            </div>
        </div>
    </section>
</main>

{{-- Abo subscription modal (listens for trs:open-abo) --}}
@include('trs-v2.partials.abo-modal')
@endsection

@push('scripts')
<script>
    window.__TRS__ = {
        lang: @json($locale),
        countries: @json($countries),
        nationalities: @json($nationalities),
        languages: @json($languages),
        tourOperators: @json($tourOperators),
        selectedTpl: @json(__('trs.SelectedCount', ['count' => '%n%'])),
        purposes: [
            { code: 'MEETINGS_WITH_OR_FOR_A_CLIENT', label: @json(__('trs.Purpose')) + ' – Meetings' },
            { code: 'INTERNAL_BUSINESS_WITHOUT_WORK_FOR_CLIENT', label: @json(__('trs.Purpose')) + ' – Internal business' },
        ],
    };

    function trsSearch() {
        const D = window.__TRS__ || {};
        return {
            tab: 'ptd',
            openId: null,
            queries: {},
            loading: false,
            searched: false,

            // lookup data
            countries: D.countries || [],
            nationalities: D.nationalities || [],
            languages: D.languages || [],
            tourOperators: D.tourOperators || [],
            purposes: D.purposes || [],
            cruiseLines: [],
            cruiseShips: [],
            cruiseRoutes: [],
            cruiseDates: [],
            cruiseLinesLoaded: false,
            cruiseLoading: { ships: false, routes: false, cruises: false },
            selectedTpl: D.selectedTpl || '%n%',

            form: {
                ptd: {
                    destinations: [], transit: [], nationalities: [],
                    language: D.lang || 'de',
                    tourOperators: [], returnCountry: null,
                    withMinors: true, showCountryInfo: true, showReturn: false,
                    modes: { air: true, land: false, sea: false },
                },
                business: {
                    destinations: [], language: D.lang || 'de',
                    tripStart: '', tripEnd: '',
                    travellers: [{ nationality: '', residence: '', secondary: '', purpose: '' }],
                },
                cruise: {
                    line: '', ship: '', route: '', date: '', days: null,
                    nationalities: [], language: D.lang || 'de',
                },
            },

            // ---- path helpers ----
            _ref(path) {
                const parts = path.split('.');
                let obj = this.form;
                for (let i = 0; i < parts.length - 1; i++) obj = obj[parts[i]];
                return { obj, key: parts[parts.length - 1] };
            },
            get(path) { const { obj, key } = this._ref(path); return obj[key]; },
            set(path, val) { const { obj, key } = this._ref(path); obj[key] = val; },

            // ---- multi-select ----
            has(path, code) { const a = this.get(path); return Array.isArray(a) && a.includes(code); },
            toggleValue(path, code) {
                const a = this.get(path); const i = a.indexOf(code);
                if (i >= 0) a.splice(i, 1); else a.push(code);
            },
            // ---- single-select ----
            is(path, code) { return this.get(path) === code; },
            setValue(path, code) { this.set(path, code); },

            // ---- options ----
            options(key) { return this[key] || []; },
            filtered(key, uid) {
                const q = (this.queries[uid] || '').toLowerCase().trim();
                const opts = this.options(key);
                if (!q) return opts;
                return opts.filter(o => o.name.toLowerCase().includes(q) || (o.code || '').toLowerCase().includes(q));
            },
            label(key, code) { const o = this.options(key).find(o => o.code === code); return o ? o.name : code; },
            summary(path, key, multiple) {
                const v = this.get(path);
                if (multiple) {
                    if (!Array.isArray(v) || !v.length) return '';
                    if (v.length === 1) return this.label(key, v[0]);
                    return this.selectedTpl.replace('%n%', v.length);
                }
                if (v === null || v === undefined || v === '') return '';
                return this.label(key, v);
            },

            // ---- dropdown ui ----
            toggleDropdown(uid) {
                this.openId = (this.openId === uid) ? null : uid;
                if (this.openId === uid && !(uid in this.queries)) this.queries[uid] = '';
            },

            // ---- travellers ----
            addTraveller() { this.form.business.travellers.push({ nationality: '', residence: '', secondary: '', purpose: '' }); },
            removeTraveller(i) { this.form.business.travellers.splice(i, 1); },

            // ---- lifecycle ----
            init() {
                // The per-record "PDF" button inside the results dispatches trs:pdf.
                window.addEventListener('trs:pdf', (e) => {
                    const p = new URLSearchParams();
                    if (e.detail && e.detail.destination) p.set('destination', e.detail.destination);
                    if (e.detail && e.detail.nationality) p.set('nationality', e.detail.nationality);
                    window.open('{{ route('travel-requirements-service-v2.pdf') }}?' + p.toString(), '_blank');
                });
                // Lazy-load cruise lines the first time the cruise tab is opened.
                this.$watch('tab', (value) => {
                    if (value === 'cruise' && !this.cruiseLinesLoaded) this.loadCruiseLines();
                });
            },

            // ---- cruise dependent dropdowns ----
            async cruiseFetch(url, body) {
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(body),
                    });
                    const json = await res.json();
                    return Array.isArray(json.data) ? json.data : [];
                } catch (e) {
                    return [];
                }
            },
            async loadCruiseLines() {
                this.cruiseLinesLoaded = true;
                this.cruiseLines = await this.cruiseFetch('{{ route('travel-requirements-service-v2.cruise.lines') }}', {});
            },
            async onCruiseLineChange() {
                this.form.cruise.ship = ''; this.form.cruise.route = ''; this.form.cruise.date = ''; this.form.cruise.days = null;
                this.cruiseShips = []; this.cruiseRoutes = []; this.cruiseDates = [];
                if (!this.form.cruise.line) return;
                this.cruiseLoading.ships = true;
                this.cruiseShips = await this.cruiseFetch('{{ route('travel-requirements-service-v2.cruise.ships') }}', { line_id: this.form.cruise.line });
                this.cruiseLoading.ships = false;
            },
            async onCruiseShipChange() {
                this.form.cruise.route = ''; this.form.cruise.date = ''; this.form.cruise.days = null;
                this.cruiseRoutes = []; this.cruiseDates = [];
                if (!this.form.cruise.ship) return;
                this.cruiseLoading.routes = true;
                this.cruiseRoutes = await this.cruiseFetch('{{ route('travel-requirements-service-v2.cruise.routes') }}', { ship_id: this.form.cruise.ship });
                this.cruiseLoading.routes = false;
            },
            async onCruiseRouteChange() {
                this.form.cruise.date = ''; this.form.cruise.days = null;
                this.cruiseDates = [];
                if (!this.form.cruise.route) return;
                this.cruiseLoading.cruises = true;
                this.cruiseDates = await this.cruiseFetch('{{ route('travel-requirements-service-v2.cruise.cruises') }}', { route_id: this.form.cruise.route });
                this.cruiseLoading.cruises = false;
            },
            onCruiseDateChange() {
                const c = this.cruiseDates.find((x) => x.date === this.form.cruise.date);
                this.form.cruise.days = c ? c.duration_in_days : null;
            },

            // ---- submit ----
            async submit() {
                const payload = this.buildPayload();
                this.loading = true;
                this.searched = true;
                window.dispatchEvent(new CustomEvent('trs:searching'));
                try {
                    const res = await fetch('{{ route('travel-requirements-service-v2.search') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload),
                    });
                    const json = await res.json();
                    if (!res.ok || !json.ok) {
                        window.dispatchEvent(new CustomEvent('trs:error', { detail: { message: (json && json.error) || 'Fehler bei der Suche.' } }));
                    } else {
                        window.dispatchEvent(new CustomEvent('trs:results', { detail: json }));
                    }
                } catch (err) {
                    window.dispatchEvent(new CustomEvent('trs:error', { detail: { message: 'Netzwerkfehler.' } }));
                } finally {
                    this.loading = false;
                }
            },
            buildPayload() {
                if (this.tab === 'ptd') return { tab: 'ptd', ...this.form.ptd };
                if (this.tab === 'business') return { tab: 'business', ...this.form.business };
                // cruise: resolve the cruise-compass id from the selected date (or route level).
                const payload = {
                    tab: 'cruise',
                    nationalities: this.form.cruise.nationalities,
                    language: this.form.cruise.language,
                };
                const sel = this.cruiseDates.find((x) => x.date === this.form.cruise.date);
                if (sel && sel.cruise_compass_cruise_id) {
                    payload.cruise_compass_cruise_id = sel.cruise_compass_cruise_id;
                } else {
                    const any = this.cruiseDates.find((x) => x.cruise_compass_route_id);
                    if (any) payload.cruise_compass_route_id = any.cruise_compass_route_id;
                }
                return payload;
            },
        };
    }
</script>
@endpush
