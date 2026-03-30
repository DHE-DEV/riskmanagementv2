<x-filament-panels::page>
    {{-- Stats Cards --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
        {{-- GTM Card --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.25rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 10px; height: 10px; border-radius: 50; background: {{ $stats['gtm']['last_run'] && $stats['gtm']['last_run']->status === 'running' ? '#22c55e' : '#6b7280' }};"></div>
                    <span style="font-weight: 600; font-size: 0.875rem; color: #111827;">GTM Notifications</span>
                </div>
                <span style="font-size: 0.75rem; color: #6b7280;">alle {{ $stats['gtm']['interval'] }} Min.</span>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;">
                <div style="text-align: center; padding: 0.5rem; background: #f0f9ff; border-radius: 8px;">
                    <div style="font-size: 1.25rem; font-weight: 700; color: #1e40af;">{{ $stats['gtm']['today_runs'] }}</div>
                    <div style="font-size: 0.625rem; color: #6b7280;">Durchläufe heute</div>
                </div>
                <div style="text-align: center; padding: 0.5rem; background: #f0fdf4; border-radius: 8px;">
                    <div style="font-size: 1.25rem; font-weight: 700; color: #166534;">{{ $stats['gtm']['today_sent'] }}</div>
                    <div style="font-size: 0.625rem; color: #6b7280;">Gesendet heute</div>
                </div>
                <div style="text-align: center; padding: 0.5rem; background: {{ $stats['gtm']['today_errors'] > 0 ? '#fef2f2' : '#f9fafb' }}; border-radius: 8px;">
                    <div style="font-size: 1.25rem; font-weight: 700; color: {{ $stats['gtm']['today_errors'] > 0 ? '#dc2626' : '#6b7280' }};">{{ $stats['gtm']['today_errors'] }}</div>
                    <div style="font-size: 0.625rem; color: #6b7280;">Fehler heute</div>
                </div>
            </div>
            @if($stats['gtm']['last_run'])
                <div style="margin-top: 0.75rem; font-size: 0.75rem; color: #6b7280;">
                    Letzter Lauf: {{ $stats['gtm']['last_run']->started_at->format('d.m.Y H:i:s') }}
                    @if($stats['gtm']['last_run']->duration)
                        ({{ $stats['gtm']['last_run']->duration }})
                    @endif
                    <span style="display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; padding: 2px 8px; border-radius: 9999px; font-size: 0.625rem; font-weight: 600;
                        {{ $stats['gtm']['last_run']->status === 'completed' ? 'background: #dcfce7; color: #166534;' : ($stats['gtm']['last_run']->status === 'failed' ? 'background: #fee2e2; color: #dc2626;' : 'background: #dbeafe; color: #1e40af;') }}">
                        {{ $stats['gtm']['last_run']->status }}
                    </span>
                </div>
            @else
                <div style="margin-top: 0.75rem; font-size: 0.75rem; color: #9ca3af;">Noch kein Durchlauf</div>
            @endif
        </div>

        {{-- Travel Alert Card --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.25rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 10px; height: 10px; border-radius: 50; background: {{ $stats['travel_alert']['last_run'] && $stats['travel_alert']['last_run']->status === 'running' ? '#22c55e' : '#6b7280' }};"></div>
                    <span style="font-weight: 600; font-size: 0.875rem; color: #111827;">Travel Alert Notifications</span>
                </div>
                <span style="font-size: 0.75rem; color: #6b7280;">alle {{ $stats['travel_alert']['interval'] }} Min.</span>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;">
                <div style="text-align: center; padding: 0.5rem; background: #fffbeb; border-radius: 8px;">
                    <div style="font-size: 1.25rem; font-weight: 700; color: #92400e;">{{ $stats['travel_alert']['today_runs'] }}</div>
                    <div style="font-size: 0.625rem; color: #6b7280;">Durchläufe heute</div>
                </div>
                <div style="text-align: center; padding: 0.5rem; background: #f0fdf4; border-radius: 8px;">
                    <div style="font-size: 1.25rem; font-weight: 700; color: #166534;">{{ $stats['travel_alert']['today_sent'] }}</div>
                    <div style="font-size: 0.625rem; color: #6b7280;">Gesendet heute</div>
                </div>
                <div style="text-align: center; padding: 0.5rem; background: {{ $stats['travel_alert']['today_errors'] > 0 ? '#fef2f2' : '#f9fafb' }}; border-radius: 8px;">
                    <div style="font-size: 1.25rem; font-weight: 700; color: {{ $stats['travel_alert']['today_errors'] > 0 ? '#dc2626' : '#6b7280' }};">{{ $stats['travel_alert']['today_errors'] }}</div>
                    <div style="font-size: 0.625rem; color: #6b7280;">Fehler heute</div>
                </div>
            </div>
            @if($stats['travel_alert']['last_run'])
                <div style="margin-top: 0.75rem; font-size: 0.75rem; color: #6b7280;">
                    Letzter Lauf: {{ $stats['travel_alert']['last_run']->started_at->format('d.m.Y H:i:s') }}
                    @if($stats['travel_alert']['last_run']->duration)
                        ({{ $stats['travel_alert']['last_run']->duration }})
                    @endif
                    <span style="display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; padding: 2px 8px; border-radius: 9999px; font-size: 0.625rem; font-weight: 600;
                        {{ $stats['travel_alert']['last_run']->status === 'completed' ? 'background: #dcfce7; color: #166534;' : ($stats['travel_alert']['last_run']->status === 'failed' ? 'background: #fee2e2; color: #dc2626;' : 'background: #dbeafe; color: #1e40af;') }}">
                        {{ $stats['travel_alert']['last_run']->status }}
                    </span>
                </div>
            @else
                <div style="margin-top: 0.75rem; font-size: 0.75rem; color: #9ca3af;">Noch kein Durchlauf</div>
            @endif
        </div>

        {{-- Travel Link Sync Card --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.25rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 10px; height: 10px; border-radius: 50; background: {{ $stats['travel_link_sync']['last_run'] && $stats['travel_link_sync']['last_run']->status === 'running' ? '#22c55e' : '#6b7280' }};"></div>
                    <span style="font-weight: 600; font-size: 0.875rem; color: #111827;">Travel Link Sync</span>
                </div>
                <span style="font-size: 0.75rem; color: #6b7280;">alle {{ $stats['travel_link_sync']['interval'] }} Min.</span>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;">
                <div style="text-align: center; padding: 0.5rem; background: #f0f9ff; border-radius: 8px;">
                    <div style="font-size: 1.25rem; font-weight: 700; color: #1e40af;">{{ $stats['travel_link_sync']['today_customers'] }}</div>
                    <div style="font-size: 0.625rem; color: #6b7280;">Kunden heute</div>
                </div>
                <div style="text-align: center; padding: 0.5rem; background: #f0fdf4; border-radius: 8px;">
                    <div style="font-size: 1.25rem; font-weight: 700; color: #166534;">{{ $stats['travel_link_sync']['today_synced'] }}</div>
                    <div style="font-size: 0.625rem; color: #6b7280;">Trips sync. heute</div>
                </div>
                <div style="text-align: center; padding: 0.5rem; background: {{ $stats['travel_link_sync']['today_errors'] > 0 ? '#fef2f2' : '#f9fafb' }}; border-radius: 8px;">
                    <div style="font-size: 1.25rem; font-weight: 700; color: {{ $stats['travel_link_sync']['today_errors'] > 0 ? '#dc2626' : '#6b7280' }};">{{ $stats['travel_link_sync']['today_errors'] }}</div>
                    <div style="font-size: 0.625rem; color: #6b7280;">Fehler heute</div>
                </div>
            </div>
            @if($stats['travel_link_sync']['last_run'])
                <div style="margin-top: 0.75rem; font-size: 0.75rem; color: #6b7280;">
                    Letzter Lauf: {{ $stats['travel_link_sync']['last_run']->started_at->format('d.m.Y H:i:s') }}
                    @if($stats['travel_link_sync']['last_run']->duration)
                        ({{ $stats['travel_link_sync']['last_run']->duration }})
                    @endif
                    <span style="display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; padding: 2px 8px; border-radius: 9999px; font-size: 0.625rem; font-weight: 600;
                        {{ $stats['travel_link_sync']['last_run']->status === 'completed' ? 'background: #dcfce7; color: #166534;' : ($stats['travel_link_sync']['last_run']->status === 'failed' ? 'background: #fee2e2; color: #dc2626;' : 'background: #dbeafe; color: #1e40af;') }}">
                        {{ $stats['travel_link_sync']['last_run']->status }}
                    </span>
                </div>
            @else
                <div style="margin-top: 0.75rem; font-size: 0.75rem; color: #9ca3af;">Noch kein Durchlauf</div>
            @endif
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div style="display: flex; gap: 0; border-bottom: 1px solid #e5e7eb; margin-bottom: 1rem;">
        @foreach(['all' => 'Alle', 'gtm-notifications' => 'GTM', 'travel-alert-notifications' => 'Travel Alert', 'travel-link-sync' => 'Travel Link Sync'] as $key => $label)
            <button wire:click="setActiveTab('{{ $key }}')"
                style="padding: 0.75rem 1.25rem; font-size: 0.875rem; font-weight: 500; border: none; background: none; cursor: pointer; border-bottom: 2px solid {{ $activeTab === $key ? '#2563eb' : 'transparent' }}; color: {{ $activeTab === $key ? '#2563eb' : '#6b7280' }}; transition: all 0.15s;">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Logs Table --}}
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: #374151;">Queue</th>
                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: #374151;">Gestartet</th>
                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: #374151;">Abgeschlossen</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; color: #374151;">Dauer</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; color: #374151;">{{ $activeTab === 'travel-link-sync' ? 'Kunden' : 'Events' }}</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; color: #374151;">{{ $activeTab === 'travel-link-sync' ? 'Trips sync.' : 'Gesendet' }}</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; color: #374151;">Fehler</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; color: #374151;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr style="border-bottom: 1px solid #f3f4f6; {{ $log->status === 'failed' ? 'background: #fef2f2;' : '' }}">
                        <td style="padding: 0.75rem 1rem;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 9999px; font-size: 0.6875rem; font-weight: 600;
                                {{ $log->queue_name === 'gtm-notifications' ? 'background: #dbeafe; color: #1e40af;' : ($log->queue_name === 'travel-link-sync' ? 'background: #e0e7ff; color: #3730a3;' : 'background: #fef3c7; color: #92400e;') }}">
                                {{ $log->queue_name }}
                            </span>
                        </td>
                        <td style="padding: 0.75rem 1rem; color: #374151;">{{ $log->started_at->format('d.m.Y H:i:s') }}</td>
                        <td style="padding: 0.75rem 1rem; color: #374151;">{{ $log->completed_at?->format('d.m.Y H:i:s') ?? '—' }}</td>
                        <td style="padding: 0.75rem 1rem; text-align: center; color: #6b7280;">{{ $log->duration ?? '—' }}</td>
                        <td style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; color: #374151;">{{ $log->events_processed }}</td>
                        <td style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; color: {{ $log->notifications_sent > 0 ? '#166534' : '#6b7280' }};">{{ $log->notifications_sent }}</td>
                        <td style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; color: {{ $log->errors > 0 ? '#dc2626' : '#6b7280' }};">{{ $log->errors }}</td>
                        <td style="padding: 0.75rem 1rem; text-align: center;">
                            <span style="display: inline-flex; padding: 2px 8px; border-radius: 9999px; font-size: 0.6875rem; font-weight: 600;
                                {{ $log->status === 'completed' ? 'background: #dcfce7; color: #166534;' : ($log->status === 'failed' ? 'background: #fee2e2; color: #dc2626;' : 'background: #dbeafe; color: #1e40af;') }}">
                                {{ $log->status === 'completed' ? 'Abgeschlossen' : ($log->status === 'failed' ? 'Fehlgeschlagen' : 'Läuft...') }}
                            </span>
                        </td>
                    </tr>
                    @if($log->error_message)
                        <tr style="background: #fef2f2;">
                            <td colspan="8" style="padding: 0.5rem 1rem; font-size: 0.75rem; color: #dc2626;">
                                <strong>Fehler:</strong> {{ Str::limit($log->error_message, 200) }}
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" style="padding: 2rem; text-align: center; color: #9ca3af;">
                            Noch keine Queue-Durchläufe protokolliert.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div style="margin-top: 1rem;">
            {{ $logs->links() }}
        </div>
    @endif

    {{-- Config Info --}}
    <div style="margin-top: 1.5rem; padding: 1rem 1.25rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.75rem; color: #6b7280;">
        <strong>Konfiguration:</strong>
        GTM Intervall: <code style="background: #e5e7eb; padding: 1px 6px; border-radius: 4px;">{{ config('notifications.gtm_interval') }} Min.</code> |
        Travel Alert Intervall: <code style="background: #e5e7eb; padding: 1px 6px; border-radius: 4px;">{{ config('notifications.travel_alert_interval') }} Min.</code> |
        Travel Link Sync: <code style="background: #e5e7eb; padding: 1px 6px; border-radius: 4px;">{{ config('notifications.travel_links_sync_interval') }} Min.</code> |
        Lookback: <code style="background: #e5e7eb; padding: 1px 6px; border-radius: 4px;">{{ config('notifications.lookback_hours') }}h</code>
        <span style="margin-left: 1rem;">Konfigurierbar via <code style="background: #e5e7eb; padding: 1px 6px; border-radius: 4px;">.env</code></span>
    </div>
</x-filament-panels::page>
