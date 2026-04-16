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
