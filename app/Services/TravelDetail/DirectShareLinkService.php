<?php

namespace App\Services\TravelDetail;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Service for generating PDS share links directly from JSON payload
 * without creating a trip in the database.
 *
 * Supports two payload formats:
 * 1. Simple format: destinations + nationalities
 * 2. Itinerary format: trip.itinerary + trip.travellers (Travel Detail Schema)
 */
class DirectShareLinkService
{
    private string $apiUrl;
    private ?string $apiKey;
    private int $timeout;
    private CountryDeriver $countryDeriver;

    public function __construct(CountryDeriver $countryDeriver)
    {
        $this->apiUrl = config('travel_detail.pds.api_url');
        $this->apiKey = config('travel_detail.pds.api_key');
        $this->timeout = config('travel_detail.pds.timeout', 30);
        $this->countryDeriver = $countryDeriver;
    }

    /**
     * Generate a share link directly from a payload (without database storage)
     *
     * Supports two formats:
     *
     * 1. Simple format (PDS Share-Link Schema):
     * {
     *   "show_country_info": true,
     *   "trip": { "name": "...", "start_date": "...", "end_date": "..." },
     *   "destinations": [{"code": "ES", "type": "travel"}],
     *   "nationalities": ["DE", "AT"]
     * }
     *
     * 2. Itinerary format (Travel Detail Schema):
     * {
     *   "provider": { "id": "...", "sent_at": "..." },
     *   "trip": {
     *     "itinerary": [...],
     *     "travellers": [...]
     *   }
     * }
     */
    public function generateFromPayload(array $payload): array
    {
        if (!$this->apiKey) {
            Log::channel(config('travel_detail.logging.channel', 'stack'))
                ->error('PDS API key not configured for direct share link');
            return [
                'success' => false,
                'error' => 'PDS API nicht konfiguriert',
            ];
        }

        try {
            // Detect format and build PDS payload
            $pdsPayload = $this->detectAndBuildPayload($payload);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiUrl . '/api/v2/information/share', $pdsPayload);

            if (!$response->successful()) {
                Log::channel(config('travel_detail.logging.channel', 'stack'))
                    ->error('PDS Direct Share Link API error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                return [
                    'success' => false,
                    'error' => 'PDS API Fehler: ' . $response->status(),
                    'details' => $response->json(),
                ];
            }

            $data = $response->json();
            $shareUrl = $data['url'] ?? null;

            if (!$shareUrl) {
                return [
                    'success' => false,
                    'error' => 'PDS API hat keine URL zurückgegeben',
                ];
            }

            $tid = $this->extractTidFromUrl($shareUrl);

            Log::channel(config('travel_detail.logging.channel', 'stack'))
                ->info('Generated direct PDS share link', [
                    'tid' => $tid,
                ]);

            return [
                'success' => true,
                'share_url' => $shareUrl,
                'tid' => $tid,
                'formatted_tid' => $this->formatTid($tid),
            ];

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::channel(config('travel_detail.logging.channel', 'stack'))
                ->error('Direct Share Link generation failed', [
                    'error' => $e->getMessage(),
                ]);
            return [
                'success' => false,
                'error' => 'Fehler: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Detect payload format and build PDS API payload
     */
    protected function detectAndBuildPayload(array $payload): array
    {
        // Check if it's the itinerary format (Travel Detail Schema)
        $hasItinerary = isset($payload['trip']['itinerary']) && is_array($payload['trip']['itinerary']);
        $hasTravellers = isset($payload['trip']['travellers']) && is_array($payload['trip']['travellers']);

        if ($hasItinerary || $hasTravellers) {
            return $this->buildFromItineraryFormat($payload);
        }

        // Simple format with destinations and nationalities
        return $this->buildFromSimpleFormat($payload);
    }

    /**
     * Build PDS payload from simple format (destinations + nationalities)
     */
    protected function buildFromSimpleFormat(array $payload): array
    {
        // Validate simple format (flexible: accepts strings or objects for destinations)
        $validator = Validator::make($payload, [
            'show_country_info' => 'sometimes|boolean',
            'trip' => 'required|array',
            'trip.name' => 'sometimes|string|max:255',
            'trip.start_date' => 'required|date',
            'trip.end_date' => 'required|date|after_or_equal:trip.start_date',
            'trip.reference' => 'sometimes|string|max:128',
            'trip.note' => 'sometimes|string',
            'destinations' => 'required|array|min:1',
            'nationalities' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        // Normalize destinations (accepts strings like "US" or objects like {"code": "US"})
        $destinations = $this->normalizeExplicitDestinations($payload['destinations']);

        if (empty($destinations)) {
            throw ValidationException::withMessages([
                'destinations' => ['Ungültige Destinations. Erwartet: ["US", "DE"] oder [{"code": "US"}]'],
            ]);
        }

        // Normalize nationalities
        $nationalities = $this->normalizeExplicitNationalities($payload['nationalities']);

        if (empty($nationalities)) {
            throw ValidationException::withMessages([
                'nationalities' => ['Ungültige Nationalities. Erwartet: ["DE", "US"]'],
            ]);
        }

        $pdsPayload = [
            'show_country_info' => $validated['show_country_info'] ?? true,
            'trip' => array_filter([
                'name' => $validated['trip']['name'] ?? 'Reise',
                'start_date' => $validated['trip']['start_date'],
                'end_date' => $validated['trip']['end_date'],
                'reference' => $validated['trip']['reference'] ?? null,
                'note' => $validated['trip']['note'] ?? null,
            ], fn ($v) => $v !== null),
            'destinations' => $this->deduplicateDestinations($destinations),
            'nationalities' => array_values(array_unique($nationalities)),
        ];

        // Pass through additional PDS API fields
        $pdsPayload = $this->addOptionalPdsFields($pdsPayload, $payload);

        return $pdsPayload;
    }

    /**
     * Build PDS payload from itinerary format (Travel Detail Schema)
     */
    protected function buildFromItineraryFormat(array $payload): array
    {
        // Basic validation for itinerary format
        $validator = Validator::make($payload, [
            'trip' => 'required|array',
            'trip.external_trip_id' => 'sometimes|string',
            'trip.booking_reference' => 'sometimes|string',
            'trip.itinerary' => 'sometimes|array',
            'trip.travellers' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $trip = $payload['trip'];
        $itinerary = $trip['itinerary'] ?? [];
        $travellers = $trip['travellers'] ?? [];

        // Extract destinations from itinerary
        $extractedDestinations = $this->extractDestinationsFromItinerary($itinerary);

        // Merge with explicitly provided destinations (if any)
        $explicitDestinations = $this->normalizeExplicitDestinations($payload['destinations'] ?? []);
        $destinations = $this->mergeDestinations($extractedDestinations, $explicitDestinations);

        // Extract nationalities from travellers
        $extractedNationalities = $this->extractNationalitiesFromTravellers($travellers);

        // Merge with explicitly provided nationalities (if any)
        $explicitNationalities = $this->normalizeExplicitNationalities($payload['nationalities'] ?? []);
        $nationalities = array_values(array_unique(array_merge($extractedNationalities, $explicitNationalities)));

        // Calculate trip dates from itinerary (or use explicit dates if provided)
        $dates = $this->calculateTripDates($itinerary);
        if (!empty($trip['start_date'])) {
            $dates['start'] = $trip['start_date'];
        }
        if (!empty($trip['end_date'])) {
            $dates['end'] = $trip['end_date'];
        }

        // Generate trip name (or use explicit name if provided)
        $tripName = $trip['name'] ?? $this->generateTripName($trip, $itinerary);

        // Ensure we have data
        if (empty($destinations)) {
            throw ValidationException::withMessages([
                'destinations' => ['Keine Reiseziele gefunden. Bitte mindestens einen Flug, Aufenthalt oder destinations angeben.'],
            ]);
        }

        if (empty($nationalities)) {
            throw ValidationException::withMessages([
                'nationalities' => ['Keine Nationalitäten gefunden. Bitte Reisende mit Nationalität oder nationalities angeben.'],
            ]);
        }

        // Build base payload
        $pdsPayload = [
            'show_country_info' => $payload['show_country_info'] ?? true,
            'trip' => [
                'name' => $tripName,
                'start_date' => $dates['start'],
                'end_date' => $dates['end'],
                'reference' => $trip['reference'] ?? $trip['booking_reference'] ?? $trip['external_trip_id'] ?? null,
                'note' => $trip['note'] ?? null,
            ],
            'destinations' => $destinations,
            'nationalities' => $nationalities,
        ];

        // Pass through additional PDS API fields
        $pdsPayload = $this->addOptionalPdsFields($pdsPayload, $payload);

        // Remove null values from trip
        $pdsPayload['trip'] = array_filter($pdsPayload['trip'], fn ($v) => $v !== null);

        return $pdsPayload;
    }

    /**
     * Normalize explicitly provided destinations
     *
     * Accepts multiple formats:
     * - String: "ES"
     * - Object with code: {"code": "ES", "type": "travel"}
     * - Object with destination: {"destination": "ES", "type": "transit"}
     */
    protected function normalizeExplicitDestinations(array $destinations): array
    {
        $normalized = [];

        foreach ($destinations as $dest) {
            if (is_string($dest)) {
                // Simple string like "ES" or "st"
                $code = $this->normalizeCountryCode($dest);
                if (strlen($code) === 2) {
                    $normalized[] = ['destination' => $code, 'type' => 'travel'];
                }
            } elseif (is_array($dest)) {
                // Object with "code" or "destination" key
                $code = $dest['code'] ?? $dest['destination'] ?? null;
                if ($code) {
                    $normalized[] = [
                        'destination' => $this->normalizeCountryCode($code),
                        'type' => $dest['type'] ?? 'travel',
                    ];
                }
            }
        }

        return $normalized;
    }

    /**
     * Normalize explicitly provided nationalities
     */
    protected function normalizeExplicitNationalities(array $nationalities): array
    {
        return array_filter(array_map(function ($nat) {
            if (is_string($nat)) {
                $normalized = $this->normalizeCountryCode($nat);
                return strlen($normalized) === 2 ? $normalized : null;
            }
            return null;
        }, $nationalities));
    }

    /**
     * Merge extracted and explicit destinations
     */
    protected function mergeDestinations(array $extracted, array $explicit): array
    {
        $merged = array_merge($extracted, $explicit);
        return $this->deduplicateDestinations($merged);
    }

    /**
     * Add optional PDS API fields to payload
     */
    protected function addOptionalPdsFields(array $pdsPayload, array $sourcePayload): array
    {
        // List of optional PDS API fields to pass through
        $optionalFields = [
            'language',
            'states',
            'tour_operators',
            'individual_contents',
            'cover_media',
            'subscribe',
        ];

        foreach ($optionalFields as $field) {
            if (isset($sourcePayload[$field]) && $sourcePayload[$field] !== null) {
                $pdsPayload[$field] = $sourcePayload[$field];
            }
        }

        return $pdsPayload;
    }

    /**
     * Extract destinations from itinerary items
     */
    protected function extractDestinationsFromItinerary(array $itinerary): array
    {
        $destinations = [];

        foreach ($itinerary as $item) {
            $type = $item['type'] ?? null;

            if ($type === 'travel' && isset($item['segments'])) {
                // Extract from flight segments
                foreach ($item['segments'] as $segment) {
                    // Arrival airport
                    if (isset($segment['arrival']['airport']['code'])) {
                        $airportCode = $segment['arrival']['airport']['code'];
                        $countryCode = $segment['arrival']['airport']['country_code']
                            ?? $this->getCountryFromAirport($airportCode);

                        if ($countryCode) {
                            $destinations[] = [
                                'destination' => $this->normalizeCountryCode($countryCode),
                                'type' => 'travel',
                            ];
                        }
                    }
                }
            } elseif ($type === 'stay') {
                // Extract from stay
                $countryCode = $item['location']['country_code'] ?? null;
                if ($countryCode) {
                    $destinations[] = [
                        'destination' => $this->normalizeCountryCode($countryCode),
                        'type' => 'travel',
                    ];
                }
            }
        }

        return $this->deduplicateDestinations($destinations);
    }

    /**
     * Extract nationalities from travellers
     */
    protected function extractNationalitiesFromTravellers(array $travellers): array
    {
        $nationalities = [];

        foreach ($travellers as $traveller) {
            $nationality = $traveller['nationality'] ?? $traveller['passport']['country'] ?? null;

            if ($nationality) {
                $nationalities[] = $this->normalizeCountryCode($nationality);
            }
        }

        return array_values(array_unique($nationalities));
    }

    /**
     * Calculate trip start and end dates from itinerary
     */
    protected function calculateTripDates(array $itinerary): array
    {
        $timestamps = [];

        foreach ($itinerary as $item) {
            $type = $item['type'] ?? null;

            if ($type === 'travel' && isset($item['segments'])) {
                foreach ($item['segments'] as $segment) {
                    if (isset($segment['departure']['time'])) {
                        $timestamps[] = Carbon::parse($segment['departure']['time']);
                    }
                    if (isset($segment['arrival']['time'])) {
                        $timestamps[] = Carbon::parse($segment['arrival']['time']);
                    }
                }
            } elseif ($type === 'stay') {
                if (isset($item['check_in'])) {
                    $timestamps[] = Carbon::parse($item['check_in']);
                }
                if (isset($item['check_out'])) {
                    $timestamps[] = Carbon::parse($item['check_out']);
                }
            }
        }

        if (empty($timestamps)) {
            // Default to today + 7 days
            return [
                'start' => now()->format('Y-m-d'),
                'end' => now()->addDays(7)->format('Y-m-d'),
            ];
        }

        $start = min($timestamps);
        $end = max($timestamps);

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];
    }

    /**
     * Generate a trip name based on available data
     */
    protected function generateTripName(array $trip, array $itinerary): string
    {
        // Try booking reference first
        if (!empty($trip['booking_reference'])) {
            return "Reise {$trip['booking_reference']}";
        }

        // Try to build from first and last destinations
        $firstAirport = null;
        $lastAirport = null;

        foreach ($itinerary as $item) {
            if (($item['type'] ?? null) === 'travel' && isset($item['segments'])) {
                foreach ($item['segments'] as $segment) {
                    if (!$firstAirport && isset($segment['departure']['airport']['code'])) {
                        $firstAirport = $segment['departure']['airport']['code'];
                    }
                    if (isset($segment['arrival']['airport']['code'])) {
                        $lastAirport = $segment['arrival']['airport']['code'];
                    }
                }
            }
        }

        if ($firstAirport && $lastAirport && $firstAirport !== $lastAirport) {
            return "Reise {$firstAirport} - {$lastAirport}";
        }

        if (!empty($trip['external_trip_id'])) {
            return "Reise {$trip['external_trip_id']}";
        }

        return 'Reise';
    }

    /**
     * Get country code from IATA airport code
     */
    protected function getCountryFromAirport(string $airportCode): ?string
    {
        return $this->countryDeriver->getCountryForIata($airportCode);
    }

    /**
     * Remove duplicate destinations (by destination code)
     */
    protected function deduplicateDestinations(array $destinations): array
    {
        $unique = [];
        foreach ($destinations as $dest) {
            $key = $dest['destination'] ?? $dest['code'] ?? null;
            if ($key && !isset($unique[$key])) {
                $unique[$key] = $dest;
            }
        }
        return array_values($unique);
    }

    /**
     * Normalize 3-letter country codes to 2-letter ISO codes
     */
    protected function normalizeCountryCode(string $code): string
    {
        // Already 2-letter code
        if (strlen($code) === 2) {
            return strtoupper($code);
        }

        // 3-letter to 2-letter mapping
        $mapping = [
            'DEU' => 'DE', 'AUT' => 'AT', 'CHE' => 'CH',
            'ESP' => 'ES', 'FRA' => 'FR', 'ITA' => 'IT',
            'GBR' => 'GB', 'USA' => 'US', 'NLD' => 'NL',
            'BEL' => 'BE', 'POL' => 'PL', 'CZE' => 'CZ',
            'PRT' => 'PT', 'GRC' => 'GR', 'TUR' => 'TR',
            'HRV' => 'HR', 'HUN' => 'HU', 'DNK' => 'DK',
            'SWE' => 'SE', 'NOR' => 'NO', 'FIN' => 'FI',
        ];

        $upper = strtoupper($code);
        return $mapping[$upper] ?? substr($upper, 0, 2);
    }

    /**
     * Extract TID from share URL
     */
    protected function extractTidFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        parse_str($parsed['query'] ?? '', $query);

        return $query['tid'] ?? $this->generateLocalTid();
    }

    /**
     * Generate a local TID if extraction fails
     */
    protected function generateLocalTid(): string
    {
        $random = strtoupper(\Illuminate\Support\Str::random(12));
        return substr($random, 0, 4) . '-' . substr($random, 4, 4) . '-' . substr($random, 8, 4);
    }

    /**
     * Format TID with dashes for display
     */
    protected function formatTid(string $tid): string
    {
        if (str_contains($tid, '-')) {
            return $tid;
        }

        if (strlen($tid) === 12) {
            return substr($tid, 0, 4) . '-' . substr($tid, 4, 4) . '-' . substr($tid, 8, 4);
        }

        return $tid;
    }

    /**
     * Check if the service is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiUrl);
    }
}
