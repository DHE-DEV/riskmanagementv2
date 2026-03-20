<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Folder\Folder;
use App\Models\Folder\FolderItinerary;
use App\Models\Folder\FolderFlightService;
use App\Models\Folder\FolderFlightSegment;
use App\Models\Folder\FolderHotelService;
use App\Models\Folder\FolderParticipant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TravelDataController extends Controller
{
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
