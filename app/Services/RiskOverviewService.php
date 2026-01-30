<?php

namespace App\Services;

use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\Folder\Folder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RiskOverviewService
{
    protected GtmEventService $gtmEventService;

    public function __construct(GtmEventService $gtmEventService)
    {
        $this->gtmEventService = $gtmEventService;
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
     * Enrich country data with traveler counts from customer's folders.
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

        // Count travelers per country based on hotel/flight destinations
        foreach ($folders as $folder) {
            $countryCodes = $this->extractCountryCodesFromFolder($folder);

            foreach ($countryCodes as $countryCode) {
                if (isset($data[$countryCode])) {
                    $travelerCount = $folder->participants->count() ?: 1;
                    $data[$countryCode]['affected_travelers'] += $travelerCount;
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

        // Count travelers per country based on hotel/flight destinations
        foreach ($folders as $folder) {
            $countryCodes = $this->extractCountryCodesFromFolder($folder);

            foreach ($countryCodes as $countryCode) {
                if (isset($data[$countryCode])) {
                    $travelerCount = $folder->participants->count() ?: 1;
                    $data[$countryCode]['affected_travelers'] += $travelerCount;
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
     * Get detailed risk information for a specific country.
     */
    public function getCountryRiskDetails(int $customerId, string $countryCode, int $daysAhead = 30): array
    {
        // Get events for this country
        $events = $this->gtmEventService->getActiveEvents(null, $countryCode);

        // Get travelers in this country
        $travelers = $this->getTravelersInCountry($customerId, $countryCode, $daysAhead);

        // Format events
        $formattedEvents = $events->map(function (CustomEvent $event) {
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
        })->values()->toArray();

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
        $formattedEvents = $events->map(function (CustomEvent $event) {
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
        })->values()->toArray();

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
     */
    public function getTravelersInCountry(int $customerId, string $countryCode, int $daysAhead = 30): array
    {
        $today = now()->startOfDay();
        $endDate = $today->copy()->addDays($daysAhead);
        $countryCode = strtoupper($countryCode);

        $folders = Folder::with(['itineraries.hotelServices', 'itineraries.flightServices.segments.arrivalAirport', 'participants'])
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
                ];
            }
        }

        return $travelers;
    }

    /**
     * Get travelers in a specific country using custom date range.
     */
    public function getTravelersInCountryByDateRange(int $customerId, string $countryCode, string $dateFrom, ?string $dateTo = null): array
    {
        $startDate = \Carbon\Carbon::parse($dateFrom)->startOfDay();
        $endDate = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : $startDate->copy()->addDays(30)->endOfDay();
        $countryCode = strtoupper($countryCode);

        $folders = Folder::with(['itineraries.hotelServices', 'itineraries.flightServices.segments.arrivalAirport', 'participants'])
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
                ];
            }
        }

        return $travelers;
    }
}
