<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Archivierbar</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['archivable_count'] ?? 0) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Reisen bereit zur Archivierung</div>
        </div>

        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Bereits archiviert</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['archived_count'] ?? 0) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Reisen im Archiv</div>
        </div>

        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktive Reisen</div>
            <div class="text-2xl font-bold text-success-600 dark:text-success-400">{{ number_format($stats['active_count'] ?? 0) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Noch nicht abgeschlossen</div>
        </div>

        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Abgeschlossen</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['completed_count'] ?? 0) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Beendete Reisen</div>
        </div>
    </div>

    @if(($stats['archivable_count'] ?? 0) > 0)
        <div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-warning-500 flex-shrink-0 mt-0.5" />
                <div>
                    <div class="font-medium text-warning-800 dark:text-warning-200">Archivierung empfohlen</div>
                    <div class="text-sm text-warning-700 dark:text-warning-300 mt-1">
                        {{ number_format($stats['archivable_count']) }} Reisen sind älter als {{ $stats['archival_days'] ?? 30 }} Tage und können archiviert werden.
                    </div>
                    <div class="text-xs text-warning-600 dark:text-warning-400 mt-2">
                        Führen Sie <code class="bg-warning-100 dark:bg-warning-900 px-1 rounded">php artisan td:archive-trips</code> aus, um diese Reisen zu archivieren.
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <x-heroicon-o-check-circle class="h-5 w-5 text-success-500 flex-shrink-0 mt-0.5" />
                <div>
                    <div class="font-medium text-success-800 dark:text-success-200">Keine Archivierung nötig</div>
                    <div class="text-sm text-success-700 dark:text-success-300 mt-1">
                        Alle Reisen sind entweder noch aktiv oder bereits archiviert.
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Konfiguration</h4>
        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
            <div><strong>Archivierung nach:</strong> {{ $stats['archival_days'] ?? 30 }} Tagen</div>
            <div><strong>Endgültige Löschung nach:</strong> {{ config('travel_detail.retention.purge_archived_after_years', 2) }} Jahren</div>
            <div><strong>Batch-Größe:</strong> {{ $stats['batch_size'] ?? 1000 }} Reisen pro Durchlauf</div>
            <div><strong>Archiv-DB:</strong> {{ $stats['archive_db_enabled'] ? 'Aktiviert' : 'Deaktiviert' }}</div>
            <div><strong>Automatische Bereinigung:</strong> {{ config('travel_detail.retention.scheduled_cleanup_enabled') ? 'Aktiviert (' . config('travel_detail.retention.cleanup_time', '03:00') . ' Uhr)' : 'Deaktiviert' }}</div>
        </div>
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Befehle</h4>
        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 font-mono">
            <div><code class="bg-gray-100 dark:bg-gray-900 px-1 rounded">php artisan td:archive-trips</code> - Reisen archivieren</div>
            <div><code class="bg-gray-100 dark:bg-gray-900 px-1 rounded">php artisan td:purge-archived</code> - Alte Archive löschen</div>
            <div><code class="bg-gray-100 dark:bg-gray-900 px-1 rounded">php artisan td:prune-logs</code> - Import-Logs bereinigen</div>
        </div>
    </div>
</div>
