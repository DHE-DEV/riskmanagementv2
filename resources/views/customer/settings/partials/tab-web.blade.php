{{-- ==================== Tab: Web ==================== --}}
<div x-show="mdTab === 'web'" x-cloak x-data="{
    websites: [], webLoading: true, showForm: false, editId: null,
    form: { label: '', url: '', is_primary: false, notes: '' },
    async init() { await this.load(); },
    async load() {
        this.webLoading = true;
        try { const r = await fetch('{{ route('customer.websites.index') }}', {headers:{'Accept':'application/json'}}); const d = await r.json(); this.websites = d.websites || []; } catch(e) {}
        this.webLoading = false;
    },
    async save() {
        const url = this.editId ? '{{ route('customer.websites.index') }}/' + this.editId : '{{ route('customer.websites.store') }}';
        try { const r = await fetch(url, { method: this.editId ? 'PUT' : 'POST', headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body: JSON.stringify(this.form) });
        if (r.ok) { this.showForm = false; this.editId = null; this.load(); } } catch(e) {}
    },
    edit(w) { this.editId = w.id; this.form = { label: w.label, url: w.url, is_primary: w.is_primary, notes: w.notes || '' }; this.showForm = true; },
    async remove(id) { if (!confirm('Website wirklich löschen?')) return; try { await fetch('{{ route('customer.websites.index') }}/' + id, { method:'DELETE', headers:{'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'} }); this.load(); } catch(e) {} },
    dragId: null,
    async move(id, dir) {
        const idx = this.websites.findIndex(w => w.id === id);
        const newIdx = idx + dir;
        if (newIdx < 0 || newIdx >= this.websites.length) return;
        [this.websites[idx], this.websites[newIdx]] = [this.websites[newIdx], this.websites[idx]];
        this.websites = [...this.websites];
        this.saveOrder();
    },
    dragStart(id) { this.dragId = id; },
    dragOver(e, id) { e.preventDefault(); },
    async drop(id) {
        if (this.dragId === null || this.dragId === id) { this.dragId = null; return; }
        const from = this.websites.findIndex(w => w.id === this.dragId);
        const to = this.websites.findIndex(w => w.id === id);
        const item = this.websites.splice(from, 1)[0];
        this.websites.splice(to, 0, item);
        this.websites = [...this.websites];
        this.dragId = null;
        this.saveOrder();
    },
    async saveOrder() {
        try { await fetch('{{ route('customer.websites.reorder') }}', { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body: JSON.stringify({ ids: this.websites.map(w => w.id) }) }); } catch(e) {}
    },
    reset() { this.form = { label: '', url: '', is_primary: false, notes: '' }; }
}">
    <div class="flex items-center justify-between mb-4">
        <p class="text-xs text-gray-500"><span x-text="websites.length"></span> Websites erfasst</p>
        <button @click="showForm = true; editId = null; reset();"
                class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-xs">
            <i class="fas fa-plus"></i> Neue Website
        </button>
    </div>

    {{-- Formular --}}
    <div x-show="showForm" x-cloak class="bg-white rounded-lg border border-gray-200 mb-5 overflow-hidden">
        <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
            <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas text-blue-600" :class="editId ? 'fa-pen' : 'fa-globe'"></i>
                <span x-text="editId ? 'Website bearbeiten' : 'Neue Website erfassen'"></span>
            </h4>
        </div>
        <form @submit.prevent="save" class="p-5">
            <div class="mb-5">
                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fas fa-globe text-gray-400"></i> Website
                </h5>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Bezeichnung <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.label" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="z.B. Firmenseite, Blog, Shop">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">URL <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-link text-xs"></i></span>
                            <input type="url" x-model="form.url" required class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="https://www.beispiel.de">
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-5">
                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fas fa-sticky-note text-gray-400"></i> Notiz
                </h5>
                <textarea x-model="form.notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Interne Anmerkungen zu dieser Website..."></textarea>
            </div>
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <label class="flex items-center gap-2 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" x-model="form.is_primary" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                    </div>
                    <span class="text-xs font-medium text-gray-700">Haupt-Website</span>
                </label>
                <div class="flex gap-2">
                    <button type="button" @click="showForm = false" class="px-4 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                    <button type="submit" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1">
                        <i class="fas fa-save"></i> <span x-text="editId ? 'Aktualisieren' : 'Speichern'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div x-show="webLoading" class="text-center py-8"><i class="fas fa-spinner fa-spin text-gray-400 text-xl"></i></div>

    <template x-if="!webLoading && websites.length === 0 && !showForm">
        <div class="bg-white rounded-lg border border-dashed border-gray-300 p-8 text-center">
            <i class="fas fa-globe text-3xl text-gray-300 mb-2"></i>
            <p class="text-sm text-gray-500">Noch keine Websites erfasst.</p>
        </div>
    </template>

    <div x-show="!webLoading && websites.length > 0" class="space-y-3">
        <template x-for="w in websites" :key="w.id">
            <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow drag-item"
                 draggable="true"
                 @dragstart="dragStart(w.id)" @dragover="dragOver($event, w.id)" @drop="drop(w.id)" @dragend="dragId = null"
                 :class="{ 'dragging': dragId === w.id, 'drag-over': dragId !== null && dragId !== w.id }">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="drag-handle text-gray-300 hover:text-gray-500 px-1 flex-shrink-0" title="Ziehen zum Verschieben">
                            <i class="fas fa-grip-vertical text-xs"></i>
                        </div>
                        <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="w.is_primary ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500'">
                            <i class="fas fa-globe text-xs"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900" x-text="w.label"></span>
                                <span x-show="w.is_primary" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-800">Haupt-Website</span>
                            </div>
                            <a :href="w.url" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 hover:underline" x-text="w.url"></a>
                            <p x-show="w.notes" class="text-xs text-gray-400 mt-0.5 italic line-clamp-1" x-text="w.notes"></p>
                        </div>
                    </div>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-ellipsis-vertical text-sm"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition x-cloak
                             class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                            <a :href="w.url" target="_blank" @click="open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <i class="fas fa-external-link w-4 text-center text-gray-400"></i> Seite öffnen
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <button @click="move(w.id, -1); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <i class="fas fa-chevron-up w-4 text-center text-gray-400"></i> Nach oben
                            </button>
                            <button @click="move(w.id, 1); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <i class="fas fa-chevron-down w-4 text-center text-gray-400"></i> Nach unten
                            </button>
                            <div class="border-t border-gray-100 my-1"></div>
                            <button @click="edit(w); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <i class="fas fa-pen w-4 text-center text-blue-500"></i> Bearbeiten
                            </button>
                            <button @click="remove(w.id); open = false" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                                <i class="fas fa-trash w-4 text-center"></i> Löschen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
