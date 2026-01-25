<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Folder\Folder;
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

        if (! $customer) {
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
     * Get active travelers (travel-detail links + local folders)
     * Supports filtering by date range, status, source
     */
    public function getActiveTravelers(Request $request)
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Nicht authentifiziert'], 401);
        }

        $allTravelers = [];

        // Get filter parameters
        $dateFilter = $request->input('date_filter', 'today'); // today, 7days, 14days, 30days, all, custom
        $statusFilter = $request->input('status', 'all'); // all, confirmed, active, completed, upcoming
        $sourceFilter = $request->input('source', 'all'); // all, local, api
        $searchQuery = $request->input('search', '');

        // Calculate date range based on filter
        $dateRange = $this->calculateDateRange($dateFilter, $request);

        // 1. Load LOCAL FOLDERS (from folder system)
        if (in_array($sourceFilter, ['all', 'local'])) {
            try {
                $query = Folder::with(['participants', 'itineraries.hotelServices', 'itineraries.flightServices'])
                    ->where('customer_id', $customer->id);

                // Apply date filter
                if ($dateRange['start'] && $dateRange['end']) {
                    $query->where(function ($q) use ($dateRange) {
                        $q->whereBetween('travel_start_date', [$dateRange['start'], $dateRange['end']])
                            ->orWhereBetween('travel_end_date', [$dateRange['start'], $dateRange['end']])
                            ->orWhere(function ($q2) use ($dateRange) {
                                $q2->where('travel_start_date', '<=', $dateRange['start'])
                                    ->where('travel_end_date', '>=', $dateRange['end']);
                            });
                    });
                } elseif ($dateRange['start']) {
                    $query->where('travel_end_date', '>=', $dateRange['start']);
                } elseif ($dateRange['end']) {
                    $query->where('travel_start_date', '<=', $dateRange['end']);
                }

                // Apply status filter
                if ($statusFilter !== 'all') {
                    if ($statusFilter === 'upcoming') {
                        $query->where('travel_start_date', '>', now());
                    } elseif ($statusFilter === 'completed') {
                        $query->where('travel_end_date', '<', now())
                            ->orWhere('status', 'completed');
                    } else {
                        $query->where('status', $statusFilter);
                    }
                }

                // Apply search filter
                if ($searchQuery) {
                    $query->where(function ($q) use ($searchQuery) {
                        $q->where('folder_name', 'like', '%'.$searchQuery.'%')
                            ->orWhere('folder_number', 'like', '%'.$searchQuery.'%')
                            ->orWhere('primary_destination', 'like', '%'.$searchQuery.'%');
                    });
                }

                $localFolders = $query->orderBy('travel_start_date', 'desc')->get();

                foreach ($localFolders as $folder) {
                    $allTravelers[] = $this->formatFolderAsTraveler($folder);
                }

                Log::info('MyTravelersController: Loaded local folders', [
                    'customer_id' => $customer->id,
                    'count' => $localFolders->count(),
                ]);
            } catch (\Exception $e) {
                Log::error('MyTravelersController: Error loading local folders', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2. Load API TRAVELERS (if API token exists)
        if (in_array($sourceFilter, ['all', 'api']) && $customer->hasAnyActiveToken()) {
            try {
                // Build API filter
                $apiFilter = [];
                if ($dateRange['start'] && $dateRange['end']) {
                    $apiFilter['start_date'] = ['<=' => $dateRange['end']];
                    $apiFilter['end_date'] = ['>=' => $dateRange['start']];
                } elseif ($dateRange['start']) {
                    $apiFilter['end_date'] = ['>=' => $dateRange['start']];
                } elseif ($dateRange['end']) {
                    $apiFilter['start_date'] = ['<=' => $dateRange['end']];
                }

                // Use PdsApiService to fetch travel-details
                $response = $this->pdsApiService->get($customer, '/travel-details', [
                    'filter' => $apiFilter,
                    'include' => 'countries',
                ]);

                if ($response && $response->successful()) {
                    $data = $response->json();
                    $apiTravelers = $data['data'] ?? [];

                    // Process API travelers
                    foreach ($apiTravelers as $traveler) {
                        $processedTraveler = [
                            'id' => 'api-'.($traveler['id'] ?? uniqid()),
                            'title' => $traveler['title'] ?? 'Reisender',
                            'start_date' => $traveler['start_date'] ?? null,
                            'end_date' => $traveler['end_date'] ?? null,
                            'countries' => $traveler['countries'] ?? [],
                            'destination' => $this->extractDestination($traveler),
                            'travelers_count' => $traveler['travelers_count'] ?? 1,
                            'status' => $this->getTravelStatus($traveler),
                            'source' => 'api',
                            'source_label' => 'PDS API',
                        ];

                        // Apply search filter
                        if ($searchQuery) {
                            $matchesSearch = stripos($processedTraveler['title'], $searchQuery) !== false ||
                                           stripos($processedTraveler['destination']['name'] ?? '', $searchQuery) !== false;
                            if (! $matchesSearch) {
                                continue;
                            }
                        }

                        // Apply status filter
                        if ($statusFilter !== 'all' && $processedTraveler['status'] !== $statusFilter) {
                            continue;
                        }

                        $allTravelers[] = $processedTraveler;
                    }

                    Log::info('MyTravelersController: Loaded API travelers', [
                        'customer_id' => $customer->id,
                        'count' => count($apiTravelers),
                        'filtered_count' => count(array_filter($allTravelers, fn ($t) => $t['source'] === 'api')),
                    ]);
                } else {
                    Log::warning('MyTravelersController: Failed to fetch API travelers', [
                        'customer_id' => $customer->id,
                        'status' => $response?->status(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('MyTravelersController: Error fetching API travelers', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Sort by start date (descending - newest first)
        usort($allTravelers, function ($a, $b) {
            $dateA = $a['start_date'] ?? '1900-01-01';
            $dateB = $b['start_date'] ?? '1900-01-01';

            return strcmp($dateB, $dateA);
        });

        return response()->json([
            'success' => true,
            'travelers' => $allTravelers,
            'count' => count($allTravelers),
            'sources' => [
                'local' => count(array_filter($allTravelers, fn ($t) => ($t['source'] ?? 'local') === 'local')),
                'api' => count(array_filter($allTravelers, fn ($t) => ($t['source'] ?? null) === 'api')),
            ],
            'filters' => [
                'date_filter' => $dateFilter,
                'status' => $statusFilter,
                'source' => $sourceFilter,
                'search' => $searchQuery,
                'date_range' => $dateRange,
            ],
        ]);
    }

    /**
     * Calculate date range based on filter type
     */
    private function calculateDateRange(string $dateFilter, Request $request): array
    {
        $today = now()->startOfDay();

        switch ($dateFilter) {
            case 'today':
                return [
                    'start' => $today->format('Y-m-d'),
                    'end' => $today->format('Y-m-d'),
                ];

            case '7days':
                return [
                    'start' => $today->format('Y-m-d'),
                    'end' => $today->copy()->addDays(7)->format('Y-m-d'),
                ];

            case '14days':
                return [
                    'start' => $today->format('Y-m-d'),
                    'end' => $today->copy()->addDays(14)->format('Y-m-d'),
                ];

            case '30days':
                return [
                    'start' => $today->format('Y-m-d'),
                    'end' => $today->copy()->addDays(30)->format('Y-m-d'),
                ];

            case 'custom':
                return [
                    'start' => $request->input('start_date'),
                    'end' => $request->input('end_date'),
                ];

            case 'all':
            default:
                return [
                    'start' => null,
                    'end' => null,
                ];
        }
    }

    /**
     * Format a Folder model as a traveler for the map display
     */
    private function formatFolderAsTraveler(Folder $folder): array
    {
        // Try to get primary destination coordinates
        $destination = null;

        // First try: Get from first hotel with coordinates
        if ($folder->itineraries->isNotEmpty()) {
            foreach ($folder->itineraries as $itinerary) {
                $hotel = $itinerary->hotelServices->first(fn ($h) => $h->lat && $h->lng);
                if ($hotel) {
                    $destination = [
                        'lat' => (float) $hotel->lat,
                        'lng' => (float) $hotel->lng,
                        'name' => $hotel->city.', '.$hotel->country_code,
                    ];
                    break;
                }
            }
        }

        // Fallback: Try to parse primary_destination (e.g. "Bangkok, Thailand")
        if (! $destination && $folder->primary_destination) {
            // Try to get country code from primary_destination
            $parts = explode(',', $folder->primary_destination);
            if (count($parts) >= 2) {
                $countryName = trim($parts[1]);
                // Try to find country code (simple mapping, could be improved)
                $countryCode = $this->findCountryCodeByName($countryName);
                if ($countryCode && isset(self::$countryCoordinates[$countryCode])) {
                    $destination = self::$countryCoordinates[$countryCode];
                }
            }
        }

        // Get booking reference from first itinerary if available
        $bookingReference = null;
        if ($folder->itineraries && $folder->itineraries->count() > 0) {
            $bookingReference = $folder->itineraries->first()->booking_reference;
        }

        return [
            'id' => 'folder-'.$folder->id,
            'folder_id' => $folder->id,
            'title' => $folder->folder_name ?? 'Reise '.$folder->folder_number,
            'start_date' => $folder->travel_start_date?->format('Y-m-d'),
            'end_date' => $folder->travel_end_date?->format('Y-m-d'),
            'countries' => [], // Could be extracted from itineraries if needed
            'destination' => $destination,
            'travelers_count' => $folder->participants->count(),
            'status' => $this->getTravelStatusFromFolder($folder),
            'source' => 'local',
            'source_label' => 'Lokal importiert',
            'folder_number' => $folder->folder_number,
            'booking_reference' => $bookingReference,
        ];
    }

    /**
     * Simple country name to code mapping
     */
    private function findCountryCodeByName(string $name): ?string
    {
        $name = strtolower(trim($name));
        $mapping = [
            'thailand' => 'TH',
            'deutschland' => 'DE',
            'germany' => 'DE',
            'frankreich' => 'FR',
            'france' => 'FR',
            'spanien' => 'ES',
            'spain' => 'ES',
            'italien' => 'IT',
            'italy' => 'IT',
            'österreich' => 'AT',
            'austria' => 'AT',
            'schweiz' => 'CH',
            'switzerland' => 'CH',
            'usa' => 'US',
            'vereinigte staaten' => 'US',
            'united states' => 'US',
            // Add more as needed
        ];

        return $mapping[$name] ?? null;
    }

    /**
     * Get travel status from Folder model
     */
    private function getTravelStatusFromFolder(Folder $folder): string
    {
        $today = now()->startOfDay();
        $startDate = $folder->travel_start_date?->startOfDay();
        $endDate = $folder->travel_end_date?->startOfDay();

        if (! $startDate || ! $endDate) {
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

        if (! $startDate || ! $endDate) {
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

    /**
     * Delete a folder and all its related data
     */
    public function deleteFolder(string $folderId)
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Nicht authentifiziert'], 401);
        }

        try {
            // Find folder and verify ownership
            $folder = Folder::where('customer_id', $customer->id)->findOrFail($folderId);

            // Store folder name for response
            $folderName = $folder->folder_name ?? $folder->folder_number;

            // Delete folder (cascade will handle related data)
            $folder->delete();

            Log::info('MyTravelersController: Folder deleted', [
                'folder_id' => $folderId,
                'customer_id' => $customer->id,
                'folder_name' => $folderName,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Reise '{$folderName}' wurde erfolgreich gelöscht.",
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reise nicht gefunden oder keine Berechtigung',
            ], 404);
        } catch (\Exception $e) {
            Log::error('MyTravelersController: Error deleting folder', [
                'folder_id' => $folderId,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Löschen der Reise',
            ], 500);
        }
    }

    /**
     * Get detailed folder information for sidebar
     */
    public function getFolderDetails(string $folderId)
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Nicht authentifiziert'], 401);
        }

        try {
            // Load folder with all relations
            $folder = Folder::with([
                'customer',
                'participants',
                'itineraries.hotelServices',
                'itineraries.flightServices.segments.departureAirport',
                'itineraries.flightServices.segments.arrivalAirport',
                'itineraries.shipServices',
                'itineraries.carRentalServices',
            ])
                ->where('customer_id', $customer->id)
                ->findOrFail($folderId);

            // Format folder data for frontend
            $folderData = [
                'id' => $folder->id,
                'folder_number' => $folder->folder_number,
                'folder_name' => $folder->folder_name,
                'travel_start_date' => $folder->travel_start_date?->format('Y-m-d'),
                'travel_end_date' => $folder->travel_end_date?->format('Y-m-d'),
                'primary_destination' => $folder->primary_destination,
                'status' => $folder->status,
                'travel_type' => $folder->travel_type,
                'currency' => $folder->currency,
                'notes' => $folder->notes,

                // Custom Fields
                'custom_fields' => [
                    [
                        'label' => $folder->custom_field_1_label,
                        'value' => $folder->custom_field_1_value,
                    ],
                    [
                        'label' => $folder->custom_field_2_label,
                        'value' => $folder->custom_field_2_value,
                    ],
                    [
                        'label' => $folder->custom_field_3_label,
                        'value' => $folder->custom_field_3_value,
                    ],
                    [
                        'label' => $folder->custom_field_4_label,
                        'value' => $folder->custom_field_4_value,
                    ],
                    [
                        'label' => $folder->custom_field_5_label,
                        'value' => $folder->custom_field_5_value,
                    ],
                ],

                // Customer
                'customer' => $folder->customer ? [
                    'salutation' => $folder->customer->salutation,
                    'first_name' => $folder->customer->first_name,
                    'last_name' => $folder->customer->last_name,
                    'email' => $folder->customer->email,
                    'phone' => $folder->customer->phone,
                ] : null,

                // Participants
                'participants' => $folder->participants->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'salutation' => $p->salutation,
                        'first_name' => $p->first_name,
                        'last_name' => $p->last_name,
                        'birth_date' => $p->birth_date?->format('Y-m-d'),
                        'nationality' => $p->nationality,
                        'passport_number' => $p->passport_number,
                        'is_main_contact' => $p->is_main_contact,
                        'participant_type' => $p->participant_type,
                    ];
                })->toArray(),

                // Itineraries with services
                'itineraries' => $folder->itineraries->map(function ($itinerary) {
                    return [
                        'id' => $itinerary->id,
                        'itinerary_name' => $itinerary->itinerary_name,
                        'start_date' => $itinerary->start_date?->format('Y-m-d'),
                        'end_date' => $itinerary->end_date?->format('Y-m-d'),
                        'status' => $itinerary->status,
                        'booking_reference' => $itinerary->booking_reference,
                        'provider_name' => $itinerary->provider_name,
                        'currency' => $itinerary->currency,

                        // Hotels
                        'hotels' => $itinerary->hotelServices->map(function ($hotel) {
                            return [
                                'id' => $hotel->id,
                                'hotel_name' => $hotel->hotel_name,
                                'city' => $hotel->city,
                                'country_code' => $hotel->country_code,
                                'lat' => $hotel->lat,
                                'lng' => $hotel->lng,
                                'check_in_date' => $hotel->check_in_date?->format('Y-m-d'),
                                'check_out_date' => $hotel->check_out_date?->format('Y-m-d'),
                                'nights' => $hotel->nights,
                                'room_type' => $hotel->room_type,
                                'room_count' => $hotel->room_count,
                                'booking_reference' => $hotel->booking_reference,
                                'status' => $hotel->status,
                            ];
                        })->toArray(),

                        // Flights
                        'flights' => $itinerary->flightServices->map(function ($flight) {
                            return [
                                'id' => $flight->id,
                                'booking_reference' => $flight->booking_reference,
                                'airline_pnr' => $flight->airline_pnr,
                                'service_type' => $flight->service_type,
                                'origin_airport_code' => $flight->origin_airport_code,
                                'destination_airport_code' => $flight->destination_airport_code,
                                'departure_time' => $flight->departure_time?->format('Y-m-d H:i'),
                                'arrival_time' => $flight->arrival_time?->format('Y-m-d H:i'),
                                'status' => $flight->status,
                                'segments' => $flight->segments->map(function ($segment) {
                                    return [
                                        'segment_number' => $segment->segment_number,
                                        'airline_code' => $segment->airline_code,
                                        'flight_number' => $segment->flight_number,
                                        'departure_airport_code' => $segment->departure_airport_code,
                                        'departure_airport' => $segment->departureAirport ? [
                                            'code' => $segment->departure_airport_code,
                                            'name' => $segment->departureAirport->name,
                                            'city' => $segment->departureAirport->municipality,
                                            'lat' => $segment->departureAirport->latitude_deg ? (float) $segment->departureAirport->latitude_deg : null,
                                            'lng' => $segment->departureAirport->longitude_deg ? (float) $segment->departureAirport->longitude_deg : null,
                                        ] : null,
                                        'arrival_airport_code' => $segment->arrival_airport_code,
                                        'arrival_airport' => $segment->arrivalAirport ? [
                                            'code' => $segment->arrival_airport_code,
                                            'name' => $segment->arrivalAirport->name,
                                            'city' => $segment->arrivalAirport->municipality,
                                            'lat' => $segment->arrivalAirport->latitude_deg ? (float) $segment->arrivalAirport->latitude_deg : null,
                                            'lng' => $segment->arrivalAirport->longitude_deg ? (float) $segment->arrivalAirport->longitude_deg : null,
                                        ] : null,
                                        'departure_time' => $segment->departure_time?->format('Y-m-d H:i'),
                                        'arrival_time' => $segment->arrival_time?->format('Y-m-d H:i'),
                                        'departure_terminal' => $segment->departure_terminal,
                                        'arrival_terminal' => $segment->arrival_terminal,
                                        'aircraft_type' => $segment->aircraft_type,
                                        'cabin_class' => $segment->cabin_class,
                                        'duration_minutes' => $segment->duration_minutes,
                                    ];
                                })->toArray(),
                            ];
                        })->toArray(),

                        // Ships
                        'ships' => $itinerary->shipServices->map(function ($ship) {
                            return [
                                'id' => $ship->id,
                                'ship_name' => $ship->ship_name,
                                'cruise_line' => $ship->cruise_line,
                                'departure_port' => $ship->departure_port,
                                'arrival_port' => $ship->arrival_port,
                                'departure_date' => $ship->departure_date?->format('Y-m-d'),
                                'arrival_date' => $ship->arrival_date?->format('Y-m-d'),
                                'cabin_number' => $ship->cabin_number,
                                'cabin_type' => $ship->cabin_type,
                                'status' => $ship->status,
                            ];
                        })->toArray(),

                        // Car Rentals
                        'car_rentals' => $itinerary->carRentalServices->map(function ($car) {
                            return [
                                'id' => $car->id,
                                'rental_company' => $car->rental_company,
                                'vehicle_type' => $car->vehicle_type,
                                'pickup_location' => $car->pickup_location,
                                'dropoff_location' => $car->dropoff_location,
                                'pickup_date' => $car->pickup_date?->format('Y-m-d H:i'),
                                'dropoff_date' => $car->dropoff_date?->format('Y-m-d H:i'),
                                'status' => $car->status,
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];

            return response()->json([
                'success' => true,
                'data' => $folderData,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reise nicht gefunden',
            ], 404);
        } catch (\Exception $e) {
            Log::error('MyTravelersController: Error loading folder details', [
                'folder_id' => $folderId,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Reisedetails',
            ], 500);
        }
    }
}
