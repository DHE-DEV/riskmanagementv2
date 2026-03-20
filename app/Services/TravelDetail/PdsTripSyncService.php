<?php

namespace App\Services\TravelDetail;

use App\Models\Customer;
use App\Models\TravelDetail\TdPdsSyncLog;
use App\Models\TravelDetail\TdTrip;
use App\Services\PdsApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PdsTripSyncService
{
    protected PdsApiService $pdsApi;

    protected int $perPage = 100;

    protected int $maxPages = 100;

    public function __construct(PdsApiService $pdsApi)
    {
        $this->pdsApi = $pdsApi;
    }

    /**
     * Sync all PDS travel-details for a customer into td_trips.
     * If $updatedSince is provided, only fetches trips updated after that date (delta sync).
     */
    public function syncCustomer(Customer $customer, ?Carbon $updatedSince = null): TdPdsSyncLog
    {
        $syncLog = TdPdsSyncLog::start($customer->id);

        $stats = [
            'fetched' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'total_api' => null,
            'pages' => 0,
        ];

        if (! $this->pdsApi->hasValidToken($customer)) {
            $syncLog->markFailed('Kein gültiger PDS API Token vorhanden', $stats);

            return $syncLog;
        }

        try {
            $page = 1;

            do {
                $requestBody = [
                    'sort_by' => $updatedSince ? 'updated_at' : 'start_date',
                    'sort_order' => $updatedSince ? 'asc' : 'desc',
                    'page' => $page,
                    'per_page' => $this->perPage,
                ];

                if ($updatedSince) {
                    $requestBody['updated_at'] = ['>' => $updatedSince->format('Y-m-d')];
                }

                $response = $this->pdsApi->post($customer, '/travel-details?__with=__cruise-info', $requestBody);

                if (! $response || ! $response->successful()) {
                    $syncLog->markFailed(
                        'API-Fehler: ' . ($response ? $response->status() . ' ' . $response->body() : 'Keine Antwort'),
                        $stats
                    );

                    return $syncLog;
                }

                $data = $response->json();
                $trips = $data['data'] ?? [];
                $meta = $data['meta'] ?? [];

                $stats['total_api'] = $meta['total'] ?? $stats['total_api'];
                $stats['pages'] = $page;

                foreach ($trips as $tripData) {
                    $result = $this->upsertTrip($customer, $tripData);
                    $stats['fetched']++;
                    $stats[$result]++;
                }

                $lastPage = $meta['last_page'] ?? 1;
                $page++;
            } while ($page <= $lastPage && $page <= $this->maxPages);

            $syncLog->markCompleted($stats);
        } catch (\Exception $e) {
            Log::error('PdsTripSyncService: Sync failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            $syncLog->markFailed($e->getMessage(), $stats);
        }

        return $syncLog;
    }

    /**
     * Upsert a single trip from PDS API response into td_trips.
     *
     * @return string 'created'|'updated'|'unchanged'
     */
    protected function upsertTrip(Customer $customer, array $tripData): string
    {
        $pdsTid = $tripData['tid'] ?? $tripData['id'] ?? null;

        if (! $pdsTid) {
            return 'unchanged';
        }

        $providerId = 'pds-api';
        $externalTripId = $pdsTid;

        $startDate = isset($tripData['start_date']) ? Carbon::parse($tripData['start_date']) : null;
        $endDate = isset($tripData['end_date']) ? Carbon::parse($tripData['end_date']) : null;

        // Derive countries from destinations or countries array
        $countries = $this->extractCountries($tripData);

        // Determine status based on dates
        $status = 'active';
        if ($endDate && $endDate->isPast()) {
            $status = 'completed';
        }

        $attributes = [
            'customer_id' => $customer->id,
            'provider_name' => 'Passolution PDS',
            'provider_sent_at' => now(),
            'booking_reference' => $tripData['booking_reference'] ?? null,
            'computed_start_at' => $startDate,
            'computed_end_at' => $endDate,
            'countries_visited' => $countries,
            'status' => $status,
            'pds_tid' => $pdsTid,
            'raw_payload' => $tripData,
        ];

        $existing = TdTrip::where('provider_id', $providerId)
            ->where('external_trip_id', $externalTripId)
            ->first();

        if ($existing) {
            // Check if anything changed
            $changed = false;
            foreach (['computed_start_at', 'computed_end_at', 'countries_visited', 'status'] as $field) {
                $newVal = $attributes[$field];
                $oldVal = $existing->$field;

                if ($field === 'countries_visited') {
                    if (json_encode($newVal) !== json_encode($oldVal)) {
                        $changed = true;
                        break;
                    }
                } elseif ($newVal instanceof Carbon) {
                    if (! $oldVal || ! $newVal->eq($oldVal)) {
                        $changed = true;
                        break;
                    }
                } elseif ($newVal !== $oldVal) {
                    $changed = true;
                    break;
                }
            }

            if ($changed) {
                $existing->update($attributes);

                return 'updated';
            }

            return 'unchanged';
        }

        TdTrip::create(array_merge($attributes, [
            'provider_id' => $providerId,
            'external_trip_id' => $externalTripId,
        ]));

        // Store traveller data if available
        $this->syncTravellers($externalTripId, $providerId, $tripData);

        return 'created';
    }

    /**
     * Extract country codes from various PDS response formats.
     */
    protected function extractCountries(array $tripData): array
    {
        $countries = [];

        // From destinations array
        if (isset($tripData['destinations']) && is_array($tripData['destinations'])) {
            foreach ($tripData['destinations'] as $dest) {
                if (is_string($dest) && strlen($dest) === 2) {
                    $countries[] = strtoupper($dest);
                }
            }
        }

        // From countries array
        if (isset($tripData['countries']) && is_array($tripData['countries'])) {
            foreach ($tripData['countries'] as $country) {
                $code = is_array($country) ? ($country['code'] ?? null) : $country;
                if ($code && strlen($code) === 2) {
                    $countries[] = strtoupper($code);
                }
            }
        }

        // From cruise port calls
        if (isset($tripData['cruise']['port_calls']) && is_array($tripData['cruise']['port_calls'])) {
            foreach ($tripData['cruise']['port_calls'] as $portCall) {
                $code = $portCall['port']['country']['code'] ?? null;
                if ($code && strlen($code) === 2) {
                    $countries[] = strtoupper($code);
                }
            }
        }

        return array_values(array_unique($countries));
    }

    /**
     * Sync traveller data from PDS response if available.
     */
    protected function syncTravellers(string $externalTripId, string $providerId, array $tripData): void
    {
        $trip = TdTrip::where('provider_id', $providerId)
            ->where('external_trip_id', $externalTripId)
            ->first();

        if (! $trip) {
            return;
        }

        $nationalities = $tripData['nationalities'] ?? [];
        $travelersCount = $tripData['travelers_count'] ?? 0;

        // Store nationalities as travellers if no detailed traveller data
        if (! empty($nationalities) && $trip->travellers()->count() === 0) {
            foreach ($nationalities as $index => $nationality) {
                $trip->travellers()->create([
                    'trip_id' => $trip->id,
                    'external_traveller_id' => "nat-{$index}",
                    'nationality' => strtoupper($nationality),
                ]);
            }
        }
    }
}
