<?php

namespace App\Console\Commands\Push;

use App\Models\Push\AppDevice;
use Illuminate\Console\Command;

/**
 * Geraete entfernen, die sich laenger als push.device_stale_days nicht gemeldet haben
 * (App deinstalliert, Geraet verloren). Ihre Reise-Links gehen mit (cascade).
 */
class PruneDevices extends Command
{
    protected $signature = 'push:prune-devices';

    protected $description = 'Push: lange inaktive Geraete samt Reise-Links entfernen';

    public function handle(): int
    {
        $count = 0;

        AppDevice::where('last_seen_at', '<', now()->subDays((int) config('push.device_stale_days', 180)))
            ->chunkById(1000, function ($devices) use (&$count) {
                $count += AppDevice::whereIn('id', $devices->pluck('id'))->delete();
            });

        $this->info($count.' inaktive Geräte entfernt.');

        return self::SUCCESS;
    }
}
