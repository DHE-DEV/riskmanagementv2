<div class="space-y-6">
    {{-- Destination country header --}}
    @if(!empty($countryName))
        <div class="flex items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
            <div style="width: 48px; height: 48px;" class="flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30">
                <svg style="width: 24px; height: 24px;" class="text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $countryName }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Reiseziel ({{ $country }})</p>
            </div>
        </div>
    @endif

    @if($content['error'] ?? false)
        <div class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg style="width: 20px; height: 20px;" class="text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                        {{ $content['message'] ?? 'Ein Fehler ist aufgetreten' }}
                    </p>
                </div>
            </div>
        </div>
    @else
        @forelse($content['results'] ?? [] as $result)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Header with nationality info --}}
                <div class="bg-gradient-to-r from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div style="width: 40px; height: 40px;" class="flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30">
                                <svg style="width: 20px; height: 20px;" class="text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ $result['nationalityName'] }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Staatsangehörigkeit: {{ $result['nationality'] }}
                                </p>
                            </div>
                        </div>
                        @if($result['success'])
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <svg style="width: 12px; height: 12px; margin-right: 4px;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Daten geladen
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                <svg style="width: 12px; height: 12px; margin-right: 4px;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                Fehler
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Travellers with this nationality --}}
                @if(!empty($result['travellerNames']))
                    <div class="bg-blue-50 dark:bg-blue-900/20 px-4 py-2 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center gap-2 text-sm">
                            <svg style="width: 16px; height: 16px;" class="text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-blue-700 dark:text-blue-300 font-medium">Reisende:</span>
                            <span class="text-blue-600 dark:text-blue-400">{{ $result['travellerNames'] }}</span>
                        </div>
                    </div>
                @endif

                {{-- Content from PDS API --}}
                <div class="p-4 bg-white dark:bg-gray-900">
                    <div class="prose prose-sm max-w-none dark:prose-invert
                        prose-headings:text-gray-900 dark:prose-headings:text-white
                        prose-p:text-gray-700 dark:prose-p:text-gray-300
                        prose-strong:text-gray-900 dark:prose-strong:text-white
                        prose-ul:text-gray-700 dark:prose-ul:text-gray-300
                        prose-li:text-gray-700 dark:prose-li:text-gray-300
                        prose-a:text-primary-600 dark:prose-a:text-primary-400">
                        {!! $result['content'] !!}
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8">
                <svg style="width: 48px; height: 48px; margin: 0 auto;" class="text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Keine Daten</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Es konnten keine Einreisebestimmungen abgerufen werden.
                </p>
            </div>
        @endforelse
    @endif

    {{-- Info footer --}}
    <div class="mt-4 rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-xs text-gray-500 dark:text-gray-400">
        <div class="flex items-center gap-2">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Datenquelle: Passolution PDS API</span>
        </div>
    </div>
</div>
