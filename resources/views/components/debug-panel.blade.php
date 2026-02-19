@if(isset($isDebugUser) && $isDebugUser)
{{-- Floating Debug Panel --}}
<div x-data="{
    open: false,
    entries: [],
    log(endpoint, params, result, durationMs, serverDurationMs, pdsApiCalls) {
        this.entries.unshift({
            id: Date.now() + Math.random(),
            timestamp: new Date().toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit', second: '2-digit', fractionalSecondDigits: 3 }),
            endpoint: endpoint,
            params: params,
            response: result,
            duration_ms: Math.round(durationMs),
            server_duration_ms: serverDurationMs || null,
            pds_api_calls: pdsApiCalls || [],
            expanded: false,
        });
    },
    clear() { this.entries = []; },
    durationColor(ms) {
        if (ms < 300) return 'text-green-400';
        if (ms < 1000) return 'text-yellow-400';
        return 'text-red-400';
    },
    copyText(el, text) {
        navigator.clipboard.writeText(text);
        const span = el.querySelector('span');
        span.textContent = 'Kopiert!';
        setTimeout(() => span.textContent = 'Kopieren', 1500);
    }
}" x-init="window.debugPanel = { log: (e, p, r, d, s, pds) => $data.log(e, p, r, d, s, pds) }"
   style="position: fixed; bottom: 48px; right: 16px; z-index: 200000;"
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
         class="absolute bottom-16 right-0 w-[580px] max-h-[70vh] bg-gray-950 border border-gray-700 rounded-lg shadow-2xl flex flex-col overflow-hidden"
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
                        <template x-if="entry.pds_api_calls.length > 0">
                            <span class="px-1.5 py-0.5 rounded bg-purple-900/60 text-purple-300 font-medium" x-text="'PDS:' + entry.pds_api_calls.length"></span>
                        </template>
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
                            <div class="flex items-center justify-between mb-1">
                                <div class="text-gray-400 font-semibold">Request</div>
                                <button @click.stop="copyText($el, JSON.stringify(entry.params, null, 2))"
                                        class="flex items-center gap-1 text-gray-500 hover:text-green-400 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span>Kopieren</span>
                                </button>
                            </div>
                            <template x-if="entry.params?.url">
                                <div class="bg-gray-950 border border-gray-800 rounded p-2 mb-1 overflow-x-auto text-yellow-300 font-mono break-all" x-text="entry.params.url"></div>
                            </template>
                            <pre class="bg-gray-950 border border-gray-800 rounded p-2 overflow-x-auto text-green-300 max-h-40 overflow-y-auto" x-text="JSON.stringify(entry.params?.params || entry.params, null, 2)"></pre>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div class="text-gray-400 font-semibold">Response</div>
                                <button @click.stop="copyText($el, JSON.stringify(entry.response, null, 2))"
                                        class="flex items-center gap-1 text-gray-500 hover:text-blue-400 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span>Kopieren</span>
                                </button>
                            </div>
                            <pre class="bg-gray-950 border border-gray-800 rounded p-2 overflow-x-auto text-blue-300 max-h-60 overflow-y-auto" x-text="JSON.stringify(entry.response, null, 2)"></pre>
                        </div>
                        {{-- PDS API Calls --}}
                        <template x-if="entry.pds_api_calls && entry.pds_api_calls.length > 0">
                            <div class="border-t border-gray-800 pt-2">
                                <div class="text-purple-400 font-semibold mb-1.5">
                                    PDS API <span class="text-gray-500 font-normal" x-text="'(' + entry.pds_api_calls.length + ' Calls)'"></span>
                                </div>
                                <template x-for="(pds, pdsIdx) in entry.pds_api_calls" :key="pdsIdx">
                                    <div class="mb-2 bg-gray-950 border border-purple-900/40 rounded p-2">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-1 py-0.5 font-bold rounded bg-purple-900/60 text-purple-300" x-text="pds.method"></span>
                                                <span class="font-mono text-purple-200 break-all" x-text="pds.url"></span>
                                            </div>
                                            <div class="flex items-center gap-1.5 flex-shrink-0 ml-1">
                                                <span class="font-mono" :class="pds.status === 200 ? 'text-green-400' : 'text-red-400'" x-text="pds.status || 'ERR'"></span>
                                                <span class="font-mono text-gray-500" x-text="pds.duration_ms + 'ms'"></span>
                                            </div>
                                        </div>
                                        <div class="mb-1.5">
                                            <div class="flex items-center justify-between mb-0.5">
                                                <span class="text-gray-500">Request</span>
                                                <button @click.stop="copyText($el, JSON.stringify(pds.request_body, null, 2))"
                                                        class="flex items-center gap-1 text-gray-600 hover:text-purple-400 transition-colors">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    <span>Kopieren</span>
                                                </button>
                                            </div>
                                            <pre class="bg-gray-900 border border-gray-800 rounded p-1.5 overflow-x-auto text-yellow-300 max-h-32 overflow-y-auto" x-text="JSON.stringify(pds.request_body, null, 2)"></pre>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between mb-0.5">
                                                <span class="text-gray-500">Response</span>
                                                <button @click.stop="copyText($el, JSON.stringify(pds.response_body, null, 2))"
                                                        class="flex items-center gap-1 text-gray-600 hover:text-purple-400 transition-colors">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    <span>Kopieren</span>
                                                </button>
                                            </div>
                                            <pre class="bg-gray-900 border border-gray-800 rounded p-1.5 overflow-x-auto text-blue-300 max-h-48 overflow-y-auto" x-text="JSON.stringify(pds.response_body, null, 2)"></pre>
                                        </div>
                                        <template x-if="pds.error">
                                            <div class="mt-1 text-red-400 font-mono" x-text="'Error: ' + pds.error"></div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endif
