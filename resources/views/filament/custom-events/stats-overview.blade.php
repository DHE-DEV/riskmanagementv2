@if(!$record)
    <div class="text-gray-500 text-sm">
        Statistiken werden nach dem Erstellen verfügbar
    </div>
@else
    <div class="fi-wi-stats-overview-container">
        {{-- Main Statistics --}}
        <div class="fi-wi-stats-overview grid gap-6 md:grid-cols-4">
            {{-- Total Clicks --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-start justify-between">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Gesamt-Klicks
                        </div>
                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-gray bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2z"></path>
                            </svg>
                        </span>
                    </div>

                    <div class="flex items-end gap-x-2">
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stats['total'] ?? 0) }}
                        </span>
                        @if($weeklyTrend !== 0)
                            <span class="flex items-center gap-x-1 text-sm font-medium {{ $weeklyTrend > 0 ? 'text-success-600' : 'text-danger-600' }}">
                                @if($weeklyTrend > 0)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                    </svg>
                                @endif
                                {{ abs($weeklyTrend) }}%
                            </span>
                        @endif
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Alle Interaktionen
                    </div>
                </div>
            </div>

            {{-- List Clicks --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-start justify-between">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Event-Liste
                        </div>
                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-info bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/20" style="--c-50:var(--info-50);--c-400:var(--info-400);--c-600:var(--info-600);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </span>
                    </div>

                    <div class="flex items-end gap-x-2">
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stats['list'] ?? 0) }}
                        </span>
                        @if($stats['total'] > 0)
                            <span class="text-sm font-medium text-gray-500">
                                {{ round(($stats['list'] ?? 0) / $stats['total'] * 100, 0) }}%
                            </span>
                        @endif
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Klicks aus der Liste
                    </div>
                </div>
            </div>

            {{-- Map Clicks --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-start justify-between">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Karten-Symbol
                        </div>
                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </span>
                    </div>

                    <div class="flex items-end gap-x-2">
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stats['map_marker'] ?? 0) }}
                        </span>
                        @if($stats['total'] > 0)
                            <span class="text-sm font-medium text-gray-500">
                                {{ round(($stats['map_marker'] ?? 0) / $stats['total'] * 100, 0) }}%
                            </span>
                        @endif
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Klicks auf der Karte
                    </div>
                </div>
            </div>

            {{-- Details Clicks --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-start justify-between">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Details-Button
                        </div>
                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-warning bg-warning-50 text-warning-600 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </span>
                    </div>

                    <div class="flex items-end gap-x-2">
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stats['details_button'] ?? 0) }}
                        </span>
                        @if($stats['total'] > 0)
                            <span class="text-sm font-medium text-gray-500">
                                {{ round(($stats['details_button'] ?? 0) / $stats['total'] * 100, 0) }}%
                            </span>
                        @endif
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Details angezeigt
                    </div>
                </div>
            </div>
        </div>

        {{-- Time-based Statistics --}}
        <div class="fi-wi-stats-overview grid gap-6 md:grid-cols-3 mt-6">
            {{-- Today --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-start justify-between">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Heute
                        </div>
                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-primary bg-primary-50 text-primary-600 ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </span>
                    </div>

                    <div class="flex items-end gap-x-2">
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stats['today'] ?? 0) }}
                        </span>
                        @if($dailyTrend !== 0)
                            <span class="flex items-center gap-x-1 text-sm font-medium {{ $dailyTrend > 0 ? 'text-success-600' : 'text-danger-600' }}">
                                @if($dailyTrend > 0)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                    </svg>
                                @endif
                                {{ abs($dailyTrend) }}%
                            </span>
                        @endif
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Gestern: {{ $yesterday }}
                    </div>
                </div>
            </div>

            {{-- This Week --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-start justify-between">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Diese Woche
                        </div>
                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-primary bg-primary-50 text-primary-600 ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </span>
                    </div>

                    <div class="flex items-end gap-x-2">
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stats['this_week'] ?? 0) }}
                        </span>
                        @if(($stats['this_week'] ?? 0) > 0)
                            <span class="text-sm font-medium text-gray-500">
                                Ø {{ round(($stats['this_week'] ?? 0) / 7, 1) }}/Tag
                            </span>
                        @endif
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Letzte Woche: {{ $lastWeekTotal }}
                    </div>
                </div>
            </div>

            {{-- This Month --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-start justify-between">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Diesen Monat
                        </div>
                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-primary bg-primary-50 text-primary-600 ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </span>
                    </div>

                    <div class="flex items-end gap-x-2">
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stats['this_month'] ?? 0) }}
                        </span>
                        @if(($stats['this_month'] ?? 0) > 0)
                            <span class="text-sm font-medium text-gray-500">
                                Ø {{ round(($stats['this_month'] ?? 0) / 30, 1) }}/Tag
                            </span>
                        @endif
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ now()->format('F Y') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        @if($recentClicks && count($recentClicks) > 0)
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Letzte Aktivitäten</h4>
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full divide-y divide-gray-200 dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-start text-sm font-medium text-gray-600 dark:text-gray-400">Typ</th>
                                <th class="px-4 py-3 text-start text-sm font-medium text-gray-600 dark:text-gray-400">Benutzer</th>
                                <th class="px-4 py-3 text-start text-sm font-medium text-gray-600 dark:text-gray-400">Zeitpunkt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach($recentClicks as $click)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3">
                                        @switch($click->click_type)
                                            @case('list')
                                                <span class="fi-badge flex w-fit items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-info bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/20" style="--c-50:var(--info-50);--c-400:var(--info-400);--c-600:var(--info-600);">
                                                    Event-Liste
                                                </span>
                                                @break
                                            @case('map_marker')
                                                <span class="fi-badge flex w-fit items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20">
                                                    Karten-Symbol
                                                </span>
                                                @break
                                            @case('details_button')
                                                <span class="fi-badge flex w-fit items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-warning bg-warning-50 text-warning-600 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20">
                                                    Details-Button
                                                </span>
                                                @break
                                            @default
                                                {{ $click->click_type }}
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        {{ $click->user?->name ?? 'Anonym' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $click->clicked_at->format('d.m.Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="mt-6 rounded-xl bg-gray-50 p-6 text-center dark:bg-white/5">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Noch keine Interaktionen</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sobald Benutzer mit diesem Event interagieren, werden die Statistiken hier angezeigt.</p>
            </div>
        @endif
    </div>
@endif