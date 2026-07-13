<?php

namespace App\Services\TravelDetail;

use App\Models\Customer;
use App\Models\TravelDetail\PdsGlobalSyncState;
use App\Models\TravelDetail\TdPdsSyncLog;
use App\Models\TravelDetail\TdTrip;
use App\Services\PassolutionApiService;
use App\Services\PdsApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PdsTripSyncService
{
    protected PdsApiService $pdsApi;

    protected PassolutionApiService $passolutionApi;

    protected int $perPage = 100;

    protected int $maxPages = 100;

    public function __construct(PdsApiService $pdsApi, PassolutionApiService $passolutionApi)
    {
        $this->pdsApi = $pdsApi;
        $this->passolutionApi = $passolutionApi;
    }

    /**
     * Account-uebergreifender Delta-Sync ueber den internen Bulk-Endpoint
     * (Service-Token). Holt alle Links mit last_change_at > Wasserstand und
     * end_date >= jetzt, Keyset-paginiert, und upsertet sie nach pds_account_id.
     *
     * Der Cursor (last_change_at, id) wird persistent in pds_global_sync_states
     * gehalten und nach jeder Seite fortgeschrieben (resumierbar).
     *
     * @return array{fetched:int,created:int,updated:int,unchanged:int,pages:int}
     */
    public function syncAllChanges(int $perPage = 1000, ?int $maxPages = null): array
    {
        $state = PdsGlobalSyncState::forKey(PdsGlobalSyncState::KEY_TRAVEL_DETAILS);

        $cursor = $state->cursor_last_change_at
            ? ['last_change_at' => $state->cursor_last_change_at->format('Y-m-d H:i:s'), 'id' => $state->cursor_id]
            : null;

        $stats = ['fetched' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'pages' => 0];
        $page = 0;

        do {
            $response = $this->passolutionApi->fetchTravelDetailChanges(null, $cursor, $perPage);

            if ($response === null) {
                Log::error('PdsTripSyncService: Globaler Delta-Abruf fehlgeschlagen, Abbruch', [
                    'page' => $page,
                ]);
                break;
            }

            foreach (($response['data'] ?? []) as $tripData) {
                [$result] = $this->upsertTrip(null, $tripData);
                $stats['fetched']++;
                $stats[$result]++;
            }

            $page++;
            $stats['pages'] = $page;

            $meta = $response['meta'] ?? [];
            $next = $meta['next_cursor'] ?? null;

            if ($next && ! empty($next['last_change_at']) && isset($next['id'])) {
                $cursor = ['last_change_at' => $next['last_change_at'], 'id' => $next['id']];
                $state->update([
                    'cursor_last_change_at' => $next['last_change_at'],
                    'cursor_id' => $next['id'],
                    'last_run_at' => now(),
                ]);
            }

            $hasMore = (bool) ($meta['has_more'] ?? false);
        } while ($hasMore && (! $maxPages || $page < $maxPages));

        return $stats;
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
            'updated_trip_ids' => [],
            'created_trip_ids' => [],
        ];

        if (! $this->pdsApi->hasValidToken($customer)) {
            $syncLog->markFailed('Kein gültiger PDS API Token vorhanden', $stats);

            return $syncLog;
        }

        try {
            $page = 1;

            do {
                $requestBody = [
                    'sort_by' => $updatedSince ? 'last_change_at' : 'start_date',
                    'sort_order' => $updatedSince ? 'asc' : 'desc',
                    'page' => $page,
                    'per_page' => $this->perPage,
                    // riskmanagement interessiert nur laufende/zukuenftige Reisen
                    'end_date' => ['>=' => now()->format('Y-m-d H:i:s')],
                ];

                if ($updatedSince) {
                    // Delta ueber die generierte Spalte last_change_at = GREATEST(updated_at, important_change_at):
                    // faengt sowohl Datenaenderungen (updated_at) als auch wichtige Aenderungen
                    // (important_change_at) in EINEM Filter ab - volle Datums-/Zeit-Granularitaet (nicht nur Tag).
                    $requestBody['last_change_at'] = ['>' => $updatedSince->format('Y-m-d H:i:s')];
                }

                $response = $this->pdsApi->post($customer, '/travel-details?__with=__cruise-info', $requestBody);

                if (! $response || ! $response->successful()) {
                    $syncLog->markFailed(
                        'API-Fehler: '.($response ? $response->status().' '.$response->body() : 'Keine Antwort'),
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
                    [$result, $tripId] = $this->upsertTrip($customer, $tripData);
                    $stats['fetched']++;
                    $stats[$result]++;
                    if ($tripId && $result === 'updated') {
                        $stats['updated_trip_ids'][] = $tripId;
                    }
                    if ($tripId && $result === 'created') {
                        $stats['created_trip_ids'][] = $tripId;
                    }
                }

                $lastPage = $meta['last_page'] ?? 1;
                $page++;
            } while ($page <= $lastPage && $page <= $this->maxPages);

            $syncLog->markCompleted($stats);
            $syncLog->updated_trip_ids = $stats['updated_trip_ids'];
            $syncLog->created_trip_ids = $stats['created_trip_ids'];
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
     * @return array [string $result, int|null $tripId]
     */
    protected function upsertTrip(?Customer $customer, array $tripData): array
    {
        $pdsTid = $tripData['tid'] ?? $tripData['id'] ?? null;

        if (! $pdsTid) {
            return ['unchanged', null];
        }

        // Account-Zuordnung: aus dem Bulk-Feed (account_id) oder vom Customer.
        $accountId = $tripData['account_id'] ?? $customer?->pds_account_id ?? null;

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
            'provider_name' => 'Passolution PDS',
            'provider_sent_at' => now(),
            'booking_reference' => $tripData['reference_id'] ?? $tripData['booking_reference'] ?? null,
            'reference_id' => $tripData['reference_id'] ?? null,
            'trip_name' => $tripData['trip_name'] ?? null,
            'cruise_compass_cruise_id' => $tripData['cruise_compass']['cruise_id'] ?? null,
            'is_cruise' => ! empty($tripData['cruise_compass']['cruise_id']),
            'computed_start_at' => $startDate,
            'computed_end_at' => $endDate,
            'countries_visited' => $countries,
            'nationalities' => $tripData['nationalities'] ?? null,
            'travel_modes' => $tripData['travel']['modes'] ?? null,
            'with_minors' => $tripData['travel']['with_minors'] ?? false,
            'tour_operators' => $tripData['tour_operators'] ?? null,
            'individual_contents' => $tripData['individual_contents'] ?? null,
            'note' => $tripData['note'] ?? null,
            'cover_media' => $tripData['cover_media'] ?? null,
            'visits' => $tripData['visits'] ?? 0,
            'last_visited_at' => isset($tripData['last_visited_at']) ? Carbon::parse($tripData['last_visited_at'])->setTimezone(config('app.timezone')) : null,
            'last_important_change_at' => isset($tripData['last_important_change_at']) ? Carbon::parse($tripData['last_important_change_at'])->setTimezone(config('app.timezone')) : null,
            'status' => $status,
            'pds_tid' => $pdsTid,
            'pds_share_created_at' => isset($tripData['created_at']) ? Carbon::parse($tripData['created_at'])->setTimezone(config('app.timezone')) : null,
            'raw_payload' => $tripData,
        ];

        // pds_account_id ist der Zuordnungsschluessel des globalen Syncs.
        // Nur setzen, wenn bekannt - eine bestehende Zuordnung nie mit null ueberschreiben.
        if ($accountId !== null) {
            $attributes['pds_account_id'] = $accountId;
        }

        // customer_id nur setzen, wenn ein lokaler Customer-Kontext vorliegt
        // (per-Customer-Sync); der globale Sync laesst sie unangetastet.
        if ($customer) {
            $attributes['customer_id'] = $customer->id;
        }

        $existing = TdTrip::where('provider_id', $providerId)
            ->where('external_trip_id', $externalTripId)
            ->first();

        if ($existing) {
            // Check if anything changed
            $changed = false;
            $jsonFields = ['countries_visited', 'nationalities', 'travel_modes', 'tour_operators', 'individual_contents'];
            $checkFields = [
                'booking_reference', 'reference_id', 'trip_name', 'cruise_compass_cruise_id',
                'computed_start_at', 'computed_end_at', 'countries_visited', 'nationalities',
                'travel_modes', 'with_minors', 'tour_operators', 'individual_contents',
                'note', 'cover_media', 'visits', 'last_visited_at', 'last_important_change_at',
                'status',
            ];

            foreach ($checkFields as $field) {
                $newVal = $attributes[$field];
                $oldVal = $existing->$field;

                if (in_array($field, $jsonFields)) {
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

                return ['updated', $existing->id];
            }

            // Inhalt unveraendert: nur die Account-Zuordnung nachziehen, falls noch leer.
            if ($accountId !== null && $existing->pds_account_id !== $accountId) {
                $existing->update(['pds_account_id' => $accountId]);
            }

            return ['unchanged', $existing->id];
        }

        $newTrip = TdTrip::create(array_merge($attributes, [
            'provider_id' => $providerId,
            'external_trip_id' => $externalTripId,
        ]));

        // Store traveller data if available
        $this->syncTravellers($externalTripId, $providerId, $tripData);

        return ['created', $newTrip->id];
    }

    /**
     * Extract country codes from various PDS response formats.
     */
    protected function extractCountries(array $tripData): array
    {
        $countries = [];

        // From destinations array (simple ISO codes)
        if (isset($tripData['destinations']) && is_array($tripData['destinations'])) {
            foreach ($tripData['destinations'] as $dest) {
                if (is_string($dest) && strlen($dest) === 2) {
                    $countries[] = strtoupper($dest);
                }
            }
        }

        // From destinations_list array (objects with code + type)
        if (isset($tripData['destinations_list']) && is_array($tripData['destinations_list'])) {
            foreach ($tripData['destinations_list'] as $dest) {
                $code = is_array($dest) ? ($dest['code'] ?? null) : null;
                if ($code && strlen($code) === 2) {
                    $countries[] = strtoupper($code);
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
