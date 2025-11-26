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

            // Use PdsApiService to fetch travel-details
            $response = $this->pdsApiService->get($customer, '/travel-details', [
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
     * Country coordinates lookup (approximate center coordinates)
     */
    private static array $countryCoordinates = [
        'AF' => ['lat' => 33.93, 'lng' => 67.71, 'name' => 'Afghanistan'],
        'AL' => ['lat' => 41.15, 'lng' => 20.17, 'name' => 'Albanien'],
        'DZ' => ['lat' => 28.03, 'lng' => 1.66, 'name' => 'Algerien'],
        'AR' => ['lat' => -38.42, 'lng' => -63.62, 'name' => 'Argentinien'],
        'AU' => ['lat' => -25.27, 'lng' => 133.78, 'name' => 'Australien'],
        'AT' => ['lat' => 47.52, 'lng' => 14.55, 'name' => 'Österreich'],
        'BE' => ['lat' => 50.50, 'lng' => 4.47, 'name' => 'Belgien'],
        'BR' => ['lat' => -14.24, 'lng' => -51.93, 'name' => 'Brasilien'],
        'BG' => ['lat' => 42.73, 'lng' => 25.49, 'name' => 'Bulgarien'],
        'CA' => ['lat' => 56.13, 'lng' => -106.35, 'name' => 'Kanada'],
        'CL' => ['lat' => -35.68, 'lng' => -71.54, 'name' => 'Chile'],
        'CN' => ['lat' => 35.86, 'lng' => 104.20, 'name' => 'China'],
        'CO' => ['lat' => 4.57, 'lng' => -74.30, 'name' => 'Kolumbien'],
        'HR' => ['lat' => 45.10, 'lng' => 15.20, 'name' => 'Kroatien'],
        'CY' => ['lat' => 35.13, 'lng' => 33.43, 'name' => 'Zypern'],
        'CZ' => ['lat' => 49.82, 'lng' => 15.47, 'name' => 'Tschechien'],
        'DK' => ['lat' => 56.26, 'lng' => 9.50, 'name' => 'Dänemark'],
        'EG' => ['lat' => 26.82, 'lng' => 30.80, 'name' => 'Ägypten'],
        'EE' => ['lat' => 58.60, 'lng' => 25.01, 'name' => 'Estland'],
        'FI' => ['lat' => 61.92, 'lng' => 25.75, 'name' => 'Finnland'],
        'FR' => ['lat' => 46.23, 'lng' => 2.21, 'name' => 'Frankreich'],
        'DE' => ['lat' => 51.17, 'lng' => 10.45, 'name' => 'Deutschland'],
        'GR' => ['lat' => 39.07, 'lng' => 21.82, 'name' => 'Griechenland'],
        'HU' => ['lat' => 47.16, 'lng' => 19.50, 'name' => 'Ungarn'],
        'IS' => ['lat' => 64.96, 'lng' => -19.02, 'name' => 'Island'],
        'IN' => ['lat' => 20.59, 'lng' => 78.96, 'name' => 'Indien'],
        'ID' => ['lat' => -0.79, 'lng' => 113.92, 'name' => 'Indonesien'],
        'IE' => ['lat' => 53.14, 'lng' => -7.69, 'name' => 'Irland'],
        'IL' => ['lat' => 31.05, 'lng' => 34.85, 'name' => 'Israel'],
        'IT' => ['lat' => 41.87, 'lng' => 12.57, 'name' => 'Italien'],
        'JP' => ['lat' => 36.20, 'lng' => 138.25, 'name' => 'Japan'],
        'KE' => ['lat' => -0.02, 'lng' => 37.91, 'name' => 'Kenia'],
        'LV' => ['lat' => 56.88, 'lng' => 24.60, 'name' => 'Lettland'],
        'LT' => ['lat' => 55.17, 'lng' => 23.88, 'name' => 'Litauen'],
        'LU' => ['lat' => 49.82, 'lng' => 6.13, 'name' => 'Luxemburg'],
        'MY' => ['lat' => 4.21, 'lng' => 101.98, 'name' => 'Malaysia'],
        'MV' => ['lat' => 3.20, 'lng' => 73.22, 'name' => 'Malediven'],
        'MT' => ['lat' => 35.94, 'lng' => 14.38, 'name' => 'Malta'],
        'MX' => ['lat' => 23.63, 'lng' => -102.55, 'name' => 'Mexiko'],
        'MA' => ['lat' => 31.79, 'lng' => -7.09, 'name' => 'Marokko'],
        'NL' => ['lat' => 52.13, 'lng' => 5.29, 'name' => 'Niederlande'],
        'NZ' => ['lat' => -40.90, 'lng' => 174.89, 'name' => 'Neuseeland'],
        'NO' => ['lat' => 60.47, 'lng' => 8.47, 'name' => 'Norwegen'],
        'PE' => ['lat' => -9.19, 'lng' => -75.02, 'name' => 'Peru'],
        'PH' => ['lat' => 12.88, 'lng' => 121.77, 'name' => 'Philippinen'],
        'PL' => ['lat' => 51.92, 'lng' => 19.15, 'name' => 'Polen'],
        'PT' => ['lat' => 39.40, 'lng' => -8.22, 'name' => 'Portugal'],
        'RO' => ['lat' => 45.94, 'lng' => 24.97, 'name' => 'Rumänien'],
        'RU' => ['lat' => 61.52, 'lng' => 105.32, 'name' => 'Russland'],
        'SA' => ['lat' => 23.89, 'lng' => 45.08, 'name' => 'Saudi-Arabien'],
        'RS' => ['lat' => 44.02, 'lng' => 21.01, 'name' => 'Serbien'],
        'SG' => ['lat' => 1.35, 'lng' => 103.82, 'name' => 'Singapur'],
        'SK' => ['lat' => 48.67, 'lng' => 19.70, 'name' => 'Slowakei'],
        'SI' => ['lat' => 46.15, 'lng' => 14.99, 'name' => 'Slowenien'],
        'ZA' => ['lat' => -30.56, 'lng' => 22.94, 'name' => 'Südafrika'],
        'KR' => ['lat' => 35.91, 'lng' => 127.77, 'name' => 'Südkorea'],
        'ES' => ['lat' => 40.46, 'lng' => -3.75, 'name' => 'Spanien'],
        'SE' => ['lat' => 60.13, 'lng' => 18.64, 'name' => 'Schweden'],
        'CH' => ['lat' => 46.82, 'lng' => 8.23, 'name' => 'Schweiz'],
        'TH' => ['lat' => 15.87, 'lng' => 100.99, 'name' => 'Thailand'],
        'TR' => ['lat' => 38.96, 'lng' => 35.24, 'name' => 'Türkei'],
        'UA' => ['lat' => 48.38, 'lng' => 31.17, 'name' => 'Ukraine'],
        'AE' => ['lat' => 23.42, 'lng' => 53.85, 'name' => 'VAE'],
        'GB' => ['lat' => 55.38, 'lng' => -3.44, 'name' => 'Großbritannien'],
        'US' => ['lat' => 37.09, 'lng' => -95.71, 'name' => 'USA'],
        'VN' => ['lat' => 14.06, 'lng' => 108.28, 'name' => 'Vietnam'],
        'CU' => ['lat' => 21.52, 'lng' => -77.78, 'name' => 'Kuba'],
        'DO' => ['lat' => 18.74, 'lng' => -70.16, 'name' => 'Dominikanische Republik'],
        'JM' => ['lat' => 18.11, 'lng' => -77.30, 'name' => 'Jamaika'],
        'TZ' => ['lat' => -6.37, 'lng' => 34.89, 'name' => 'Tansania'],
        'NA' => ['lat' => -22.96, 'lng' => 18.49, 'name' => 'Namibia'],
        'BW' => ['lat' => -22.33, 'lng' => 24.68, 'name' => 'Botswana'],
        'MU' => ['lat' => -20.35, 'lng' => 57.55, 'name' => 'Mauritius'],
        'SC' => ['lat' => -4.68, 'lng' => 55.49, 'name' => 'Seychellen'],
        'LK' => ['lat' => 7.87, 'lng' => 80.77, 'name' => 'Sri Lanka'],
        'NP' => ['lat' => 28.39, 'lng' => 84.12, 'name' => 'Nepal'],
        'MM' => ['lat' => 21.91, 'lng' => 95.96, 'name' => 'Myanmar'],
        'KH' => ['lat' => 12.57, 'lng' => 104.99, 'name' => 'Kambodscha'],
        'LA' => ['lat' => 19.86, 'lng' => 102.50, 'name' => 'Laos'],
        'FJ' => ['lat' => -17.71, 'lng' => 178.07, 'name' => 'Fidschi'],
        'PF' => ['lat' => -17.68, 'lng' => -149.41, 'name' => 'Französisch-Polynesien'],
        'CR' => ['lat' => 9.75, 'lng' => -83.75, 'name' => 'Costa Rica'],
        'PA' => ['lat' => 8.54, 'lng' => -80.78, 'name' => 'Panama'],
        'EC' => ['lat' => -1.83, 'lng' => -78.18, 'name' => 'Ecuador'],
        'BO' => ['lat' => -16.29, 'lng' => -63.59, 'name' => 'Bolivien'],
        'UY' => ['lat' => -32.52, 'lng' => -55.77, 'name' => 'Uruguay'],
        'PY' => ['lat' => -23.44, 'lng' => -58.44, 'name' => 'Paraguay'],
    ];

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

        // Fallback: use destinations_list or destinations array with coordinate lookup
        if (isset($traveler['destinations_list']) && is_array($traveler['destinations_list'])) {
            foreach ($traveler['destinations_list'] as $dest) {
                $code = $dest['code'] ?? null;
                if ($code && isset(self::$countryCoordinates[$code])) {
                    return self::$countryCoordinates[$code];
                }
            }
        }

        // Fallback: use destinations array (country codes)
        if (isset($traveler['destinations']) && is_array($traveler['destinations'])) {
            foreach ($traveler['destinations'] as $code) {
                if (isset(self::$countryCoordinates[$code])) {
                    return self::$countryCoordinates[$code];
                }
            }
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
