<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomEvent;
use App\Models\Folder\Folder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RiskOverviewService
{
    protected GtmEventService $gtmEventService;

    protected PdsApiService $pdsApiService;

    public function __construct(GtmEventService $gtmEventService, PdsApiService $pdsApiService)
    {
        $this->gtmEventService = $gtmEventService;
        $this->pdsApiService = $pdsApiService;
    }

    /**
     * Fetch travelers from Passolution API with 1000 records.
     * Returns travelers grouped by country code.
     *
     * @return array<string, array> Country code => travelers array
     */
    protected function fetchApiTravelers(Customer $customer, string $startDate, string $endDate): array
    {
        if (! $this->pdsApiService->hasValidToken($customer)) {
            Log::info('RiskOverviewService: Customer has no valid API token, skipping API fetch', [
                'customer_id' => $customer->id,
            ]);

            return [];
        }

        try {
            $apiRequestBody = [
                'sort_by' => 'start_date',
                'sort_order' => 'desc',
                'page' => 1,
                'per_page' => 1000,
                'start_date' => ['<=' => $endDate],
                'end_date' => ['>=' => $startDate],
            ];

            Log::info('RiskOverviewService: Fetching API travelers', [
                'customer_id' => $customer->id,
                'request_body' => $apiRequestBody,
            ]);

            $response = $this->pdsApiService->post($customer, '/travel-details', $apiRequestBody);

            if (! $response || ! $response->successful()) {
                Log::warning('RiskOverviewService: Failed to fetch API travelers', [
                    'customer_id' => $customer->id,
                    'status' => $response?->status(),
                ]);

                return [];
            }

            $data = $response->json();
            $apiTravelers = $data['data'] ?? [];

            Log::info('RiskOverviewService: Received API travelers', [
                'customer_id' => $customer->id,
                'count' => count($apiTravelers),
                'total_in_api' => $data['meta']['total'] ?? 'unknown',
            ]);

            // Pre-load country names for resolving ISO codes
            $allIsoCodes = collect($apiTravelers)->flatMap(function ($t) {
                $codes = $t['destinations'] ?? [];
                $nationalities = $t['nationalities'] ?? [];
                return array_merge($codes, $nationalities);
            })->unique()->values()->toArray();

            $countryNames = Country::whereIn('iso_code', $allIsoCodes)
                ->pluck('name_translations', 'iso_code')
                ->map(fn ($translations) => $translations['de'] ?? $translations['en'] ?? null)
                ->toArray();

            // Group travelers by country code
            $travelersByCountry = [];

            foreach ($apiTravelers as $traveler) {
                $countryCodes = $this->extractCountryCodesFromApiTraveler($traveler);

                // Resolve destination codes to names
                $destinations = collect($traveler['destinations'] ?? [])
                    ->map(fn ($code) => ['code' => strtoupper($code), 'name' => $countryNames[strtoupper($code)] ?? strtoupper($code)])
                    ->values()->toArray();

                // Resolve nationality codes to names
                $nationalities = collect($traveler['nationalities'] ?? [])
                    ->map(fn ($code) => ['code' => strtoupper($code), 'name' => $countryNames[strtoupper($code)] ?? strtoupper($code)])
                    ->values()->toArray();

                foreach ($countryCodes as $countryCode) {
                    if (! isset($travelersByCountry[$countryCode])) {
                        $travelersByCountry[$countryCode] = [];
                    }

                    $travelersByCountry[$countryCode][] = [
                        'trip_id' => $traveler['tid'] ?? $traveler['id'] ?? null,
                        'folder_id' => 'api-'.($traveler['tid'] ?? $traveler['id'] ?? uniqid()),
                        'folder_name' => $traveler['trip_name'] ?? 'Unbenannte Reise',
                        'folder_number' => null,
                        'start_date' => $traveler['start_date'] ?? null,
                        'end_date' => $traveler['end_date'] ?? null,
                        'participant_count' => $traveler['travelers_count'] ?? 1,
                        'participants' => [],
                        'destinations' => $destinations,
                        'nationalities' => $nationalities,
                        'with_minors' => $traveler['travel']['with_minors'] ?? false,
                        'source' => 'api',
                        'source_label' => 'PDS API',
                    ];
                }
            }

            return $travelersByCountry;
        } catch (\Exception $e) {
            Log::error('RiskOverviewService: Error fetching API travelers', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Extract country codes from API traveler data.
     */
    protected function extractCountryCodesFromApiTraveler(array $traveler): array
    {
        $countryCodes = [];

        // From countries array
        if (isset($traveler['countries']) && is_array($traveler['countries'])) {
            foreach ($traveler['countries'] as $country) {
                if (isset($country['code'])) {
                    $countryCodes[] = strtoupper($country['code']);
                }
            }
        }

        // From destinations array (country codes)
        if (isset($traveler['destinations']) && is_array($traveler['destinations'])) {
            foreach ($traveler['destinations'] as $code) {
                if (is_string($code)) {
                    $countryCodes[] = strtoupper($code);
                }
            }
        }

        // From destinations_list array
        if (isset($traveler['destinations_list']) && is_array($traveler['destinations_list'])) {
            foreach ($traveler['destinations_list'] as $dest) {
                if (isset($dest['code'])) {
                    $countryCodes[] = strtoupper($dest['code']);
                }
            }
        }

        return array_unique($countryCodes);
    }

    /**
     * Get aggregated risk data grouped by country.
     * Returns countries with active events and affected travelers count.
     */
    public function getAggregatedRiskData(int $customerId, ?string $priorityFilter = null, int $daysAhead = 30): array
    {
        $cacheKey = "risk_overview_{$customerId}_{$priorityFilter}_{$daysAhead}";
        $cacheDuration = config('feed.cache_duration', 300); // 5 minutes default

        return Cache::remember($cacheKey, $cacheDuration, function () use ($customerId, $priorityFilter, $daysAhead) {
            // Get all active events
            $events = $this->gtmEventService->getActiveEvents($priorityFilter);

            // Group events by country
            $countriesData = $this->groupEventsByCountry($events);

            // Get traveler counts for each country
            $countriesData = $this->enrichWithTravelerCounts($countriesData, $customerId, $daysAhead);

            // Calculate summary
            $summary = $this->calculateSummary($countriesData);

            return [
                'countries' => $countriesData->values()->toArray(),
                'summary' => $summary,
            ];
        });
    }

    /**
     * Get aggregated risk data grouped by country with custom date range.
     */
    public function getAggregatedRiskDataByDateRange(int $customerId, string $dateFrom, ?string $dateTo = null, ?string $priorityFilter = null): array
    {
        $cacheKey = "risk_overview_{$customerId}_{$priorityFilter}_{$dateFrom}_{$dateTo}";
        $cacheDuration = config('feed.cache_duration', 300);

        return Cache::remember($cacheKey, $cacheDuration, function () use ($customerId, $priorityFilter, $dateFrom, $dateTo) {
            // Get all active events
            $events = $this->gtmEventService->getActiveEvents($priorityFilter);

            // Group events by country
            $countriesData = $this->groupEventsByCountry($events);

            // Get traveler counts for each country with date range
            $countriesData = $this->enrichWithTravelerCountsByDateRange($countriesData, $customerId, $dateFrom, $dateTo);

            // Calculate summary
            $summary = $this->calculateSummary($countriesData);

            return [
                'countries' => $countriesData->values()->toArray(),
                'summary' => $summary,
            ];
        });
    }

    /**
     * Group events by country and calculate statistics.
     */
    protected function groupEventsByCountry(Collection $events): Collection
    {
        // Use a plain PHP array to avoid Collection modification issues
        $countryData = [];

        foreach ($events as $event) {
            // Get all countries for this event
            $eventCountries = [];

            if ($event->country_id && $event->country) {
                $eventCountries[] = $event->country;
            }

            if ($event->countries->isNotEmpty()) {
                foreach ($event->countries as $country) {
                    $alreadyAdded = false;
                    foreach ($eventCountries as $ec) {
                        if ($ec->id === $country->id) {
                            $alreadyAdded = true;
                            break;
                        }
                    }
                    if (! $alreadyAdded) {
                        $eventCountries[] = $country;
                    }
                }
            }

            // Add event to each country
            foreach ($eventCountries as $country) {
                $key = $country->iso_code;

                if (! isset($countryData[$key])) {
                    // Get German name from translations or fallback
                    $nameDe = $country->name_translations['de'] ?? $country->name_translations['en'] ?? $country->name ?? $country->iso_code;

                    $countryData[$key] = [
                        'country' => [
                            'code' => $country->iso_code,
                            'code3' => $country->iso3_code,
                            'name' => $nameDe,
                            'lat' => $country->capital?->latitude ?? $country->lat ?? null,
                            'lng' => $country->capital?->longitude ?? $country->lng ?? null,
                        ],
                        'event_ids' => [],
                        'events_by_priority' => [
                            'high' => 0,
                            'medium' => 0,
                            'low' => 0,
                            'info' => 0,
                        ],
                        'affected_travelers' => 0,
                    ];
                }

                // Add event if not already added
                if (! in_array($event->id, $countryData[$key]['event_ids'])) {
                    $countryData[$key]['event_ids'][] = $event->id;
                    $priority = $event->priority ?? 'info';
                    if (isset($countryData[$key]['events_by_priority'][$priority])) {
                        $countryData[$key]['events_by_priority'][$priority]++;
                    }
                }
            }
        }

        // Convert to collection and calculate totals
        return collect($countryData)->map(function ($data) {
            $data['total_events'] = count($data['event_ids']);
            $data['highest_priority'] = $this->getHighestPriority($data['events_by_priority']);
            // Remove event_ids to keep response lightweight
            unset($data['event_ids']);

            return $data;
        })->sortByDesc(function ($item) {
            // Sort by priority level (high first) then by event count
            $priorityOrder = ['high' => 4, 'medium' => 3, 'low' => 2, 'info' => 1];

            return ($priorityOrder[$item['highest_priority']] ?? 0) * 1000 + $item['total_events'];
        });
    }

    /**
     * Enrich country data with traveler counts from customer's folders and API.
     */
    protected function enrichWithTravelerCounts(Collection $countriesData, int $customerId, int $daysAhead): Collection
    {
        $today = now()->startOfDay();
        $endDate = $today->copy()->addDays($daysAhead);

        // Get all active folders for this customer in the date range
        $folders = Folder::with(['itineraries.hotelServices', 'itineraries.flightServices.segments', 'participants'])
            ->where('customer_id', $customerId)
            ->where(function ($query) use ($today, $endDate) {
                $query->whereBetween('travel_start_date', [$today, $endDate])
                    ->orWhereBetween('travel_end_date', [$today, $endDate])
                    ->orWhere(function ($q) use ($today, $endDate) {
                        $q->where('travel_start_date', '<=', $today)
                            ->where('travel_end_date', '>=', $endDate);
                    });
            })
            ->get();

        // Convert to array for modification
        $data = $countriesData->all();

        // Count trips (folders) per country based on hotel/flight destinations
        foreach ($folders as $folder) {
            $countryCodes = $this->extractCountryCodesFromFolder($folder);

            foreach ($countryCodes as $countryCode) {
                if (isset($data[$countryCode])) {
                    $data[$countryCode]['affected_travelers'] += 1;
                }
            }
        }

        // Fetch and count API trips
        $customer = Customer::find($customerId);
        if ($customer) {
            $apiTravelersByCountry = $this->fetchApiTravelers(
                $customer,
                $today->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            foreach ($apiTravelersByCountry as $countryCode => $travelers) {
                if (isset($data[$countryCode])) {
                    $data[$countryCode]['affected_travelers'] += count($travelers);
                }
            }
        }

        return collect($data);
    }

    /**
     * Enrich country data with traveler counts using custom date range.
     */
    protected function enrichWithTravelerCountsByDateRange(Collection $countriesData, int $customerId, string $dateFrom, ?string $dateTo = null): Collection
    {
        $startDate = \Carbon\Carbon::parse($dateFrom)->startOfDay();
        $endDate = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : $startDate->copy()->addDays(30)->endOfDay();

        // Get all active folders for this customer in the date range
        $folders = Folder::with(['itineraries.hotelServices', 'itineraries.flightServices.segments', 'participants'])
            ->where('customer_id', $customerId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('travel_start_date', [$startDate, $endDate])
                    ->orWhereBetween('travel_end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('travel_start_date', '<=', $startDate)
                            ->where('travel_end_date', '>=', $endDate);
                    });
            })
            ->get();

        // Convert to array for modification
        $data = $countriesData->all();

        // Count trips (folders) per country based on hotel/flight destinations
        foreach ($folders as $folder) {
            $countryCodes = $this->extractCountryCodesFromFolder($folder);

            foreach ($countryCodes as $countryCode) {
                if (isset($data[$countryCode])) {
                    $data[$countryCode]['affected_travelers'] += 1;
                }
            }
        }

        // Fetch and count API trips
        $customer = Customer::find($customerId);
        if ($customer) {
            $apiTravelersByCountry = $this->fetchApiTravelers(
                $customer,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            foreach ($apiTravelersByCountry as $countryCode => $travelers) {
                if (isset($data[$countryCode])) {
                    $data[$countryCode]['affected_travelers'] += count($travelers);
                }
            }
        }

        return collect($data);
    }

    /**
     * Extract country codes from a folder's itineraries.
     */
    protected function extractCountryCodesFromFolder(Folder $folder): array
    {
        $countryCodes = collect();

        foreach ($folder->itineraries as $itinerary) {
            // From hotels
            foreach ($itinerary->hotelServices as $hotel) {
                if ($hotel->country_code) {
                    $countryCodes->push(strtoupper($hotel->country_code));
                }
            }

            // From flights (destination airports)
            foreach ($itinerary->flightServices as $flight) {
                foreach ($flight->segments as $segment) {
                    if ($segment->arrivalAirport?->iso_country) {
                        $countryCodes->push(strtoupper($segment->arrivalAirport->iso_country));
                    }
                }
            }
        }

        return $countryCodes->unique()->values()->toArray();
    }

    /**
     * Get the highest priority from priority counts.
     */
    protected function getHighestPriority(array $priorityCount): string
    {
        if ($priorityCount['high'] > 0) {
            return 'high';
        }
        if ($priorityCount['medium'] > 0) {
            return 'medium';
        }
        if ($priorityCount['low'] > 0) {
            return 'low';
        }

        return 'info';
    }

    /**
     * Calculate summary statistics.
     */
    protected function calculateSummary(Collection $countriesData): array
    {
        $totalEvents = 0;
        $totalAffectedTravelers = 0;

        foreach ($countriesData as $data) {
            $totalEvents += $data['total_events'];
            $totalAffectedTravelers += $data['affected_travelers'];
        }

        return [
            'total_countries' => $countriesData->count(),
            'total_events' => $totalEvents,
            'total_affected_travelers' => $totalAffectedTravelers,
        ];
    }

    /**
     * Format a single event into an array for API responses.
     */
    protected function formatEvent(CustomEvent $event): array
    {
        return [
            'id' => $event->id,
            'uuid' => $event->uuid,
            'title' => $event->title,
            'description' => $event->description,
            'popup_content' => $event->popup_content,
            'priority' => $event->priority,
            'severity' => $event->severity,
            'start_date' => $event->start_date?->format('Y-m-d'),
            'end_date' => $event->end_date?->format('Y-m-d'),
            'event_type' => $event->eventType?->name ?? null,
            'event_category' => $event->eventCategory?->name ?? null,
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
            'radius_km' => $event->radius_km ?? null,
            'data_source' => $event->data_source,
            'tags' => $event->tags,
            'created_at' => $event->created_at?->format('Y-m-d'),
            'updated_at' => $event->updated_at?->format('Y-m-d'),
        ];
    }

    /**
     * Get detailed risk information for a specific country.
     */
    public function getCountryRiskDetails(int $customerId, string $countryCode, int $daysAhead = 30): array
    {
        // Get events for this country
        $events = $this->gtmEventService->getActiveEvents(null, $countryCode);

        // Get travelers in this country
        $travelers = $this->getTravelersInCountry($customerId, $countryCode, $daysAhead);

        // Format events
        $formattedEvents = $events->map(fn (CustomEvent $event) => $this->formatEvent($event))->values()->toArray();

        // Get country info
        $country = Country::where('iso_code', $countryCode)
            ->orWhere('iso3_code', $countryCode)
            ->first();

        $countryName = $country
            ? ($country->name_translations['de'] ?? $country->name_translations['en'] ?? $country->iso_code)
            : null;

        return [
            'country' => $country ? [
                'code' => $country->iso_code,
                'code3' => $country->iso3_code,
                'name' => $countryName,
            ] : null,
            'events' => $formattedEvents,
            'travelers' => $travelers,
            'summary' => [
                'total_events' => count($formattedEvents),
                'total_travelers' => count($travelers),
            ],
        ];
    }

    /**
     * Get detailed risk information for a specific country with custom date range.
     */
    public function getCountryRiskDetailsByDateRange(int $customerId, string $countryCode, string $dateFrom, ?string $dateTo = null): array
    {
        // Get events for this country
        $events = $this->gtmEventService->getActiveEvents(null, $countryCode);

        // Get travelers in this country
        $travelers = $this->getTravelersInCountryByDateRange($customerId, $countryCode, $dateFrom, $dateTo);

        // Format events
        $formattedEvents = $events->map(fn (CustomEvent $event) => $this->formatEvent($event))->values()->toArray();

        // Get country info
        $country = Country::where('iso_code', $countryCode)
            ->orWhere('iso3_code', $countryCode)
            ->first();

        $countryName = $country
            ? ($country->name_translations['de'] ?? $country->name_translations['en'] ?? $country->iso_code)
            : null;

        return [
            'country' => $country ? [
                'code' => $country->iso_code,
                'code3' => $country->iso3_code,
                'name' => $countryName,
            ] : null,
            'events' => $formattedEvents,
            'travelers' => $travelers,
            'summary' => [
                'total_events' => count($formattedEvents),
                'total_travelers' => count($travelers),
            ],
        ];
    }

    /**
     * Get travelers currently in or traveling to a specific country.
     * Includes both local folders and API travelers.
     */
    public function getTravelersInCountry(int $customerId, string $countryCode, int $daysAhead = 30): array
    {
        $today = now()->startOfDay();
        $endDate = $today->copy()->addDays($daysAhead);
        $countryCode = strtoupper($countryCode);

        $folders = Folder::with(['itineraries.hotelServices', 'itineraries.flightServices.segments.arrivalAirport', 'participants', 'labels'])
            ->where('customer_id', $customerId)
            ->where(function ($query) use ($today, $endDate) {
                $query->whereBetween('travel_start_date', [$today, $endDate])
                    ->orWhereBetween('travel_end_date', [$today, $endDate])
                    ->orWhere(function ($q) use ($today, $endDate) {
                        $q->where('travel_start_date', '<=', $today)
                            ->where('travel_end_date', '>=', $endDate);
                    });
            })
            ->get();

        $travelers = [];

        // Add local folder travelers
        foreach ($folders as $folder) {
            $countryCodes = $this->extractCountryCodesFromFolder($folder);

            if (in_array($countryCode, $countryCodes)) {
                $travelers[] = [
                    'folder_id' => $folder->id,
                    'folder_name' => $folder->folder_name ?? 'Reise '.$folder->folder_number,
                    'folder_number' => $folder->folder_number,
                    'start_date' => $folder->travel_start_date?->format('Y-m-d'),
                    'end_date' => $folder->travel_end_date?->format('Y-m-d'),
                    'participants' => $folder->participants->map(function ($p) {
                        return [
                            'name' => trim($p->first_name.' '.$p->last_name),
                            'is_main_contact' => $p->is_main_contact,
                        ];
                    })->toArray(),
                    'participant_count' => $folder->participants->count() ?: 1,
                    'source' => 'local',
                    'source_label' => 'Lokal importiert',
                ];
            }
        }

        // Add API travelers
        $customer = Customer::find($customerId);
        if ($customer) {
            $apiTravelersByCountry = $this->fetchApiTravelers(
                $customer,
                $today->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            if (isset($apiTravelersByCountry[$countryCode])) {
                foreach ($apiTravelersByCountry[$countryCode] as $apiTraveler) {
                    $travelers[] = $apiTraveler;
                }
            }
        }

        // Sort by start_date ascending (earliest first)
        usort($travelers, function ($a, $b) {
            $dateA = $a['start_date'] ?? '9999-12-31';
            $dateB = $b['start_date'] ?? '9999-12-31';

            return strcmp($dateA, $dateB);
        });

        return $travelers;
    }

    /**
     * Get travelers in a specific country using custom date range.
     * Includes both local folders and API travelers.
     */
    public function getTravelersInCountryByDateRange(int $customerId, string $countryCode, string $dateFrom, ?string $dateTo = null): array
    {
        $startDate = \Carbon\Carbon::parse($dateFrom)->startOfDay();
        $endDate = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : $startDate->copy()->addDays(30)->endOfDay();
        $countryCode = strtoupper($countryCode);

        $folders = Folder::with(['itineraries.hotelServices', 'itineraries.flightServices.segments.arrivalAirport', 'participants', 'labels'])
            ->where('customer_id', $customerId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('travel_start_date', [$startDate, $endDate])
                    ->orWhereBetween('travel_end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('travel_start_date', '<=', $startDate)
                            ->where('travel_end_date', '>=', $endDate);
                    });
            })
            ->get();

        $travelers = [];

        // Add local folder travelers
        foreach ($folders as $folder) {
            $countryCodes = $this->extractCountryCodesFromFolder($folder);

            if (in_array($countryCode, $countryCodes)) {
                $travelers[] = [
                    'folder_id' => $folder->id,
                    'folder_name' => $folder->folder_name ?? 'Reise '.$folder->folder_number,
                    'folder_number' => $folder->folder_number,
                    'start_date' => $folder->travel_start_date?->format('Y-m-d'),
                    'end_date' => $folder->travel_end_date?->format('Y-m-d'),
                    'participants' => $folder->participants->map(function ($p) {
                        return [
                            'name' => trim($p->first_name.' '.$p->last_name),
                            'is_main_contact' => $p->is_main_contact,
                        ];
                    })->toArray(),
                    'participant_count' => $folder->participants->count() ?: 1,
                    'source' => 'local',
                    'source_label' => 'Lokal importiert',
                ];
            }
        }

        // Add API travelers
        $customer = Customer::find($customerId);
        if ($customer) {
            $apiTravelersByCountry = $this->fetchApiTravelers(
                $customer,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            if (isset($apiTravelersByCountry[$countryCode])) {
                foreach ($apiTravelersByCountry[$countryCode] as $apiTraveler) {
                    $travelers[] = $apiTraveler;
                }
            }
        }

        // Sort by start_date ascending (earliest first)
        usort($travelers, function ($a, $b) {
            $dateA = $a['start_date'] ?? '9999-12-31';
            $dateB = $b['start_date'] ?? '9999-12-31';

            return strcmp($dateA, $dateB);
        });

        return $travelers;
    }

    /**
     * Fetch travelers from API grouped by trip ID instead of country.
     *
     * @return array<string, array> Trip ID => trip data with destinations
     */
    protected function fetchApiTravelersByTrip(Customer $customer, string $startDate, string $endDate): array
    {
        if (! $this->pdsApiService->hasValidToken($customer)) {
            return [];
        }

        try {
            $apiRequestBody = [
                'sort_by' => 'start_date',
                'sort_order' => 'desc',
                'page' => 1,
                'per_page' => 1000,
                'start_date' => ['<=' => $endDate],
                'end_date' => ['>=' => $startDate],
            ];

            $response = $this->pdsApiService->post($customer, '/travel-details', $apiRequestBody);

            if (! $response || ! $response->successful()) {
                return [];
            }

            $data = $response->json();
            $apiTravelers = $data['data'] ?? [];

            // Pre-load country names
            $allIsoCodes = collect($apiTravelers)->flatMap(function ($t) {
                $codes = $t['destinations'] ?? [];
                $nationalities = $t['nationalities'] ?? [];

                return array_merge($codes, $nationalities);
            })->unique()->values()->toArray();

            $countryNames = Country::whereIn('iso_code', $allIsoCodes)
                ->pluck('name_translations', 'iso_code')
                ->map(fn ($translations) => $translations['de'] ?? $translations['en'] ?? null)
                ->toArray();

            // Group by trip ID
            $tripsByTripId = [];

            foreach ($apiTravelers as $traveler) {
                $tripId = $traveler['tid'] ?? $traveler['id'] ?? uniqid();
                $countryCodes = $this->extractCountryCodesFromApiTraveler($traveler);

                $destinations = collect($traveler['destinations'] ?? [])
                    ->map(fn ($code) => ['code' => strtoupper($code), 'name' => $countryNames[strtoupper($code)] ?? strtoupper($code)])
                    ->values()->toArray();

                $nationalities = collect($traveler['nationalities'] ?? [])
                    ->map(fn ($code) => ['code' => strtoupper($code), 'name' => $countryNames[strtoupper($code)] ?? strtoupper($code)])
                    ->values()->toArray();

                $tripsByTripId[$tripId] = [
                    'trip_id' => $tripId,
                    'folder_id' => 'api-'.$tripId,
                    'folder_name' => $traveler['trip_name'] ?? 'Unbenannte Reise',
                    'start_date' => $traveler['start_date'] ?? null,
                    'end_date' => $traveler['end_date'] ?? null,
                    'participant_count' => $traveler['travelers_count'] ?? 1,
                    'destinations' => $destinations,
                    'destination_codes' => $countryCodes,
                    'nationalities' => $nationalities,
                    'source' => 'api',
                    'source_label' => 'PDS API',
                ];
            }

            return $tripsByTripId;
        } catch (\Exception $e) {
            Log::error('RiskOverviewService: Error fetching API travelers by trip', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get trips with matched events from all destination countries.
     */
    public function getTripsWithEvents(int $customerId, ?string $priorityFilter = null, int $daysAhead = 30): array
    {
        $today = now()->startOfDay();
        $endDate = $today->copy()->addDays($daysAhead);

        return $this->buildTripsWithEvents(
            $customerId,
            $today,
            $endDate,
            $priorityFilter
        );
    }

    /**
     * Get trips with matched events using custom date range.
     */
    public function getTripsWithEventsByDateRange(int $customerId, string $dateFrom, ?string $dateTo = null, ?string $priorityFilter = null): array
    {
        $startDate = \Carbon\Carbon::parse($dateFrom)->startOfDay();
        $endDate = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : $startDate->copy()->addDays(30)->endOfDay();

        return $this->buildTripsWithEvents(
            $customerId,
            $startDate,
            $endDate,
            $priorityFilter
        );
    }

    /**
     * Core logic for building trips with their matched events.
     */
    protected function buildTripsWithEvents(int $customerId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate, ?string $priorityFilter): array
    {
        // 1. Get all active events
        $events = $this->gtmEventService->getActiveEvents($priorityFilter);

        // 2. Index events by country code
        $eventsByCountry = [];
        foreach ($events as $event) {
            $eventCountries = [];

            if ($event->country_id && $event->country) {
                $eventCountries[] = $event->country;
            }

            if ($event->countries->isNotEmpty()) {
                foreach ($event->countries as $country) {
                    $alreadyAdded = false;
                    foreach ($eventCountries as $ec) {
                        if ($ec->id === $country->id) {
                            $alreadyAdded = true;
                            break;
                        }
                    }
                    if (! $alreadyAdded) {
                        $eventCountries[] = $country;
                    }
                }
            }

            $formatted = $this->formatEvent($event);

            foreach ($eventCountries as $country) {
                $code = $country->iso_code;
                $countryName = $country->name_translations['de'] ?? $country->name_translations['en'] ?? $country->iso_code;

                if (! isset($eventsByCountry[$code])) {
                    $eventsByCountry[$code] = [];
                }

                // Avoid duplicates
                $alreadyAdded = false;
                foreach ($eventsByCountry[$code] as $existing) {
                    if ($existing['id'] === $formatted['id']) {
                        $alreadyAdded = true;
                        break;
                    }
                }

                if (! $alreadyAdded) {
                    $formatted['country_code'] = $code;
                    $formatted['country_name'] = $countryName;
                    $eventsByCountry[$code][] = $formatted;
                }
            }
        }

        // 3. Collect all trips (local folders + API)
        $trips = [];

        // Local folders
        $folders = Folder::with(['itineraries.hotelServices', 'itineraries.flightServices.segments.arrivalAirport', 'participants', 'labels'])
            ->where('customer_id', $customerId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('travel_start_date', [$startDate, $endDate])
                    ->orWhereBetween('travel_end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('travel_start_date', '<=', $startDate)
                            ->where('travel_end_date', '>=', $endDate);
                    });
            })
            ->get();

        // Pre-load country names for folder destination codes
        $allFolderCodes = collect();
        foreach ($folders as $folder) {
            $allFolderCodes = $allFolderCodes->merge($this->extractCountryCodesFromFolder($folder));
        }
        $folderCountryNames = [];
        if ($allFolderCodes->isNotEmpty()) {
            $folderCountryNames = Country::whereIn('iso_code', $allFolderCodes->unique()->toArray())
                ->pluck('name_translations', 'iso_code')
                ->map(fn ($translations) => $translations['de'] ?? $translations['en'] ?? null)
                ->toArray();
        }

        foreach ($folders as $folder) {
            $countryCodes = $this->extractCountryCodesFromFolder($folder);
            $destinations = collect($countryCodes)->map(fn ($code) => [
                'code' => $code,
                'name' => $folderCountryNames[$code] ?? $code,
            ])->values()->toArray();

            $trips[] = [
                'folder_id' => $folder->id,
                'folder_name' => $folder->folder_name ?? 'Reise '.$folder->folder_number,
                'folder_number' => $folder->folder_number,
                'start_date' => $folder->travel_start_date?->format('Y-m-d'),
                'end_date' => $folder->travel_end_date?->format('Y-m-d'),
                'participant_count' => $folder->participants->count() ?: 1,
                'destinations' => $destinations,
                'destination_codes' => $countryCodes,
                'labels' => $folder->labels->map(fn ($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'color' => $l->color,
                    'icon' => $l->icon,
                ])->toArray(),
                'source' => 'local',
                'source_label' => 'Lokal importiert',
            ];
        }

        // API trips
        $customer = Customer::find($customerId);
        if ($customer) {
            $apiTrips = $this->fetchApiTravelersByTrip(
                $customer,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            foreach ($apiTrips as $trip) {
                $trips[] = $trip;
            }
        }

        // 4. Match events to trips (by country AND date overlap)
        $tripsWithEvents = [];
        $totalEventsAcrossTrips = 0;
        $tripsWithEventsCount = 0;

        foreach ($trips as $trip) {
            $matchedEvents = [];
            $seenEventIds = [];

            $tripStart = $trip['start_date'] ? \Carbon\Carbon::parse($trip['start_date'])->startOfDay() : null;
            $tripEnd = $trip['end_date'] ? \Carbon\Carbon::parse($trip['end_date'])->endOfDay() : null;

            foreach ($trip['destination_codes'] as $countryCode) {
                if (! isset($eventsByCountry[$countryCode])) {
                    continue;
                }

                foreach ($eventsByCountry[$countryCode] as $event) {
                    // Check date overlap: event must overlap with trip date range
                    // Overlap condition: eventStart <= tripEnd AND eventEnd >= tripStart
                    // Events without dates are always included (ongoing/permanent events)
                    if ($tripStart && $tripEnd) {
                        $eventStart = $event['start_date'] ? \Carbon\Carbon::parse($event['start_date'])->startOfDay() : null;
                        $eventEnd = $event['end_date'] ? \Carbon\Carbon::parse($event['end_date'])->endOfDay() : null;

                        // If event has a start date that is after the trip ends, skip
                        if ($eventStart && $eventStart->gt($tripEnd)) {
                            continue;
                        }

                        // If event has an end date that is before the trip starts, skip
                        if ($eventEnd && $eventEnd->lt($tripStart)) {
                            continue;
                        }
                    }

                    if (in_array($event['id'], $seenEventIds)) {
                        // Add country to matched_countries if not already there
                        foreach ($matchedEvents as &$me) {
                            if ($me['id'] === $event['id']) {
                                if (! in_array($countryCode, array_column($me['matched_countries'], 'code'))) {
                                    $me['matched_countries'][] = [
                                        'code' => $countryCode,
                                        'name' => $event['country_name'],
                                    ];
                                }
                                break;
                            }
                        }
                        unset($me);

                        continue;
                    }

                    $seenEventIds[] = $event['id'];
                    $eventWithMatch = $event;
                    $eventWithMatch['matched_countries'] = [[
                        'code' => $countryCode,
                        'name' => $event['country_name'],
                    ]];
                    $matchedEvents[] = $eventWithMatch;
                }
            }

            // Sort events by priority (high first)
            $priorityOrder = ['high' => 4, 'medium' => 3, 'low' => 2, 'info' => 1];
            usort($matchedEvents, function ($a, $b) use ($priorityOrder) {
                return ($priorityOrder[$b['priority']] ?? 0) - ($priorityOrder[$a['priority']] ?? 0);
            });

            $highestPriority = ! empty($matchedEvents) ? $matchedEvents[0]['priority'] : null;

            $tripData = $trip;
            unset($tripData['destination_codes']);
            $tripData['events'] = $matchedEvents;
            $tripData['total_events'] = count($matchedEvents);
            $tripData['highest_priority'] = $highestPriority;

            $tripsWithEvents[] = $tripData;
            $totalEventsAcrossTrips += count($matchedEvents);

            if (count($matchedEvents) > 0) {
                $tripsWithEventsCount++;
            }
        }

        // 5. Sort: highest priority first, then event count, trips without events last
        usort($tripsWithEvents, function ($a, $b) {
            $priorityOrder = ['high' => 4, 'medium' => 3, 'low' => 2, 'info' => 1];
            $aPriority = $priorityOrder[$a['highest_priority']] ?? 0;
            $bPriority = $priorityOrder[$b['highest_priority']] ?? 0;

            if ($aPriority !== $bPriority) {
                return $bPriority - $aPriority;
            }

            return $b['total_events'] - $a['total_events'];
        });

        return [
            'trips' => $tripsWithEvents,
            'summary' => [
                'total_trips' => count($tripsWithEvents),
                'trips_with_events' => $tripsWithEventsCount,
                'total_events_across_trips' => $totalEventsAcrossTrips,
            ],
        ];
    }
}
