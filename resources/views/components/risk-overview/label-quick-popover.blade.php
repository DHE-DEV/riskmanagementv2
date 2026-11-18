{{--
    Popover an einer Ereignis- oder Reisekarte (Travel Alert): Labels per Klick setzen oder entfernen,
    nach Namen suchen und ein neues Label anlegen – ohne das Ereignis zu öffnen.
--}}
<div x-show="quickLabel.item" x-cloak id="quick-label-popover"
    @click.outside="closeQuickLabel()"
    class="fixed z-[200002] w-[260px] overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl"
    :style="'top: ' + quickLabel.top + 'px; left: ' + quickLabel.left + 'px'"
    role="dialog" aria-label="Labels vergeben">
    <div class="border-b border-gray-100 px-3 py-2">
        <p class="truncate text-[11px] font-medium text-gray-500" x-text="quickLabelTitle"></p>
        <input id="quick-label-input" type="text" x-model="quickLabel.query" maxlength="50"
            @keydown.enter.prevent="quickLabelOptions.length === 1 && !quickLabelCanCreate ? toggleQuickLabel(quickLabelOptions[0]) : createQuickLabel()"
            placeholder="Label suchen oder neu anlegen …"
            class="mt-1.5 w-full rounded-md border border-gray-300 px-2.5 py-1.5 text-xs outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
    </div>

    <div class="max-h-60 overflow-y-auto py-1">
        <template x-for="label in quickLabelOptions" :key="'quick-' + label.id">
            <button type="button" @click="toggleQuickLabel(label)" :disabled="quickLabel.busy"
                class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs hover:bg-gray-50 disabled:opacity-60">
                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border"
                    :class="itemHasLabel(quickLabel.item, label.id) ? 'border-transparent' : 'border-gray-300'"
                    :style="itemHasLabel(quickLabel.item, label.id) ? 'background-color: ' + label.color : ''">
                    <i class="fa-solid fa-check text-[9px]" x-show="itemHasLabel(quickLabel.item, label.id)"
                        :style="'color: ' + labelTextColor(label.color)"></i>
                </span>
                <span class="h-2 w-2 shrink-0 rounded-full" :style="'background-color: ' + label.color"></span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-gray-800" x-text="label.name"></span>
                    <span class="block truncate text-[10px] text-gray-400" x-show="label.description" x-text="label.description"></span>
                </span>
            </button>
        </template>

        <template x-if="quickLabelOptions.length === 0 && !quickLabelCanCreate">
            <p class="px-3 py-2 text-xs text-gray-500">Noch keine Labels – Namen eintippen, um eines anzulegen.</p>
        </template>

        <template x-if="quickLabelCanCreate">
            <button type="button" @click="createQuickLabel()" :disabled="quickLabel.busy"
                class="flex w-full items-center gap-2 border-t border-gray-100 px-3 py-2 text-left text-xs text-blue-600 hover:bg-gray-50 disabled:opacity-60">
                <i class="fa-regular fa-plus text-[10px]"></i>
                <span>„<span x-text="quickLabel.query.trim()"></span>“ anlegen und vergeben</span>
            </button>
        </template>
    </div>

    <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50 px-3 py-1.5">
        <button type="button" @click="closeQuickLabel(); openLabelManager()" class="text-[11px] text-gray-500 hover:text-gray-800">Labels verwalten</button>
        <button type="button" @click="closeQuickLabel()" class="text-[11px] font-medium text-gray-700 hover:text-gray-900">Fertig</button>
    </div>
</div>
