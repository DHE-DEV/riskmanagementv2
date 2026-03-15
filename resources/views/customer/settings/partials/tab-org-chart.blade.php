<div x-data="orgChart()" x-init="load()">
    {{-- Toolbar --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-xs text-gray-500">Organisationsstruktur per Drag &amp; Drop anordnen.</p>
        <div class="flex items-center gap-2">
            <button @click="zoomOut()" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors" title="Verkleinern">
                <i class="fas fa-minus text-xs"></i>
            </button>
            <span class="text-[10px] text-gray-400 w-8 text-center" x-text="Math.round(zoom * 100) + '%'"></span>
            <button @click="zoomIn()" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors" title="Vergrößern">
                <i class="fas fa-plus text-xs"></i>
            </button>
            <button @click="zoom = 1" class="px-1.5 py-0.5 text-[10px] text-gray-400 hover:text-gray-600 rounded border border-gray-300 hover:bg-gray-100 transition-colors font-mono leading-none" title="Auf 100% zurücksetzen">
                1:1
            </button>
            <template x-if="nodes.length === 0">
                <button @click="addRoot()" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-xs">
                    <i class="fas fa-plus"></i> Wurzelknoten erstellen
                </button>
            </template>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="chartLoading" class="text-center py-12"><i class="fas fa-spinner fa-spin text-gray-400 text-xl"></i></div>

    {{-- Empty State --}}
    <template x-if="!chartLoading && nodes.length === 0">
        <div class="bg-white rounded-lg border border-dashed border-gray-300 p-12 text-center">
            <i class="fas fa-sitemap text-4xl text-gray-300 mb-3"></i>
            <p class="text-sm text-gray-500 mb-2">Noch keine Organisationsstruktur vorhanden.</p>
            <p class="text-xs text-gray-400 mb-4">Beginnen Sie mit einem Wurzelknoten (z.B. Geschäftsführung).</p>
            <button @click="addRoot()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-xs inline-flex items-center gap-2">
                <i class="fas fa-plus"></i> Struktur beginnen
            </button>
        </div>
    </template>

    {{-- Org Chart Canvas --}}
    <div x-show="!chartLoading && nodes.length > 0"
         class="bg-white rounded-lg border border-gray-200 overflow-auto flex-1" style="height: calc(100vh - 260px);"
         @org-add-child.window="openAdd($event.detail.parentId, null)"
         @org-add-sibling.window="openAdd($event.detail.parentId, $event.detail.afterId)"
         @org-edit.window="openEditById($event.detail.id)"
         @org-delete.window="deleteNode($event.detail.id)"
         @org-drop.window="handleDrop($event.detail)">
        <div class="p-8 inline-block min-w-full" :style="'transform: scale(' + zoom + '); transform-origin: top left;'">
            <template x-for="rootNode in nodes" :key="rootNode.id">
                <div class="org-tree">
                    <div x-html="renderNode(rootNode)"></div>
                </div>
            </template>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 z-[10000] flex items-center justify-center" @click.self="showModal = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 px-5 py-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-900" x-text="modalEditId ? 'Knoten bearbeiten' : 'Neuen Knoten erstellen'"></h4>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <form @submit.prevent="saveNode()" class="p-5">
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Bezeichnung <span class="text-red-500">*</span></label>
                            <input type="text" x-model="modalForm.name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="z.B. Geschäftsführung" x-ref="modalNameInput">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Code</label>
                            <input type="text" x-model="modalForm.code" maxlength="30" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="z.B. GF">
                        </div>
                    </div>
                    <div x-show="modalParentId || (modalEditId && findNode(modalEditId, nodes)?.parent_id)">
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Beziehung zum übergeordneten Knoten
                        </label>
                        <div class="flex gap-2">
                            <select x-model="modalForm.relation_label" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="">-- Bitte wählen oder eingeben --</option>
                                <option value="berichtet an">berichtet an</option>
                                <option value="untersteht">untersteht</option>
                                <option value="gehört zu">gehört zu</option>
                                <option value="ist Teil von">ist Teil von</option>
                                <option value="leitet">leitet</option>
                                <option value="koordiniert">koordiniert</option>
                                <option value="berät">berät</option>
                                <option value="unterstützt">unterstützt</option>
                                <option value="Stabsstelle">Stabsstelle</option>
                                <option value="Projektteam">Projektteam</option>
                                <option value="__custom__">Eigene Bezeichnung...</option>
                            </select>
                        </div>
                        <input x-show="modalForm.relation_label === '__custom__'" x-cloak type="text"
                               x-model="modalForm.relation_label_custom" maxlength="100"
                               class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Eigene Beziehungsbezeichnung eingeben">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Beschreibung</label>
                        <input type="text" x-model="modalForm.description" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Optionale Beschreibung">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Farbe</label>
                        <div class="flex gap-2 flex-wrap">
                            <template x-for="c in colors" :key="c">
                                <button type="button" @click="modalForm.color = c"
                                    class="w-7 h-7 rounded-full border-2 transition-all"
                                    :style="'background-color:' + c"
                                    :class="modalForm.color === c ? 'border-gray-900 scale-110' : 'border-transparent hover:scale-105'">
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-5 pt-4 border-t border-gray-200">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                    <button type="submit" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1">
                        <i class="fas fa-save"></i> <span x-text="modalEditId ? 'Aktualisieren' : 'Erstellen'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .org-tree { display: flex; flex-direction: column; align-items: center; width: fit-content; margin: 0 auto; }
    .org-node-wrap { position: relative; }
    .org-node {
        position: relative; min-width: 140px; max-width: 220px;
        padding: 8px 12px; border-radius: 10px; background: white;
        border: 2px solid #e5e7eb; text-align: center;
        transition: box-shadow 0.15s, border-color 0.15s, transform 0.15s;
        cursor: grab; user-select: none;
    }
    .org-node:active { cursor: grabbing; }
    .org-node:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .org-node.drag-source { opacity: 0.4; transform: scale(0.95); }
    .org-node.drag-target-left { border-left: 3px solid #3b82f6 !important; }
    .org-node.drag-target-right { border-right: 3px solid #3b82f6 !important; }
    .org-node.drag-target-child { border-bottom: 3px solid #10b981 !important; box-shadow: 0 4px 12px rgba(16,185,129,0.2); }
    .org-node-color { width: 100%; height: 3px; border-radius: 2px; margin-bottom: 4px; }
    .org-node-name { font-size: 12px; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .org-node-code { font-size: 9px; font-family: monospace; color: #9ca3af; margin-top: 1px; }
    .org-node-desc { font-size: 10px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
    .org-node-menu { position: absolute; top: 3px; right: 3px; opacity: 0; transition: opacity 0.15s; }
    .org-node:hover .org-node-menu { opacity: 1; }
    .org-menu-btn {
        width: 20px; height: 20px; border-radius: 4px; display: flex; align-items: center; justify-content: center;
        font-size: 10px; color: #9ca3af; background: transparent; border: none; cursor: pointer;
    }
    .org-menu-btn:hover { background: #f3f4f6; color: #374151; }
    .org-dropdown {
        display: none; position: absolute; right: 0; top: 22px; width: 170px;
        background: white; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        border: 1px solid #e5e7eb; padding: 4px 0; z-index: 100;
    }
    .org-dropdown.open { display: block; }
    .org-dropdown button {
        display: flex; align-items: center; gap: 8px; width: 100%; padding: 5px 12px;
        font-size: 11px; color: #374151; background: none; border: none; cursor: pointer; text-align: left;
    }
    .org-dropdown button:hover { background: #f9fafb; }
    .org-dropdown .sep { border-top: 1px solid #f3f4f6; margin: 2px 0; }
    .org-dropdown .del { color: #dc2626; }
    .org-dropdown .del:hover { background: #fef2f2; }
    .org-dropdown i { width: 14px; text-align: center; }
    .org-children { display: flex; padding-top: 24px; position: relative; }
    .org-children::before { content: ''; position: absolute; top: 0; left: 50%; height: 24px; border-left: 2px solid #d1d5db; }
    .org-child-wrap { position: relative; padding: 0 18px; }
    .org-child-wrap::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 0; border-top: 2px solid #d1d5db; }
    .org-child-wrap:first-child::before { left: 50%; right: 0; }
    .org-child-wrap:last-child::before { left: 0; right: 50%; }
    .org-child-wrap:only-child::before { display: none; }
    .org-child-inner { display: flex; flex-direction: column; align-items: center; margin: 0 auto; width: fit-content; }
    .org-child-connector { width: 0; height: 24px; border-left: 2px solid #d1d5db; position: relative; }
    .org-relation-label {
        position: absolute; left: 6px; top: 50%; transform: translateY(-50%);
        font-size: 8px; color: #9ca3af; background: white; padding: 0 4px;
        white-space: nowrap; line-height: 1; border-radius: 3px;
        border: 1px solid #e5e7eb;
    }
    .org-node-box-wrap { display: flex; flex-direction: column; align-items: center; position: relative; }
    .org-add-btn {
        width: 24px; height: 24px; border-radius: 50%;
        border: 2px dashed #d1d5db; display: flex; align-items: center; justify-content: center;
        cursor: pointer; color: #9ca3af; font-size: 10px; transition: all 0.15s; background: white;
        flex-shrink: 0;
    }
    .org-add-btn:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
    .org-add-btn.below { margin-top: 8px; }
    .org-add-btn.beside {
        position: absolute;
        right: -30px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
    }
</style>

@push('scripts')
<script>
document.addEventListener('click', function(e) {
    if (!e.target.closest('.org-node-menu')) {
        document.querySelectorAll('.org-dropdown.open').forEach(d => d.classList.remove('open'));
    }
});

var _orgDragId = null;

function orgChart() {
    return {
        nodes: [],
        chartLoading: true,
        zoom: 1,
        showModal: false,
        modalEditId: null,
        modalParentId: null,
        modalAfterId: null,
        modalForm: { name: '', code: '', relation_label: '', relation_label_custom: '', description: '', color: '#3b82f6' },
        colors: ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1'],

        zoomIn() { this.zoom = Math.min(this.zoom + 0.1, 2); },
        zoomOut() { this.zoom = Math.max(this.zoom - 0.1, 0.4); },

        async load() {
            this.chartLoading = true;
            try {
                const r = await fetch('{{ route("customer.org-nodes.index") }}', { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                this.nodes = d.nodes || [];
            } catch (e) { console.error(e); }
            this.chartLoading = false;
        },

        addRoot() {
            this.modalEditId = null; this.modalParentId = null; this.modalAfterId = null;
            this.modalForm = { name: '', code: '', relation_label: '', relation_label_custom: '', description: '', color: '#3b82f6' };
            this.showModal = true;
            this.$nextTick(() => this.$refs.modalNameInput?.focus());
        },

        openAdd(parentId, afterId) {
            this.modalEditId = null; this.modalParentId = parentId; this.modalAfterId = afterId;
            this.modalForm = { name: '', code: '', relation_label: '', relation_label_custom: '', description: '', color: '#3b82f6' };
            this.showModal = true;
            this.$nextTick(() => this.$refs.modalNameInput?.focus());
        },

        openEdit(node) {
            this.modalEditId = node.id; this.modalParentId = node.parent_id; this.modalAfterId = null;
            const rl = node.relation_label || '';
            const presets = ['berichtet an','untersteht','gehört zu','ist Teil von','leitet','koordiniert','berät','unterstützt','Stabsstelle','Projektteam'];
            const isPreset = presets.includes(rl);
            this.modalForm = {
                name: node.name, code: node.code || '',
                relation_label: isPreset ? rl : (rl ? '__custom__' : ''),
                relation_label_custom: isPreset ? '' : rl,
                description: node.description || '', color: node.color || '#3b82f6'
            };
            this.showModal = true;
            this.$nextTick(() => this.$refs.modalNameInput?.focus());
        },

        openEditById(id) {
            const node = this.findNode(id, this.nodes);
            if (node) this.openEdit(node);
        },

        findNode(id, list) {
            for (const n of list) {
                if (n.id === id) return n;
                if ((n.all_children || []).length) {
                    const found = this.findNode(id, n.all_children);
                    if (found) return found;
                }
            }
            return null;
        },

        async saveNode() {
            const url = this.modalEditId
                ? '{{ route("customer.org-nodes.index") }}/' + this.modalEditId
                : '{{ route("customer.org-nodes.store") }}';
            const payload = { ...this.modalForm };
            // Resolve relation_label
            if (payload.relation_label === '__custom__') {
                payload.relation_label = payload.relation_label_custom || '';
            }
            delete payload.relation_label_custom;
            if (!this.modalEditId) {
                payload.parent_id = this.modalParentId;
                if (this.modalAfterId) payload.after_id = this.modalAfterId;
            }
            try {
                const r = await fetch(url, {
                    method: this.modalEditId ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(payload)
                });
                if (r.ok) { this.showModal = false; this.load(); }
            } catch (e) { console.error(e); }
        },

        async deleteNode(id) {
            if (!confirm('Knoten und alle Unterknoten löschen?')) return;
            try {
                await fetch('{{ route("customer.org-nodes.index") }}/' + id, {
                    method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                this.load();
            } catch (e) { console.error(e); }
        },

        async handleDrop(detail) {
            const { dragId, targetId, zone } = detail;
            if (dragId === targetId) return;
            const dragNode = this.findNode(dragId, this.nodes);
            const targetNode = this.findNode(targetId, this.nodes);
            if (!dragNode || !targetNode) return;

            if (zone === 'child') {
                // Move as child of target
                try {
                    await fetch('{{ route("customer.org-nodes.index") }}/' + dragId + '/move', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ new_parent_id: targetId, position: 999 })
                    });
                    this.load();
                } catch (e) { console.error(e); }
            } else {
                // Move as sibling (before or after target)
                const position = zone === 'left' ? targetNode.sort_order : targetNode.sort_order + 1;
                try {
                    await fetch('{{ route("customer.org-nodes.index") }}/' + dragId + '/move', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ new_parent_id: targetNode.parent_id, position: position })
                    });
                    this.load();
                } catch (e) { console.error(e); }
            }
        },

        renderNode(node) {
            const children = node.all_children || [];
            const esc = (s) => s ? s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;') : '';
            const nid = node.id;
            const pid = node.parent_id || 'null';

            let html = '<div class="org-node-wrap">';
            html += '<div class="org-node-box-wrap">';

            // Node card with drag
            html += '<div class="org-node" id="orgn-' + nid + '" style="border-color:' + node.color + '40;" draggable="true"';
            html += ' ondragstart="event.dataTransfer.setData(\'text/plain\',\'' + nid + '\'); _orgDragId=' + nid + '; this.classList.add(\'drag-source\')"';
            html += ' ondragend="this.classList.remove(\'drag-source\'); _orgDragId=null; document.querySelectorAll(\'.drag-target-left,.drag-target-right,.drag-target-child\').forEach(e=>e.classList.remove(\'drag-target-left\',\'drag-target-right\',\'drag-target-child\'))"';
            html += ' ondragover="event.preventDefault(); if(_orgDragId==' + nid + ')return;';
            html += ' var r=this.getBoundingClientRect(), x=event.clientX-r.left, w=r.width, zone=x<w*0.3?\'left\':x>w*0.7?\'right\':\'child\';';
            html += ' this.classList.remove(\'drag-target-left\',\'drag-target-right\',\'drag-target-child\'); this.classList.add(\'drag-target-\'+zone)"';
            html += ' ondragleave="this.classList.remove(\'drag-target-left\',\'drag-target-right\',\'drag-target-child\')"';
            html += ' ondrop="event.preventDefault(); if(_orgDragId==' + nid + ')return;';
            html += ' var r=this.getBoundingClientRect(), x=event.clientX-r.left, w=r.width, zone=x<w*0.3?\'left\':x>w*0.7?\'right\':\'child\';';
            html += ' this.classList.remove(\'drag-target-left\',\'drag-target-right\',\'drag-target-child\');';
            html += ' window.dispatchEvent(new CustomEvent(\'org-drop\',{detail:{dragId:_orgDragId,targetId:' + nid + ',zone:zone}}))"';
            html += '>';

            html += '<div class="org-node-color" style="background:' + node.color + ';"></div>';

            // Menu
            html += '<div class="org-node-menu"><button class="org-menu-btn" onclick="event.stopPropagation(); var dd=this.nextElementSibling; document.querySelectorAll(\'.org-dropdown.open\').forEach(d=>d!==dd&&d.classList.remove(\'open\')); dd.classList.toggle(\'open\')"><i class="fas fa-ellipsis-vertical"></i></button>';
            html += '<div class="org-dropdown">';
            html += '<button onclick="this.closest(\'.org-dropdown\').classList.remove(\'open\'); window.dispatchEvent(new CustomEvent(\'org-edit\',{detail:{id:' + nid + '}}))"><i class="fas fa-pen text-gray-400"></i> Bearbeiten</button>';
            html += '<button class="del" onclick="this.closest(\'.org-dropdown\').classList.remove(\'open\'); window.dispatchEvent(new CustomEvent(\'org-delete\',{detail:{id:' + nid + '}}))"><i class="fas fa-trash"></i> Löschen</button>';
            html += '</div></div>';

            html += '<div class="org-node-name">' + esc(node.name) + '</div>';
            if (node.code) html += '<div class="org-node-code">' + esc(node.code) + '</div>';
            if (node.description) html += '<div class="org-node-desc">' + esc(node.description) + '</div>';

            // Add sibling button (absolute, right of box)
            html += '<button class="org-add-btn beside" onclick="event.stopPropagation(); window.dispatchEvent(new CustomEvent(\'org-add-sibling\',{detail:{parentId:' + pid + ',afterId:' + nid + '}}))" title="Knoten daneben"><i class="fas fa-plus"></i></button>';

            html += '</div>'; // end org-node

            // Add child button (below, centered under box)
            html += '<button class="org-add-btn below" onclick="window.dispatchEvent(new CustomEvent(\'org-add-child\',{detail:{parentId:' + nid + '}}))" title="Unterknoten"><i class="fas fa-plus"></i></button>';

            // Children (inside box-wrap so lines align to box center)
            if (children.length) {
                html += '<div class="org-children">';
                children.forEach(child => {
                    html += '<div class="org-child-wrap"><div class="org-child-inner"><div class="org-child-connector">';
                    if (child.relation_label) {
                        html += '<span class="org-relation-label">' + esc(child.relation_label) + '</span>';
                    }
                    html += '</div>' + this.renderNode(child) + '</div></div>';
                });
                html += '</div>';
            }

            html += '</div>'; // end org-node-box-wrap

            html += '</div>'; // end org-node-wrap
            return html;
        }
    };
}
</script>
@endpush
