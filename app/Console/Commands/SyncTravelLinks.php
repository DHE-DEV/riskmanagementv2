<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\NotificationQueueLog;
use App\Models\TravelDetail\TdTrip;
use App\Services\TravelDetail\PdsTripSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTravelLinks extends Command
{
    protected $signature = 'travel-links:sync';

    protected $description = 'Synchronisiert Travel Links für alle Kunden mit aktiviertem Travel Links Feature';

    public function handle(PdsTripSyncService $syncService): int
    {
        $queueName = 'travel-link-sync';

        $log = NotificationQueueLog::create([
            'queue_name' => $queueName,
            'started_at' => now(),
            'status' => 'running',
        ]);

        $customers = Customer::where('travel_links_enabled', true)->get();

        if ($customers->isEmpty()) {
            $this->info('Keine Kunden mit aktivierten Travel Links gefunden.');

            $log->update([
                'completed_at' => now(),
                'events_processed' => 0,
                'notifications_sent' => 0,
                'errors' => 0,
                'status' => 'completed',
            ]);

            return self::SUCCESS;
        }

        $this->info("Synchronisiere Travel Links für {$customers->count()} Kunden...");

        $customersProcessed = 0;
        $totalTripsCreated = 0;
        $totalTripsUpdated = 0;
        $errors = 0;

        foreach ($customers as $customer) {
            try {
                $updatedSince = $customer->pds_last_synced_at;
                $syncLog = $syncService->syncCustomer($customer, $updatedSince);

                if ($syncLog->status === 'failed') {
                    $errors++;
                    $this->warn("Kunde #{$customer->id}: Sync fehlgeschlagen - {$syncLog->error_message}");

                    continue;
                }

                // Share-URLs generieren
                $travelDetailsBase = rtrim(env('PASSOLUTION_TRAVEL_DETAILS_LINK', 'https://travel-details.eu'), '/');
                TdTrip::where('customer_id', $customer->id)
                    ->whereNotNull('pds_tid')
                    ->whereNull('pds_share_url')
                    ->get()
                    ->each(function ($trip) use ($travelDetailsBase) {
                        $trip->update([
                            'pds_share_url' => $travelDetailsBase . '/de?tid=' . $trip->pds_tid,
                        ]);
                    });

                $customer->update(['pds_last_synced_at' => now()]);

                $customersProcessed++;
                $totalTripsCreated += $syncLog->trips_created;
                $totalTripsUpdated += $syncLog->trips_updated;

                $this->info("Kunde #{$customer->id} ({$customer->company_name}): {$syncLog->trips_fetched} abgerufen, {$syncLog->trips_created} neu, {$syncLog->trips_updated} aktualisiert");
            } catch (\Exception $e) {
                $errors++;
                $this->error("Kunde #{$customer->id}: {$e->getMessage()}");
                Log::error('Travel Link Sync Fehler', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $log->update([
            'completed_at' => now(),
            'events_processed' => $customersProcessed,
            'notifications_sent' => $totalTripsCreated + $totalTripsUpdated,
            'errors' => $errors,
            'status' => $errors > 0 && $customersProcessed === 0 ? 'failed' : 'completed',
        ]);

        $this->info("Abgeschlossen: {$customersProcessed} Kunden, {$totalTripsCreated} neue Trips, {$totalTripsUpdated} aktualisierte Trips, {$errors} Fehler");

        return $errors > 0 && $customersProcessed === 0 ? self::FAILURE : self::SUCCESS;
    }
}
