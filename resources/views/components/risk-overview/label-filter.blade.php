{{--
    Label-Filter (Travel Alert): Mehrfachauswahl als Chips. Gezeigt werden Reisen und
    Ereignisse mit mindestens einem der gewählten Labels; dazu der Weg zur Verwaltung.
--}}
<div class="mb-4">
    <div class="mb-2 flex items-center justify-between gap-2">
        <span class="text-xs font-medium text-gray-700">Labels</span>
        <button type="button" @click="openLabelManager()" class="inline-flex items-center gap-1 text-[11px] text-blue-600 hover:underline">
            <i class="fa-regular fa-gear"></i> Labels verwalten
        </button>
    </div>

    <template x-if="availableLabels.length === 0">
        <p class="mb-2 text-[11px] leading-relaxed text-gray-500">
            Noch keine Labels. Vergib sie an einer Ereigniskarte, in den Reisedetails – oder lege sie unter „Labels verwalten“ an.
        </p>
    </template>

    <div class="flex flex-wrap gap-1.5">
        <template x-for="label in availableLabels" :key="'label-filter-' + label.id">
            <button type="button" @click="toggleLabelFilter(label.id)" :title="label.description || ''"
                class="inline-flex max-w-full items-center gap-1.5 rounded-full border px-2 py-1 text-[11px] font-medium transition"
                :class="isLabelFiltered(label.id) ? 'border-transparent shadow-sm' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'"
                :style="isLabelFiltered(label.id) ? 'background-color: ' + label.color + '; color: ' + labelTextColor(label.color) : ''"
                :aria-pressed="isLabelFiltered(label.id).toString()">
                <span class="h-2 w-2 shrink-0 rounded-full"
                    :style="'background-color: ' + (isLabelFiltered(label.id) ? labelTextColor(label.color) : label.color)"></span>
                <span class="truncate" x-text="label.name"></span>
                <span class="tabular-nums opacity-70" x-show="sidebarTab === 'reisen'" x-text="labelFilterCounts[label.id] || 0"></span>
            </button>
        </template>

        {{-- Noch nicht bearbeitet: Reisen bzw. Ereignisse ganz ohne Label --}}
        <template x-for="option in [{ id: NO_TRIP_LABEL, name: 'Reise ohne Label', hint: 'Reisen, die selbst kein Label tragen' }, { id: NO_EVENT_LABEL, name: 'Ereignis ohne Label', hint: 'Reisen mit mindestens einem Ereignis ohne Label – im Detail nur diese Ereignisse' }]" :key="option.id">
            <button type="button" @click="toggleLabelFilter(option.id)" :title="option.hint"
                class="inline-flex items-center gap-1.5 rounded-full border border-dashed px-2 py-1 text-[11px] font-medium transition"
                :class="isLabelFiltered(option.id) ? 'border-gray-800 bg-gray-800 text-white' : 'border-gray-300 bg-white text-gray-600 hover:border-gray-400'"
                :aria-pressed="isLabelFiltered(option.id).toString()">
                <i class="fa-regular fa-tag text-[9px]"></i>
                <span x-text="option.name"></span>
                <span class="tabular-nums opacity-70" x-show="sidebarTab === 'reisen'" x-text="labelFilterCounts[option.id] || 0"></span>
            </button>
        </template>
    </div>

    <div class="mt-2 flex items-start justify-between gap-2" x-show="filters.labels.length > 0" x-cloak>
        <span class="text-[10px] leading-snug text-gray-500">Reisen und Ereignisse, auf die mindestens eine der gewählten Optionen zutrifft. Die Zahlen nennen die Reisen im gewählten Zeitraum.</span>
        <button type="button" @click="clearLabelFilters()" class="shrink-0 text-[11px] text-gray-500 hover:text-gray-800">Zurücksetzen</button>
    </div>
</div>
