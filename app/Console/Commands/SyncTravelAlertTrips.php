<?php

namespace App\Console\Commands;

use App\Services\TravelDetail\TravelAlertTripMirror;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Hält den lokalen Spiegel der pds-Reisen für den Travel Alert aktuell.
 *
 * Ohne Option (jede Minute): inaktive Konten entfernen, neu hinzugekommene Konten
 * vollständig laden, dann die Änderungen aus dem Delta-Feed übernehmen.
 * Mit --full (nachts): alle gespiegelten Konten vollständig neu laden – entfernt auch
 * gelöschte Links, die der Delta-Feed nicht meldet.
 */
class SyncTravelAlertTrips extends Command
{
    protected $signature = 'travel-alert:sync-trips
        {--full : Alle gespiegelten Konten vollständig neu laden}
        {--account=* : Nur diese pds-Konten vollständig laden (legt sie bei Bedarf an)}';

    protected $description = 'Spiegelt die pds-Reisen der Travel-Alert-Konten lokal (Vollabruf + Delta-Feed)';

    public function handle(TravelAlertTripMirror $mirror): int
    {
        // Ein Lauf zur Zeit – auch zwischen Minutenlauf und nächtlichem Vollabgleich.
        $lock = Cache::lock('travel-alert:sync-trips', 3600);

        if (! $lock->get()) {
            $this->info('Läuft bereits – übersprungen.');

            return self::SUCCESS;
        }

        try {
            $pruned = $mirror->pruneInactive();
            if ($pruned > 0) {
                $this->info("{$pruned} inaktive Konten entfernt.");
            }

            $accounts = match (true) {
                $this->option('account') !== [] => array_map('intval', $this->option('account')),
                (bool) $this->option('full') => $mirror->activeAccounts(),
                default => $mirror->accountsAwaitingFirstSync(),
            };

            foreach ($accounts as $accountId) {
                $mirror->noteRequestNow($accountId);
                $started = microtime(true);
                $ok = $mirror->syncAccount($accountId);
                $this->line(sprintf('Konto %d: %s (%.1f s)', $accountId, $ok ? 'geladen' : 'fehlgeschlagen', microtime(true) - $started));
            }

            $stats = $mirror->syncChanges();
            $this->info("Delta: {$stats['pages']} Seiten, {$stats['received']} Änderungen, {$stats['stored']} übernommen.");
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }
}
