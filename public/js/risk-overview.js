function riskOverviewApp() {
    return {
        countries: [],
        summary: {
            total_countries: 0,
            total_events: 0,
            total_affected_travelers: 0,
        },
        selectedCountry: null,
        countryDetails: null,
        selectedEvent: null,
        showEventModal: false,
        selectedTraveler: null,
        showTravelerModal: false,
        loading: false,
        loadingCountryDetails: false,
        error: null,
        map: null,
        markers: [],
        filters: {
            priority: null,
            days: 30,
            onlyWithTravelers: false,
            onlyWithEvents: true,
            country: '',
            customDateRange: false,
            dateFrom: '',
            dateTo: '',
        },
        filterOpen: false,
        showTripFilters: false,
        showCountryFilters: false,
        isDebugUser: window.__riskOverviewConfig.isDebugUser,
        debugLogs: [],
        showCountrySidebar: false,
        sidebarTab: 'reisen',
        activeTab: 'tiles',
        maximizedSection: null,
        selectedTrip: null,
        selectedTripCountry: null,
        tripActiveTab: 'tiles',
        tripMaximizedSection: null,
        trips: [],
        tripsSummary: { total_trips: 0, trips_with_events: 0, total_events_across_trips: 0 },
        loadingTrips: false,
        tripsLoaded: false,
        countriesStale: false,
        filterDebounceTimer: null,

        // Labels
        labelInput: '',
        labelSuggestions: [],
        labelLoading: false,
        showLabelSuggestions: false,
        labelDebounceTimer: null,

        async fetchApi(endpoint, options = {}) {
            const headers = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                ...options.headers,
            };
            const response = await fetch(endpoint, { ...options, headers });
            if (!response.ok) {
                throw new Error(options.errorMessage || 'Fehler beim Laden der Daten');
            }
            return response.json();
        },

        buildFilterParams({ includePriority = true } = {}) {
            const params = new URLSearchParams();
            if (includePriority && this.filters.priority) {
                params.append('priority', this.filters.priority);
            }
            if (this.filters.customDateRange && this.filters.dateFrom) {
                params.append('date_from', this.filters.dateFrom);
                if (this.filters.dateTo) {
                    params.append('date_to', this.filters.dateTo);
                }
            } else {
                params.append('days', this.filters.days);
            }
            return params;
        },

        toggleMaximize(section) {
            if (this.maximizedSection === section) {
                this.maximizedSection = null;
            } else {
                this.maximizedSection = section;
            }
        },

        toggleTripMaximize(section) {
            if (this.tripMaximizedSection === section) {
                this.tripMaximizedSection = null;
            } else {
                this.tripMaximizedSection = section;
            }
        },

        selectTrip(trip, countryCode = null) {
            this.selectedTrip = trip;
            this.selectedTripCountry = countryCode;
            this.tripMaximizedSection = null;
            this.labelInput = '';
            this.labelSuggestions = [];
            this.showLabelSuggestions = false;
        },

        searchLabels() {
            clearTimeout(this.labelDebounceTimer);
            if (this.labelInput.trim().length === 0) {
                this.labelSuggestions = [];
                this.showLabelSuggestions = false;
                return;
            }
            this.labelDebounceTimer = setTimeout(async () => {
                this.labelLoading = true;
                try {
                    const resp = await fetch(`${window.__riskOverviewConfig.routes.labelsSearch}?q=${encodeURIComponent(this.labelInput.trim())}`);
                    const data = await resp.json();
                    // Filter out already attached labels
                    const existingIds = (this.selectedTrip.labels || []).map(l => l.id);
                    this.labelSuggestions = data.filter(l => !existingIds.includes(l.id));
                    this.showLabelSuggestions = true;
                } catch (e) {
                    console.error('Label search error', e);
                } finally {
                    this.labelLoading = false;
                }
            }, 250);
        },

        async attachLabel(labelId, labelName) {
            if (!this.selectedTrip?.folder_id || this.selectedTrip.source === 'api') return;
            try {
                const body = labelId ? { label_id: labelId } : { name: labelName };
                const data = await this.fetchApi(`/risk-overview/folder/${this.selectedTrip.folder_id}/labels`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body),
                });
                if (data.success) {
                    this.selectedTrip.labels = data.labels;
                    // Also update in trips array
                    const idx = this.trips.findIndex(t => t.folder_id === this.selectedTrip.folder_id);
                    if (idx !== -1) this.trips[idx].labels = data.labels;
                }
            } catch (e) {
                console.error('Attach label error', e);
            }
            this.labelInput = '';
            this.labelSuggestions = [];
            this.showLabelSuggestions = false;
        },

        async detachLabel(labelId) {
            if (!this.selectedTrip?.folder_id || this.selectedTrip.source === 'api') return;
            try {
                const data = await this.fetchApi(`/risk-overview/folder/${this.selectedTrip.folder_id}/labels/${labelId}`, {
                    method: 'DELETE',
                });
                if (data.success) {
                    this.selectedTrip.labels = data.labels;
                    const idx = this.trips.findIndex(t => t.folder_id === this.selectedTrip.folder_id);
                    if (idx !== -1) this.trips[idx].labels = data.labels;
                }
            } catch (e) {
                console.error('Detach label error', e);
            }
        },

        addLabelFromInput() {
            const name = this.labelInput.trim();
            if (!name) return;
            // Check if there's an exact match in suggestions
            const match = this.labelSuggestions.find(l => l.name.toLowerCase() === name.toLowerCase());
            if (match) {
                this.attachLabel(match.id, null);
            } else {
                this.attachLabel(null, name);
            }
        },

        getTripCountryPriority(trip, countryCode) {
            if (!trip.events || trip.events.length === 0) return null;
            const priorityOrder = { high: 0, medium: 1, low: 2, info: 3 };
            let highest = null;
            trip.events.forEach(e => {
                if (e.matched_countries && e.matched_countries.some(mc => mc.code === countryCode)) {
                    if (highest === null || priorityOrder[e.priority] < priorityOrder[highest]) {
                        highest = e.priority;
                    }
                }
            });
            return highest;
        },

        get filteredTripEvents() {
            if (!this.selectedTrip?.events) return [];
            if (!this.selectedTripCountry) return this.selectedTrip.events;
            return this.selectedTrip.events.filter(e =>
                e.matched_countries && e.matched_countries.some(mc => mc.code === this.selectedTripCountry)
            );
        },

        get filteredTrips() {
            let result = this.trips;
            if (this.filters.priority) {
                result = result.filter(t => t.events && t.events.some(e => e.priority === this.filters.priority));
            }
            if (this.filters.onlyWithEvents) {
                result = result.filter(t => t.total_events > 0);
            }

            // Filter by travel date overlap (skip for "Alle" = -1)
            if (this.filters.days !== -1) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                let rangeStart, rangeEnd;

                if (this.filters.customDateRange && this.filters.dateFrom) {
                    rangeStart = new Date(this.filters.dateFrom);
                    rangeStart.setHours(0, 0, 0, 0);
                    rangeEnd = this.filters.dateTo ? new Date(this.filters.dateTo) : new Date(rangeStart);
                    rangeEnd.setHours(0, 0, 0, 0);
                } else {
                    rangeStart = new Date(today);
                    rangeEnd = new Date(today);
                    if (this.filters.days > 0) {
                        rangeEnd.setDate(rangeEnd.getDate() + this.filters.days);
                    }
                }

                result = result.filter(t => {
                    const tripStart = new Date(t.start_date);
                    tripStart.setHours(0, 0, 0, 0);
                    const tripEnd = new Date(t.end_date);
                    tripEnd.setHours(0, 0, 0, 0);
                    return tripStart <= rangeEnd && tripEnd >= rangeStart;
                });
            }

            return result;
        },

        get filteredTripsSummary() {
            const filtered = this.filteredTrips;
            let tripsWithEvents = 0;
            let totalEvents = 0;
            filtered.forEach(t => {
                if (t.total_events > 0) tripsWithEvents++;
                totalEvents += t.total_events;
            });
            return {
                total_trips: this.trips.length,
                trips_with_events: tripsWithEvents,
                total_events_across_trips: totalEvents,
            };
        },

        applyFilters() {
            clearTimeout(this.filterDebounceTimer);
            this.filterDebounceTimer = setTimeout(async () => {
                if (this.sidebarTab === 'reisen') {
                    // Reisen-Tab: only reload trips, mark countries as stale
                    this.countriesStale = true;
                    await this.loadTrips();
                } else {
                    // Länder-Tab: only reload countries, mark trips as stale
                    this.tripsLoaded = false;
                    await this.loadData();
                }
            }, 300);
        },

        get filteredCountries() {
            let result = this.countries;

            // Filter by country
            if (this.filters.country) {
                result = result.filter(c => c.country.code === this.filters.country);
            }

            // Filter by travelers
            if (this.filters.onlyWithTravelers) {
                result = result.filter(c => c.affected_travelers > 0);
            }

            return result;
        },

        get filteredSummary() {
            const filtered = this.filteredCountries;
            let totalEvents = 0;
            let totalAffectedTravelers = 0;
            filtered.forEach(c => {
                totalEvents += c.total_events;
                totalAffectedTravelers += c.affected_travelers;
            });
            return {
                total_countries: filtered.length,
                total_events: totalEvents,
                total_affected_travelers: totalAffectedTravelers,
            };
        },

        init() {
            this.$nextTick(() => {
                this.initMap();
                this.loadData();
                this.loadTrips();

                // Lazy-load: reload stale data when switching sidebar tabs
                this.$watch('sidebarTab', (newTab) => {
                    if (newTab === 'laender' && this.countriesStale) {
                        this.loadData();
                    } else if (newTab === 'reisen' && !this.tripsLoaded) {
                        this.loadTrips();
                    }
                });

                // ESC key to close modals
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        if (this.showTravelerModal) {
                            this.closeTravelerModal();
                        } else if (this.showEventModal) {
                            this.closeEventModal();
                        }
                    }
                });

                // Watch for client-side filter changes to update map and auto-select first result
                this.$watch('filters.onlyWithTravelers', () => {
                    this.updateMapMarkers();
                    this.reselectCountryIfNeeded();
                });

                this.$watch('filters.country', () => {
                    this.updateMapMarkers();
                    this.reselectCountryIfNeeded();
                });

                // Watch for tab changes to invalidate map size
                this.$watch('activeTab', (newTab) => {
                    if (newTab === 'map' && this.map) {
                        setTimeout(() => {
                            this.map.invalidateSize();
                        }, 100);
                    }
                    // Close sidebar when switching to list view
                    if (newTab === 'list') {
                        this.showCountrySidebar = false;
                    }
                });

                // Watch for trip filter panel toggle to invalidate map size
                this.$watch('showTripFilters', () => {
                    setTimeout(() => {
                        if (this.map) this.map.invalidateSize();
                    }, 350);
                });

                // Watch for country filter panel toggle to invalidate map size
                this.$watch('showCountryFilters', () => {
                    setTimeout(() => {
                        if (this.map) this.map.invalidateSize();
                    }, 350);
                });
            });
        },

        initMap() {
            this.map = L.map('risk-map', {
                center: [30.0, 10.0],
                zoom: 3,
                zoomControl: true,
                worldCopyJump: false,
                maxBounds: [[-90, -180], [90, 180]],
                minZoom: 2
            });

            L.tileLayer('https://tile.openstreetmap.de/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(this.map);

            // Window Resize
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    if (this.map) {
                        this.map.invalidateSize();
                    }
                }, 250);
            });

            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                }
            }, 100);
        },

        async loadData() {
            this.loading = true;
            this.error = null;
            const fetchStart = performance.now();

            try {
                const params = this.buildFilterParams();
                const endpoint = `${window.__riskOverviewConfig.routes.data}?${params.toString()}`;
                const result = await this.fetchApi(endpoint, { errorMessage: 'Fehler beim Laden der Daten' });
                this.logDebug('getData', Object.fromEntries(params), result, fetchStart, endpoint);

                if (result.success) {
                    this.countries = result.data.countries;
                    this.summary = result.data.summary;
                    this.updateMapMarkers();

                    // Mark trips data as stale so it reloads on next tab visit
                    this.tripsLoaded = false;

                    // Re-select country if one was already selected (e.g. after filter change)
                    this.reselectCountryIfNeeded();
                } else {
                    throw new Error(result.message || 'Unbekannter Fehler');
                }
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        async loadTrips() {
            this.loadingTrips = true;
            const fetchStart = performance.now();

            try {
                const params = this.buildFilterParams();
                const endpoint = `${window.__riskOverviewConfig.routes.trips}?${params.toString()}`;
                const result = await this.fetchApi(endpoint, { errorMessage: 'Fehler beim Laden der Reisen' });
                this.logDebug('getTrips', Object.fromEntries(params), result, fetchStart, endpoint);

                if (result.success) {
                    this.trips = result.data.trips;
                    this.tripsSummary = result.data.summary;
                    this.tripsLoaded = true;
                }
            } catch (e) {
                console.error('Error loading trips:', e);
            } finally {
                this.loadingTrips = false;
            }
        },

        updateMapMarkers() {
            // Clear existing markers
            this.markers.forEach(marker => this.map.removeLayer(marker));
            this.markers = [];

            // Add markers for each country (use filtered list)
            this.filteredCountries.forEach(country => {
                if (country.country.lat && country.country.lng) {
                    const markerColor = this.getPriorityColor(country.highest_priority);
                    const markerSize = Math.min(30, 20 + country.total_events * 2);

                    const icon = L.divIcon({
                        className: 'event-marker event-marker-' + country.highest_priority,
                        html: `<span>${country.total_events}</span>`,
                        iconSize: [markerSize, markerSize],
                        iconAnchor: [markerSize / 2, markerSize / 2],
                    });

                    const marker = L.marker([country.country.lat, country.country.lng], { icon })
                        .addTo(this.map)
                        .on('click', () => this.selectCountry(country));

                    // Tooltip
                    marker.bindTooltip(`
                        <strong>${country.country.name}</strong><br>
                        ${country.total_events} Ereignis${country.total_events !== 1 ? 'se' : ''}<br>
                        ${country.affected_travelers} ${country.affected_travelers === 1 ? 'Reise' : 'Reisen'}
                    `, {
                        direction: 'top',
                        offset: [0, -markerSize / 2]
                    });

                    this.markers.push(marker);
                }
            });

            // Fit bounds if we have markers
            if (this.markers.length > 0) {
                const group = L.featureGroup(this.markers);
                this.map.fitBounds(group.getBounds().pad(0.1));
            }
        },

        getPriorityColor(priority) {
            const colors = {
                high: '#ef4444',
                medium: '#f97316',
                low: '#eab308',
                info: '#3b82f6',
            };
            return colors[priority] || colors.info;
        },

        async selectCountry(country) {
            this.selectedCountry = country;
            this.loadingCountryDetails = true;
            this.countryDetails = null;
            const fetchStart = performance.now();

            // Only show sidebar in map view
            if (this.activeTab === 'map') {
                this.showCountrySidebar = true;
                // Center map on country
                if (country.country.lat && country.country.lng) {
                    this.map.setView([country.country.lat, country.country.lng], 5);
                }
            }

            try {
                const params = this.buildFilterParams({ includePriority: false });
                const endpoint = `${window.__riskOverviewConfig.routes.country}/${country.country.code}?${params.toString()}`;
                const result = await this.fetchApi(endpoint, { errorMessage: 'Fehler beim Laden der Details' });
                this.logDebug('getCountryDetails(' + country.country.code + ')', Object.fromEntries(params), result, fetchStart, endpoint);

                if (result.success) {
                    this.countryDetails = result.data;
                }
            } catch (e) {
                console.error('Error loading country details:', e);
            } finally {
                this.loadingCountryDetails = false;
            }
        },

        closeCountrySidebar() {
            this.showCountrySidebar = false;
            this.selectedCountry = null;
            this.countryDetails = null;
        },

        openEventModal(event) {
            this.selectedEvent = event;
            this.showEventModal = true;
            document.body.style.overflow = 'hidden';
        },

        closeEventModal() {
            this.showEventModal = false;
            this.selectedEvent = null;
            document.body.style.overflow = '';
        },

        openTravelerModal(traveler) {
            this.selectedTraveler = traveler;
            this.showTravelerModal = true;
            document.body.style.overflow = 'hidden';
        },

        closeTravelerModal() {
            this.showTravelerModal = false;
            this.selectedTraveler = null;
            document.body.style.overflow = '';
        },

        reselectCountryIfNeeded() {
            // Only re-select if a country was already selected by the user
            if (!this.selectedCountry) {
                return;
            }

            // Check if the currently selected country is still in the filtered list
            const stillVisible = this.filteredCountries.find(
                c => c.country.code === this.selectedCountry.country.code
            );

            if (stillVisible) {
                // Refresh details with updated data
                this.selectCountry(stillVisible);
            } else {
                // Selected country no longer in filtered results, clear selection
                this.selectedCountry = null;
                this.countryDetails = null;
            }
        },

        resetFilters() {
            this.filters = {
                priority: null,
                days: 30,
                onlyWithTravelers: false,
                onlyWithEvents: false,
                country: '',
                customDateRange: false,
                dateFrom: '',
                dateTo: '',
            };
        },

        logDebug(endpoint, params, result, fetchStartTime, fullUrl) {
            if (!this.isDebugUser) return;
            const pdsApiCalls = result?.debug?.pds_api_calls || [];
            const entry = {
                id: Date.now() + Math.random(),
                timestamp: new Date().toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit', second: '2-digit', fractionalSecondDigits: 3 }),
                endpoint: endpoint,
                fullUrl: fullUrl || null,
                params: params,
                response: result,
                duration_ms: Math.round(performance.now() - fetchStartTime),
                server_duration_ms: result?.debug?.duration_ms || null,
                pds_api_calls: pdsApiCalls,
                expanded: false,
            };
            this.debugLogs.unshift(entry);
            if (window.debugPanel) {
                window.debugPanel.log(endpoint, { url: fullUrl, params: params }, result, entry.duration_ms, entry.server_duration_ms, pdsApiCalls);
            }
        },

        clearDebugLogs() {
            this.debugLogs = [];
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            });
        },

        formatDateRange(start, end) {
            const startStr = this.formatDate(start);
            const endStr = this.formatDate(end);
            if (startStr === endStr) return startStr;
            return `${startStr} - ${endStr}`;
        },

        getTripProgress(startDate, endDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const start = new Date(startDate);
            start.setHours(0, 0, 0, 0);

            const end = new Date(endDate);
            end.setHours(0, 0, 0, 0);

            // Trip hasn't started yet
            if (today < start) {
                return { started: false, progress: 0, status: 'upcoming' };
            }

            // Trip has ended
            if (today > end) {
                return { started: true, progress: 100, status: 'completed' };
            }

            // Trip is in progress
            const totalDays = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1);
            const elapsedDays = Math.ceil((today - start) / (1000 * 60 * 60 * 24)) + 1;
            const progress = Math.min(100, Math.round((elapsedDays / totalDays) * 100));

            return { started: true, progress, status: 'active' };
        },
    };
}
