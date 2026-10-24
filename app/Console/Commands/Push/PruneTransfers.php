<?php

namespace App\Console\Commands\Push;

use App\Models\Push\AppDeviceTransfer;
use Illuminate\Console\Command;

/**
 * Uebertragungen auf neue Geraete aufraeumen: abgelaufene offene Anfragen → expired, das aufbewahrte Secret
 * 10 Minuten nach dem Abschluss loeschen, Anfragen aelter als 30 Tage entfernen.
 */
class PruneTransfers extends Command
{
    protected $signature = 'app-devices:prune-transfers';

    protected $description = 'TripData: Geräteübertragungen aufräumen (abgelaufen, Secret, älter als 30 Tage)';

    public function handle(): int
    {
        $expired = AppDeviceTransfer::open()->where('expires_at', '<=', now())
            ->update(['status' => AppDeviceTransfer::STATUS_EXPIRED]);

        $secrets = AppDeviceTransfer::whereNotNull('secret_encrypted')
            ->where(fn ($q) => $q->whereNull('completed_at')
                ->orWhere('completed_at', '<', now()->subMinutes(AppDeviceTransfer::SECRET_KEEP_MINUTES)))
            ->update(['secret_encrypted' => null]);

        $deleted = AppDeviceTransfer::where('created_at', '<', now()->subDays(30))->delete();

        $this->info("{$expired} abgelaufen, {$secrets} Secrets gelöscht, {$deleted} alte Übertragungen entfernt.");

        return self::SUCCESS;
    }
}
