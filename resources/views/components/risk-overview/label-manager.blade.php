{{--
    Dialog "Labels verwalten" (Travel Alert): Labels anlegen, umbenennen, umfärben und
    löschen. Zeigt je Label, wie viele Reisen und Ereignisse es tragen.
--}}
<div x-show="showLabelManager" x-cloak x-transition.opacity class="fixed inset-0 z-[200001] overflow-y-auto"
    role="dialog" aria-modal="true" aria-labelledby="label-manager-title">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeLabelManager()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div @click.stop class="relative flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4">
                <div>
                    <h3 id="label-manager-title" class="text-base font-semibold text-gray-900">Labels verwalten</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Labels gelten für Reisen und Ereignisse und sind nur für dein Konto sichtbar.</p>
                </div>
                <button type="button" @click="closeLabelManager()" class="text-gray-400 hover:text-gray-600" aria-label="Schließen">
                    <i class="fa-regular fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                {{-- Neues Label --}}
                <form @submit.prevent="createManagedLabel()" class="mb-5 rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700" for="new-label-name">Neues Label</label>
                    <div class="flex items-center gap-2">
                        <input id="new-label-name" type="text" x-model="newLabel.name" maxlength="50" placeholder="z. B. Kunde informiert"
                            class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        <button type="submit" :disabled="!newLabel.name.trim() || labelManagerBusy"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-40">
                            <i class="fa-regular fa-plus"></i> Anlegen
                        </button>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <template x-for="color in labelPalette" :key="'new-' + color">
                            <button type="button" @click="newLabel.color = color" class="h-5 w-5 rounded-full ring-offset-1 transition"
                                :class="newLabel.color === color ? 'ring-2 ring-gray-900' : 'hover:scale-110'"
                                :style="'background-color: ' + color" :aria-label="'Farbe ' + color"></button>
                        </template>
                    </div>
                </form>

                <p x-show="labelManagerError" x-text="labelManagerError" class="mb-3 rounded-md bg-red-50 px-3 py-2 text-xs text-red-700"></p>

                <template x-if="labelManagerLoading">
                    <p class="py-6 text-center text-sm text-gray-500">Labels werden geladen …</p>
                </template>

                <template x-if="!labelManagerLoading && managedLabels.length === 0">
                    <p class="py-6 text-center text-sm text-gray-500">Noch keine Labels angelegt.</p>
                </template>

                <ul class="flex flex-col divide-y divide-gray-100" x-show="!labelManagerLoading && managedLabels.length > 0">
                    <template x-for="label in managedLabels" :key="'managed-' + label.id">
                        <li class="py-3">
                            <div class="flex items-center gap-3">
                                <span class="h-3 w-3 shrink-0 rounded-full" :style="'background-color: ' + label.color"></span>
                                <template x-if="editingLabelId !== label.id">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-900" x-text="label.name"></p>
                                        <p class="text-[11px] text-gray-500"
                                            x-text="label.trips_count + (label.trips_count === 1 ? ' Reise' : ' Reisen') + ' · ' + label.events_count + (label.events_count === 1 ? ' Ereignis' : ' Ereignisse')"></p>
                                    </div>
                                </template>
                                <template x-if="editingLabelId === label.id">
                                    <input type="text" x-model="editLabel.name" maxlength="50" @keydown.enter.prevent="saveManagedLabel(label)"
                                        @keydown.escape.stop="cancelEditLabel()"
                                        class="min-w-0 flex-1 rounded-lg border border-gray-300 px-2.5 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                                </template>

                                <div class="flex shrink-0 items-center gap-1" x-show="editingLabelId !== label.id">
                                    <button type="button" @click="filterByManagedLabel(label)" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="Danach filtern">
                                        <i class="fa-regular fa-filter text-xs"></i>
                                    </button>
                                    <button type="button" @click="startEditLabel(label)" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="Bearbeiten">
                                        <i class="fa-regular fa-pen text-xs"></i>
                                    </button>
                                    <button type="button" @click="deleteManagedLabel(label)" class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600" title="Löschen">
                                        <i class="fa-regular fa-trash text-xs"></i>
                                    </button>
                                </div>
                                <div class="flex shrink-0 items-center gap-1" x-show="editingLabelId === label.id">
                                    <button type="button" @click="saveManagedLabel(label)" :disabled="labelManagerBusy"
                                        class="rounded-lg bg-gray-900 px-2.5 py-1 text-xs font-medium text-white hover:bg-gray-800 disabled:opacity-40">Speichern</button>
                                    <button type="button" @click="cancelEditLabel()" class="rounded-lg px-2 py-1 text-xs text-gray-500 hover:text-gray-800">Abbrechen</button>
                                </div>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-1.5 ps-6" x-show="editingLabelId === label.id">
                                <template x-for="color in labelPalette" :key="'edit-' + label.id + '-' + color">
                                    <button type="button" @click="editLabel.color = color" class="h-5 w-5 rounded-full ring-offset-1 transition"
                                        :class="editLabel.color === color ? 'ring-2 ring-gray-900' : 'hover:scale-110'"
                                        :style="'background-color: ' + color" :aria-label="'Farbe ' + color"></button>
                                </template>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-3">
                <button type="button" @click="closeLabelManager()"
                    class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Fertig</button>
            </div>
        </div>
    </div>
</div>
