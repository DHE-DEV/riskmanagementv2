<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\PdsApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MyTravelersController extends Controller
{
    protected PdsApiService $pdsApiService;

    public function __construct(PdsApiService $pdsApiService)
    {
        $this->pdsApiService = $pdsApiService;
    }

    /**
     * Display the my travelers page
     */
    public function index()
    {
        $customer = auth('customer')->user();

        if (!$customer) {
            return redirect()->route('customer.login');
        }

        // Check if customer has valid API token
        $hasValidToken = $customer->hasAnyActiveToken();

        return view('livewire.pages.my-travelers', [
            'customer' => $customer,
            'hasValidToken' => $hasValidToken,
        ]);
    }

    /**
     * Get active travelers (travel-detail links for today)
     */
    public function getActiveTravelers()
    {
        $customer = auth('customer')->user();

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Nicht authentifiziert'], 401);
        }

        if (!$customer->hasAnyActiveToken()) {
            return response()->json([
                'success' => false,
                'message' => 'Keine gültige API-Verbindung. Bitte melden Sie sich erneut via SSO an oder verbinden Sie die Passolution-Integration.',
            ], 403);
        }

        try {
            $today = now()->format('Y-m-d');

            // Use PdsApiService to fetch travel-detail links
            $response = $this->pdsApiService->get($customer, '/travel-detail-links', [
                'filter' => [
                    'start_date' => ['<=' => $today],
                    'end_date' => ['>=' => $today],
                ],
                'include' => 'countries',
            ]);

            if (!$response || !$response->successful()) {
                Log::warning('MyTravelersController: Failed to fetch travel-detail links', [
                    'customer_id' => $customer->id,
                    'status' => $response?->status(),
                    'body' => $response?->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Fehler beim Abrufen der Reisenden',
                ], 500);
            }

            $data = $response->json();
            $travelers = $data['data'] ?? [];

            // Process travelers to extract destination coordinates
            $processedTravelers = collect($travelers)->map(function ($traveler) {
                return [
                    'id' => $traveler['id'] ?? null,
                    'title' => $traveler['title'] ?? 'Reisender',
                    'start_date' => $traveler['start_date'] ?? null,
                    'end_date' => $traveler['end_date'] ?? null,
                    'countries' => $traveler['countries'] ?? [],
                    'destination' => $this->extractDestination($traveler),
                    'travelers_count' => $traveler['travelers_count'] ?? 1,
                    'status' => $this->getTravelStatus($traveler),
                ];
            })->values()->all();

            return response()->json([
                'success' => true,
                'travelers' => $processedTravelers,
                'count' => count($processedTravelers),
            ]);

        } catch (\Exception $e) {
            Log::error('MyTravelersController: Error fetching travelers', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ein Fehler ist aufgetreten',
            ], 500);
        }
    }

    /**
     * Extract destination coordinates from traveler data
     */
    private function extractDestination(array $traveler): ?array
    {
        // Try to get coordinates from countries
        if (isset($traveler['countries']) && is_array($traveler['countries'])) {
            foreach ($traveler['countries'] as $country) {
                if (isset($country['latitude']) && isset($country['longitude'])) {
                    return [
                        'lat' => (float) $country['latitude'],
                        'lng' => (float) $country['longitude'],
                        'name' => $country['name'] ?? $country['name_de'] ?? 'Unbekannt',
                    ];
                }
            }
        }

        // Fallback: try to get destination from the link itself
        if (isset($traveler['destination_latitude']) && isset($traveler['destination_longitude'])) {
            return [
                'lat' => (float) $traveler['destination_latitude'],
                'lng' => (float) $traveler['destination_longitude'],
                'name' => $traveler['destination_name'] ?? 'Unbekannt',
            ];
        }

        return null;
    }

    /**
     * Determine travel status based on dates
     */
    private function getTravelStatus(array $traveler): string
    {
        $today = now()->startOfDay();
        $startDate = isset($traveler['start_date']) ? \Carbon\Carbon::parse($traveler['start_date'])->startOfDay() : null;
        $endDate = isset($traveler['end_date']) ? \Carbon\Carbon::parse($traveler['end_date'])->startOfDay() : null;

        if (!$startDate || !$endDate) {
            return 'unknown';
        }

        if ($today->lt($startDate)) {
            return 'upcoming';
        }

        if ($today->gt($endDate)) {
            return 'completed';
        }

        return 'traveling';
    }
}
