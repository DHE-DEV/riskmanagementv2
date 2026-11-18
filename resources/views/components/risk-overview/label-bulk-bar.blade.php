{{--
    Leiste der Mehrfachauswahl (Travel Alert): ein Label an allen gewählten Ereignissen
    bzw. Reisen auf einmal vergeben oder entfernen – auch als neues Label.
--}}
<div x-show="selection.mode" x-cloak x-transition
    class="fixed bottom-4 left-1/2 z-[200003] w-[min(56rem,calc(100vw-2rem))] -translate-x-1/2 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-2xl"
    role="region" aria-label="Mehrfachauswahl">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
        <div class="flex items-center gap-2 text-sm">
            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-blue-600 px-2 text-xs font-semibold text-white"
                x-text="selection.ids.length"></span>
            <span class="text-gray-700"
                x-text="selection.mode === 'trips' ? (selection.ids.length === 1 ? 'Reise ausgewählt' : 'Reisen ausgewählt') : (selection.ids.length === 1 ? 'Ereignis ausgewählt' : 'Ereignisse ausgewählt')"></span>
            <button type="button" @click="toggleSelectAll()" class="text-xs text-blue-600 hover:underline"
                x-text="allCandidatesSelected ? 'Keine' : 'Alle sichtbaren (' + selectionCandidates.length + ')'"></button>
        </div>

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
            <select x-model="bulk.labelId" aria-label="Label"
                class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500 sm:max-w-56">
                <option value="">Label wählen …</option>
                <template x-for="label in availableLabels" :key="'bulk-' + label.id">
                    <option :value="label.id" x-text="label.name"></option>
                </template>
                <option value="__new">＋ Neues Label …</option>
            </select>
            <input x-show="bulk.labelId === '__new'" x-model="bulk.newName" maxlength="50" placeholder="Name des neuen Labels"
                @keydown.enter.prevent="applyBulkLabel(false)"
                class="min-w-0 flex-1 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500 sm:max-w-48">
            <button type="button" @click="applyBulkLabel(false)" :disabled="!selection.ids.length || bulk.busy"
                class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800 disabled:opacity-40">
                <i class="fa-regular fa-tag"></i> Vergeben
            </button>
            <button type="button" @click="applyBulkLabel(true)" :disabled="!selection.ids.length || bulk.busy || bulk.labelId === '__new'"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40">
                <i class="fa-regular fa-tag"></i> Entfernen
            </button>
        </div>

        <button type="button" @click="endSelection()" class="text-xs font-medium text-gray-500 hover:text-gray-900">Fertig</button>
    </div>
    <p x-show="bulk.message" x-text="bulk.message" class="mt-2 text-xs text-gray-600"></p>
</div>
