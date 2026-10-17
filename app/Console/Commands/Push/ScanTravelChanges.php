<?php

namespace App\Console\Commands\Push;

use App\Services\Push\TravelChangeScanner;
use Illuminate\Console\Command;

/**
 * Wichtige Aenderungen an Reise-Links aus dem Delta-Feed der pds-api holen und die
 * Geraete benachrichtigen, die diese Links in der App haben. Laeuft alle 2 Minuten.
 */
class ScanTravelChanges extends Command
{
    protected $signature = 'push:scan-travel-changes
                            {--max-pages= : Hoechstens so viele Seiten je Lauf}
                            {--per-page= : Links je Seite (10 bis 1000)}';

    protected $description = 'Push-Benachrichtigungen bei wichtigen Aenderungen an Reise-Links (TripData)';

    public function handle(TravelChangeScanner $scanner): int
    {
        $stats = $scanner->run(
            $this->option('max-pages') !== null ? (int) $this->option('max-pages') : null,
            $this->option('per-page') !== null ? (int) $this->option('per-page') : null,
        );

        if ($stats['initialized']) {
            $this->info('Erster Lauf: Beobachtung beginnt jetzt.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%d Seiten, %d geänderte Links, %d Geräte benachrichtigt.', $stats['pages'], $stats['links'], $stats['notified']));

        return self::SUCCESS;
    }
}
