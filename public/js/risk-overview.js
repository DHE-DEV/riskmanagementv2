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
        // Versionshistorie des geoeffneten Ereignisses
        eventVersions: [],
        eventVersionsLoading: false,
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
            labels: [],
        },
        availableLabels: [],

        // Labels verwalten
        showLabelManager: false,
        managedLabels: [],
        labelPalette: [],
        labelManagerLoading: false,
        labelManagerBusy: false,
        labelManagerError: '',
        newLabel: { name: '', color: '', description: '' },
        editingLabelId: null,
        editLabel: { name: '', color: '', description: '' },
        mergingLabelId: null,
        mergeTargetId: '',

        // Label direkt an einer Karte vergeben (Popover) – Ereignis oder Reise
        quickLabel: { item: null, kind: 'event', top: 0, left: 0, query: '', busy: false },

        // Mehrfachauswahl: mehrere Ereignisse oder Reisen auf einmal labeln
        selection: { mode: null, scope: null, ids: [] },
        bulk: { labelId: '', newName: '', busy: false, message: '' },
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
        // Große Konten haben tausende Reisen: die Liste zeigt sie schrittweise
        tripListLimit: 100,
        tripsSummary: { total_trips: 0, trips_with_events: 0, total_events_across_trips: 0 },
        loadingTrips: false,
        tripsLoaded: false,
        countriesStale: false,
        filterDebounceTimer: null,
        selectedCountryEventId: null,

        // Labels (trips)
        labelInput: '',
        labelSuggestions: [],
        labelLoading: false,
        showLabelSuggestions: false,
        labelDebounceTimer: null,

        // Labels (events)
        eventLabelInput: '',
        eventLabelSuggestions: [],
        eventLabelLoading: false,
        showEventLabelSuggestions: false,
        eventLabelDebounceTimer: null,

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
            // Server filtert Ereignisse: gewählte Labels und "none" (Ereignis ohne Label)
            const serverLabels = this.filters.labels.filter(id => !String(id).startsWith('__'));
            if (this.filters.labels.includes(this.NO_EVENT_LABEL)) serverLabels.push('none');
            if (serverLabels.length > 0) {
                params.append('labels', serverLabels.join(','));
            }
            return params;
        },

        async loadAvailableLabels() {
            try {
                const resp = await fetch(`${window.__riskOverviewConfig.routes.labelsSearch}?q=`);
                this.availableLabels = await resp.json();
                // Gelöschte Labels aus dem Filter nehmen (die "ohne Label"-Optionen bleiben)
                this.filters.labels = this.filters.labels.filter(id => String(id).startsWith('__') || this.availableLabels.some(l => l.id === id));
            } catch (e) {
                console.error('Error loading available labels', e);
            }
        },

        // ── Labels: Darstellung ──────────────────────────────────────────

        /** Filter-Optionen ohne echtes Label */
        NO_TRIP_LABEL: '__trip_none',
        NO_EVENT_LABEL: '__event_none',

        labelById(id) {
            return this.availableLabels.find(l => l.id === id) || null;
        },

        /** Aktuelle Farbe (nach Umfärben sofort überall gleich). */
        labelColor(label) {
            if (String(label?.id).startsWith('__')) return '#9ca3af';
            return this.labelById(label?.id)?.color || label?.color || '#6b7280';
        },

        /** Aktueller Name (nach Umbenennen sofort überall gleich). */
        labelName(label) {
            if (label?.id === this.NO_TRIP_LABEL) return 'Reise ohne Label';
            if (label?.id === this.NO_EVENT_LABEL) return 'Ereignis ohne Label';
            return this.labelById(label?.id)?.name || label?.name || 'Label';
        },

        /** Schwarz oder Weiß – je nachdem, was auf der Farbe besser lesbar ist. */
        labelTextColor(hex) {
            const value = String(hex || '').replace('#', '');
            if (value.length !== 6) return '#ffffff';
            const [r, g, b] = [0, 2, 4].map(i => parseInt(value.substr(i, 2), 16) / 255)
                .map(c => c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4));
            return (0.2126 * r + 0.7152 * g + 0.0722 * b) > 0.45 ? '#111827' : '#ffffff';
        },

        // ── Labels: Filter ───────────────────────────────────────────────

        isLabelFiltered(id) {
            return this.filters.labels.includes(id);
        },

        toggleLabelFilter(id) {
            this.filters.labels = this.isLabelFiltered(id)
                ? this.filters.labels.filter(l => l !== id)
                : [...this.filters.labels, id];
        },

        clearLabelFilters() {
            this.filters.labels = [];
        },

        /** Trägt die Liste mindestens eines der gewählten Labels? */
        hasFilteredLabel(labels) {
            return (labels || []).some(l => this.filters.labels.includes(l.id));
        },

        /** Passt die Reise selbst zum Label-Filter (eigenes Label oder "Reise ohne Label")? */
        tripMatchesLabelFilter(trip) {
            return this.hasFilteredLabel(trip?.labels)
                || (this.filters.labels.includes(this.NO_TRIP_LABEL) && !(trip?.labels || []).length);
        },

        /** Passt das Ereignis zum Label-Filter (eigenes Label oder "Ereignis ohne Label")? */
        eventMatchesLabelFilter(event) {
            return this.hasFilteredLabel(event?.labels)
                || (this.filters.labels.includes(this.NO_EVENT_LABEL) && !(event?.labels || []).length);
        },

        /** In der vom Kunden festgelegten Reihenfolge, sonst alphabetisch. */
        sortLabels(labels) {
            return [...labels].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0) || a.name.localeCompare(b.name, 'de'));
        },

        /** Tooltip eines Label-Chips: Beschreibung und wann es gesetzt wurde. */
        labelTooltip(chip, action = '') {
            const label = this.labelById(chip?.id) || chip || {};
            return [
                this.labelName(chip),
                label.description || '',
                chip?.assigned_at ? 'gesetzt am ' + this.formatDate(chip.assigned_at) : '',
                action,
            ].filter(Boolean).join(' · ');
        },

        /** Neu angelegtes Label sofort in Filter und Vorschläge aufnehmen. */
        rememberLabel(label) {
            if (!label || this.labelById(label.id)) return;
            this.availableLabels = this.sortLabels([...this.availableLabels, label]);
        },

        /** Name oder Farbe eines Labels in allen geladenen Reisen und Ereignissen übernehmen. */
        applyLabelUpdateEverywhere(label) {
            const fields = { name: label.name, color: label.color, description: label.description ?? null };
            const patch = list => (list || []).forEach(l => { if (l.id === label.id) Object.assign(l, fields); });
            this.availableLabels = this.availableLabels.map(l => l.id === label.id ? { ...l, ...fields } : l);
            this.trips.forEach(trip => { patch(trip.labels); (trip.events || []).forEach(ev => patch(ev.labels)); });
            (this.countryDetails?.events || []).forEach(ev => patch(ev.labels));
            patch(this.selectedEvent?.labels);
        },

        /** Gelöschtes Label überall entfernen. */
        removeLabelEverywhere(id) {
            const strip = owner => { if (owner && owner.labels) owner.labels = owner.labels.filter(l => l.id !== id); };
            this.availableLabels = this.availableLabels.filter(l => l.id !== id);
            this.filters.labels = this.filters.labels.filter(l => l !== id);
            this.trips.forEach(trip => { strip(trip); (trip.events || []).forEach(strip); });
            (this.countryDetails?.events || []).forEach(strip);
            strip(this.selectedEvent);
        },

        // ── Labels: Verwaltung ───────────────────────────────────────────

        async openLabelManager() {
            this.showLabelManager = true;
            this.labelManagerError = '';
            this.editingLabelId = null;
            await this.loadManagedLabels();
        },

        closeLabelManager() {
            this.showLabelManager = false;
            this.editingLabelId = null;
        },

        async loadManagedLabels() {
            this.labelManagerLoading = true;
            try {
                const data = await this.fetchApi(window.__riskOverviewConfig.routes.labels);
                this.managedLabels = data.labels;
                this.labelPalette = data.palette;
                if (!this.newLabel.color) this.newLabel.color = this.labelPalette[this.managedLabels.length % this.labelPalette.length];
                this.availableLabels = this.sortLabels(this.managedLabels.map(({ trips_count, events_count, ...label }) => label));
            } catch (e) {
                this.labelManagerError = 'Die Labels konnten nicht geladen werden.';
            } finally {
                this.labelManagerLoading = false;
            }
        },

        async sendLabelRequest(url, method, body = null) {
            const response = await fetch(url, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: body ? JSON.stringify(body) : null,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                throw new Error(firstError || data.message || 'Das hat nicht geklappt.');
            }
            return data;
        },

        async createManagedLabel() {
            const name = this.newLabel.name.trim();
            if (!name) return;
            this.labelManagerBusy = true;
            this.labelManagerError = '';
            try {
                const data = await this.sendLabelRequest(window.__riskOverviewConfig.routes.labels, 'POST', {
                    name, color: this.newLabel.color || null, description: this.newLabel.description || null,
                });
                this.rememberLabel(data.label);
                this.newLabel = { name: '', color: '', description: '' };
                await this.loadManagedLabels();
            } catch (e) {
                this.labelManagerError = e.message;
            } finally {
                this.labelManagerBusy = false;
            }
        },

        startEditLabel(label) {
            this.editingLabelId = label.id;
            this.mergingLabelId = null;
            this.editLabel = { name: label.name, color: label.color, description: label.description || '' };
            this.labelManagerError = '';
        },

        cancelEditLabel() {
            this.editingLabelId = null;
        },

        async saveManagedLabel(label) {
            const name = this.editLabel.name.trim();
            if (!name) {
                this.labelManagerError = 'Bitte einen Namen angeben.';
                return;
            }
            this.labelManagerBusy = true;
            this.labelManagerError = '';
            try {
                const data = await this.sendLabelRequest(`${window.__riskOverviewConfig.routes.labels}/${label.id}`, 'PATCH', {
                    name, color: this.editLabel.color, description: this.editLabel.description || null,
                });
                Object.assign(label, data.label);
                this.applyLabelUpdateEverywhere(data.label);
                this.editingLabelId = null;
            } catch (e) {
                this.labelManagerError = e.message;
            } finally {
                this.labelManagerBusy = false;
            }
        },

        async deleteManagedLabel(label) {
            const usage = label.trips_count + label.events_count;
            const question = usage > 0
                ? `Label „${label.name}“ löschen? Es wird von ${label.trips_count} Reise(n) und ${label.events_count} Ereignis(sen) entfernt.`
                : `Label „${label.name}“ löschen?`;
            if (!window.confirm(question)) return;
            this.labelManagerBusy = true;
            this.labelManagerError = '';
            try {
                await this.sendLabelRequest(`${window.__riskOverviewConfig.routes.labels}/${label.id}`, 'DELETE');
                this.managedLabels = this.managedLabels.filter(l => l.id !== label.id);
                this.removeLabelEverywhere(label.id);
            } catch (e) {
                this.labelManagerError = e.message;
            } finally {
                this.labelManagerBusy = false;
            }
        },

        filterByManagedLabel(label) {
            this.filters.labels = [label.id];
            this.closeLabelManager();
        },

        /** Label in der Liste nach oben (-1) oder unten (+1) schieben und speichern. */
        async moveManagedLabel(label, direction) {
            const index = this.managedLabels.findIndex(l => l.id === label.id);
            const target = index + direction;
            if (index < 0 || target < 0 || target >= this.managedLabels.length) return;
            const list = [...this.managedLabels];
            [list[index], list[target]] = [list[target], list[index]];
            list.forEach((l, i) => { l.sort_order = i + 1; });
            this.managedLabels = list;
            this.availableLabels = this.sortLabels(this.availableLabels.map(l => ({ ...l, sort_order: list.find(m => m.id === l.id)?.sort_order ?? l.sort_order })));
            try {
                await this.sendLabelRequest(window.__riskOverviewConfig.routes.labelsOrder, 'POST', { ids: list.map(l => l.id) });
            } catch (e) {
                this.labelManagerError = e.message;
            }
        },

        startMergeLabel(label) {
            this.mergingLabelId = label.id;
            this.editingLabelId = null;
            this.mergeTargetId = '';
            this.labelManagerError = '';
        },

        /** Label in ein anderes überführen: Zuordnungen wandern mit, das Label entfällt. */
        async mergeManagedLabel(label) {
            const target = this.managedLabels.find(l => l.id === this.mergeTargetId);
            if (!target) {
                this.labelManagerError = 'Bitte das Ziel-Label wählen.';
                return;
            }
            if (!window.confirm(`„${label.name}“ in „${target.name}“ zusammenführen? Alle Reisen und Ereignisse mit „${label.name}“ tragen danach „${target.name}“.`)) return;
            this.labelManagerBusy = true;
            this.labelManagerError = '';
            try {
                await this.sendLabelRequest(`${window.__riskOverviewConfig.routes.labels}/${label.id}/merge`, 'POST', { target_id: target.id });
                this.replaceLabelEverywhere(label.id, target);
                this.mergingLabelId = null;
                await this.loadManagedLabels();
            } catch (e) {
                this.labelManagerError = e.message;
            } finally {
                this.labelManagerBusy = false;
            }
        },

        /** Nach dem Zusammenführen: Quell-Label überall durch das Ziel ersetzen (ohne Doppelte). */
        replaceLabelEverywhere(sourceId, target) {
            const swap = owner => {
                if (!owner || !owner.labels || !owner.labels.some(l => l.id === sourceId)) return;
                const rest = owner.labels.filter(l => l.id !== sourceId);
                owner.labels = rest.some(l => l.id === target.id) ? rest : [...rest, { ...target }];
            };
            this.trips.forEach(trip => { swap(trip); (trip.events || []).forEach(swap); });
            (this.countryDetails?.events || []).forEach(swap);
            swap(this.selectedEvent);
            this.availableLabels = this.availableLabels.filter(l => l.id !== sourceId);
            this.filters.labels = [...new Set(this.filters.labels.map(id => id === sourceId ? target.id : id))];
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

        searchLabels(showAll = false) {
            clearTimeout(this.labelDebounceTimer);
            if (this.labelInput.trim().length === 0) {
                // Beim Antippen alle noch nicht vergebenen Labels anbieten
                const existingIds = (this.selectedTrip?.labels || []).map(l => l.id);
                this.labelSuggestions = showAll ? this.availableLabels.filter(l => !existingIds.includes(l.id)) : [];
                this.showLabelSuggestions = showAll && this.labelSuggestions.length > 0;
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

        /**
         * Label an einer Reise setzen (labelId oder neuer Name) bzw. entfernen –
         * lokale Reisen über den Ordner, API-Reisen über ihre PDS-ID.
         */
        async changeTripLabel(trip, { labelId = null, name = null, detach = false }) {
            if (!trip) return;
            let base;
            if (trip.source === 'api') {
                const pdsTid = trip.pds_tid || trip.trip_id;
                if (!pdsTid) return;
                base = `/travel-alert/pds-trip/${encodeURIComponent(pdsTid)}/labels`;
            } else {
                if (!trip.folder_id) return;
                base = `/travel-alert/folder/${trip.folder_id}/labels`;
            }
            const data = await this.fetchApi(detach ? `${base}/${labelId}` : base, detach ? { method: 'DELETE' } : {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(labelId ? { label_id: labelId } : { name }),
            });
            if (data.success) {
                this.rememberLabel(data.label);
                this.setTripLabels(trip.folder_id, data.labels);
            }
        },

        setTripLabels(folderId, labels) {
            this.trips.forEach(t => { if (t.folder_id === folderId) t.labels = [...labels]; });
            if (this.selectedTrip && this.selectedTrip.folder_id === folderId) this.selectedTrip.labels = [...labels];
        },

        async attachLabel(labelId, labelName) {
            if (!this.selectedTrip) return;
            try {
                await this.changeTripLabel(this.selectedTrip, { labelId, name: labelName });
            } catch (e) {
                console.error('Attach label error', e);
            }
            this.labelInput = '';
            this.labelSuggestions = [];
            this.showLabelSuggestions = false;
        },

        async detachLabel(labelId) {
            if (!this.selectedTrip) return;
            try {
                await this.changeTripLabel(this.selectedTrip, { labelId, detach: true });
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

        searchEventLabels(showAll = false) {
            clearTimeout(this.eventLabelDebounceTimer);
            if (this.eventLabelInput.trim().length === 0) {
                const existingIds = (this.selectedEvent?.labels || []).map(l => l.id);
                this.eventLabelSuggestions = showAll ? this.availableLabels.filter(l => !existingIds.includes(l.id)) : [];
                this.showEventLabelSuggestions = showAll && this.eventLabelSuggestions.length > 0;
                return;
            }
            this.eventLabelDebounceTimer = setTimeout(async () => {
                this.eventLabelLoading = true;
                try {
                    const resp = await fetch(`${window.__riskOverviewConfig.routes.labelsSearch}?q=${encodeURIComponent(this.eventLabelInput.trim())}`);
                    const data = await resp.json();
                    const existingIds = (this.selectedEvent?.labels || []).map(l => l.id);
                    this.eventLabelSuggestions = data.filter(l => !existingIds.includes(l.id));
                    this.showEventLabelSuggestions = true;
                } catch (e) {
                    console.error('Event label search error', e);
                } finally {
                    this.eventLabelLoading = false;
                }
            }, 250);
        },

        /**
         * Label an einem Ereignis setzen (labelId oder neuer Name) bzw. entfernen –
         * und das Ergebnis in allen Karten, im Dialog und im Filter übernehmen.
         */
        async changeEventLabel(event, { labelId = null, name = null, detach = false }) {
            if (!event) return;
            const url = detach
                ? `/travel-alert/event/${event.id}/labels/${labelId}`
                : `/travel-alert/event/${event.id}/labels`;
            const data = await this.fetchApi(url, detach ? { method: 'DELETE' } : {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(labelId ? { label_id: labelId } : { name }),
            });
            if (data.success) {
                this.rememberLabel(data.label);
                event.labels = [...data.labels];
                if (this.selectedEvent && this.selectedEvent.id === event.id) this.selectedEvent.labels = [...data.labels];
                this.updateEventLabelsEverywhere(event.id, data.labels);
            }
        },

        async attachEventLabel(labelId, labelName) {
            if (!this.selectedEvent) return;
            try {
                await this.changeEventLabel(this.selectedEvent, { labelId, name: labelName });
            } catch (e) {
                console.error('Attach event label error', e);
            }
            this.eventLabelInput = '';
            this.eventLabelSuggestions = [];
            this.showEventLabelSuggestions = false;
        },

        async detachEventLabel(labelId) {
            if (!this.selectedEvent) return;
            try {
                await this.changeEventLabel(this.selectedEvent, { labelId, detach: true });
            } catch (e) {
                console.error('Detach event label error', e);
            }
        },

        // ── Label direkt an der Ereigniskarte ────────────────────────────

        openQuickLabel(item, domEvent, kind = 'event') {
            const rect = domEvent.currentTarget.getBoundingClientRect();
            const width = 260;
            this.quickLabel = {
                item,
                kind,
                top: Math.min(rect.bottom + 6, window.innerHeight - 320),
                left: Math.max(8, Math.min(rect.left, window.innerWidth - width - 8)),
                query: '',
                busy: false,
            };
            this.$nextTick(() => document.getElementById('quick-label-input')?.focus());
        },

        closeQuickLabel() {
            this.quickLabel.item = null;
        },

        get quickLabelTitle() {
            const item = this.quickLabel.item;
            return item ? (this.quickLabel.kind === 'trip' ? item.folder_name : item.title) : '';
        },

        get quickLabelOptions() {
            const query = this.quickLabel.query.trim().toLowerCase();
            return this.availableLabels.filter(l => !query || l.name.toLowerCase().includes(query));
        },

        get quickLabelCanCreate() {
            const query = this.quickLabel.query.trim().toLowerCase();
            return query.length > 0 && !this.availableLabels.some(l => l.name.toLowerCase() === query);
        },

        itemHasLabel(item, labelId) {
            return (item?.labels || []).some(l => l.id === labelId);
        },

        changeQuickTargetLabel(options) {
            return this.quickLabel.kind === 'trip'
                ? this.changeTripLabel(this.quickLabel.item, options)
                : this.changeEventLabel(this.quickLabel.item, options);
        },

        async toggleQuickLabel(label) {
            const item = this.quickLabel.item;
            if (!item || this.quickLabel.busy) return;
            this.quickLabel.busy = true;
            try {
                await this.changeQuickTargetLabel({ labelId: label.id, detach: this.itemHasLabel(item, label.id) });
            } catch (e) {
                console.error('Quick label error', e);
            } finally {
                this.quickLabel.busy = false;
            }
        },

        async createQuickLabel() {
            const item = this.quickLabel.item;
            const name = this.quickLabel.query.trim();
            if (!item || !name || this.quickLabel.busy) return;
            const existing = this.availableLabels.find(l => l.name.toLowerCase() === name.toLowerCase());
            if (existing) {
                if (!this.itemHasLabel(item, existing.id)) await this.toggleQuickLabel(existing);
                this.quickLabel.query = '';
                return;
            }
            this.quickLabel.busy = true;
            try {
                await this.changeQuickTargetLabel({ name });
                this.quickLabel.query = '';
            } catch (e) {
                console.error('Quick label create error', e);
            } finally {
                this.quickLabel.busy = false;
            }
        },

        // ── Mehrfachauswahl ──────────────────────────────────────────────

        /** Auswahl starten: 'events' (scope 'trip' oder 'country') oder 'trips'. */
        startSelection(mode, scope = null) {
            this.closeQuickLabel();
            this.selection = { mode, scope, ids: [] };
            this.bulk = { labelId: this.bulk.labelId || '', newName: '', busy: false, message: '' };
        },

        endSelection() {
            this.selection = { mode: null, scope: null, ids: [] };
        },

        selectionKey(item) {
            return this.selection.mode === 'trips' ? item.folder_id : item.id;
        },

        isSelected(item) {
            return !!this.selection.mode && this.selection.ids.includes(this.selectionKey(item));
        },

        toggleSelected(item) {
            const key = this.selectionKey(item);
            this.selection.ids = this.selection.ids.includes(key)
                ? this.selection.ids.filter(k => k !== key)
                : [...this.selection.ids, key];
        },

        /** Die Karten, aus denen gerade ausgewählt wird. */
        get selectionCandidates() {
            if (this.selection.mode === 'trips') return this.filteredTrips;
            if (this.selection.scope === 'country') return this.countryDetails?.events || [];
            return this.filteredTripEvents;
        },

        get allCandidatesSelected() {
            const candidates = this.selectionCandidates;
            return candidates.length > 0 && candidates.every(item => this.isSelected(item));
        },

        toggleSelectAll() {
            this.selection.ids = this.allCandidatesSelected ? [] : this.selectionCandidates.map(item => this.selectionKey(item));
        },

        /** Karten-Klick: in der Auswahl an- bzw. abwählen, sonst wie gewohnt öffnen. */
        onEventCardClick(event) {
            if (this.selection.mode === 'events') {
                this.toggleSelected(event);
            } else {
                this.openEventModal(event);
            }
        },

        onTripCardClick(trip) {
            if (this.selection.mode === 'trips') {
                this.toggleSelected(trip);
            } else {
                this.selectTrip(trip);
            }
        },

        /** Gewähltes Label an allen gewählten Karten setzen (detach=false) oder entfernen. */
        async applyBulkLabel(detach = false) {
            if (this.selection.ids.length === 0 || this.bulk.busy) return;
            const creating = this.bulk.labelId === '__new';
            const name = this.bulk.newName.trim();
            if ((!this.bulk.labelId) || (creating && (detach || !name))) {
                this.bulk.message = creating ? 'Bitte einen Namen für das neue Label eingeben.' : 'Bitte ein Label wählen.';
                return;
            }
            this.bulk.busy = true;
            this.bulk.message = '';
            try {
                const payload = {
                    detach,
                    ...(creating ? { name } : { label_id: this.bulk.labelId }),
                    ...(this.selection.mode === 'trips' ? { trip_ids: this.selection.ids } : { event_ids: this.selection.ids }),
                };
                const data = await this.sendLabelRequest(window.__riskOverviewConfig.routes.labelsBulk, 'POST', payload);
                this.rememberLabel(data.label);
                Object.entries(data.events || {}).forEach(([eventId, labels]) => {
                    const id = Number(eventId);
                    this.updateEventLabelsEverywhere(id, labels);
                    if (this.selectedEvent?.id === id) this.selectedEvent.labels = [...labels];
                });
                Object.entries(data.trips || {}).forEach(([folderId, labels]) => this.setTripLabels(folderId, labels));
                const count = this.selection.ids.length;
                const noun = this.selection.mode === 'trips' ? (count === 1 ? 'Reise' : 'Reisen') : (count === 1 ? 'Ereignis' : 'Ereignissen');
                this.bulk.message = `„${data.label.name}“ ${detach ? 'von' : 'an'} ${count} ${noun} ${detach ? 'entfernt' : 'vergeben'}.`;
                if (creating) {
                    this.bulk.labelId = data.label.id;
                    this.bulk.newName = '';
                }
            } catch (e) {
                this.bulk.message = e.message;
            } finally {
                this.bulk.busy = false;
            }
        },

        addEventLabelFromInput() {
            const name = this.eventLabelInput.trim();
            if (!name) return;
            const match = this.eventLabelSuggestions.find(l => l.name.toLowerCase() === name.toLowerCase());
            if (match) {
                this.attachEventLabel(match.id, null);
            } else {
                this.attachEventLabel(null, name);
            }
        },

        updateEventLabelsEverywhere(eventId, labels) {
            // Update in trips
            this.trips.forEach(trip => {
                if (trip.events) {
                    trip.events.forEach(ev => {
                        if (ev.id === eventId) ev.labels = [...labels];
                    });
                }
            });
            // Update in country details
            if (this.countryDetails?.events) {
                this.countryDetails.events.forEach(ev => {
                    if (ev.id === eventId) ev.labels = [...labels];
                });
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
            let events = this.selectedTrip.events;
            if (this.selectedTripCountry) {
                events = events.filter(e =>
                    e.matched_countries && e.matched_countries.some(mc => mc.code === this.selectedTripCountry)
                );
            }
            // Label-Filter: Trägt die Reise selbst ein gewähltes Label, bleiben alle Ereignisse
            // sichtbar; sonst nur die Ereignisse mit einem der Labels.
            if (this.filters.labels.length > 0 && !this.tripMatchesLabelFilter(this.selectedTrip)) {
                events = events.filter(e => this.eventMatchesLabelFilter(e));
            }
            return events;
        },

        /** Reisen nach allen Filtern außer den Labels – Grundlage für Liste und Zähler. */
        get tripsBeforeLabelFilter() {
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

        get filteredTrips() {
            const trips = this.tripsBeforeLabelFilter;
            if (this.filters.labels.length === 0) return trips;
            // Die Reise selbst oder eines ihrer Ereignisse passt zu einer der gewählten Optionen
            return trips.filter(t =>
                this.tripMatchesLabelFilter(t) || (t.events || []).some(e => this.eventMatchesLabelFilter(e))
            );
        },

        /**
         * Anzahl Reisen je Filter-Option im aktuellen Zeitraum (ohne Label-Filter) –
         * so zeigt der Filter den offenen Arbeitsstand, z. B. "Ereignis ohne Label · 12".
         */
        get labelFilterCounts() {
            const counts = {};
            const trips = this.tripsBeforeLabelFilter;
            const add = (key) => { counts[key] = (counts[key] || 0) + 1; };
            trips.forEach(trip => {
                const ids = new Set((trip.labels || []).map(l => l.id));
                (trip.events || []).forEach(e => (e.labels || []).forEach(l => ids.add(l.id)));
                ids.forEach(add);
                if (!(trip.labels || []).length) add(this.NO_TRIP_LABEL);
                if ((trip.events || []).some(e => !(e.labels || []).length)) add(this.NO_EVENT_LABEL);
            });
            return counts;
        },

        /** Die gerade angezeigten Reisen der Liste (schrittweise mehr). */
        get visibleTrips() {
            return this.filteredTrips.slice(0, this.tripListLimit);
        },

        showMoreTrips() {
            this.tripListLimit += 100;
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

        /** Gewählte Label-Filter in diesem Browser merken (Bedienkomfort, kein Muss). */
        restoreLabelFilter() {
            try {
                const saved = JSON.parse(localStorage.getItem('travelAlert.labelFilter') || '[]');
                if (Array.isArray(saved)) this.filters.labels = saved.filter(id => typeof id === 'string');
            } catch (e) {
                // Speicher nicht verfügbar (privates Fenster o. Ä.) – dann eben ohne
            }
        },

        storeLabelFilter() {
            try {
                localStorage.setItem('travelAlert.labelFilter', JSON.stringify(this.filters.labels));
            } catch (e) {
                // siehe restoreLabelFilter
            }
        },

        init() {
            this.restoreLabelFilter();
            this.$nextTick(() => {
                this.initMap();
                this.loadData();
                this.loadTrips();
                this.loadAvailableLabels();

                // Lazy-load: reload stale data when switching sidebar tabs
                this.$watch('sidebarTab', (newTab) => {
                    if (newTab === 'laender' && this.countriesStale) {
                        this.loadData();
                    } else if (newTab === 'reisen' && !this.tripsLoaded) {
                        this.loadTrips();
                    }
                });

                // Label-Popover schließen, sobald gescrollt wird (es hängt an der Karte)
                document.addEventListener('scroll', (e) => {
                    if (this.quickLabel.item && !e.target.closest?.('#quick-label-popover')) this.closeQuickLabel();
                }, true);

                // ESC key to close modals
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        if (this.quickLabel.item) {
                            this.closeQuickLabel();
                        } else if (this.selection.mode && !this.showEventModal && !this.showTravelerModal && !this.showLabelManager) {
                            this.endSelection();
                        } else if (this.showTravelerModal) {
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

                // Andere Filter: Liste wieder von vorn zeigen
                this.$watch('filters', () => { this.tripListLimit = 100; });

                this.$watch('filters.labels', () => {
                    this.storeLabelFilter();
                    if (this.sidebarTab === 'laender') {
                        this.loadData();
                    } else {
                        this.countriesStale = true;
                    }
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
                    this.tripListLimit = 100;
                    this.trips = result.data.trips;
                    this.tripsSummary = result.data.summary;
                    this.tripsLoaded = true;

                    // Keep selectedTrip in sync with new data
                    if (this.selectedTrip) {
                        const updated = this.trips.find(t => t.folder_id === this.selectedTrip.folder_id);
                        if (updated) {
                            this.selectedTrip = updated;
                        } else {
                            this.selectedTrip = null;
                            this.selectedTripCountry = null;
                        }
                    }
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
            this.selectedCountryEventId = null;
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

        filterTravelersByEvent(event) {
            this.selectedCountryEventId = event.id;
        },

        clearCountryEventFilter() {
            this.selectedCountryEventId = null;
        },

        get filteredCountryTravelers() {
            if (!this.countryDetails?.travelers) return [];
            if (!this.selectedCountryEventId) return this.countryDetails.travelers;
            const event = this.countryDetails.events?.find(e => e.id === this.selectedCountryEventId);
            if (!event) return this.countryDetails.travelers;
            const eventStart = new Date(event.start_date);
            eventStart.setHours(0, 0, 0, 0);
            const eventEnd = event.end_date ? new Date(event.end_date) : new Date(eventStart);
            eventEnd.setHours(0, 0, 0, 0);
            return this.countryDetails.travelers.filter(t => {
                const tripStart = new Date(t.start_date);
                tripStart.setHours(0, 0, 0, 0);
                const tripEnd = new Date(t.end_date);
                tripEnd.setHours(0, 0, 0, 0);
                return tripStart <= eventEnd && tripEnd >= eventStart;
            });
        },

        get selectedCountryEvent() {
            if (!this.selectedCountryEventId || !this.countryDetails?.events) return null;
            return this.countryDetails.events.find(e => e.id === this.selectedCountryEventId) || null;
        },

        openEventModal(event) {
            this.selectedEvent = event;
            this.showEventModal = true;
            this.eventLabelInput = '';
            this.eventLabelSuggestions = [];
            this.showEventLabelSuggestions = false;
            document.body.style.overflow = 'hidden';

            this.loadEventVersions(event?.id);
        },

        closeEventModal() {
            this.showEventModal = false;
            this.selectedEvent = null;
            this.eventVersions = [];
            document.body.style.overflow = '';
        },

        /**
         * Versionshistorie des Ereignisses laden. Ereignisse werden bei
         * Aenderungen nicht ueberschrieben, sondern als neue Version gefuehrt -
         * hier koennen Kunden nachvollziehen, was sich wann geaendert hat.
         */
        async loadEventVersions(eventId) {
            this.eventVersions = [];
            this.eventVersionsLoading = false;

            if (!eventId) {
                return;
            }

            this.eventVersionsLoading = true;

            try {
                const response = await fetch(`/api/custom-events/${eventId}/versions`);
                const result = await response.json();

                // Erst ab zwei Fassungen ist eine Historie ueberhaupt interessant.
                if (result.success && (result.data?.versions?.length || 0) > 1) {
                    this.eventVersions = result.data.versions;
                }
            } catch (error) {
                this.eventVersions = [];
            } finally {
                this.eventVersionsLoading = false;
            }
        },

        formatVersionPeriod(version) {
            const from = version.valid_from ? this.formatDate(version.valid_from) : null;
            const until = version.valid_until ? this.formatDate(version.valid_until) : null;

            if (from && until) {
                return `gültig ${from} – ${until}`;
            }
            if (from) {
                return `gültig seit ${from}`;
            }
            return 'noch nicht veröffentlicht';
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
                labels: [],
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
