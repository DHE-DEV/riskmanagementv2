@if(isset($isDebugUser) && $isDebugUser)
{{-- Floating Debug Panel --}}
<div x-data="{
    open: false,
    entries: [],
    log(endpoint, params, result, durationMs, serverDurationMs) {
        this.entries.unshift({
            id: Date.now() + Math.random(),
            timestamp: new Date().toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit', second: '2-digit', fractionalSecondDigits: 3 }),
            endpoint: endpoint,
            params: params,
            response: result,
            duration_ms: Math.round(durationMs),
            server_duration_ms: serverDurationMs || null,
            expanded: false,
        });
    },
    clear() { this.entries = []; },
    durationColor(ms) {
        if (ms < 300) return 'text-green-400';
        if (ms < 1000) return 'text-yellow-400';
        return 'text-red-400';
    }
}" x-init="window.debugPanel = { log: (e, p, r, d, s) => $data.log(e, p, r, d, s) }"
   style="position: fixed; bottom: 16px; right: 16px; z-index: 99999;"
>
    {{-- Toggle Button --}}
    <button @click="open = !open"
            class="flex items-center justify-center w-12 h-12 rounded-full shadow-lg transition-all"
            :class="open ? 'bg-orange-600 hover:bg-orange-700' : 'bg-gray-900 hover:bg-gray-800'"
            title="Debug Panel">
        <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
        </svg>
        <span x-show="entries.length > 0 && !open"
              class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-orange-500 rounded-full"
              x-text="entries.length"></span>
    </button>

    {{-- Panel --}}
    <div x-show="open" x-cloak x-transition
         class="absolute bottom-16 right-0 w-[520px] max-h-[70vh] bg-gray-950 border border-gray-700 rounded-lg shadow-2xl flex flex-col overflow-hidden"
         @click.outside="open = false">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-700 bg-gray-900 flex-shrink-0">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <span class="text-sm font-semibold text-gray-200">API Debug</span>
                <span class="text-xs text-gray-500" x-text="entries.length + ' Eintr&auml;ge'"></span>
            </div>
            <button @click="clear()" x-show="entries.length > 0"
                    class="text-xs text-gray-400 hover:text-orange-400 transition-colors">
                Leeren
            </button>
        </div>

        {{-- Entries --}}
        <div class="flex-1 overflow-y-auto p-2 space-y-1.5" style="scrollbar-width: thin; scrollbar-color: #4b5563 #030712;">
            <template x-if="entries.length === 0">
                <div class="text-center py-8 text-gray-500 text-sm">
                    Noch keine API-Aufrufe aufgezeichnet.
                </div>
            </template>

            <template x-for="entry in entries" :key="entry.id">
                <div class="bg-gray-900 border border-gray-800 rounded-md text-xs">
                    {{-- Summary row --}}
                    <button @click="entry.expanded = !entry.expanded"
                            class="w-full flex items-center gap-2 px-3 py-2 text-left hover:bg-gray-800 transition-colors rounded-md">
                        <span class="text-gray-500 font-mono" x-text="entry.timestamp"></span>
                        <span class="text-orange-300 font-medium truncate flex-1" x-text="entry.endpoint"></span>
                        <span class="font-mono" :class="durationColor(entry.duration_ms)" x-text="entry.duration_ms + 'ms'"></span>
                        <template x-if="entry.server_duration_ms !== null">
                            <span class="text-gray-500 font-mono" x-text="'(' + entry.server_duration_ms + 'ms srv)'"></span>
                        </template>
                        <svg class="w-3 h-3 text-gray-500 transition-transform" :class="entry.expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Expanded details --}}
                    <div x-show="entry.expanded" x-cloak class="border-t border-gray-800 px-3 py-2 space-y-2">
                        <div>
                            <div class="text-gray-400 font-semibold mb-1">Request</div>
                            <pre class="bg-gray-950 border border-gray-800 rounded p-2 overflow-x-auto text-green-300 max-h-40 overflow-y-auto" x-text="JSON.stringify(entry.params, null, 2)"></pre>
                        </div>
                        <div>
                            <div class="text-gray-400 font-semibold mb-1">Response</div>
                            <pre class="bg-gray-950 border border-gray-800 rounded p-2 overflow-x-auto text-blue-300 max-h-60 overflow-y-auto" x-text="JSON.stringify(entry.response, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endif
