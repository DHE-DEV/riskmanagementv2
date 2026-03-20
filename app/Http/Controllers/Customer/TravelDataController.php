<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Folder\Folder;
use App\Models\Folder\FolderItinerary;
use App\Models\Folder\FolderFlightService;
use App\Models\Folder\FolderFlightSegment;
use App\Models\Folder\FolderHotelService;
use App\Models\Folder\FolderParticipant;
use App\Models\TravelDetail\TdPdsSyncLog;
use App\Models\TravelDetail\TdTrip;
use App\Services\PdsApiService;
use App\Services\TravelDetail\PdsShareLinkService;
use App\Services\TravelDetail\PdsTripSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TravelDataController extends Controller
{
    public function folders(Request $request): JsonResponse
    {
        $tab = $request->query('tab', 'current');
        $today = Carbon::today();

        $query = Folder::with(['participants', 'flightServices.segments', 'hotelServices'])
            ->orderBy('travel_start_date', $tab === 'upcoming' ? 'asc' : 'desc');

        match ($tab) {
            'current' => $query->where(function ($q) use ($today) {
                $q->where(function ($q2) use ($today) {
                    $q2->where('travel_start_date', '<=', $today)
                        ->where('travel_end_date', '>=', $today);
                })->orWhere('status', 'active');
            }),
            'upcoming' => $query->where(function ($q) use ($today) {
                $q->where('travel_start_date', '>', $today)
                    ->whereIn('status', ['draft', 'confirmed']);
            }),
            'archive' => $query->where(function ($q) use ($today) {
                $q->where(function ($q2) use ($today) {
                    $q2->where('travel_end_date', '<', $today)
                        ->whereNotIn('status', ['cancelled']);
                })->orWhere('status', 'cancelled');
            }),
            default => null,
        };

        $folders = $query->paginate(10);

        return response()->json($folders);
    }

    public function toggleSync(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $enabled = ! $customer->pds_sync_enabled;
        $customer->update(['pds_sync_enabled' => $enabled]);

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled ? 'Synchronisierung aktiviert' : 'Synchronisierung deaktiviert',
        ]);
    }

    public function syncNow(PdsTripSyncService $syncService): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer->pds_sync_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Synchronisierung ist nicht aktiviert',
            ], 422);
        }

        // Delta-Sync: nur geänderte Trips seit letzter Synchronisierung abrufen
        $updatedSince = $customer->pds_last_synced_at;
        $log = $syncService->syncCustomer($customer, $updatedSince);

        $customer->update(['pds_last_synced_at' => now()]);

        $syncType = $updatedSince ? 'Delta-Sync' : 'Voll-Sync';

        return response()->json([
            'success' => $log->status === 'success',
            'status' => $log->status,
            'message' => match ($log->status) {
                'success' => "{$syncType}: {$log->trips_created} neu, {$log->trips_updated} aktualisiert, {$log->trips_unchanged} unverändert",
                'partial' => "Teilweise synchronisiert: {$log->error_message}",
                default => "Fehler: {$log->error_message}",
            },
            'stats' => [
                'fetched' => $log->trips_fetched,
                'created' => $log->trips_created,
                'updated' => $log->trips_updated,
                'unchanged' => $log->trips_unchanged,
                'total_api' => $log->trips_total_api,
                'duration_ms' => $log->duration_ms,
            ],
            'synced_at' => now()->format('d.m.Y H:i'),
        ]);
    }

    public function travelLinksApi(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $filter = $request->query('filter', 'all');

        $today = Carbon::today();

        $query = TdTrip::where('customer_id', $customer->id)
            ->orderBy('computed_start_at', 'desc');

        match ($filter) {
            'current' => $query->where('status', 'active')
                ->where('computed_start_at', '<=', $today)
                ->where('computed_end_at', '>=', $today),
            'upcoming' => $query->where('status', 'active')
                ->where('computed_start_at', '>', $today),
            'expired' => $query->where(function ($q) use ($today) {
                $q->where(function ($q2) use ($today) {
                    $q2->whereNotNull('computed_end_at')
                        ->where('computed_end_at', '<', $today);
                })->orWhereIn('status', ['completed', 'cancelled']);
            }),
            default => null,
        };

        $perPage = $request->query('per_page', 15);
        $trips = $query->with('travellers')->paginate($perPage);

        // Pre-load country names for all trips
        $allCodes = $trips->getCollection()->flatMap(fn ($t) => $t->countries_visited ?? [])
            ->merge($trips->getCollection()->flatMap(fn ($t) => $t->travellers->pluck('nationality')->filter()))
            ->unique()->values()->toArray();

        $countryNames = \App\Models\Country::whereIn('iso_code', $allCodes)
            ->pluck('name_translations', 'iso_code')
            ->map(fn ($tr) => $tr['de'] ?? $tr['en'] ?? null)
            ->toArray();

        // Enrich trips with resolved names
        $trips->getCollection()->transform(function ($trip) use ($countryNames) {
            $trip->trip_name = $trip->raw_payload['trip_name']
                ?? $trip->raw_payload['trip']['name']
                ?? $trip->booking_reference
                ?? null;

            $trip->destinations = collect($trip->countries_visited ?? [])
                ->map(fn ($code) => ['code' => strtoupper($code), 'name' => $countryNames[strtoupper($code)] ?? strtoupper($code)])
                ->values();

            $trip->nationalities = $trip->travellers->pluck('nationality')->filter()->unique()
                ->map(fn ($code) => ['code' => strtoupper($code), 'name' => $countryNames[strtoupper($code)] ?? strtoupper($code)])
                ->values();

            return $trip;
        });

        return response()->json($trips);
    }

    public function toggleTravelLinks(): JsonResponse
    {
        $customer = auth('customer')->user();
        $enabled = ! $customer->travel_links_enabled;
        $customer->update(['travel_links_enabled' => $enabled]);

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
        ]);
    }

    public function syncLinks(PdsTripSyncService $syncService, PdsShareLinkService $shareLinkService): JsonResponse
    {
        $customer = auth('customer')->user();

        // 1. Sync trips from PDS API (delta if previously synced)
        $updatedSince = $customer->pds_last_synced_at;
        $syncLog = $syncService->syncCustomer($customer, $updatedSince);

        if ($syncLog->status === 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Synchronisierung fehlgeschlagen: ' . $syncLog->error_message,
            ], 500);
        }

        // 2. Count all customer trips and determine eligible ones
        $allTrips = TdTrip::where('customer_id', $customer->id)->get();
        $eligibleTrips = $allTrips->filter(fn ($t) => $t->status === 'active' && $t->computed_start_at !== null);
        $skippedCount = $allTrips->count() - $eligibleTrips->count();

        // IDs of trips that were updated during sync (need link refresh)
        $updatedTripIds = $syncLog->updated_trip_ids ?? [];

        // 3. Generate or refresh share links for eligible trips
        $linksCreated = 0;
        $linksRefreshed = 0;
        $linksExisting = 0;
        $linksFailed = 0;

        foreach ($eligibleTrips as $trip) {
            $existingLink = $trip->pdsShareLinks()->active()->first();

            // Trip was updated during sync → refresh the link
            if ($existingLink && in_array($trip->id, $updatedTripIds)) {
                $refreshed = $shareLinkService->refreshShareLink($trip);
                if ($refreshed) {
                    $linksRefreshed++;
                } else {
                    $linksFailed++;
                }
                continue;
            }

            // Existing active link, not updated → skip
            if ($existingLink) {
                $linksExisting++;
                continue;
            }

            // No link → create new
            $shareLink = $shareLinkService->generateShareLink($trip);
            if ($shareLink) {
                $linksCreated++;
            } else {
                $linksFailed++;
            }
        }

        $customer->update(['pds_last_synced_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Reisen synchronisiert, {$linksCreated} neue Links, {$linksRefreshed} aktualisiert",
            'stats' => [
                'trips_synced' => $syncLog->trips_fetched,
                'trips_created' => $syncLog->trips_created,
                'trips_updated' => $syncLog->trips_updated,
                'links_created' => $linksCreated,
                'links_refreshed' => $linksRefreshed,
                'links_existing' => $linksExisting,
                'links_failed' => $linksFailed,
                'skipped' => $skippedCount,
            ],
            'synced_at' => now()->format('d.m.Y H:i'),
        ]);
    }

    public function updateLink(Request $request, PdsApiService $pdsApi): JsonResponse
    {
        $request->validate([
            'pds_tid' => 'required|string',
            'trip_id' => 'required',
            'trip_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'destinations' => 'nullable|array',
            'nationalities' => 'nullable|array',
            'reference_id' => 'nullable|string|max:128',
            'note' => 'nullable|string',
            'show_country_info' => 'nullable|boolean',
        ]);

        $customer = auth('customer')->user();

        // Verify trip belongs to customer
        $trip = TdTrip::where('id', $request->input('trip_id'))
            ->where('customer_id', $customer->id)
            ->first();

        if (! $trip) {
            return response()->json(['success' => false, 'message' => 'Reise nicht gefunden'], 404);
        }

        // Use external_trip_id (the travel-detail ID from PDS API /travel-details listing)
        $travelDetailId = $trip->external_trip_id;

        if (! $travelDetailId) {
            return response()->json(['success' => false, 'message' => 'Keine Travel Detail ID vorhanden.'], 422);
        }

        // 1. Build API payload (only include fields that were sent)
        // Note: destinations are managed locally only – the PDS API does not accept
        // destination updates via this endpoint (requires pre-stay/follow-up type)
        $apiPayload = [];
        foreach (['trip_name', 'start_date', 'end_date', 'nationalities', 'reference_id', 'note', 'show_country_info'] as $field) {
            if ($request->has($field)) {
                $apiPayload[$field] = $request->input($field);
            }
        }

        Log::info('TravelDataController: Updating travel detail', [
            'travel_detail_id' => $travelDetailId,
            'endpoint' => "/travel-details/{$travelDetailId}",
            'payload' => $apiPayload,
        ]);

        $response = $pdsApi->post($customer, "/travel-details/{$travelDetailId}", $apiPayload);

        if (! $response || ! $response->successful()) {
            $errorBody = $response?->json() ?? [];
            $errorMessage = $errorBody['message'] ?? $errorBody['error'] ?? null;
            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage);
            }
            if (! $errorMessage) {
                $errorMessage = $response ? "Status {$response->status()}" : 'Keine Antwort';
            }

            Log::warning('TravelDataController: PDS update failed', [
                'travel_detail_id' => $travelDetailId,
                'status' => $response?->status(),
                'payload' => $apiPayload,
                'response_body' => $response?->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'API-Fehler: ' . $errorMessage,
            ], 500);
        }

        // 2. Update local td_trips record
        $updateData = [];

        $rawPayload = $trip->raw_payload ?? [];

        if ($request->has('trip_name')) {
            $rawPayload['trip_name'] = $request->input('trip_name');
        }

        if ($request->has('start_date') && $request->input('start_date')) {
            $updateData['computed_start_at'] = Carbon::parse($request->input('start_date'));
        }

        if ($request->has('end_date') && $request->input('end_date')) {
            $updateData['computed_end_at'] = Carbon::parse($request->input('end_date'));
        }

        if ($request->has('destinations')) {
            $updateData['countries_visited'] = $request->input('destinations');
        }

        if ($request->has('note')) {
            $rawPayload['note'] = $request->input('note');
        }

        if ($request->has('show_country_info')) {
            $rawPayload['show_country_info'] = $request->boolean('show_country_info');
        }

        $updateData['raw_payload'] = $rawPayload;

        if ($request->has('reference_id')) {
            $updateData['booking_reference'] = $request->input('reference_id');
        }

        if (! empty($updateData)) {
            $trip->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Travel Link erfolgreich aktualisiert',
        ]);
    }

    public function importJson(Request $request): JsonResponse
    {
        $request->validate([
            'json_payload' => 'required|string',
        ]);

        $payload = json_decode($request->input('json_payload'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Ungültiges JSON: ' . json_last_error_msg(),
            ], 422);
        }

        $customer = auth('customer')->user();

        try {
            $folder = DB::transaction(function () use ($payload, $customer) {
                $tripData = $payload['trip'] ?? $payload;
                $travellers = $tripData['travellers'] ?? [];
                $itinerary = $tripData['itinerary'] ?? [];

                // Determine dates from itinerary
                $dates = $this->extractDatesFromItinerary($itinerary);

                // Extract destination country codes
                $countryCodes = $this->extractCountryCodes($itinerary);

                // Create folder
                $folder = Folder::create([
                    'customer_id' => $customer->id,
                    'folder_number' => Folder::generateFolderNumber($customer->id),
                    'folder_name' => $tripData['booking_reference']
                        ?? $tripData['external_trip_id']
                        ?? ('Reise ' . now()->format('d.m.Y')),
                    'travel_start_date' => $dates['start'],
                    'travel_end_date' => $dates['end'],
                    'destinations_visited' => $countryCodes,
                    'primary_destination' => $countryCodes[0] ?? null,
                    'status' => 'confirmed',
                ]);

                // Create participants from travellers
                foreach ($travellers as $index => $traveller) {
                    $name = $traveller['name'] ?? [];
                    $salutationMap = [
                        'Herr' => 'mr', 'herr' => 'mr', 'Mr' => 'mr', 'mr' => 'mr',
                        'Frau' => 'mrs', 'frau' => 'mrs', 'Mrs' => 'mrs', 'mrs' => 'mrs', 'Ms' => 'mrs', 'ms' => 'mrs',
                        'Kind' => 'child', 'child' => 'child',
                        'Baby' => 'infant', 'infant' => 'infant',
                        'Divers' => 'diverse', 'diverse' => 'diverse',
                    ];
                    $rawSalutation = $name['salutation'] ?? null;
                    $mappedSalutation = $rawSalutation ? ($salutationMap[$rawSalutation] ?? null) : null;

                    FolderParticipant::create([
                        'folder_id' => $folder->id,
                        'customer_id' => $customer->id,
                        'first_name' => $name['first'] ?? 'Person',
                        'last_name' => $name['last'] ?? (string) ($index + 1),
                        'salutation' => $mappedSalutation,
                        'birth_date' => isset($traveller['date_of_birth'])
                            ? Carbon::parse($traveller['date_of_birth'])->toDateString()
                            : null,
                        'nationality' => $traveller['nationality'] ?? null,
                        'email' => $traveller['contact']['email'] ?? null,
                        'phone' => $traveller['contact']['phone'] ?? null,
                    ]);
                }

                // Process itinerary
                foreach ($itinerary as $item) {
                    $type = $item['type'] ?? null;

                    if ($type === 'travel' && isset($item['segments'])) {
                        $this->createFlightItinerary($folder, $customer, $item);
                    } elseif ($type === 'stay') {
                        $this->createHotelItinerary($folder, $customer, $item);
                    }
                }

                // Update statistics
                $folder->updateStatistics();

                return $folder;
            });

            return response()->json([
                'success' => true,
                'message' => 'Reise erfolgreich erstellt',
                'folder_id' => $folder->id,
                'folder_number' => $folder->folder_number,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Import: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function extractDatesFromItinerary(array $itinerary): array
    {
        $start = null;
        $end = null;

        foreach ($itinerary as $item) {
            if ($item['type'] === 'travel' && isset($item['segments'])) {
                foreach ($item['segments'] as $segment) {
                    $depTime = Carbon::parse($segment['departure']['time'] ?? null);
                    $arrTime = Carbon::parse($segment['arrival']['time'] ?? null);
                    if (!$start || $depTime->lt($start)) $start = $depTime;
                    if (!$end || $arrTime->gt($end)) $end = $arrTime;
                }
            } elseif ($item['type'] === 'stay') {
                $checkIn = Carbon::parse($item['check_in'] ?? null);
                $checkOut = Carbon::parse($item['check_out'] ?? null);
                if (!$start || $checkIn->lt($start)) $start = $checkIn;
                if (!$end || $checkOut->gt($end)) $end = $checkOut;
            }
        }

        return [
            'start' => $start?->toDateString(),
            'end' => $end?->toDateString(),
        ];
    }

    protected function extractCountryCodes(array $itinerary): array
    {
        $codes = [];

        foreach ($itinerary as $item) {
            if ($item['type'] === 'travel' && isset($item['segments'])) {
                foreach ($item['segments'] as $segment) {
                    $arrCode = $segment['arrival']['airport']['country_code'] ?? null;
                    if ($arrCode && !in_array(strtoupper($arrCode), $codes)) {
                        $codes[] = strtoupper($arrCode);
                    }
                }
            } elseif ($item['type'] === 'stay') {
                $code = $item['location']['country_code'] ?? null;
                if ($code && !in_array(strtoupper($code), $codes)) {
                    $codes[] = strtoupper($code);
                }
            }
        }

        return $codes;
    }

    protected function createFlightItinerary(Folder $folder, $customer, array $item): void
    {
        $segments = $item['segments'] ?? [];
        $firstSegment = $segments[0] ?? null;
        $lastSegment = end($segments) ?: null;

        if (!$firstSegment) return;

        $itinerary = FolderItinerary::create([
            'folder_id' => $folder->id,
            'customer_id' => $customer->id,
            'start_date' => Carbon::parse($firstSegment['departure']['time']),
            'end_date' => Carbon::parse($lastSegment['arrival']['time']),
        ]);

        $flightService = FolderFlightService::create([
            'folder_id' => $folder->id,
            'itinerary_id' => $itinerary->id,
            'customer_id' => $customer->id,
        ]);

        foreach ($segments as $index => $segmentData) {
            $dep = $segmentData['departure'];
            $arr = $segmentData['arrival'];
            $carrier = $segmentData['marketing_carrier'] ?? [];

            FolderFlightSegment::create([
                'flight_service_id' => $flightService->id,
                'folder_id' => $folder->id,
                'customer_id' => $customer->id,
                'segment_number' => $index + 1,
                'departure_airport_code' => $dep['airport']['code'] ?? null,
                'departure_time' => Carbon::parse($dep['time']),
                'departure_terminal' => $dep['terminal'] ?? null,
                'arrival_airport_code' => $arr['airport']['code'] ?? null,
                'arrival_time' => Carbon::parse($arr['time']),
                'arrival_terminal' => $arr['terminal'] ?? null,
                'airline_code' => $carrier['airline_code'] ?? null,
                'flight_number' => $carrier['flight_number'] ?? null,
            ]);
        }
    }

    protected function createHotelItinerary(Folder $folder, $customer, array $item): void
    {
        $location = $item['location'] ?? [];

        $itinerary = FolderItinerary::create([
            'folder_id' => $folder->id,
            'customer_id' => $customer->id,
            'start_date' => Carbon::parse($item['check_in']),
            'end_date' => Carbon::parse($item['check_out']),
        ]);

        FolderHotelService::create([
            'folder_id' => $folder->id,
            'itinerary_id' => $itinerary->id,
            'customer_id' => $customer->id,
            'hotel_name' => $location['name'] ?? null,
            'country_code' => isset($location['country_code']) ? strtoupper($location['country_code']) : null,
            'check_in_date' => Carbon::parse($item['check_in']),
            'check_out_date' => Carbon::parse($item['check_out']),
        ]);
    }
}
