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

        return Cache::remember('gtm_active_events', $cacheDuration, function () {
            return CustomEvent::active()
                ->notArchived()
                ->where('start_date', '<=', now())
                ->with([
                    'country.continent', 'country.capital',
                    'countries.continent', 'countries.capital',
                    'eventType', 'eventTypes', 'eventCategory',
                ])
                ->orderBy('start_date', 'desc')
                ->limit(100)
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
    ): Collection {
        $events = $this->getBaseEvents();

        if ($priority !== null) {
            $events = $events->where('priority', $priority);
        }

        if ($countryCode !== null) {
            $country = Country::where('iso_code', $countryCode)
                ->orWhere('iso3_code', $countryCode)
                ->first();

            if ($country) {
                $events = $events->filter(function (CustomEvent $event) use ($country) {
                    return $event->country_id === $country->id
                        || $event->countries->contains('id', $country->id);
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
