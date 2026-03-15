@extends('layouts.dashboard-minimal')

@section('title', 'Meine Ereignisse - Global Travel Monitor')

@php
    $active = 'customer-events';
@endphp

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    #quill-editor-container .ql-toolbar { border: 1px solid #d1d5db; border-radius: 8px 8px 0 0; background: #f9fafb; padding: 4px 8px; }
    #quill-editor-container .ql-container { border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 8px 8px; font-size: 13px; }
    #quill-editor-container .ql-editor { min-height: 120px; padding: 10px 14px; }
    #quill-editor-container .ql-editor.ql-blank::before { color: #9ca3af; font-style: normal; }
    .main-content {
        display: flex !important;
        overflow: hidden !important;
        overflow-y: hidden !important;
    }
    .events-sidebar {
        flex-shrink: 0;
        width: 304px;
        background: #f9fafb;
        overflow-y: auto;
        height: 100%;
        border-right: 1px solid #e5e7eb;
    }
    .events-content {
        flex: 1;
        overflow-y: auto;
        height: 100%;
    }
    .org-tree-node { position: relative; }
    .org-tree-node-row { display: flex; align-items: stretch; margin-bottom: 4px; }
    .org-tree-branch { position: relative; padding-left: 24px; }
    .org-tree-branch::before {
        content: ''; position: absolute; left: 11px; top: 0; bottom: 18px;
        border-left: 2px solid #d1d5db;
    }
    .org-tree-branch > .org-tree-node { position: relative; }
    .org-tree-branch > .org-tree-node::before {
        content: ''; position: absolute; left: -13px; top: 18px; width: 13px;
        border-top: 2px solid #d1d5db;
    }
    .org-tree-branch > .org-tree-node:last-child::after {
        content: ''; position: absolute; left: -13px; top: 18px; bottom: 0;
        background: white; width: 4px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>

function eventContentManager() {
    return {
        showEventTab: false,
        showEventView: false,
        showOrgNodePicker: false,
        eventMode: 'create',
        viewData: null,
        visibilityOrgNodes: [],
        eventData: {
            id: null, title: '', description: '',
            selectedEventTypes: [], priority: 'medium',
            startDate: null, endDate: null, isActive: true,
            latitude: '', longitude: '',
            visibleCommunity: false, communityStartDate: '', communityEndDate: '',
            visibleOrganization: true, organizationStartDate: '', organizationEndDate: '',
            selectedOrgNodes: [], orgNodeDates: [],
        },

        init() {
            window.addEventListener('event-view-opened', (e) => {
                this.viewData = e.detail.data;
                this.showEventView = true;
                this.showEventTab = false;
                this.destroyEditor();
                if (this.eventMap) { this.eventMap.remove(); this.eventMap = null; }

                // Detail-Karte anzeigen
                if (this.viewData.latitude && this.viewData.longitude) {
                    this.$nextTick(() => {
                        setTimeout(() => {
                            const container = document.getElementById('event-view-map');
                            if (!container) return;
                            const lat = parseFloat(this.viewData.latitude);
                            const lng = parseFloat(this.viewData.longitude);
                            const map = L.map('event-view-map').setView([lat, lng], 14);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; OpenStreetMap'
                            }).addTo(map);
                            L.marker([lat, lng]).addTo(map);
                            this._viewMap = map;
                            setTimeout(() => map.invalidateSize(), 100);
                        }, 50);
                    });
                }
            });

            window.addEventListener('event-form-opened', (e) => {
                this.eventMode = e.detail.mode;
                const d = e.detail.data;
                this.eventData = {
                    id: d.id, title: d.title, description: d.description,
                    selectedEventTypes: d.selectedEventTypes || [],
                    priority: d.priority || 'medium',
                    startDate: d.startDate, endDate: d.endDate,
                    isActive: d.isActive !== undefined ? d.isActive : true,
                    latitude: d.latitude || '', longitude: d.longitude || '',
                    visibleCommunity: d.visibleCommunity || false,
                    communityStartDate: d.communityStartDate || '',
                    communityEndDate: d.communityEndDate || '',
                    visibleOrganization: d.visibleOrganization !== undefined ? d.visibleOrganization : true,
                    organizationStartDate: d.organizationStartDate || '',
                    organizationEndDate: d.organizationEndDate || '',
                    selectedOrgNodes: d.selectedOrgNodes || [],
                    orgNodeDates: d.orgNodeDates || [],
                };
                this.showOrgNodePicker = (this.eventData.selectedOrgNodes.length > 0);
                this.showEventView = false;
                this.showEventTab = true;

                // Editor + Karte initialisieren
                this.$nextTick(() => {
                    this.initEditor();
                    if (this.eventData.latitude && this.eventData.longitude) {
                        this.updateMap();
                    }
                });
            });

            window.addEventListener('event-form-closed', () => {
                this.destroyEditor();
                if (this.eventMap) { this.eventMap.remove(); this.eventMap = null; this.eventMarker = null; }
                this.showEventTab = false;
                this.showEventView = false;
                this.viewData = null;
                if (this._viewMap) { this._viewMap.remove(); this._viewMap = null; }
                this.showOrgNodePicker = false;
            });
        },

        toggleEventType(typeId) {
            const idx = this.eventData.selectedEventTypes.indexOf(typeId);
            if (idx === -1) {
                this.eventData.selectedEventTypes.push(typeId);
            } else {
                this.eventData.selectedEventTypes.splice(idx, 1);
            }
            this.syncToLivewire('selectedEventTypes', this.eventData.selectedEventTypes);
        },

        toggleOrgNodeVisibility(id) {
            const idx = this.eventData.selectedOrgNodes.indexOf(id);
            if (idx === -1) {
                this.eventData.selectedOrgNodes.push(id);
                // Datum aus dem Zeitraum oben übernehmen
                this.eventData.orgNodeDates.push({
                    id: id,
                    start_date: this.eventData.startDate || '',
                    end_date: this.eventData.endDate || '',
                });
            } else {
                this.eventData.selectedOrgNodes.splice(idx, 1);
                this.eventData.orgNodeDates = this.eventData.orgNodeDates.filter(d => d.id !== id);
            }
            this.syncToLivewire('selectedOrgNodes', this.eventData.selectedOrgNodes);
            this.syncToLivewire('orgNodeDates', this.eventData.orgNodeDates);
        },

        getOrgNodeDate(nodeId) {
            return this.eventData.orgNodeDates.find(d => d.id === nodeId) || { id: nodeId, start_date: '', end_date: '' };
        },

        updateOrgNodeDate(nodeId, field, value) {
            let entry = this.eventData.orgNodeDates.find(d => d.id === nodeId);
            if (!entry) {
                entry = { id: nodeId, start_date: '', end_date: '' };
                this.eventData.orgNodeDates.push(entry);
            }
            entry[field] = value;
            this.syncToLivewire('orgNodeDates', this.eventData.orgNodeDates);
        },

        selectAllOrgNodes() {
            const allIds = this._collectAllNodeIds(this.visibilityOrgNodes);
            this.eventData.selectedOrgNodes = allIds;
            this.eventData.orgNodeDates = allIds.map(id => ({
                id, start_date: this.eventData.startDate || '', end_date: this.eventData.endDate || ''
            }));
            this.syncToLivewire('selectedOrgNodes', this.eventData.selectedOrgNodes);
            this.syncToLivewire('orgNodeDates', this.eventData.orgNodeDates);
        },

        deselectAllOrgNodes() {
            this.eventData.selectedOrgNodes = [];
            this.eventData.orgNodeDates = [];
            this.syncToLivewire('selectedOrgNodes', []);
            this.syncToLivewire('orgNodeDates', []);
        },

        _collectAllNodeIds(nodes) {
            let ids = [];
            nodes.forEach(n => {
                ids.push(n.id);
                if (n.all_children && n.all_children.length) {
                    ids = ids.concat(this._collectAllNodeIds(n.all_children));
                }
            });
            return ids;
        },

        renderVisibilityTree() {
            let html = '';
            this.visibilityOrgNodes.forEach(node => {
                html += this.renderVisibilityNode(node, 0);
            });
            return html;
        },

        renderVisibilityNode(node, depth) {
            const checked = this.eventData.selectedOrgNodes.includes(node.id);
            const children = node.all_children || [];
            const esc = (s) => s ? s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : '';
            const nid = node.id;

            let html = '<div class="org-tree-node">';
            html += '<div class="org-tree-node-row">';
            const nd = this.getOrgNodeDate(nid);

            html += '<div class="flex-1 rounded-lg border transition-colors ' + (checked ? 'bg-purple-50 border-purple-200' : 'bg-gray-50 border-gray-200 hover:bg-gray-100') + '">';
            html += '<div class="flex items-center gap-2 px-3 py-2 cursor-pointer" onclick="window.dispatchEvent(new CustomEvent(\'toggle-event-org-node\',{detail:{id:' + nid + '}}))">';
            html += '<input type="checkbox" ' + (checked ? 'checked' : '') + ' onclick="event.stopPropagation()" onchange="window.dispatchEvent(new CustomEvent(\'toggle-event-org-node\',{detail:{id:' + nid + '}}))" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 w-3.5 h-3.5 flex-shrink-0">';
            html += '<span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:' + (node.color || '#8b5cf6') + '"></span>';
            html += '<span class="text-xs font-medium ' + (checked ? 'text-purple-900' : 'text-gray-700') + '">' + esc(node.name) + '</span>';
            if (node.code) html += '<span class="text-[10px] text-gray-400 font-mono ml-auto">' + esc(node.code) + '</span>';
            html += '</div>';
            if (node.description) html += '<p class="text-[10px] text-gray-500 px-3 ml-8">' + esc(node.description) + '</p>';

            if (checked) {
                html += '<div class="grid grid-cols-2 gap-2 px-3 pb-2 mt-1 ml-8">';
                html += '<div><label class="block text-[9px] text-gray-400 mb-0.5">Start</label><input type="date" value="' + esc(nd.start_date) + '" onclick="event.stopPropagation()" onchange="window.dispatchEvent(new CustomEvent(\'update-org-node-date\',{detail:{id:' + nid + ',field:\'start_date\',value:this.value}}))" class="w-full px-2 py-1 border border-gray-300 rounded text-[11px] focus:ring-1 focus:ring-purple-500 bg-white"></div>';
                html += '<div><label class="block text-[9px] text-gray-400 mb-0.5">Ende</label><input type="date" value="' + esc(nd.end_date) + '" onclick="event.stopPropagation()" onchange="window.dispatchEvent(new CustomEvent(\'update-org-node-date\',{detail:{id:' + nid + ',field:\'end_date\',value:this.value}}))" class="w-full px-2 py-1 border border-gray-300 rounded text-[11px] focus:ring-1 focus:ring-purple-500 bg-white"></div>';
                html += '</div>';
            }

            html += '</div>';
            html += '</div>';

            if (children.length) {
                html += '<div class="org-tree-branch">';
                children.forEach(child => {
                    html += this.renderVisibilityNode(child, depth + 1);
                });
                html += '</div>';
            }

            html += '</div>';
            return html;
        },

        quillInstance: null,

        editFromView() {
            if (!this.viewData) return;
            const wireEl = document.querySelector('[wire\\:id]');
            if (wireEl) {
                Livewire.find(wireEl.getAttribute('wire:id')).call('openEditForm', this.viewData.id);
            }
        },

        eventMap: null,
        eventMarker: null,

        parseCoordinates(event) {
            const text = (event.clipboardData || window.clipboardData).getData('text');
            this.parseCoordinatesFromValue(text);
        },

        parseCoordinatesFromValue(text) {
            if (!text) return;
            // Google Maps Formate: "48.137154, 11.576124" oder "48.137154,11.576124" oder "@48.137154,11.576124"
            const cleaned = text.replace(/@/g, '').trim();
            // Verschiedene Trennzeichen: Komma, Leerzeichen, Tab
            const match = cleaned.match(/(-?\d+[.,]\d+)\s*[,;\s]\s*(-?\d+[.,]\d+)/);
            if (match) {
                const lat = match[1].replace(',', '.');
                const lng = match[2].replace(',', '.');
                const latNum = parseFloat(lat);
                const lngNum = parseFloat(lng);
                if (latNum >= -90 && latNum <= 90 && lngNum >= -180 && lngNum <= 180) {
                    this.eventData.latitude = lat;
                    this.eventData.longitude = lng;
                    this.syncToLivewire('latitude', lat);
                    this.syncToLivewire('longitude', lng);
                    this.$nextTick(() => this.updateMap());
                }
            }
        },

        updateMap() {
            const lat = parseFloat(this.eventData.latitude);
            const lng = parseFloat(this.eventData.longitude);

            if (isNaN(lat) || isNaN(lng)) {
                if (this.eventMap) {
                    this.eventMap.remove();
                    this.eventMap = null;
                    this.eventMarker = null;
                }
                return;
            }

            this.$nextTick(() => {
                const container = document.getElementById('event-map');
                if (!container) return;

                if (!this.eventMap) {
                    this.eventMap = L.map('event-map').setView([lat, lng], 14);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(this.eventMap);
                    this.eventMarker = L.marker([lat, lng]).addTo(this.eventMap);
                } else {
                    this.eventMap.setView([lat, lng], 14);
                    if (this.eventMarker) {
                        this.eventMarker.setLatLng([lat, lng]);
                    } else {
                        this.eventMarker = L.marker([lat, lng]).addTo(this.eventMap);
                    }
                }

                // Fix für Leaflet-Rendering in dynamisch sichtbaren Containern
                setTimeout(() => this.eventMap.invalidateSize(), 100);
            });
        },

        clearDescription() {
            this.eventData.description = '';
            this.syncToLivewire('description', '');
            if (this.quillInstance) {
                this.quillInstance.setText('');
            }
        },

        initEditor() {
            this.destroyEditor();

            const editorEl = document.getElementById('quill-editor');
            if (!editorEl) return;

            this.quillInstance = new Quill('#quill-editor', {
                theme: 'snow',
                placeholder: 'Beschreibung des Ereignisses...',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'header': [1, 2, 3, false] }],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        ['blockquote'],
                        ['link'],
                        ['clean']
                    ]
                }
            });

            // Inhalt laden
            if (this.eventData.description) {
                this.quillInstance.root.innerHTML = this.eventData.description;
            }

            // Änderungen synchronisieren
            const self = this;
            this.quillInstance.on('text-change', function() {
                const html = self.quillInstance.root.innerHTML;
                const value = html === '<p><br></p>' ? '' : html;
                self.eventData.description = value;
                self.syncToLivewire('description', value);
            });
        },

        destroyEditor() {
            if (this.quillInstance) {
                this.quillInstance = null;
            }
            const editorEl = document.getElementById('quill-editor');
            if (editorEl) {
                editorEl.innerHTML = '';
                // Toolbar entfernen
                const toolbar = editorEl.previousElementSibling;
                if (toolbar && toolbar.classList.contains('ql-toolbar')) {
                    toolbar.remove();
                }
            }
        },

        syncToLivewire(field, value) {
            const wireEl = document.querySelector('[wire\\:id]');
            if (wireEl) {
                const component = Livewire.find(wireEl.getAttribute('wire:id'));
                if (component) {
                    component.set(field, value);
                }
            }
        },

        saveEvent() {
            const wireEl = document.querySelector('[wire\\:id]');
            if (wireEl) {
                const component = Livewire.find(wireEl.getAttribute('wire:id'));
                if (component) {
                    component.call('save');
                }
            }
        },

        closeForm() {
            const wireEl = document.querySelector('[wire\\:id]');
            if (wireEl) {
                const component = Livewire.find(wireEl.getAttribute('wire:id'));
                if (component) {
                    component.call('cancelForm');
                }
            }
            this.showEventTab = false;
        }
    };
}
</script>
@endpush

@section('content')
    {{-- Sidebar --}}
    <div class="events-sidebar">
        <div class="p-4">
            <h2 class="text-sm font-bold text-gray-900 mb-3">
                <i class="fas fa-calendar-alt mr-2"></i>
                Meine Ereignisse
            </h2>
            <p class="text-xs text-gray-500 mb-4">Erstellen und verwalten Sie eigene Ereignisse für Ihr Unternehmen.</p>

            @livewire('customer.customer-event-manager')
        </div>
    </div>

    @php
        $orgNodes = \App\Models\OrgNode::where('customer_id', auth('customer')->id())
            ->whereNull('parent_id')->with('allChildren')->orderBy('sort_order')->get();
    @endphp

    {{-- Main Content Area --}}
    <div class="events-content" x-data="eventContentManager()" x-init="visibilityOrgNodes = {{ $orgNodes->toJson() }}" @toggle-event-org-node.window="toggleOrgNodeVisibility($event.detail.id)" @update-org-node-date.window="updateOrgNodeDate($event.detail.id, $event.detail.field, $event.detail.value)">
        {{-- Tab-Leiste (nur sichtbar wenn Formular offen) --}}
        <div x-show="showEventTab" class="tab-navigation flex border-b border-gray-200 bg-white px-4" x-cloak>
            <button class="px-4 py-3 text-sm font-medium border-b-2 border-blue-500 text-blue-600">
                <i class="fas fa-plus mr-2" x-show="eventMode === 'create'"></i>
                <i class="fas fa-pen mr-2" x-show="eventMode === 'edit'"></i>
                <span x-text="eventMode === 'edit' ? 'Bearbeiten' : 'Neu'"></span>
            </button>
        </div>

        {{-- Event Detail-Ansicht --}}
        <div x-show="showEventView && viewData" x-cloak>
            {{-- Tab-Leiste --}}
            <div class="tab-navigation flex border-b border-gray-200 bg-white px-4">
                <button class="px-4 py-3 text-sm font-medium border-b-2 border-blue-500 text-blue-600">
                    <i class="fas fa-eye mr-2"></i>
                    <span x-text="viewData?.title || 'Details'"></span>
                </button>
            </div>

            <div class="p-6">
                {{-- Header mit Aktionen --}}
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" x-text="viewData?.title"></h3>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                  :class="viewData?.isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                  x-text="viewData?.isActive ? 'Aktiv' : 'Inaktiv'"></span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                  :class="{'bg-blue-100 text-blue-800': viewData?.priority === 'info', 'bg-yellow-100 text-yellow-800': viewData?.priority === 'low', 'bg-orange-100 text-orange-800': viewData?.priority === 'medium', 'bg-red-100 text-red-800': viewData?.priority === 'high'}"
                                  x-text="({info:'Information',low:'Niedrig',medium:'Mittel',high:'Hoch'})[viewData?.priority]"></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="editFromView()" class="px-3 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1">
                            <i class="fas fa-pen"></i> Bearbeiten
                        </button>
                        <button @click="showEventView = false; viewData = null" class="px-3 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Linke Spalte --}}
                    <div class="space-y-4">
                        {{-- Beschreibung --}}
                        <div class="bg-white rounded-lg border border-gray-200 p-5">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                <i class="fas fa-align-left text-gray-400 mr-1"></i> Beschreibung
                            </h4>
                            <div x-show="viewData?.description" class="prose prose-sm max-w-none text-sm text-gray-700" x-html="viewData?.description"></div>
                            <p x-show="!viewData?.description" class="text-xs text-gray-400 italic">Keine Beschreibung vorhanden.</p>
                        </div>

                        {{-- Zeitraum --}}
                        <div class="bg-white rounded-lg border border-gray-200 p-5">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                <i class="fas fa-calendar text-gray-400 mr-1"></i> Zeitraum
                            </h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-xs text-gray-500">Startdatum</span>
                                    <p class="font-medium text-gray-900" x-text="viewData?.startDate || '—'"></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">Enddatum</span>
                                    <p class="font-medium text-gray-900" x-text="viewData?.endDate || '—'"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Karte (wenn Koordinaten vorhanden) --}}
                        <div x-show="viewData?.latitude && viewData?.longitude" class="bg-white rounded-lg border border-gray-200 p-5">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                <i class="fas fa-map-pin text-gray-400 mr-1"></i> Standort
                            </h4>
                            <p class="text-[10px] text-gray-500 mb-2 font-mono" x-text="viewData?.latitude + ', ' + viewData?.longitude"></p>
                            <div id="event-view-map" style="height: 200px; border-radius: 8px; border: 1px solid #e5e7eb;"></div>
                        </div>

                        {{-- Meta --}}
                        <div class="bg-white rounded-lg border border-gray-200 p-5">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                <i class="fas fa-clock text-gray-400 mr-1"></i> Verlauf
                            </h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-xs text-gray-500">Erstellt am</span>
                                    <p class="font-medium text-gray-900" x-text="viewData?.createdAt || '—'"></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">Zuletzt geändert</span>
                                    <p class="font-medium text-gray-900" x-text="viewData?.updatedAt || '—'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Rechte Spalte --}}
                    <div class="space-y-4">
                        {{-- Kategorien --}}
                        <div class="bg-white rounded-lg border border-gray-200 p-5">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                <i class="fas fa-tags text-gray-400 mr-1"></i> Kategorien
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="cat in (viewData?.selectedEventTypes || [])" :key="cat">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200" x-text="cat"></span>
                                </template>
                                <p x-show="!viewData?.selectedEventTypes?.length" class="text-xs text-gray-400 italic">Keine Kategorien zugeordnet.</p>
                            </div>
                        </div>

                        {{-- Sichtbarkeit --}}
                        <div class="bg-white rounded-lg border border-gray-200 p-5">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                <i class="fas fa-eye text-gray-400 mr-1"></i> Sichtbarkeit
                            </h4>
                            <div class="space-y-3">
                                <div x-show="viewData?.visibleCommunity" class="rounded-lg border border-blue-200 bg-blue-50 p-3">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-earth-europe text-blue-500 text-xs w-4 text-center"></i>
                                        <span class="text-xs font-medium text-gray-900">GTM-Community</span>
                                    </div>
                                    <div x-show="viewData?.communityStartDate || viewData?.communityEndDate" class="flex gap-4 mt-2 ml-6 text-[10px] text-gray-500">
                                        <span x-show="viewData?.communityStartDate"><i class="fas fa-calendar-alt mr-1"></i>Start: <span x-text="viewData?.communityStartDate"></span></span>
                                        <span x-show="viewData?.communityEndDate"><i class="fas fa-calendar-alt mr-1"></i>Ende: <span x-text="viewData?.communityEndDate"></span></span>
                                    </div>
                                </div>
                                <div x-show="viewData?.visibleOrganization" class="rounded-lg border border-green-200 bg-green-50 p-3">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-building text-green-500 text-xs w-4 text-center"></i>
                                        <span class="text-xs font-medium text-gray-900">Gesamte Organisation</span>
                                    </div>
                                    <div x-show="viewData?.organizationStartDate || viewData?.organizationEndDate" class="flex gap-4 mt-2 ml-6 text-[10px] text-gray-500">
                                        <span x-show="viewData?.organizationStartDate"><i class="fas fa-calendar-alt mr-1"></i>Start: <span x-text="viewData?.organizationStartDate"></span></span>
                                        <span x-show="viewData?.organizationEndDate"><i class="fas fa-calendar-alt mr-1"></i>Ende: <span x-text="viewData?.organizationEndDate"></span></span>
                                    </div>
                                </div>
                                <template x-if="viewData?.orgNodes?.length > 0">
                                    <div class="rounded-lg border border-purple-200 bg-purple-50 p-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-sitemap text-purple-500 text-xs w-4 text-center"></i>
                                            <span class="text-xs font-medium text-gray-900">Bestimmte Bereiche</span>
                                        </div>
                                        <div class="space-y-1.5 ml-6">
                                            <template x-for="node in viewData.orgNodes" :key="node.name">
                                                <div class="flex items-center justify-between bg-white rounded px-2.5 py-1.5 border border-purple-100">
                                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-purple-800">
                                                        <span class="w-2 h-2 rounded-full" :style="'background:' + node.color"></span>
                                                        <span x-text="node.name"></span>
                                                    </span>
                                                    <span x-show="node.start_date || node.end_date" class="text-[10px] text-gray-400">
                                                        <span x-show="node.start_date" x-text="node.start_date"></span>
                                                        <span x-show="node.start_date && node.end_date"> – </span>
                                                        <span x-show="node.end_date" x-text="node.end_date"></span>
                                                    </span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="!viewData?.visibleCommunity && !viewData?.visibleOrganization && !viewData?.orgNodes?.length" class="text-xs text-gray-400 italic">Keine Sichtbarkeit definiert.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Event Formular im Content --}}
        <div x-show="showEventTab" x-cloak class="p-6">
            @php $eventTypes = \App\Models\EventType::active()->ordered()->get(); @endphp

            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1" x-text="eventMode === 'edit' ? 'Ereignis bearbeiten' : 'Neues Ereignis erstellen'"></h3>
                <p class="text-sm text-gray-500 mb-6">Erfassen Sie die Details für Ihr Ereignis.</p>

                <form @submit.prevent="$wire = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')); eventMode === 'edit' ? $wire.call('save') : $wire.call('save')">

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Linke Spalte --}}
                        <div class="space-y-4">
                            <div class="bg-white rounded-lg border border-gray-200 p-5">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-info-circle text-blue-500 mr-1"></i> Grunddaten
                                </h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Titel <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="eventData.title" @input="syncToLivewire('title', $event.target.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Titel des Ereignisses">
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-xs font-medium text-gray-700">Beschreibung</label>
                                            <button type="button" @click="clearDescription()" class="text-[10px] text-gray-400 hover:text-red-500 flex items-center gap-1 transition-colors" title="Beschreibung leeren">
                                                <i class="fas fa-eraser"></i> Leeren
                                            </button>
                                        </div>
                                        <div id="quill-editor-container">
                                            <div id="quill-editor" style="min-height:120px"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Geokoordinaten --}}
                            <div class="bg-white rounded-lg border border-gray-200 p-5">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-map-pin text-blue-500 mr-1"></i> Geokoordinaten
                                </h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Koordinaten einfügen <span class="text-[10px] text-gray-400 font-normal">(z.B. aus Google Maps kopieren)</span></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-paste text-xs"></i></span>
                                            <input type="text" placeholder="z.B. 48.137154, 11.576124 oder einfach einfügen"
                                                   class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
                                                   @paste.prevent="parseCoordinates($event)"
                                                   @change="parseCoordinatesFromValue($event.target.value); $event.target.value = ''"
                                                   @keydown.enter.prevent="parseCoordinatesFromValue($event.target.value); $event.target.value = ''">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Breitengrad (Lat)</label>
                                            <input type="text" x-model="eventData.latitude" @change="syncToLivewire('latitude', $event.target.value); updateMap()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono" placeholder="z.B. 48.137154">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Längengrad (Lng)</label>
                                            <input type="text" x-model="eventData.longitude" @change="syncToLivewire('longitude', $event.target.value); updateMap()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono" placeholder="z.B. 11.576124">
                                        </div>
                                    </div>
                                    <div x-show="eventData.latitude && eventData.longitude" x-cloak class="flex items-center justify-between">
                                        <p class="text-[10px] text-green-600"><i class="fas fa-check-circle mr-1"></i>Koordinaten gesetzt: <span x-text="eventData.latitude"></span>, <span x-text="eventData.longitude"></span></p>
                                        <button type="button" @click="eventData.latitude = ''; eventData.longitude = ''; syncToLivewire('latitude', ''); syncToLivewire('longitude', ''); updateMap()" class="text-[10px] text-gray-400 hover:text-red-500"><i class="fas fa-eraser mr-1"></i>Entfernen</button>
                                    </div>
                                    {{-- Karte --}}
                                    <div x-show="eventData.latitude && eventData.longitude" x-cloak>
                                        <div id="event-map" style="height: 250px; border-radius: 8px; border: 1px solid #e5e7eb;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Rechte Spalte --}}
                        <div class="space-y-4">
                            {{-- Status --}}
                            <div class="bg-white rounded-lg border border-gray-200 p-5">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-gray-900">
                                        <i class="fas fa-power-off text-blue-500 mr-1"></i> Status
                                    </h4>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <div class="relative">
                                            <input type="checkbox" x-model="eventData.isActive" @change="syncToLivewire('isActive', $event.target.checked)" class="sr-only peer">
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700" x-text="eventData.isActive ? 'Aktiv' : 'Inaktiv'"></span>
                                    </label>
                                </div>
                            </div>

                            {{-- Zeitraum --}}
                            <div class="bg-white rounded-lg border border-gray-200 p-5">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-calendar text-blue-500 mr-1"></i> Zeitraum
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Startdatum <span class="text-red-500">*</span></label>
                                        <input type="date" x-model="eventData.startDate" @change="syncToLivewire('startDate', $event.target.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Enddatum</label>
                                        <input type="date" x-model="eventData.endDate" @change="syncToLivewire('endDate', $event.target.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>

                            {{-- Kategorie --}}
                            <div class="bg-white rounded-lg border border-gray-200 p-5">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-tags text-blue-500 mr-1"></i> Kategorie <span class="text-red-500">*</span>
                                </h4>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    @foreach($eventTypes as $type)
                                        <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                               :class="eventData.selectedEventTypes.includes({{ $type->id }}) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                            <input type="checkbox" value="{{ $type->id }}"
                                                   :checked="eventData.selectedEventTypes.includes({{ $type->id }})"
                                                   @change="toggleEventType({{ $type->id }})"
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">
                                            <span class="text-xs">{{ $type->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="bg-white rounded-lg border border-gray-200 p-5">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-gauge-high text-blue-500 mr-1"></i> Risikostufe <span class="text-red-500">*</span>
                                </h4>
                                <div class="grid grid-cols-2 gap-2">
                                    <template x-for="p in [{v:'info',l:'Information',c:'bg-blue-50 border-blue-300 text-blue-700'},{v:'low',l:'Niedrig',c:'bg-yellow-50 border-yellow-300 text-yellow-700'},{v:'medium',l:'Mittel',c:'bg-orange-50 border-orange-300 text-orange-700'},{v:'high',l:'Hoch',c:'bg-red-50 border-red-300 text-red-700'}]">
                                        <button type="button" @click="eventData.priority = p.v; syncToLivewire('priority', p.v)"
                                            class="px-3 py-2 rounded-lg text-xs font-medium border transition-colors"
                                            :class="eventData.priority === p.v ? p.c : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                                            x-text="p.l">
                                        </button>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Sichtbarkeit --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
                        <h4 class="text-sm font-semibold text-gray-900 mb-4">
                            <i class="fas fa-eye text-blue-500 mr-1"></i> Sichtbarkeit
                        </h4>
                        <p class="text-xs text-gray-500 mb-4">Bestimmen Sie, wer dieses Ereignis sehen kann. Mehrfachauswahl ist möglich.</p>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                            {{-- GTM Community --}}
                            <div class="rounded-lg border transition-colors" :class="eventData.visibleCommunity ? 'border-blue-300 bg-blue-50' : 'border-gray-200'">
                                <label class="flex items-start gap-3 p-3 cursor-pointer">
                                    <div class="pt-0.5">
                                        <input type="checkbox" x-model="eventData.visibleCommunity"
                                               @change="if($event.target.checked && !eventData.communityStartDate) { eventData.communityStartDate = eventData.startDate || ''; eventData.communityEndDate = eventData.endDate || ''; syncToLivewire('communityStartDate', eventData.communityStartDate); syncToLivewire('communityEndDate', eventData.communityEndDate); } syncToLivewire('visibleCommunity', $event.target.checked)"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold text-gray-900 flex items-center gap-1">
                                            <i class="fas fa-earth-europe text-blue-500"></i> GTM-Community
                                        </span>
                                        <p class="text-[10px] text-gray-500 mt-1">Sichtbar für alle Nutzer des Global Travel Monitors weltweit.</p>
                                    </div>
                                </label>
                                <div x-show="eventData.visibleCommunity" x-cloak class="grid grid-cols-2 gap-3 pr-3 pb-3 ml-10">
                                    <div>
                                        <label class="block text-[9px] text-gray-400 mb-0.5">Start</label>
                                        <input type="date" x-model="eventData.communityStartDate" @change="syncToLivewire('communityStartDate', $event.target.value)" class="w-full px-2 py-1 border border-gray-300 rounded text-[11px] focus:ring-1 focus:ring-blue-500 bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] text-gray-400 mb-0.5">Ende</label>
                                        <input type="date" x-model="eventData.communityEndDate" @change="syncToLivewire('communityEndDate', $event.target.value)" class="w-full px-2 py-1 border border-gray-300 rounded text-[11px] focus:ring-1 focus:ring-blue-500 bg-white">
                                    </div>
                                </div>
                            </div>

                            {{-- Eigene Organisation --}}
                            <div class="rounded-lg border transition-colors" :class="eventData.visibleOrganization ? 'border-green-300 bg-green-50' : 'border-gray-200'">
                                <label class="flex items-start gap-3 p-3 cursor-pointer">
                                    <div class="pt-0.5">
                                        <input type="checkbox" x-model="eventData.visibleOrganization"
                                               @change="if($event.target.checked) { if(!eventData.organizationStartDate) { eventData.organizationStartDate = eventData.startDate || ''; eventData.organizationEndDate = eventData.endDate || ''; syncToLivewire('organizationStartDate', eventData.organizationStartDate); syncToLivewire('organizationEndDate', eventData.organizationEndDate); } showOrgNodePicker = false; eventData.selectedOrgNodes = []; eventData.orgNodeDates = []; syncToLivewire('selectedOrgNodes', []); syncToLivewire('orgNodeDates', []); } syncToLivewire('visibleOrganization', $event.target.checked)"
                                               class="rounded border-gray-300 text-green-600 focus:ring-green-500 w-4 h-4">
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold text-gray-900 flex items-center gap-1">
                                            <i class="fas fa-building text-green-500"></i> Gesamte Organisation
                                        </span>
                                        <p class="text-[10px] text-gray-500 mt-1">Sichtbar für alle Mitglieder, Filialen und Kooperationspartner.</p>
                                    </div>
                                </label>
                                <div x-show="eventData.visibleOrganization" x-cloak class="grid grid-cols-2 gap-3 pr-3 pb-3 ml-10">
                                    <div>
                                        <label class="block text-[9px] text-gray-400 mb-0.5">Start</label>
                                        <input type="date" x-model="eventData.organizationStartDate" @change="syncToLivewire('organizationStartDate', $event.target.value)" class="w-full px-2 py-1 border border-gray-300 rounded text-[11px] focus:ring-1 focus:ring-green-500 bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] text-gray-400 mb-0.5">Ende</label>
                                        <input type="date" x-model="eventData.organizationEndDate" @change="syncToLivewire('organizationEndDate', $event.target.value)" class="w-full px-2 py-1 border border-gray-300 rounded text-[11px] focus:ring-1 focus:ring-green-500 bg-white">
                                    </div>
                                </div>
                            </div>

                            {{-- Bestimmte Bereiche --}}
                            <div class="rounded-lg border transition-colors" :class="showOrgNodePicker ? 'border-purple-300 bg-purple-50' : 'border-gray-200'">
                                <label class="flex items-start gap-3 p-3 cursor-pointer">
                                    <div class="pt-0.5">
                                        <input type="checkbox" x-model="showOrgNodePicker"
                                               @change="if(showOrgNodePicker) { eventData.visibleOrganization = false; eventData.organizationStartDate = ''; eventData.organizationEndDate = ''; syncToLivewire('visibleOrganization', false); syncToLivewire('organizationStartDate', ''); syncToLivewire('organizationEndDate', ''); } else { eventData.selectedOrgNodes = []; eventData.orgNodeDates = []; syncToLivewire('selectedOrgNodes', []); syncToLivewire('orgNodeDates', []); }"
                                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 w-4 h-4">
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold text-gray-900 flex items-center gap-1">
                                            <i class="fas fa-sitemap text-purple-500"></i> Bestimmte Bereiche
                                        </span>
                                        <p class="text-[10px] text-gray-500 mt-1">Nur für ausgewählte Bereiche der Organisationsstruktur sichtbar.</p>
                                        <p class="text-[10px] text-purple-600 font-medium mt-1" x-show="eventData.selectedOrgNodes.length > 0" x-text="eventData.selectedOrgNodes.length + ' Bereiche ausgewählt'"></p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Org-Knoten Auswahl (nur sichtbar wenn "Bestimmte Bereiche" aktiv) --}}
                        @if($orgNodes->isNotEmpty())
                        <div x-show="showOrgNodePicker" x-cloak x-transition class="border-t border-gray-200 pt-4">
                            <div class="flex items-center justify-between mb-3">
                                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Bereiche auswählen</h5>
                                <div class="flex gap-2">
                                    <button type="button" @click="selectAllOrgNodes()" class="text-[10px] text-purple-600 hover:text-purple-800 flex items-center gap-1">
                                        <i class="fas fa-check-double"></i> Alle auswählen
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button type="button" @click="deselectAllOrgNodes()" class="text-[10px] text-gray-500 hover:text-gray-700 flex items-center gap-1">
                                        <i class="fas fa-times"></i> Alle abwählen
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-1" x-html="renderVisibilityTree()"></div>
                        </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-gray-200">
                        <button type="button" @click="closeForm()" class="px-4 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                        <button type="button" @click="saveEvent()" class="px-5 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1">
                            <i class="fas fa-save"></i> <span x-text="eventMode === 'edit' ? 'Aktualisieren' : 'Erstellen'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Promo/Info wenn keine Events und kein Formular --}}
        <div x-show="!showEventTab && !showEventView">
        @if(!$hasEvents)
            {{-- Promo/Info Content when no events exist --}}
            <div style="background: #021a2b;" class="min-h-full">

                {{-- Hero Section --}}
                <section class="relative flex items-center justify-center overflow-hidden py-16 px-6" style="background: #021a2b;">
                    <div class="max-w-4xl mx-auto text-center relative z-10">
                        <h1 class="text-4xl md:text-5xl font-extrabold leading-tight mb-6" style="font-family: Archivo, sans-serif;">
                            <span style="color: #ffffff;">Eigene </span><span class="text-[#cee741]">Ereignisse</span>
                        </h1>

                        <p class="text-base md:text-lg max-w-3xl mx-auto mb-10 leading-relaxed" style="color: #91daf2;">
                            Erstellen Sie eigene Ereignisse für Ihr Unternehmen und informieren Sie Ihre Mitarbeiter, Filialen oder externe Partner gezielt
                            über wichtige Vorfälle, Sicherheitshinweise oder organisatorische Mitteilungen.
                            Ergänzen Sie die Passolution-Ereignisse des Global Travel Monitors um Ihre eigenen Informationen und
                            stellen Sie diese wahlweise nur intern oder auch der gesamten GTM-Community zur Verfügung.
                        </p>

                        {{-- Stats Row --}}
                        <div class="flex flex-wrap justify-center gap-4 lg:gap-8">
                            <div class="flex items-center gap-2 px-5 py-3 rounded-xl shadow-sm" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <i class="fas fa-sitemap text-[#cee741]"></i>
                                <span class="text-sm font-medium" style="color: #ffffff;">Filialen, Ketten & Kooperationen</span>
                            </div>
                            <div class="flex items-center gap-2 px-5 py-3 rounded-xl shadow-sm" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <i class="fas fa-users text-[#cee741]"></i>
                                <span class="text-sm font-medium" style="color: #ffffff;">Mitarbeiter & Teams</span>
                            </div>
                            <div class="flex items-center gap-2 px-5 py-3 rounded-xl shadow-sm" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <i class="fas fa-eye text-[#cee741]"></i>
                                <span class="text-sm font-medium" style="color: #ffffff;">Flexible Sichtbarkeit</span>
                            </div>
                            <div class="flex items-center gap-2 px-5 py-3 rounded-xl shadow-sm" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <i class="fas fa-earth-europe text-[#cee741]"></i>
                                <span class="text-sm font-medium" style="color: #ffffff;">Öffentlich für alle GTM-Nutzer</span>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Feature Grid --}}
                <section class="py-16 px-6" style="background: #021a2b;">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-center mb-10">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border mb-4" style="background: rgba(206, 231, 65, 0.15); border-color: rgba(206, 231, 65, 0.25);">
                                <span class="text-sm font-medium text-[#cee741]">Features</span>
                            </div>
                            <h2 class="text-2xl md:text-3xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Was Sie mit eigenen <span class="text-[#cee741]">Ereignissen</span> tun können</h2>
                            <p class="max-w-2xl mx-auto" style="color: #91daf2;">
                                Nutzen Sie eigene Ereignisse, um Ihre Organisation gezielt zu informieren und die Kommunikation zu verbessern.
                            </p>
                        </div>
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            {{-- Interne Ereignisse --}}
                            <div class="p-6 rounded-2xl backdrop-blur-sm" style="background: rgba(6, 82, 114, 0.5); border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(206, 231, 65, 0.15);">
                                    <i class="fas fa-lock text-xl text-[#cee741]"></i>
                                </div>
                                <h3 class="font-semibold mb-2 text-center" style="color: #ffffff;">Interne Ereignisse</h3>
                                <p class="text-sm text-center" style="color: #91daf2;">Erstellen Sie Ereignisse, die nur für Ihre Mitarbeiter, Filialen, Kooperationen und Ketten-Mitglieder sichtbar sind.</p>
                            </div>
                            {{-- Externe Sichtbarkeit --}}
                            <div class="p-6 rounded-2xl backdrop-blur-sm" style="background: rgba(6, 82, 114, 0.5); border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(206, 231, 65, 0.15);">
                                    <i class="fas fa-globe text-xl text-[#cee741]"></i>
                                </div>
                                <h3 class="font-semibold mb-2 text-center" style="color: #ffffff;">Externe Sichtbarkeit</h3>
                                <p class="text-sm text-center" style="color: #91daf2;">Machen Sie Ereignisse auch für alle Nutzer des Global Travel Monitors sichtbar - Reisebüros, Veranstalter und die gesamte Community.</p>
                            </div>
                            {{-- Kategorien & Prioritäten --}}
                            <div class="p-6 rounded-2xl backdrop-blur-sm" style="background: rgba(6, 82, 114, 0.5); border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(206, 231, 65, 0.15);">
                                    <i class="fas fa-tags text-xl text-[#cee741]"></i>
                                </div>
                                <h3 class="font-semibold mb-2 text-center" style="color: #ffffff;">Kategorien & Prioritäten</h3>
                                <p class="text-sm text-center" style="color: #91daf2;">Ordnen Sie Ereignisse nach Kategorien und Prioritätsstufen, damit Ihre Mitarbeiter sofort wissen, wie dringend eine Meldung ist.</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Infografik: Zusammenspiel mit der GTM-Community --}}
                <section class="py-16 px-6" style="background: #021a2b; border-top: 1px solid rgba(145, 218, 242, 0.1);">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-center mb-10">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border mb-4" style="background: rgba(145, 218, 242, 0.1); border-color: rgba(145, 218, 242, 0.2);">
                                <i class="fas fa-diagram-project text-[#91daf2]"></i>
                                <span class="text-sm font-medium text-[#91daf2]">So funktioniert das Zusammenspiel</span>
                            </div>
                            <h2 class="text-2xl md:text-3xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Ihr Unternehmen im <span class="text-[#cee741]">Global Travel Monitor</span></h2>
                        </div>

                        {{-- SVG Infografik --}}
                        <div class="flex justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 660" class="w-full max-w-3xl" style="font-family: system-ui, -apple-system, sans-serif;">
                                <defs>
                                    {{-- Gradients --}}
                                    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#043451;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#021a2b;stop-opacity:1" />
                                    </linearGradient>
                                    <linearGradient id="limeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#cee741;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#a8c430;stop-opacity:1" />
                                    </linearGradient>
                                    <linearGradient id="blueGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#91daf2;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#5cb8d6;stop-opacity:1" />
                                    </linearGradient>
                                    <linearGradient id="arrowGradLime" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#cee741;stop-opacity:0.6" />
                                        <stop offset="100%" style="stop-color:#cee741;stop-opacity:1" />
                                    </linearGradient>
                                    <linearGradient id="arrowGradBlue" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#91daf2;stop-opacity:0.6" />
                                        <stop offset="100%" style="stop-color:#91daf2;stop-opacity:1" />
                                    </linearGradient>
                                    <linearGradient id="arrowGradDown" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style="stop-color:#cee741;stop-opacity:0.4" />
                                        <stop offset="100%" style="stop-color:#cee741;stop-opacity:1" />
                                    </linearGradient>
                                    <linearGradient id="arrowGradDownBlue" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style="stop-color:#91daf2;stop-opacity:0.4" />
                                        <stop offset="100%" style="stop-color:#91daf2;stop-opacity:1" />
                                    </linearGradient>
                                    <filter id="glow">
                                        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                                        <feMerge>
                                            <feMergeNode in="coloredBlur"/>
                                            <feMergeNode in="SourceGraphic"/>
                                        </feMerge>
                                    </filter>
                                    <filter id="shadow">
                                        <feDropShadow dx="0" dy="2" stdDeviation="4" flood-opacity="0.3" flood-color="#000"/>
                                    </filter>
                                    {{-- Arrow markers --}}
                                    <marker id="arrowLime" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                        <polygon points="0 0, 10 3.5, 0 7" fill="#cee741"/>
                                    </marker>
                                    <marker id="arrowBlue" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                        <polygon points="0 0, 10 3.5, 0 7" fill="#91daf2"/>
                                    </marker>
                                    <marker id="arrowWhite" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                        <polygon points="0 0, 10 3.5, 0 7" fill="#ffffff" opacity="0.6"/>
                                    </marker>
                                </defs>

                                {{-- Background --}}
                                <rect width="900" height="660" rx="16" fill="url(#bgGrad)" stroke="rgba(145,218,242,0.15)" stroke-width="1"/>

                                {{-- ============ TOP ROW: Ihr Unternehmen (center) ============ --}}
                                <rect x="310" y="30" width="280" height="90" rx="12" fill="#065272" stroke="#cee741" stroke-width="2" filter="url(#shadow)"/>
                                <text x="450" y="62" text-anchor="middle" fill="#cee741" font-size="13" font-weight="700" letter-spacing="1">IHR UNTERNEHMEN</text>
                                <text x="450" y="82" text-anchor="middle" fill="#91daf2" font-size="11">erstellt eigene Ereignisse</text>
                                {{-- Company icon --}}
                                <rect x="430" y="98" width="40" height="3" rx="1.5" fill="#cee741" opacity="0.4"/>

                                {{-- ============ Arrows from Company going down to the two paths ============ --}}
                                {{-- Left arrow: Nur intern --}}
                                <path d="M 380 120 L 380 155 Q 380 165 370 165 L 220 165" fill="none" stroke="url(#arrowGradLime)" stroke-width="2.5" marker-end="url(#arrowLime)" stroke-dasharray="6,3"/>
                                <rect x="275" y="135" width="90" height="22" rx="11" fill="rgba(206,231,65,0.15)" stroke="rgba(206,231,65,0.3)" stroke-width="1"/>
                                <text x="320" y="150" text-anchor="middle" fill="#cee741" font-size="9" font-weight="600">NUR INTERN</text>

                                {{-- Right arrow: Öffentlich --}}
                                <path d="M 520 120 L 520 180 Q 520 190 530 190 L 690 190" fill="none" stroke="url(#arrowGradBlue)" stroke-width="2.5" marker-end="url(#arrowBlue)" stroke-dasharray="6,3"/>
                                <rect x="540" y="160" width="100" height="22" rx="11" fill="rgba(145,218,242,0.15)" stroke="rgba(145,218,242,0.3)" stroke-width="1"/>
                                <text x="590" y="175" text-anchor="middle" fill="#91daf2" font-size="9" font-weight="600">ÖFFENTLICH</text>

                                {{-- ============ LEFT COLUMN: Interne Nutzung ============ --}}
                                {{-- Box: Eigene Mitarbeiter --}}
                                <rect x="40" y="175" width="200" height="56" rx="10" fill="#043451" stroke="rgba(206,231,65,0.3)" stroke-width="1.5" filter="url(#shadow)"/>
                                <circle cx="75" cy="203" r="13" fill="rgba(206,231,65,0.15)"/>
                                <text x="75" y="207" text-anchor="middle" fill="#cee741" font-size="13">&#xf0c0;</text>
                                <text x="150" y="199" text-anchor="middle" fill="#ffffff" font-size="11" font-weight="600">Mitarbeiter</text>
                                <text x="150" y="215" text-anchor="middle" fill="#91daf2" font-size="9">Ihr Team &amp; Kollegen</text>

                                {{-- Box: Filialen & Standorte --}}
                                <rect x="40" y="243" width="200" height="56" rx="10" fill="#043451" stroke="rgba(206,231,65,0.3)" stroke-width="1.5" filter="url(#shadow)"/>
                                <circle cx="75" cy="271" r="13" fill="rgba(206,231,65,0.15)"/>
                                <text x="75" y="275" text-anchor="middle" fill="#cee741" font-size="13">&#xf1ad;</text>
                                <text x="150" y="267" text-anchor="middle" fill="#ffffff" font-size="11" font-weight="600">Filialen &amp; Standorte</text>
                                <text x="150" y="283" text-anchor="middle" fill="#91daf2" font-size="9">Alle Niederlassungen</text>

                                {{-- Box: Kooperationen & Ketten --}}
                                <rect x="40" y="311" width="200" height="56" rx="10" fill="#043451" stroke="rgba(206,231,65,0.3)" stroke-width="1.5" filter="url(#shadow)"/>
                                <circle cx="75" cy="339" r="13" fill="rgba(206,231,65,0.15)"/>
                                <text x="75" y="343" text-anchor="middle" fill="#cee741" font-size="13">&#xf0c1;</text>
                                <text x="150" y="335" text-anchor="middle" fill="#ffffff" font-size="11" font-weight="600">Kooperationen</text>
                                <text x="150" y="351" text-anchor="middle" fill="#91daf2" font-size="9">Ketten &amp; Verbünde</text>

                                {{-- Box: Reiseveranstalter --}}
                                <rect x="40" y="379" width="200" height="56" rx="10" fill="#043451" stroke="rgba(206,231,65,0.3)" stroke-width="1.5" filter="url(#shadow)"/>
                                <circle cx="75" cy="407" r="13" fill="rgba(206,231,65,0.15)"/>
                                <text x="75" y="411" text-anchor="middle" fill="#cee741" font-size="13">&#xf5b7;</text>
                                <text x="150" y="403" text-anchor="middle" fill="#ffffff" font-size="11" font-weight="600">Reiseveranstalter</text>
                                <text x="150" y="419" text-anchor="middle" fill="#91daf2" font-size="9">Partner &amp; Veranstalter</text>

                                {{-- Box: Eigene Benutzer --}}
                                <rect x="40" y="447" width="200" height="56" rx="10" fill="#043451" stroke="rgba(206,231,65,0.3)" stroke-width="1.5" filter="url(#shadow)"/>
                                <circle cx="75" cy="475" r="13" fill="rgba(206,231,65,0.15)"/>
                                <text x="75" y="479" text-anchor="middle" fill="#cee741" font-size="13">&#xf007;</text>
                                <text x="150" y="471" text-anchor="middle" fill="#ffffff" font-size="11" font-weight="600">Eigene Benutzer</text>
                                <text x="150" y="487" text-anchor="middle" fill="#91daf2" font-size="9">Registrierte Nutzer</text>

                                {{-- Connecting lines left column --}}
                                <line x1="140" y1="231" x2="140" y2="243" stroke="rgba(206,231,65,0.3)" stroke-width="1"/>
                                <line x1="140" y1="299" x2="140" y2="311" stroke="rgba(206,231,65,0.3)" stroke-width="1"/>
                                <line x1="140" y1="367" x2="140" y2="379" stroke="rgba(206,231,65,0.3)" stroke-width="1"/>
                                <line x1="140" y1="435" x2="140" y2="447" stroke="rgba(206,231,65,0.3)" stroke-width="1"/>

                                {{-- Left column label --}}
                                <rect x="55" y="520" width="170" height="30" rx="8" fill="rgba(206,231,65,0.08)" stroke="rgba(206,231,65,0.2)" stroke-width="1"/>
                                <text x="140" y="539" text-anchor="middle" fill="#cee741" font-size="10" font-weight="600">Nur Ihre Organisation</text>

                                {{-- ============ RIGHT COLUMN: GTM Community ============ --}}
                                {{-- Large GTM Community Box --}}
                                <rect x="560" y="210" width="290" height="280" rx="14" fill="rgba(145,218,242,0.06)" stroke="rgba(145,218,242,0.25)" stroke-width="1.5"/>
                                <text x="705" y="237" text-anchor="middle" fill="#91daf2" font-size="10" font-weight="700" letter-spacing="1.5">GLOBAL TRAVEL MONITOR</text>

                                {{-- Passolution Events sub-box --}}
                                <rect x="580" y="250" width="250" height="60" rx="8" fill="#043451" stroke="rgba(145,218,242,0.2)" stroke-width="1" filter="url(#shadow)"/>
                                <circle cx="610" cy="280" r="12" fill="rgba(145,218,242,0.15)"/>
                                <text x="610" y="284" text-anchor="middle" fill="#91daf2" font-size="12">&#xf3ed;</text>
                                <text x="715" y="275" text-anchor="middle" fill="#ffffff" font-size="11" font-weight="600">Passolution Ereignisse</text>
                                <text x="715" y="292" text-anchor="middle" fill="#91daf2" font-size="9">Globale Sicherheitsereignisse</text>

                                {{-- Plus sign --}}
                                <circle cx="705" cy="325" r="11" fill="rgba(206,231,65,0.2)" stroke="#cee741" stroke-width="1.5"/>
                                <text x="705" y="329" text-anchor="middle" fill="#cee741" font-size="15" font-weight="700">+</text>

                                {{-- Ihre Events sub-box --}}
                                <rect x="580" y="340" width="250" height="60" rx="8" fill="#065272" stroke="#cee741" stroke-width="1.5" filter="url(#shadow)"/>
                                <circle cx="610" cy="370" r="12" fill="rgba(206,231,65,0.15)"/>
                                <text x="610" y="374" text-anchor="middle" fill="#cee741" font-size="12">&#xf073;</text>
                                <text x="715" y="365" text-anchor="middle" fill="#cee741" font-size="11" font-weight="600">Ihre Ereignisse</text>
                                <text x="715" y="382" text-anchor="middle" fill="#91daf2" font-size="9">Öffentlich geteilt</text>

                                {{-- Arrow: Ihre Events fließen nach unten zu allen Nutzern --}}
                                <path d="M 705 400 L 705 420" fill="none" stroke="url(#arrowGradDownBlue)" stroke-width="2" marker-end="url(#arrowBlue)"/>

                                {{-- Sichtbar für alle --}}
                                <rect x="580" y="425" width="250" height="18" rx="9" fill="rgba(145,218,242,0.1)"/>
                                <text x="705" y="438" text-anchor="middle" fill="#91daf2" font-size="9" font-weight="500">Sichtbar für alle GTM-Nutzer weltweit</text>

                                {{-- Community users row --}}
                                <g transform="translate(590, 452)">
                                    {{-- User icons --}}
                                    <circle cx="20" cy="15" r="12" fill="rgba(145,218,242,0.1)" stroke="rgba(145,218,242,0.2)" stroke-width="1"/>
                                    <text x="20" y="19" text-anchor="middle" fill="#91daf2" font-size="11">&#xf0c0;</text>

                                    <circle cx="60" cy="15" r="12" fill="rgba(145,218,242,0.1)" stroke="rgba(145,218,242,0.2)" stroke-width="1"/>
                                    <text x="60" y="19" text-anchor="middle" fill="#91daf2" font-size="11">&#xf1ad;</text>

                                    <circle cx="100" cy="15" r="12" fill="rgba(145,218,242,0.1)" stroke="rgba(145,218,242,0.2)" stroke-width="1"/>
                                    <text x="100" y="19" text-anchor="middle" fill="#91daf2" font-size="11">&#xf0ac;</text>

                                    <circle cx="140" cy="15" r="12" fill="rgba(145,218,242,0.1)" stroke="rgba(145,218,242,0.2)" stroke-width="1"/>
                                    <text x="140" y="19" text-anchor="middle" fill="#91daf2" font-size="11">&#xf5b7;</text>

                                    <circle cx="180" cy="15" r="12" fill="rgba(145,218,242,0.1)" stroke="rgba(145,218,242,0.2)" stroke-width="1"/>
                                    <text x="180" y="19" text-anchor="middle" fill="#91daf2" font-size="11">&#xf007;</text>

                                    <text x="210" y="19" fill="#91daf2" font-size="11" opacity="0.6">...</text>
                                </g>

                                {{-- Right column label --}}
                                <rect x="585" y="520" width="240" height="30" rx="8" fill="rgba(145,218,242,0.08)" stroke="rgba(145,218,242,0.2)" stroke-width="1"/>
                                <text x="705" y="539" text-anchor="middle" fill="#91daf2" font-size="10" font-weight="600">Alle Reisenden, Reisebüros &amp; Veranstalter</text>

                                {{-- ============ CENTER: Verbindungslinie + VS / ODER ============ --}}
                                <line x1="260" y1="370" x2="555" y2="370" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="4,4"/>

                                {{-- Center label "ODER" --}}
                                <rect x="385" y="348" width="50" height="24" rx="12" fill="#021a2b" stroke="rgba(255,255,255,0.15)" stroke-width="1"/>
                                <text x="410" y="364" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="10" font-weight="600">ODER</text>

                                {{-- Center label "BEIDES" --}}
                                <rect x="378" y="378" width="64" height="24" rx="12" fill="rgba(206,231,65,0.1)" stroke="rgba(206,231,65,0.25)" stroke-width="1"/>
                                <text x="410" y="394" text-anchor="middle" fill="#cee741" font-size="10" font-weight="600">BEIDES</text>

                                {{-- Bottom summary bar --}}
                                <rect x="40" y="618" width="820" height="28" rx="8" fill="rgba(206,231,65,0.06)" stroke="rgba(206,231,65,0.15)" stroke-width="1"/>
                                <text x="450" y="636" text-anchor="middle" fill="#ffffff" font-size="10" font-weight="500" opacity="0.7">Sie entscheiden: intern, öffentlich oder beides gleichzeitig - pro Ereignis individuell steuerbar</text>

                            </svg>
                        </div>
                    </div>
                </section>

                {{-- Use Cases --}}
                <section class="py-16 px-6" style="background: #021a2b; border-top: 1px solid rgba(145, 218, 242, 0.1);">
                    <div class="max-w-4xl mx-auto">
                        <div class="grid md:grid-cols-2 gap-12 items-center mb-16">
                            <div>
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border mb-4" style="background: rgba(206, 231, 65, 0.1); border-color: rgba(206, 231, 65, 0.2);">
                                    <i class="fas fa-sitemap text-[#cee741] text-sm"></i>
                                    <span class="text-xs font-medium text-[#cee741]">Ihre Organisation</span>
                                </div>
                                <h3 class="text-2xl md:text-3xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Ereignisse für Ihr <span class="text-[#cee741]">Netzwerk</span></h3>
                                <p class="leading-relaxed mb-6" style="color: #91daf2;">
                                    Informieren Sie Ihre gesamte Organisation auf einen Schlag - ob Filialen, Kooperationspartner, Ketten-Mitglieder
                                    oder Reiseveranstalter. Alle relevanten Stellen erhalten Ihre Ereignisse sofort.
                                </p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <i class="fas fa-check text-[#cee741] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Filialen, Standorte und Niederlassungen</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fas fa-check text-[#cee741] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Kooperationen und Ketten-Verbünde</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fas fa-check text-[#cee741] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Reiseveranstalter und Partner</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fas fa-check text-[#cee741] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Eigene Mitarbeiter und Teams</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="p-8 rounded-2xl" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="text-center">
                                    <i class="fas fa-sitemap text-6xl text-[#cee741] mb-4 opacity-50"></i>
                                    <p class="text-sm" style="color: #91daf2;">Verwalten Sie Ereignisse zentral und steuern Sie die Sichtbarkeit für Filialen, Kooperationen, Ketten und Veranstalter.</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-12 items-center">
                            <div class="p-8 rounded-2xl order-2 md:order-1" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="text-center">
                                    <i class="fas fa-users text-6xl text-[#91daf2] mb-4 opacity-50"></i>
                                    <p class="text-sm" style="color: #91daf2;">Bestimmen Sie, ob Ereignisse nur intern oder auch extern sichtbar sein sollen.</p>
                                </div>
                            </div>
                            <div class="order-1 md:order-2">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border mb-4" style="background: rgba(145, 218, 242, 0.1); border-color: rgba(145, 218, 242, 0.2);">
                                    <i class="fas fa-eye text-[#91daf2] text-sm"></i>
                                    <span class="text-xs font-medium text-[#91daf2]">Sichtbarkeit</span>
                                </div>
                                <h3 class="text-2xl md:text-3xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Flexible <span class="text-[#91daf2]">Sichtbarkeit</span></h3>
                                <p class="leading-relaxed mb-6" style="color: #91daf2;">
                                    Steuern Sie genau, wer Ihre Ereignisse sehen kann. Erstellen Sie interne Meldungen nur für Ihr Team, Ihre Kooperationspartner
                                    und Ketten-Mitglieder oder machen Sie wichtige Informationen auch für alle Nutzer des Global Travel Monitors zugänglich.
                                </p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <i class="fas fa-check text-[#91daf2] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Nur eigene Mitglieder, Ketten &amp; Kooperationen</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fas fa-check text-[#91daf2] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Sichtbar für alle GTM-Nutzer</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <i class="fas fa-check text-[#91daf2] mt-0.5 flex-shrink-0"></i>
                                        <span style="color: #b8e6f7;">Dynamisch steuerbare Freigabe</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Öffentliche Events für die GTM-Community --}}
                <section class="py-16 px-6" style="background: #021a2b; border-top: 1px solid rgba(145, 218, 242, 0.1);">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-center mb-10">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border mb-4" style="background: rgba(206, 231, 65, 0.15); border-color: rgba(206, 231, 65, 0.25);">
                                <i class="fas fa-earth-europe text-[#cee741]"></i>
                                <span class="text-sm font-medium text-[#cee741]">Ergänzung zu Passolution Events</span>
                            </div>
                            <h2 class="text-2xl md:text-3xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">Ihre Ereignisse für die <span class="text-[#cee741]">Allgemeinheit</span></h2>
                            <p class="max-w-2xl mx-auto" style="color: #91daf2;">
                                Der Global Travel Monitor zeigt allen Nutzern die Passolution-Ereignisse zu Sicherheit, Umwelt, Gesundheit und Reiseverkehr.
                                Als Unternehmen können Sie Ihre eigenen Ereignisse zusätzlich für die gesamte GTM-Community veröffentlichen und so die
                                Informationsgrundlage für alle Reisenden erweitern.
                            </p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-12 items-start mb-12">
                            {{-- Vorteile --}}
                            <div>
                                <h3 class="text-xl font-bold mb-6" style="color: #ffffff; font-family: Archivo, sans-serif;">
                                    <i class="fas fa-star text-[#cee741] mr-2"></i>Vorteile öffentlicher Ereignisse
                                </h3>
                                <ul class="space-y-4">
                                    <li class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5" style="background: rgba(206, 231, 65, 0.15);">
                                            <i class="fas fa-handshake text-[#cee741] text-sm"></i>
                                        </div>
                                        <div>
                                            <span class="font-semibold block mb-1" style="color: #ffffff;">Branchenwissen teilen</span>
                                            <span class="text-sm" style="color: #91daf2;">Teilen Sie Ihr lokales Wissen und Ihre Branchenexpertise mit der gesamten Reise-Community. Ereignisse, die Passolution nicht abdeckt, ergänzen das Gesamtbild.</span>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5" style="background: rgba(206, 231, 65, 0.15);">
                                            <i class="fas fa-bullhorn text-[#cee741] text-sm"></i>
                                        </div>
                                        <div>
                                            <span class="font-semibold block mb-1" style="color: #ffffff;">Sichtbarkeit & Reputation</span>
                                            <span class="text-sm" style="color: #91daf2;">Positionieren Sie Ihr Unternehmen als verlässliche Informationsquelle. Alle GTM-Nutzer sehen Ihre Ereignisse neben den offiziellen Passolution-Meldungen.</span>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5" style="background: rgba(206, 231, 65, 0.15);">
                                            <i class="fas fa-shield-halved text-[#cee741] text-sm"></i>
                                        </div>
                                        <div>
                                            <span class="font-semibold block mb-1" style="color: #ffffff;">Gemeinsame Sicherheit</span>
                                            <span class="text-sm" style="color: #91daf2;">Lokale Vorfälle, regionale Streiks oder branchenspezifische Warnungen, die anderen Reisebüros und Reiseveranstaltern helfen, schneller zu reagieren.</span>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5" style="background: rgba(206, 231, 65, 0.15);">
                                            <i class="fas fa-network-wired text-[#cee741] text-sm"></i>
                                        </div>
                                        <div>
                                            <span class="font-semibold block mb-1" style="color: #ffffff;">Netzwerkeffekt</span>
                                            <span class="text-sm" style="color: #91daf2;">Je mehr Unternehmen ihre Erfahrungen teilen, desto wertvoller wird die Informationsbasis für die gesamte GTM-Community.</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            {{-- Beispiele --}}
                            <div>
                                <h3 class="text-xl font-bold mb-6" style="color: #ffffff; font-family: Archivo, sans-serif;">
                                    <i class="fas fa-lightbulb text-[#91daf2] mr-2"></i>Beispiele für öffentliche Ereignisse
                                </h3>
                                <div class="space-y-3">
                                    <div class="p-4 rounded-xl" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-orange-100 text-orange-800">Mittel</span>
                                            <span class="text-xs font-semibold" style="color: #ffffff;">Lokaler Flughafenstreik</span>
                                        </div>
                                        <p class="text-xs" style="color: #91daf2;">Informieren Sie die Community über angekündigte Streiks, die Passolution ggf. noch nicht erfasst hat.</p>
                                    </div>
                                    <div class="p-4 rounded-xl" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-yellow-100 text-yellow-800">Niedrig</span>
                                            <span class="text-xs font-semibold" style="color: #ffffff;">Hotelrenovierung in Ferienregion</span>
                                        </div>
                                        <p class="text-xs" style="color: #91daf2;">Warnen Sie andere Reisebüros vor Baulärm oder eingeschränkten Hotelleistungen vor Ort.</p>
                                    </div>
                                    <div class="p-4 rounded-xl" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-800">Hoch</span>
                                            <span class="text-xs font-semibold" style="color: #ffffff;">Regionale Überflutungen</span>
                                        </div>
                                        <p class="text-xs" style="color: #91daf2;">Melden Sie regionale Vorfälle aus erster Hand, bevor sie in den internationalen Medien erscheinen.</p>
                                    </div>
                                    <div class="p-4 rounded-xl" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-800">Info</span>
                                            <span class="text-xs font-semibold" style="color: #ffffff;">Neue Visabestimmungen</span>
                                        </div>
                                        <p class="text-xs" style="color: #91daf2;">Teilen Sie Erfahrungen mit geänderten Einreiseregeln, die Sie vor Ort festgestellt haben.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Hinweis-Box --}}
                        <div class="p-6 rounded-2xl text-center" style="background: rgba(206, 231, 65, 0.08); border: 1px solid rgba(206, 231, 65, 0.2);">
                            <i class="fas fa-circle-info text-[#cee741] text-xl mb-3"></i>
                            <p class="text-sm leading-relaxed" style="color: #b8e6f7;">
                                <strong style="color: #ffffff;">Passolution-Ereignisse + Ihre Expertise = bessere Reisesicherheit.</strong><br>
                                Die offiziellen Passolution-Ereignisse decken globale Sicherheitslagen ab. Ihre eigenen Ereignisse ergänzen dieses Bild mit
                                lokalem Wissen, Branchenerfahrung und Echtzeit-Informationen aus erster Hand. Gemeinsam entsteht eine umfassendere
                                Informationsgrundlage für alle Reisenden und Reisebüros im Global Travel Monitor.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- How it works --}}
                <section class="py-16 px-6" style="background: #021a2b; border-top: 1px solid rgba(145, 218, 242, 0.1);">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-center mb-10">
                            <h2 class="text-2xl md:text-3xl font-bold mb-4" style="color: #ffffff; font-family: Archivo, sans-serif;">So funktioniert's</h2>
                            <p style="color: #91daf2;" class="max-w-2xl mx-auto">In drei einfachen Schritten zu Ihren eigenen Ereignissen</p>
                        </div>
                        <div class="grid md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                            <div class="p-6 rounded-2xl text-center" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(206, 231, 65, 0.1);">
                                    <span class="text-[#cee741] font-bold text-lg">1</span>
                                </div>
                                <h3 class="text-base font-semibold mb-2" style="color: #ffffff;">Ereignis erstellen</h3>
                                <p class="text-sm" style="color: #91daf2;">
                                    Erstellen Sie ein neues Ereignis mit Titel, Beschreibung, Kategorie und Priorität.
                                </p>
                            </div>
                            <div class="p-6 rounded-2xl text-center" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(206, 231, 65, 0.1);">
                                    <span class="text-[#cee741] font-bold text-lg">2</span>
                                </div>
                                <h3 class="text-base font-semibold mb-2" style="color: #ffffff;">Sichtbarkeit festlegen</h3>
                                <p class="text-sm" style="color: #91daf2;">
                                    Bestimmen Sie, ob das Ereignis nur intern oder auch extern sichtbar sein soll.
                                </p>
                            </div>
                            <div class="p-6 rounded-2xl text-center" style="background: #043451; border: 1px solid rgba(145, 218, 242, 0.15);">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 mx-auto" style="background: rgba(206, 231, 65, 0.1);">
                                    <span class="text-[#cee741] font-bold text-lg">3</span>
                                </div>
                                <h3 class="text-base font-semibold mb-2" style="color: #ffffff;">Mitarbeiter informiert</h3>
                                <p class="text-sm" style="color: #91daf2;">
                                    Ihre Mitarbeiter und Partner sehen die Ereignisse im Global Travel Monitor und bleiben informiert.
                                </p>
                            </div>
                        </div>

                        {{-- CTA --}}
                        <div class="text-center mt-12">
                            <p class="text-lg mb-2" style="color: #ffffff;">Erstellen Sie jetzt Ihr erstes Ereignis</p>
                            <p class="text-sm mb-0" style="color: #91daf2;">
                                <i class="fas fa-arrow-left mr-1"></i> Nutzen Sie die Sidebar links, um Ihr erstes Ereignis anzulegen.
                            </p>
                        </div>
                    </div>
                </section>

            </div>
        @else
            <div class="p-4">
                {{-- Content area when events exist --}}
            </div>
        @endif
        </div>
    </div>
@endsection
