<?php

namespace App\Console\Commands;

use App\Jobs\GeocodeSubAccounts;
use App\Models\Customer;
use Illuminate\Console\Command;

/**
 * Koordinaten der Unteraccounts einer Zentrale ermitteln (Karte "Filialen &
 * Standorte"). Standard: als Hintergrund-Job; mit --sync direkt hier.
 */
class GeocodeSubAccountsCommand extends Command
{
    protected $signature = 'customers:geocode-sub-accounts {agency : Kundennummer der Zentrale} {--sync : Direkt ausfuehren statt per Queue}';

    protected $description = 'Ermittelt die Koordinaten der Unteraccounts einer Zentrale (OpenStreetMap)';

    public function handle(): int
    {
        $agency = Customer::find((int) $this->argument('agency'));

        if (! $agency) {
            $this->error('Kunde nicht gefunden.');

            return self::FAILURE;
        }

        $pending = GeocodeSubAccounts::pending($agency->id)->count();
        $this->info("{$pending} Unteraccounts ohne Koordinaten (etwa eine Adresse pro Sekunde).");

        if ($pending === 0) {
            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            GeocodeSubAccounts::$runSeconds = PHP_INT_MAX;
            GeocodeSubAccounts::dispatchSync($agency->id);
            $this->info('Fertig.');
        } else {
            GeocodeSubAccounts::dispatch($agency->id);
            $this->info('Job eingestellt.');
        }

        return self::SUCCESS;
    }
}
