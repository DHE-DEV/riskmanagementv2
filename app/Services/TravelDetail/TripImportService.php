<?php

namespace App\Services\TravelDetail;

use App\Models\TravelDetail\TdTrip;
use App\Models\TravelDetail\TdAirLeg;
use App\Models\TravelDetail\TdFlightSegment;
use App\Models\TravelDetail\TdStay;
use App\Models\TravelDetail\TdTraveller;
use App\Models\TravelDetail\TdImportLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TripImportService
{
    public function __construct(
        private AirLegBuilder $airLegBuilder,
        private TransferDetector $transferDetector,
        private TripRangeCalculator $rangeCalculator,
        private CountryDeriver $countryDeriver,
        private StayTimelineBuilder $stayBuilder,
        private TripLocationBuilder $locationBuilder,
    ) {}

    /**
     * Import a trip from validated payload
     */
    public function importTrip(array $payload): TdTrip
    {
        $startTime = microtime(true);
        $providerId = $payload['provider']['id'];
        $externalTripId = $payload['trip']['external_trip_id'];

        try {
            $trip = DB::transaction(function () use ($payload) {
                // 1. Upsert main trip record
                $trip = $this->upsertTrip($payload);

                // 2. Process travellers
                $this->processTravellers($trip, $payload['trip']['travellers'] ?? []);

                // 3. Process itinerary items
                $this->processItinerary($trip, $payload['trip']['itinerary'] ?? []);

                // 4. Build air leg summaries
                $this->airLegBuilder->buildForTrip($trip);

                // 5. Detect transfers
                $this->transferDetector->detectForTrip($trip);

                // 6. Calculate trip range (start/end dates)
                $this->rangeCalculator->calculateForTrip($trip);

                // 7. Derive countries visited
                $this->countryDeriver->deriveForTrip($trip);

                // 8. Build stay durations
                $this->stayBuilder->buildForTrip($trip);

                // 9. Build location timeline for geo queries
                $this->locationBuilder->buildForTrip($trip);

                return $trip;
            });

            // Log success
            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $this->logSuccess($providerId, $externalTripId, $trip, $processingTimeMs);

            return $trip->fresh([
                'airLegs.segments',
                'stays',
                'transfers',
            ]);

        } catch (\Exception $e) {
            // Log failure
            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $this->logFailure($providerId, $externalTripId, $e, $payload, $processingTimeMs);

            throw $e;
        }
    }

    /**
     * Upsert the main trip record
     */
    protected function upsertTrip(array $payload): TdTrip
    {
        $provider = $payload['provider'];
        $tripData = $payload['trip'];

        $existingTrip = TdTrip::where('provider_id', $provider['id'])
            ->where('external_trip_id', $tripData['external_trip_id'])
            ->first();

        $isUpdate = $existingTrip !== null;

        $trip = TdTrip::updateOrCreate(
            [
                'provider_id' => $provider['id'],
                'external_trip_id' => $tripData['external_trip_id'],
            ],
            [
                'provider_name' => $provider['name'] ?? null,
                'provider_sent_at' => Carbon::parse($provider['sent_at']),
                'booking_reference' => $tripData['booking_reference'] ?? null,
                'schema_version' => $payload['schema_version'] ?? '1.1',
                'raw_payload' => $payload,
            ]
        );

        if (config('travel_detail.logging.enabled')) {
            Log::channel(config('travel_detail.logging.channel'))
                ->info($isUpdate ? 'Updated trip' : 'Created trip', [
                    'trip_id' => $trip->id,
                    'provider_id' => $provider['id'],
                    'external_trip_id' => $tripData['external_trip_id'],
                ]);
        }

        return $trip;
    }

    /**
     * Process itinerary items
     */
    protected function processItinerary(TdTrip $trip, array $itinerary): void
    {
        // Group itinerary by type
        $travelItems = [];
        $stayItems = [];

        foreach ($itinerary as $item) {
            if ($item['type'] === 'travel') {
                $travelItems[] = $item;
            } elseif ($item['type'] === 'stay') {
                $stayItems[] = $item;
            }
        }

        // Process travel items (air legs)
        $this->processTravelItems($trip, $travelItems);

        // Process stay items
        $this->processStayItems($trip, $stayItems);
    }

    /**
     * Process travel items (air legs and segments)
     */
    protected function processTravelItems(TdTrip $trip, array $travelItems): void
    {
        // Get existing leg IDs
        $existingLegIds = $trip->airLegs()->pluck('leg_id')->toArray();
        $processedLegIds = [];

        foreach ($travelItems as $item) {
            if (($item['mode'] ?? 'air') === 'air' && isset($item['segments'])) {
                $legId = $item['leg_id'];
                $processedLegIds[] = $legId;

                // Upsert air leg
                $airLeg = TdAirLeg::updateOrCreate(
                    [
                        'trip_id' => $trip->id,
                        'leg_id' => $legId,
                    ],
                    [
                        'mode' => $item['mode'] ?? 'air',
                    ]
                );

                // Delete existing segments for this leg and recreate
                $airLeg->segments()->delete();

                // Process segments
                foreach ($item['segments'] as $index => $segmentData) {
                    $this->createSegment($trip, $airLeg, $segmentData, $index);
                }
            }
        }

        // Delete legs that are no longer in the payload
        $legsToDelete = array_diff($existingLegIds, $processedLegIds);
        if (!empty($legsToDelete)) {
            $trip->airLegs()->whereIn('leg_id', $legsToDelete)->delete();
        }
    }

    /**
     * Create a flight segment
     */
    protected function createSegment(TdTrip $trip, TdAirLeg $airLeg, array $segmentData, int $sequence): void
    {
        $departure = $segmentData['departure'];
        $arrival = $segmentData['arrival'];
        $carrier = $segmentData['marketing_carrier'] ?? [];

        // Enrich with geo data from IATA codes
        $depGeo = $this->countryDeriver->getGeoDataForIata($departure['airport']['code']);
        $arrGeo = $this->countryDeriver->getGeoDataForIata($arrival['airport']['code']);

        $segment = TdFlightSegment::create([
            'air_leg_id' => $airLeg->id,
            'trip_id' => $trip->id,
            'segment_id' => $segmentData['segment_id'],
            'sequence_in_leg' => $sequence,

            // Departure
            'departure_airport_code' => $departure['airport']['code'],
            'departure_lat' => $departure['airport']['geocode']['lat'] ?? $depGeo['lat'] ?? null,
            'departure_lng' => $departure['airport']['geocode']['lng'] ?? $depGeo['lng'] ?? null,
            'departure_country_code' => $depGeo['country_code'] ?? null,
            'departure_time' => Carbon::parse($departure['time']),
            'departure_terminal' => $departure['terminal'] ?? null,

            // Arrival
            'arrival_airport_code' => $arrival['airport']['code'],
            'arrival_lat' => $arrival['airport']['geocode']['lat'] ?? $arrGeo['lat'] ?? null,
            'arrival_lng' => $arrival['airport']['geocode']['lng'] ?? $arrGeo['lng'] ?? null,
            'arrival_country_code' => $arrGeo['country_code'] ?? null,
            'arrival_time' => Carbon::parse($arrival['time']),
            'arrival_terminal' => $arrival['terminal'] ?? null,

            // Flight details
            'marketing_airline_code' => $carrier['airline_code'] ?? null,
            'flight_number' => $carrier['flight_number'] ?? null,
            'operating_airline_code' => $segmentData['operating_carrier']['airline_code'] ?? null,

            // Transfer hint
            'transfer_role_hint' => $segmentData['transfer_role_hint'] ?? 'none',

            // Calculate duration
            'duration_minutes' => Carbon::parse($departure['time'])
                ->diffInMinutes(Carbon::parse($arrival['time'])),
        ]);
    }

    /**
     * Process stay items
     */
    protected function processStayItems(TdTrip $trip, array $stayItems): void
    {
        // Get existing stay IDs
        $existingStayIds = $trip->stays()->pluck('stay_id')->toArray();
        $processedStayIds = [];

        foreach ($stayItems as $item) {
            $stayId = $item['stay_id'];
            $processedStayIds[] = $stayId;
            $location = $item['location'] ?? [];

            TdStay::updateOrCreate(
                [
                    'trip_id' => $trip->id,
                    'stay_id' => $stayId,
                ],
                [
                    'stay_type' => $item['stay_type'] ?? 'hotel',
                    'location_name' => $location['name'] ?? null,
                    'giata_id' => $location['giata_id'] ?? null,
                    'lat' => $location['geocode']['lat'] ?? null,
                    'lng' => $location['geocode']['lng'] ?? null,
                    'country_code' => $this->normalizeCountryCode($location['country_code'] ?? null),
                    'check_in' => Carbon::parse($item['check_in']),
                    'check_out' => Carbon::parse($item['check_out']),
                ]
            );
        }

        // Delete stays that are no longer in the payload
        $staysToDelete = array_diff($existingStayIds, $processedStayIds);
        if (!empty($staysToDelete)) {
            $trip->stays()->whereIn('stay_id', $staysToDelete)->delete();
        }
    }

    /**
     * Process travellers
     */
    protected function processTravellers(TdTrip $trip, array $travellers): void
    {
        if (empty($travellers)) {
            return;
        }

        // Get existing traveller IDs
        $existingTravellerIds = $trip->travellers()->pluck('external_traveller_id')->toArray();
        $processedTravellerIds = [];

        foreach ($travellers as $traveller) {
            $externalId = $traveller['external_traveller_id'] ?? null;
            if (!$externalId) {
                continue;
            }

            $processedTravellerIds[] = $externalId;
            $name = $traveller['name'] ?? [];

            TdTraveller::updateOrCreate(
                [
                    'trip_id' => $trip->id,
                    'external_traveller_id' => $externalId,
                ],
                [
                    'traveller_type' => $traveller['type'] ?? 'adult',
                    'first_name' => $name['first'] ?? null,
                    'last_name' => $name['last'] ?? null,
                    'salutation' => $name['salutation'] ?? null,
                    'date_of_birth' => isset($traveller['date_of_birth'])
                        ? Carbon::parse($traveller['date_of_birth'])->toDateString()
                        : null,
                    'nationality' => $this->normalizeCountryCode($traveller['nationality'] ?? null),
                    'email' => $traveller['contact']['email'] ?? null,
                    'phone' => $traveller['contact']['phone'] ?? null,
                    'passport_country' => $this->normalizeCountryCode($traveller['passport']['country'] ?? null),
                    'meta' => $traveller['meta'] ?? null,
                ]
            );
        }

        // Delete travellers that are no longer in the payload
        $travellersToDelete = array_diff($existingTravellerIds, $processedTravellerIds);
        if (!empty($travellersToDelete)) {
            $trip->travellers()->whereIn('external_traveller_id', $travellersToDelete)->delete();
        }
    }

    /**
     * Log successful import
     */
    protected function logSuccess(string $providerId, string $externalTripId, TdTrip $trip, int $processingTimeMs): void
    {
        $existingTrip = TdTrip::where('provider_id', $providerId)
            ->where('external_trip_id', $externalTripId)
            ->where('id', '!=', $trip->id)
            ->exists();

        TdImportLog::logSuccess(
            $providerId,
            $externalTripId,
            $existingTrip ? 'update' : 'create',
            $processingTimeMs,
            [
                'trip_id' => $trip->id,
                'segments_count' => $trip->flightSegments()->count(),
                'stays_count' => $trip->stays()->count(),
            ]
        );
    }

    /**
     * Log failed import
     */
    protected function logFailure(string $providerId, ?string $externalTripId, \Exception $e, array $payload, int $processingTimeMs): void
    {
        TdImportLog::logFailure(
            $providerId,
            $externalTripId,
            $e->getMessage(),
            [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice($e->getTrace(), 0, 5),
            ],
            config('travel_detail.logging.log_payloads') ? $payload : null,
            $processingTimeMs
        );

        if (config('travel_detail.logging.enabled')) {
            Log::channel(config('travel_detail.logging.channel'))
                ->error('Trip import failed', [
                    'provider_id' => $providerId,
                    'external_trip_id' => $externalTripId,
                    'error' => $e->getMessage(),
                ]);
        }
    }

    /**
     * Import a trip from simple format (without itinerary)
     *
     * Creates a minimal trip with:
     * - Basic trip info (dates, name, reference)
     * - Travellers generated from nationalities ("Person 1", "Person 2", etc.)
     * - Countries from destinations
     * - No air legs, stays, or transfers
     */
    public function importFromSimpleFormat(array $payload): TdTrip
    {
        $startTime = microtime(true);

        // Generate provider ID if not present
        $providerId = $payload['provider']['id'] ?? 'direct-import';
        // Use reference, external_trip_id, or trip name - don't auto-generate random IDs
        $externalTripId = $payload['trip']['reference']
            ?? $payload['trip']['external_trip_id']
            ?? $payload['trip']['name']
            ?? null;

        try {
            $trip = DB::transaction(function () use ($payload, $providerId, $externalTripId) {
                // 1. Create/update trip record
                $trip = TdTrip::updateOrCreate(
                    [
                        'provider_id' => $providerId,
                        'external_trip_id' => $externalTripId,
                    ],
                    [
                        'provider_name' => $payload['provider']['name'] ?? null,
                        'provider_sent_at' => isset($payload['provider']['sent_at'])
                            ? Carbon::parse($payload['provider']['sent_at'])
                            : now(),
                        'booking_reference' => $payload['trip']['reference'] ?? null,
                        'schema_version' => $payload['schema_version'] ?? '1.1',
                        'computed_start_at' => isset($payload['trip']['start_date'])
                            ? Carbon::parse($payload['trip']['start_date'])->startOfDay()
                            : null,
                        'computed_end_at' => isset($payload['trip']['end_date'])
                            ? Carbon::parse($payload['trip']['end_date'])->endOfDay()
                            : null,
                        'status' => 'active',
                        'raw_payload' => $payload,
                    ]
                );

                // 2. Derive countries from destinations
                $countries = $this->extractCountriesFromDestinations($payload['destinations'] ?? []);
                $trip->update(['countries_visited' => $countries]);

                // 3. Create travellers from nationalities
                $this->createTravellersFromNationalities($trip, $payload['nationalities'] ?? []);

                return $trip;
            });

            // Log success
            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $this->logSuccess($providerId, $externalTripId, $trip, $processingTimeMs);

            return $trip->fresh(['travellers']);

        } catch (\Exception $e) {
            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $this->logFailure($providerId, $externalTripId, $e, $payload, $processingTimeMs);
            throw $e;
        }
    }

    /**
     * Extract country codes from destinations array
     */
    protected function extractCountriesFromDestinations(array $destinations): array
    {
        $countries = [];

        foreach ($destinations as $dest) {
            $code = null;

            if (is_string($dest)) {
                $code = $dest;
            } elseif (is_array($dest)) {
                $code = $dest['destination'] ?? $dest['code'] ?? null;
            }

            if ($code) {
                $normalized = $this->normalizeCountryCode($code);
                if ($normalized && !in_array($normalized, $countries)) {
                    $countries[] = $normalized;
                }
            }
        }

        return $countries;
    }

    /**
     * Create travellers from nationalities array
     * Each nationality creates a traveller named "Person 1", "Person 2", etc.
     */
    protected function createTravellersFromNationalities(TdTrip $trip, array $nationalities): void
    {
        // Delete existing travellers for this trip (full replacement)
        $trip->travellers()->delete();

        $counter = 1;
        foreach ($nationalities as $nationality) {
            $normalizedNationality = $this->normalizeCountryCode($nationality);

            if ($normalizedNationality) {
                TdTraveller::create([
                    'trip_id' => $trip->id,
                    'external_traveller_id' => 'AUTO-' . $counter,
                    'first_name' => 'Person',
                    'last_name' => (string) $counter,
                    'nationality' => $normalizedNationality,
                    // Don't set traveller_type - let database use default
                ]);
                $counter++;
            }
        }
    }

    /**
     * Get import summary for a trip
     */
    public function getImportSummary(TdTrip $trip): array
    {
        return [
            'trip_id' => $trip->id,
            'provider_id' => $trip->provider_id,
            'external_trip_id' => $trip->external_trip_id,
            'computed_start_at' => $trip->computed_start_at?->toIso8601String(),
            'computed_end_at' => $trip->computed_end_at?->toIso8601String(),
            'duration_days' => $trip->duration_days,
            'countries_visited' => $trip->countries_visited,
            'status' => $trip->status,
            'summary' => [
                'total_legs' => $trip->airLegs()->count(),
                'total_segments' => $trip->flightSegments()->count(),
                'total_stays' => $trip->stays()->count(),
                'total_transfers' => $trip->transfers()->count(),
                'total_nights' => $trip->stays()->sum('duration_nights'),
            ],
        ];
    }

    /**
     * Normalize country code to ISO 3166-1 alpha-2 format
     * Accepts both alpha-2 (DE) and alpha-3 (DEU) codes
     */
    protected function normalizeCountryCode(?string $code): ?string
    {
        if (empty($code)) {
            return null;
        }

        // Already alpha-2
        if (strlen($code) === 2) {
            return strtoupper($code);
        }

        // Convert alpha-3 to alpha-2
        if (strlen($code) === 3) {
            return Cache::remember(
                "td_country_alpha3_to_alpha2_{$code}",
                86400,
                fn () => $this->convertAlpha3ToAlpha2($code)
            );
        }

        return null;
    }

    /**
     * Convert ISO 3166-1 alpha-3 to alpha-2
     */
    protected function convertAlpha3ToAlpha2(string $alpha3): ?string
    {
        // Common country code mappings (alpha-3 => alpha-2)
        $mappings = [
            'DEU' => 'DE', 'USA' => 'US', 'GBR' => 'GB', 'FRA' => 'FR',
            'ESP' => 'ES', 'ITA' => 'IT', 'NLD' => 'NL', 'BEL' => 'BE',
            'AUT' => 'AT', 'CHE' => 'CH', 'POL' => 'PL', 'CZE' => 'CZ',
            'HUN' => 'HU', 'DNK' => 'DK', 'SWE' => 'SE', 'NOR' => 'NO',
            'FIN' => 'FI', 'PRT' => 'PT', 'GRC' => 'GR', 'TUR' => 'TR',
            'RUS' => 'RU', 'UKR' => 'UA', 'JPN' => 'JP', 'CHN' => 'CN',
            'KOR' => 'KR', 'IND' => 'IN', 'AUS' => 'AU', 'NZL' => 'NZ',
            'CAN' => 'CA', 'MEX' => 'MX', 'BRA' => 'BR', 'ARG' => 'AR',
            'ZAF' => 'ZA', 'EGY' => 'EG', 'MAR' => 'MA', 'TUN' => 'TN',
            'ARE' => 'AE', 'SAU' => 'SA', 'ISR' => 'IL', 'THA' => 'TH',
            'SGP' => 'SG', 'MYS' => 'MY', 'IDN' => 'ID', 'PHL' => 'PH',
            'VNM' => 'VN', 'HKG' => 'HK', 'TWN' => 'TW', 'IRL' => 'IE',
            'LUX' => 'LU', 'MCO' => 'MC', 'LIE' => 'LI', 'AND' => 'AD',
            'SVN' => 'SI', 'HRV' => 'HR', 'SRB' => 'RS', 'BGR' => 'BG',
            'ROU' => 'RO', 'SVK' => 'SK', 'LTU' => 'LT', 'LVA' => 'LV',
            'EST' => 'EE', 'MLT' => 'MT', 'CYP' => 'CY', 'ISL' => 'IS',
        ];

        $alpha3Upper = strtoupper($alpha3);

        if (isset($mappings[$alpha3Upper])) {
            return $mappings[$alpha3Upper];
        }

        // Try database lookup via countries table if available
        try {
            $country = DB::table('countries')
                ->where('iso_alpha_3', $alpha3Upper)
                ->value('iso_alpha_2');

            return $country ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
