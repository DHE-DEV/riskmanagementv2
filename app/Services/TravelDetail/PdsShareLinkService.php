<?php

namespace App\Services\TravelDetail;

use App\Models\TravelDetail\TdTrip;
use App\Models\TravelDetail\TdPdsShareLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PdsShareLinkService
{
    private string $apiUrl;
    private ?string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->apiUrl = config('travel_detail.pds.api_url');
        $this->apiKey = config('travel_detail.pds.api_key');
        $this->timeout = config('travel_detail.pds.timeout', 30);
    }

    /**
     * Generate a share link for a trip via PDS API
     */
    public function generateShareLink(TdTrip $trip): ?TdPdsShareLink
    {
        if (!$this->apiKey) {
            Log::channel(config('travel_detail.logging.channel'))
                ->error('PDS API key not configured');
            return null;
        }

        try {
            $payload = $this->buildSharePayload($trip);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiUrl . '/api/v2/information/share', $payload);

            if (!$response->successful()) {
                Log::channel(config('travel_detail.logging.channel'))
                    ->error('PDS Share Link API error', [
                        'trip_id' => $trip->id,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                return null;
            }

            $data = $response->json();
            $shareUrl = $data['url'] ?? null;

            if (!$shareUrl) {
                Log::channel(config('travel_detail.logging.channel'))
                    ->error('PDS API returned no URL', [
                        'trip_id' => $trip->id,
                        'response' => $data,
                    ]);
                return null;
            }

            // Extract TID from URL
            $tid = $this->extractTidFromUrl($shareUrl);

            // Create share link record
            $shareLink = TdPdsShareLink::create([
                'trip_id' => $trip->id,
                'share_url' => $shareUrl,
                'tid' => $tid,
                'created_at' => now(),
                'expires_at' => isset($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at']) : null,
            ]);

            // Update trip with share link info
            $trip->update([
                'pds_share_url' => $shareUrl,
                'pds_tid' => $tid,
                'pds_share_created_at' => now(),
            ]);

            Log::channel(config('travel_detail.logging.channel'))
                ->info('Generated PDS share link', [
                    'trip_id' => $trip->id,
                    'tid' => $tid,
                ]);

            return $shareLink;

        } catch (\Exception $e) {
            Log::channel(config('travel_detail.logging.channel'))
                ->error('PDS Share Link generation failed', [
                    'trip_id' => $trip->id,
                    'error' => $e->getMessage(),
                ]);
            return null;
        }
    }

    /**
     * Build the payload for the PDS share API
     */
    protected function buildSharePayload(TdTrip $trip): array
    {
        $payload = [
            'show_country_info' => true,
            'trip' => array_filter([
                'name' => $this->generateTripName($trip),
                'start_date' => $trip->computed_start_at?->format('Y-m-d'),
                'end_date' => $trip->computed_end_at?->format('Y-m-d'),
                'reference' => $trip->booking_reference,
            ], fn($v) => $v !== null),
            'destinations' => $this->buildDestinations($trip),
            'nationalities' => $this->buildNationalities($trip),
        ];

        return $payload;
    }

    /**
     * Build nationalities array from travellers
     */
    protected function buildNationalities(TdTrip $trip): array
    {
        $nationalities = $trip->travellers()
            ->whereNotNull('nationality')
            ->pluck('nationality')
            ->unique()
            ->values()
            ->toArray();

        // Fallback to DE if no nationalities found
        return !empty($nationalities) ? $nationalities : ['DE'];
    }

    /**
     * Generate a trip name based on the itinerary
     */
    protected function generateTripName(TdTrip $trip): string
    {
        // Check if trip name was provided in raw payload
        $rawPayload = $trip->raw_payload;
        if (!empty($rawPayload['trip']['name'])) {
            return $rawPayload['trip']['name'];
        }

        $firstLeg = $trip->airLegs()->first();
        $lastLeg = $trip->airLegs()->orderByDesc('leg_end_at')->first();

        if ($firstLeg && $lastLeg) {
            $origin = $firstLeg->origin_airport_code ?? '?';
            $destination = $lastLeg->destination_airport_code ?? '?';
            return "Reise {$origin} - {$destination}";
        }

        if ($trip->booking_reference) {
            return "Reise {$trip->booking_reference}";
        }

        if ($trip->external_trip_id) {
            return "Reise {$trip->external_trip_id}";
        }

        return "Reise";
    }

    /**
     * Build destinations array from trip countries
     */
    protected function buildDestinations(TdTrip $trip): array
    {
        $destinations = [];

        // Get countries from stays
        foreach ($trip->stays as $stay) {
            if ($stay->country_code) {
                $destinations[] = [
                    'destination' => $stay->country_code,
                    'type' => 'travel',
                ];
            }
        }

        // Get countries from flight segments (arrival countries)
        foreach ($trip->flightSegments as $segment) {
            if ($segment->arrival_country_code) {
                $destinations[] = [
                    'destination' => $segment->arrival_country_code,
                    'type' => 'travel',
                ];
            }
        }

        // Fallback: Use countries_visited from trip (for simple format imports)
        if (empty($destinations) && !empty($trip->countries_visited)) {
            foreach ($trip->countries_visited as $countryCode) {
                $destinations[] = [
                    'destination' => $countryCode,
                    'type' => 'travel',
                ];
            }
        }

        // Deduplicate
        $unique = [];
        foreach ($destinations as $dest) {
            $key = $dest['destination'];
            if (!isset($unique[$key])) {
                $unique[$key] = $dest;
            }
        }

        return array_values($unique);
    }

    /**
     * Extract TID from share URL
     */
    protected function extractTidFromUrl(string $url): string
    {
        // URL format: https://example.com/de?tid=XXXX-XXXX-XXXX
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
     * Refresh a share link (regenerate)
     */
    public function refreshShareLink(TdTrip $trip): ?TdPdsShareLink
    {
        // Delete existing share links
        $trip->pdsShareLinks()->delete();

        // Generate new one
        return $this->generateShareLink($trip);
    }

    /**
     * Get share link for a trip (existing or generate new)
     */
    public function getOrCreateShareLink(TdTrip $trip): ?TdPdsShareLink
    {
        // Check for existing active share link
        $existingLink = $trip->pdsShareLinks()
            ->active()
            ->latest()
            ->first();

        if ($existingLink) {
            return $existingLink;
        }

        // Generate new link
        return $this->generateShareLink($trip);
    }

    /**
     * Check if share link service is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiUrl);
    }
}
