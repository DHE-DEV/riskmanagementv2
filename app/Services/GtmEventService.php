<?php

namespace App\Services;

use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Models\Region;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GtmEventService
{
    /**
     * Get the base active events query result from cache.
     */
    protected function getBaseEvents(): Collection
    {
        $cacheDuration = config('feed.cache_duration', 3600);

        return Cache::remember('gtm_all_events', $cacheDuration, function () {
            return CustomEvent::active()
                ->notArchived()
                ->approved()
                ->global()
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', now()->startOfDay());
                })
                ->with([
                    'country.continent', 'country.capital',
                    'countries.continent', 'countries.capital',
                    'eventType', 'eventTypes', 'eventCategory',
                    'apiClient',
                ])
                ->orderBy('start_date', 'desc')
                ->get();
        });
    }

    /**
     * Get active events, optionally filtered by priority, country, event type, or region.
     */
    public function getActiveEvents(
        ?string $priority = null,
        ?string $countryCode = null,
        ?string $eventTypeCode = null,
        ?int $regionId = null,
        ?string $source = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $events = $this->getBaseEvents();

        if ($startDate !== null) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $events = $events->filter(fn (CustomEvent $event) =>
                $event->start_date === null || $event->start_date >= $start
            );
        }

        if ($endDate !== null) {
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $events = $events->filter(fn (CustomEvent $event) =>
                $event->start_date !== null && $event->start_date <= $end
            );
        }

        if ($priority !== null) {
            $events = $events->where('priority', $priority);
        }

        if ($countryCode !== null) {
            $codes = array_map('trim', explode(',', $countryCode));
            $countries = Country::where(function ($query) use ($codes) {
                $query->whereIn('iso_code', $codes)
                      ->orWhereIn('iso3_code', $codes);
            })->get();

            if ($countries->isNotEmpty()) {
                $countryIds = $countries->pluck('id');
                $events = $events->filter(function (CustomEvent $event) use ($countryIds) {
                    return $countryIds->contains($event->country_id)
                        || $event->countries->pluck('id')->intersect($countryIds)->isNotEmpty();
                });
            } else {
                $events = collect();
            }
        }

        if ($eventTypeCode !== null) {
            $eventType = EventType::where('code', $eventTypeCode)
                ->where('is_active', true)
                ->first();

            if ($eventType) {
                $events = $events->filter(function (CustomEvent $event) use ($eventType) {
                    return $event->event_type_id === $eventType->id
                        || $event->eventTypes->contains('id', $eventType->id);
                });
            } else {
                $events = collect();
            }
        }

        if ($source !== null) {
            $events = $events->filter(function (CustomEvent $event) use ($source) {
                // Filter by data_source value or by API client name/slug
                if ($event->data_source === $source) {
                    return true;
                }

                if ($event->apiClient) {
                    return strcasecmp($event->apiClient->name, $source) === 0
                        || strcasecmp($event->apiClient->company_name ?? '', $source) === 0;
                }

                return false;
            });
        }

        if ($regionId !== null) {
            $region = Region::find($regionId);

            if ($region) {
                $events = $events->filter(function (CustomEvent $event) use ($region) {
                    return $event->country_id === $region->country_id
                        || $event->countries->contains('id', $region->country_id);
                });
            } else {
                $events = collect();
            }
        }

        return $events->values();
    }

    /**
     * Get active events within a radius (km) of given coordinates.
     */
    public function getEventsNearbyCoordinates(float $latitude, float $longitude, float $radiusKm): Collection
    {
        $events = $this->getBaseEvents();

        return $events->filter(function (CustomEvent $event) use ($latitude, $longitude, $radiusKm) {
            $coordinates = $this->resolveEventCoordinates($event);

            if ($coordinates === null) {
                return false;
            }

            $distance = $this->haversineDistance($latitude, $longitude, $coordinates['lat'], $coordinates['lng']);

            return $distance <= $radiusKm;
        })->values();
    }

    /**
     * Resolve the primary coordinates for an event using the same cascade as the API resource.
     */
    private function resolveEventCoordinates(CustomEvent $event): ?array
    {
        // Try first country pivot coordinates
        if ($event->relationLoaded('countries') && $event->countries->isNotEmpty()) {
            foreach ($event->countries as $country) {
                $lat = null;
                $lng = null;

                if ($country->pivot && $country->pivot->use_default_coordinates) {
                    if ($country->pivot->city_id) {
                        $city = \App\Models\City::find($country->pivot->city_id);
                        if ($city && $city->lat && $city->lng) {
                            $lat = (float) $city->lat;
                            $lng = (float) $city->lng;
                        }
                    }
                    if (!$lat && !$lng && $country->pivot->region_id) {
                        $region = Region::find($country->pivot->region_id);
                        if ($region && $region->lat && $region->lng) {
                            $lat = (float) $region->lat;
                            $lng = (float) $region->lng;
                        }
                    }
                    if (!$lat && !$lng && $country->capital && $country->capital->lat && $country->capital->lng) {
                        $lat = (float) $country->capital->lat;
                        $lng = (float) $country->capital->lng;
                    }
                    if (!$lat && !$lng) {
                        $lat = (float) $country->lat;
                        $lng = (float) $country->lng;
                    }
                } elseif ($country->pivot && $country->pivot->latitude && $country->pivot->longitude) {
                    $lat = (float) $country->pivot->latitude;
                    $lng = (float) $country->pivot->longitude;
                }

                if ($lat && $lng) {
                    return ['lat' => $lat, 'lng' => $lng];
                }
            }
        }

        // Fallback: event-level coordinates
        if ($event->latitude && $event->longitude) {
            return ['lat' => (float) $event->latitude, 'lng' => (float) $event->longitude];
        }

        return null;
    }

    /**
     * Calculate the distance between two points using the Haversine formula.
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    /**
     * Get countries that have active events, with a count of active events per country.
     */
    public function getCountriesWithEventCounts(): Collection
    {
        $events = $this->getBaseEvents();

        $countryIds = collect();

        $events->each(function (CustomEvent $event) use ($countryIds) {
            if ($event->country_id) {
                $countryIds->push($event->country_id);
            }

            $event->countries->each(function ($country) use ($countryIds) {
                $countryIds->push($country->id);
            });
        });

        $countryCounts = $countryIds->countBy();

        $countries = Country::whereIn('id', $countryCounts->keys())
            ->with('continent')
            ->get();

        return $countries->map(function (Country $country) use ($countryCounts) {
            $country->active_events_count = $countryCounts->get($country->id, 0);

            return $country;
        })->sortByDesc('active_events_count')->values();
    }
}
