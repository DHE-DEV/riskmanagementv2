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

        $query = Folder::with(['participants', 'itineraries', 'flightServices.segments', 'hotelServices', 'shipServices', 'carRentalServices'])
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

        // Resolve country names for destinations and nationalities
        $allCodes = collect();
        foreach ($folders as $folder) {
            $allCodes = $allCodes->merge($folder->destinations_visited ?? []);
            foreach ($folder->participants as $p) {
                if ($p->nationality) {
                    $allCodes->push(strtoupper($p->nationality));
                }
            }
        }
        $countryNames = [];
        if ($allCodes->isNotEmpty()) {
            $countryNames = \App\Models\Country::whereIn('iso_code', $allCodes->unique()->toArray())
                ->get()
                ->mapWithKeys(fn ($c) => [$c->iso_code => $c->getName('de')])
                ->toArray();
        }

        // Transform response to include resolved names
        $folders->getCollection()->transform(function ($folder) use ($countryNames) {
            $folder->destinations_resolved = collect($folder->destinations_visited ?? [])
                ->map(fn ($code) => ['code' => $code, 'name' => $countryNames[$code] ?? $code])
                ->values()
                ->toArray();

            $folder->nationalities_resolved = $folder->participants
                ->pluck('nationality')
                ->filter()
                ->unique()
                ->map(fn ($code) => ['code' => strtoupper($code), 'name' => $countryNames[strtoupper($code)] ?? strtoupper($code)])
                ->values()
                ->toArray();

            return $folder;
        });

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
        $trips = $query->paginate($perPage);

        // Pre-load country names for all trips
        $allCodes = $trips->getCollection()->flatMap(fn ($t) => $t->countries_visited ?? [])
            ->merge($trips->getCollection()->flatMap(fn ($t) => $t->nationalities ?? []))
            ->unique()->values()->toArray();

        $countryNames = \App\Models\Country::whereIn('iso_code', $allCodes)
            ->pluck('name_translations', 'iso_code')
            ->map(fn ($tr) => $tr['de'] ?? $tr['en'] ?? null)
            ->toArray();

        // Enrich trips with resolved names
        $trips->getCollection()->transform(function ($trip) use ($countryNames) {
            $trip->destinations = collect($trip->countries_visited ?? [])
                ->map(fn ($code) => ['code' => strtoupper($code), 'name' => $countryNames[strtoupper($code)] ?? strtoupper($code)])
                ->values();

            $trip->nationalities_resolved = collect($trip->nationalities ?? [])
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

    public function syncLinks(PdsTripSyncService $syncService, PdsApiService $pdsApi): JsonResponse
    {
        $customer = auth('customer')->user();

        // DEBUG MODE: Show raw API request/response without syncing
        if (filter_var(env('TD_LINK_SYNC_DEBUG', false), FILTER_VALIDATE_BOOLEAN)) {
            $updatedSince = $customer->pds_last_synced_at;
            $baseUrl = config('services.pds_api.base_url', 'https://api.passolution.eu/api/v2');
            $endpoint = '/travel-details?__with=__cruise-info';
            $fullUrl = rtrim($baseUrl, '/') . $endpoint;

            $requestBody = [
                'sort_by' => $updatedSince ? 'updated_at' : 'start_date',
                'sort_order' => $updatedSince ? 'asc' : 'desc',
                'page' => 1,
                'per_page' => 100,
            ];

            if ($updatedSince) {
                $requestBody['updated_at'] = ['>' => $updatedSince->format('Y-m-d')];
            }

            $response = $pdsApi->post($customer, $endpoint, $requestBody);

            return response()->json([
                'success' => true,
                'debug' => true,
                'message' => 'Sync Debug-Modus: API-Call angezeigt, nicht synchronisiert',
                'api_request' => [
                    'method' => 'POST',
                    'url' => $fullUrl,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $customer->getActiveApiToken(),
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'body' => $requestBody,
                ],
                'api_response' => [
                    'status' => $response?->status(),
                    'body' => $response?->json(),
                ],
            ]);
        }

        // 1. Sync trips from PDS API (delta if previously synced)
        $updatedSince = $customer->pds_last_synced_at;
        $syncLog = $syncService->syncCustomer($customer, $updatedSince);

        if ($syncLog->status === 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Synchronisierung fehlgeschlagen: ' . $syncLog->error_message,
            ], 500);
        }

        // 2. Build share URLs from TID for all synced trips that have a pds_tid
        $travelDetailsBase = rtrim(env('PASSOLUTION_TRAVEL_DETAILS_LINK', 'https://travel-details.eu'), '/');
        $allTrips = TdTrip::where('customer_id', $customer->id)->get();
        $linksGenerated = 0;

        foreach ($allTrips as $trip) {
            if ($trip->pds_tid && ! $trip->pds_share_url) {
                $trip->update([
                    'pds_share_url' => $travelDetailsBase . '/de?tid=' . $trip->pds_tid,
                ]);
                $linksGenerated++;
            }
        }

        $customer->update(['pds_last_synced_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Reisen synchronisiert, {$linksGenerated} neue Links",
            'stats' => [
                'trips_synced' => $syncLog->trips_fetched,
                'trips_created' => $syncLog->trips_created,
                'trips_updated' => $syncLog->trips_updated,
                'links_created' => $linksGenerated,
                'links_existing' => $allTrips->count() - $linksGenerated,
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

        // Use external_trip_id (the original travel-detail TID from PDS API)
        $travelDetailId = $trip->external_trip_id;

        if (! $travelDetailId) {
            return response()->json(['success' => false, 'message' => 'Keine Travel Detail ID vorhanden.'], 422);
        }

        // 1. Build API payload (only fields the PDS API accepts)
        // destinations are managed locally only
        $apiPayload = [];
        foreach (['trip_name', 'nationalities', 'reference_id', 'note', 'show_country_info'] as $field) {
            if ($request->has($field)) {
                $apiPayload[$field] = $request->input($field);
            }
        }
        // Send destinations as simple array of country codes
        if ($request->has('destinations') && is_array($request->input('destinations'))) {
            $apiPayload['destinations'] = collect($request->input('destinations'))
                ->map(fn ($code) => strtoupper($code))
                ->values()
                ->toArray();
        }
        // Ensure dates are always sent as Y-m-d
        if ($request->has('start_date') && $request->input('start_date')) {
            $apiPayload['start_date'] = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        }
        if ($request->has('end_date') && $request->input('end_date')) {
            $apiPayload['end_date'] = Carbon::parse($request->input('end_date'))->format('Y-m-d');
        }

        $baseUrl = config('services.pds_api.base_url', 'https://api.passolution.eu/api/v2');
        $fullUrl = rtrim($baseUrl, '/') . "/travel-details/{$travelDetailId}";

        // DEBUG MODE: Show what would be sent without executing
        if (filter_var(env('TD_LINK_UPDATE_DEBUG', false), FILTER_VALIDATE_BOOLEAN)) {
            return response()->json([
                'success' => true,
                'debug' => true,
                'message' => 'Debug-Modus: API-Call nicht ausgeführt',
                'api_request' => [
                    'method' => 'POST',
                    'url' => $fullUrl,
                    'headers' => [
                        'Authorization' => 'Bearer ' . substr($customer->getActiveApiToken(), 0, 20) . '...',
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'body' => $apiPayload,
                ],
            ]);
        }

        Log::info('TravelDataController: Updating travel detail', [
            'travel_detail_id' => $travelDetailId,
            'endpoint' => "/travel-details/{$travelDetailId}",
            'payload' => $apiPayload,
        ]);

        $response = $pdsApi->post($customer, "/travel-details/{$travelDetailId}", $apiPayload);

        if (! $response || ! $response->successful()) {
            $errorBody = $response?->json() ?? [];

            Log::warning('TravelDataController: PDS update failed', [
                'travel_detail_id' => $travelDetailId,
                'status' => $response?->status(),
                'payload' => $apiPayload,
                'response_body' => $response?->body(),
            ]);

            // User-friendly error messages for common validation errors
            if ($response?->status() === 422) {
                $fieldErrors = collect($errorBody)
                    ->except(['requestid', 'responsetime', 'message', 'errors'])
                    ->merge($errorBody['errors'] ?? []);

                $fieldLabels = [
                    'start_date' => 'Beginn der Reise',
                    'end_date' => 'Ende der Reise',
                    'trip_name' => 'Reisename',
                    'nationalities' => 'Nationalitäten',
                    'reference_id' => 'Referenz-ID',
                    'note' => 'Notiz',
                ];

                $messages = [];
                foreach ($fieldErrors as $field => $errs) {
                    $errList = is_array($errs) ? $errs : [$errs];
                    foreach ($errList as $err) {
                        // Replace field name with German label (both underscore and space variants)
                        $label = $fieldLabels[$field] ?? $field;
                        $err = str_replace([$field, str_replace('_', ' ', $field)], [$label, $label], $err);
                        // Convert dates from Y-m-d to d.m.Y
                        $err = preg_replace_callback('/(\d{4})-(\d{2})-(\d{2})/', fn ($m) => "{$m[3]}.{$m[2]}.{$m[1]}", $err);
                        $messages[] = $err;
                    }
                }

                $userMessage = ! empty($messages)
                    ? implode(' ', $messages)
                    : 'Die eingegebenen Daten konnten nicht verarbeitet werden.';

                return response()->json([
                    'success' => false,
                    'message' => $userMessage . ' Diese Einschränkung wird systemseitig durch den Travel Requirements Service vorgegeben. Bei Rückfragen wenden Sie sich bitte an das Passolution-Team.',
                ], 422);
            }

            $errorMessage = $errorBody['message'] ?? $errorBody['error'] ?? null;
            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage);
            }
            if (! $errorMessage) {
                $errorMessage = $response ? "Status {$response->status()}" : 'Keine Antwort vom Server';
            }

            return response()->json([
                'success' => false,
                'message' => 'Die Änderung konnte nicht gespeichert werden: ' . $errorMessage,
            ], 500);
        }

        // 2. Update local td_trips record with API response and form data
        $responseData = $response->json() ?? [];
        $updateData = [];
        $rawPayload = $trip->raw_payload ?? [];

        if ($request->has('trip_name')) {
            $updateData['trip_name'] = $request->input('trip_name');
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

        if ($request->has('nationalities')) {
            $updateData['nationalities'] = $request->input('nationalities');
            $rawPayload['nationalities'] = $request->input('nationalities');
        }

        if ($request->has('reference_id')) {
            $updateData['booking_reference'] = $request->input('reference_id');
            $updateData['reference_id'] = $request->input('reference_id');
            $rawPayload['reference_id'] = $request->input('reference_id');
        }

        if ($request->has('note')) {
            $updateData['note'] = $request->input('note');
            $rawPayload['note'] = $request->input('note');
        }

        if ($request->has('show_country_info')) {
            $rawPayload['show_country_info'] = $request->boolean('show_country_info');
        }

        $updateData['raw_payload'] = $rawPayload;
        $updateData['last_important_change_at'] = now();

        $trip->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Travel Link erfolgreich aktualisiert',
        ]);
    }

    public function deleteAllLinks(): JsonResponse
    {
        $customer = auth('customer')->user();

        $tripsDeleted = TdTrip::where('customer_id', $customer->id)->forceDelete();

        $customer->update(['pds_last_synced_at' => null]);

        return response()->json([
            'success' => true,
            'message' => "{$tripsDeleted} Reisen und zugehörige Links lokal gelöscht",
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

    public function showFolder(int $id): JsonResponse
    {
        $customer = auth('customer')->user();
        $folder = Folder::where('customer_id', $customer->id)
            ->with(['participants', 'flightServices.segments', 'hotelServices'])
            ->findOrFail($id);

        return response()->json(['folder' => $folder]);
    }

    public function updateFolder(Request $request, int $id): JsonResponse
    {
        $customer = auth('customer')->user();
        $folder = Folder::where('customer_id', $customer->id)->findOrFail($id);

        return DB::transaction(function () use ($request, $folder, $customer) {
            $data = $request->validate([
                'booking_reference' => 'nullable|string|max:50',
                'travellers' => 'required|array|min:1',
                'travellers.*.salutation' => 'required|string',
                'travellers.*.first_name' => 'required|string|max:255',
                'travellers.*.last_name' => 'required|string|max:255',
                'travellers.*.dob' => 'nullable|date',
                'travellers.*.nationality' => 'nullable|string|max:2',
                'travellers.*.email' => 'nullable|email|max:255',
                'travellers.*.phone' => 'nullable|string|max:50',
                'flights' => 'nullable|array',
                'flights.*.dep_code' => 'required|string|max:3',
                'flights.*.dep_time' => 'required|string',
                'flights.*.arr_code' => 'required|string|max:3',
                'flights.*.arr_time' => 'required|string',
                'flights.*.airline' => 'nullable|string|max:2',
                'flights.*.flight_nr' => 'nullable|string|max:10',
                'flights.*.dep_terminal' => 'nullable|string|max:10',
                'flights.*.arr_terminal' => 'nullable|string|max:10',
                'hotels' => 'nullable|array',
                'hotels.*.name' => 'required|string|max:255',
                'hotels.*.check_in' => 'required|string',
                'hotels.*.check_out' => 'required|string',
                'hotels.*.country' => 'required|string|max:2',
                'hotels.*.city' => 'nullable|string|max:255',
                'hotels.*.room_type' => 'nullable|string|max:50',
                'hotels.*.board' => 'nullable|string|max:10',
            ]);

            // Update participants
            $folder->participants()->delete();
            $salutationMap = ['Herr' => 'mr', 'Frau' => 'mrs', 'Kind' => 'child', 'Baby' => 'infant', 'Divers' => 'diverse'];
            foreach ($data['travellers'] as $t) {
                FolderParticipant::create([
                    'folder_id' => $folder->id,
                    'customer_id' => $customer->id,
                    'salutation' => $salutationMap[$t['salutation']] ?? 'mr',
                    'first_name' => $t['first_name'],
                    'last_name' => $t['last_name'],
                    'birth_date' => $t['dob'] ?? null,
                    'nationality' => $t['nationality'] ?? null,
                    'email' => $t['email'] ?? null,
                    'phone' => $t['phone'] ?? null,
                ]);
            }

            // Update itinerary: delete old services, rebuild
            $folder->flightServices()->each(fn ($fs) => $fs->segments()->delete());
            $folder->flightServices()->delete();
            $folder->hotelServices()->delete();

            $itinerary = $folder->itineraries()->first();
            if (!$itinerary) {
                $itinerary = FolderItinerary::create([
                    'folder_id' => $folder->id,
                    'customer_id' => $customer->id,
                    'booking_reference' => $data['booking_reference'] ?? null,
                    'start_date' => $folder->travel_start_date,
                    'end_date' => $folder->travel_end_date,
                ]);
            }

            $destinations = [];
            $dates = [];

            foreach ($data['flights'] ?? [] as $f) {
                $depTime = Carbon::parse($f['dep_time']);
                $arrTime = Carbon::parse($f['arr_time']);
                $dates[] = $depTime;
                $dates[] = $arrTime;

                $flightService = FolderFlightService::create([
                    'itinerary_id' => $itinerary->id,
                    'folder_id' => $folder->id,
                    'customer_id' => $customer->id,
                    'departure_time' => $depTime,
                    'arrival_time' => $arrTime,
                    'origin_airport_code' => strtoupper($f['dep_code']),
                    'destination_airport_code' => strtoupper($f['arr_code']),
                ]);

                FolderFlightSegment::create([
                    'flight_service_id' => $flightService->id,
                    'folder_id' => $folder->id,
                    'customer_id' => $customer->id,
                    'segment_number' => 1,
                    'departure_airport_code' => strtoupper($f['dep_code']),
                    'departure_time' => $depTime,
                    'departure_terminal' => $f['dep_terminal'] ?? null,
                    'arrival_airport_code' => strtoupper($f['arr_code']),
                    'arrival_time' => $arrTime,
                    'arrival_terminal' => $f['arr_terminal'] ?? null,
                    'airline_code' => isset($f['airline']) ? strtoupper($f['airline']) : null,
                    'flight_number' => $f['flight_nr'] ?? null,
                ]);
            }

            foreach ($data['hotels'] ?? [] as $h) {
                $checkIn = Carbon::parse($h['check_in']);
                $checkOut = Carbon::parse($h['check_out']);
                $dates[] = $checkIn;
                $dates[] = $checkOut;
                $cc = strtoupper($h['country']);
                $destinations[] = $cc;

                FolderHotelService::create([
                    'itinerary_id' => $itinerary->id,
                    'folder_id' => $folder->id,
                    'customer_id' => $customer->id,
                    'hotel_name' => $h['name'],
                    'country_code' => $cc,
                    'city' => $h['city'] ?? null,
                    'check_in_date' => $checkIn,
                    'check_out_date' => $checkOut,
                    'room_type' => $h['room_type'] ?? null,
                    'board_type' => $h['board'] ?? null,
                ]);
            }

            // Update folder dates and destinations
            if (!empty($dates)) {
                $folder->travel_start_date = collect($dates)->min();
                $folder->travel_end_date = collect($dates)->max();
            }
            if (!empty($destinations)) {
                $existing = $folder->destinations_visited ?? [];
                $folder->destinations_visited = array_values(array_unique(array_merge($existing, $destinations)));
            }
            $folder->total_participants = count($data['travellers']);
            if ($data['booking_reference'] ?? null) {
                $itinerary->update(['booking_reference' => $data['booking_reference']]);
            }
            $folder->save();

            return response()->json(['success' => true, 'message' => 'Reise erfolgreich aktualisiert.']);
        });
    }

    public function deleteFolder(int $id): JsonResponse
    {
        $customer = auth('customer')->user();
        $folder = Folder::where('customer_id', $customer->id)->findOrFail($id);

        DB::transaction(function () use ($folder) {
            $folder->flightServices()->each(fn ($fs) => $fs->segments()->delete());
            $folder->flightServices()->delete();
            $folder->hotelServices()->delete();
            $folder->participants()->delete();
            $folder->itineraries()->delete();
            $folder->delete();
        });

        return response()->json(['success' => true, 'message' => 'Reise erfolgreich gelöscht.']);
    }
}
