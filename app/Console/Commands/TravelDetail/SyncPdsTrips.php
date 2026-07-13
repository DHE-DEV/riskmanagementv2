<?php

namespace App\Console\Commands\TravelDetail;

use App\Models\Customer;
use App\Services\TravelDetail\PdsTripSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncPdsTrips extends Command
{
    protected $signature = 'td:sync-pds-trips
                            {--customer= : Sync a specific customer by ID}
                            {--all : Sync all customers with valid PDS tokens}
                            {--delta : Only fetch trips updated since last sync}
                            {--since= : Only fetch trips updated after this date (Y-m-d)}
                            {--dry-run : Show what would be synced without making changes}';

    protected $description = 'Synchronize PDS travel-details into local td_trips table';

    public function handle(PdsTripSyncService $syncService): int
    {
        $customerId = $this->option('customer');
        $syncAll = $this->option('all');

        if (! $customerId && ! $syncAll) {
            $this->error('Bitte --customer=ID oder --all angeben.');

            return self::FAILURE;
        }

        if ($customerId) {
            $customer = Customer::find($customerId);
            if (! $customer) {
                $this->error("Customer {$customerId} nicht gefunden.");

                return self::FAILURE;
            }

            return $this->syncSingleCustomer($syncService, $customer);
        }

        return $this->syncAllCustomers($syncService);
    }

    protected function syncSingleCustomer(PdsTripSyncService $syncService, Customer $customer): int
    {
        $this->info("Synchronisiere PDS-Reisen für: {$customer->email} (ID: {$customer->id})");

        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] Keine Änderungen durchgeführt.');

            return self::SUCCESS;
        }

        $updatedSince = $this->resolveUpdatedSince($customer);
        $syncType = $updatedSince ? "Delta-Sync (seit {$updatedSince->format('d.m.Y H:i')})" : 'Voll-Sync';
        $this->info($syncType);

        $log = $syncService->syncCustomer($customer, $updatedSince);

        $customer->update(['pds_last_synced_at' => now()]);

        $this->table(
            ['Status', 'Abgerufen', 'Neu', 'Aktualisiert', 'Unverändert', 'API Total', 'Seiten', 'Dauer'],
            [[
                $log->status,
                $log->trips_fetched,
                $log->trips_created,
                $log->trips_updated,
                $log->trips_unchanged,
                $log->trips_total_api ?? '-',
                $log->pages_fetched,
                $log->duration_ms ? ($log->duration_ms.'ms') : '-',
            ]]
        );

        if ($log->error_message) {
            $this->error("Fehler: {$log->error_message}");
        }

        return $log->status === 'success' ? self::SUCCESS : self::FAILURE;
    }

    protected function syncAllCustomers(PdsTripSyncService $syncService): int
    {
        $customers = Customer::whereNotNull('pds_api_token')
            ->orWhereNotNull('passolution_access_token')
            ->get();

        $this->info("Synchronisiere {$customers->count()} Kunden mit PDS-Token...");

        $results = ['success' => 0, 'partial' => 0, 'failed' => 0];

        foreach ($customers as $customer) {
            $this->line("  → {$customer->email}...");

            if ($this->option('dry-run')) {
                $this->line('    [DRY-RUN] Übersprungen');

                continue;
            }

            $updatedSince = $this->resolveUpdatedSince($customer);
            $log = $syncService->syncCustomer($customer, $updatedSince);
            $customer->update(['pds_last_synced_at' => now()]);
            $results[$log->status === 'success' ? 'success' : ($log->status === 'partial' ? 'partial' : 'failed')]++;

            $this->line("    {$log->status}: {$log->trips_created} neu, {$log->trips_updated} aktualisiert ({$log->duration_ms}ms)");
        }

        $this->newLine();
        $this->info("Ergebnis: {$results['success']} erfolgreich, {$results['partial']} teilweise, {$results['failed']} fehlgeschlagen");

        return $results['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function resolveUpdatedSince(Customer $customer): ?Carbon
    {
        if ($this->option('since')) {
            return Carbon::parse($this->option('since'));
        }

        if ($this->option('delta') && $customer->pds_last_synced_at) {
            // Ueberlappungsfenster gegen Lag des pds-queue-Jobs und die Sync-Laufzeit:
            // lieber wenige Saetze doppelt holen (Upsert ist idempotent) als Aenderungen verpassen.
            return $customer->pds_last_synced_at->copy()->subMinutes(10);
        }

        return null;
    }
}
