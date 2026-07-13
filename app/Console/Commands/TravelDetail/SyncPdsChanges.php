<?php

namespace App\Console\Commands\TravelDetail;

use App\Services\TravelDetail\PdsTripSyncService;
use Illuminate\Console\Command;

/**
 * Account-uebergreifender Delta-Sync: holt ueber den internen Bulk-Endpoint
 * /__internal/travel-details/changes alle seit dem letzten Lauf geaenderten
 * (oder neuen) zukuenftigen Travel-Detail-Links und upsertet sie lokal nach
 * pds_account_id. Resumierbar ueber den persistenten Keyset-Cursor.
 *
 * Im Scheduler regelmaessig laufen lassen (z. B. alle paar Minuten).
 */
class SyncPdsChanges extends Command
{
    protected $signature = 'td:sync-pds-changes
                            {--per-page=1000 : Seitengroesse fuer den Bulk-Endpoint}
                            {--max-pages= : Maximale Seitenzahl pro Lauf (Sicherheits-Limit)}';

    protected $description = 'Account-uebergreifender Delta-Sync der PDS Travel-Detail-Links (Bulk-Endpoint, Keyset)';

    public function handle(PdsTripSyncService $syncService): int
    {
        $perPage = (int) $this->option('per-page');
        $maxPages = $this->option('max-pages') !== null ? (int) $this->option('max-pages') : null;

        $this->info('Starte account-uebergreifenden Delta-Sync...');

        $stats = $syncService->syncAllChanges($perPage, $maxPages);

        $this->table(
            ['Seiten', 'Abgerufen', 'Neu', 'Aktualisiert', 'Unveraendert'],
            [[
                $stats['pages'],
                $stats['fetched'],
                $stats['created'],
                $stats['updated'],
                $stats['unchanged'],
            ]],
        );

        return self::SUCCESS;
    }
}
