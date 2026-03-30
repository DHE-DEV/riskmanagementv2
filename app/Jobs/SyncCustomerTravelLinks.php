<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\TravelDetail\TdTrip;
use App\Services\TravelDetail\PdsTripSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCustomerTravelLinks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public Customer $customer,
    ) {
        $this->onQueue('travel-link-sync');
    }

    public function handle(PdsTripSyncService $syncService): void
    {
        $customer = $this->customer;

        try {
            // Delta-Sync: nur Änderungen seit letztem Sync
            $updatedSince = $customer->pds_last_synced_at;
            $syncLog = $syncService->syncCustomer($customer, $updatedSince);

            if ($syncLog->status === 'failed') {
                Log::warning('Travel Link Sync fehlgeschlagen', [
                    'customer_id' => $customer->id,
                    'error' => $syncLog->error_message,
                ]);

                return;
            }

            // Share-URLs generieren für neue Trips ohne URL
            $travelDetailsBase = rtrim(env('PASSOLUTION_TRAVEL_DETAILS_LINK', 'https://travel-details.eu'), '/');
            $linksGenerated = TdTrip::where('customer_id', $customer->id)
                ->whereNotNull('pds_tid')
                ->whereNull('pds_share_url')
                ->get()
                ->each(function ($trip) use ($travelDetailsBase) {
                    $trip->update([
                        'pds_share_url' => $travelDetailsBase . '/de?tid=' . $trip->pds_tid,
                    ]);
                })
                ->count();

            $customer->update(['pds_last_synced_at' => now()]);

            Log::info('Travel Link Sync abgeschlossen', [
                'customer_id' => $customer->id,
                'trips_fetched' => $syncLog->trips_fetched,
                'trips_created' => $syncLog->trips_created,
                'trips_updated' => $syncLog->trips_updated,
                'links_generated' => $linksGenerated,
            ]);
        } catch (\Exception $e) {
            Log::error('Travel Link Sync Fehler', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
