<?php

namespace App\Http\Controllers;

use App\Models\CustomEvent;
use App\Models\Country;
use App\Models\EventType;
use App\Models\Region;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FeedController extends Controller
{
    /**
     * Cache duration in seconds (1 hour)
     */
    private const CACHE_DURATION = 3600;

    /**
     * Maximum items per feed
     */
    private const MAX_ITEMS = 100;

    /**
     * Base URL for the application
     */
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('app.url');
    }

    /**
     * Get all active events in RSS format
     */
    public function allEvents(): Response
    {
        $cacheKey = 'feed:all_events:rss';

        $content = Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            $events = $this->getActiveEvents();
            return $this->generateRss($events, 'Global Travel Monitor - Aktuelle Ereignisse', 'Aktuelle Reisesicherheitsinformationen und Ereignisse');
        });

        return response($content, 200)->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Get all active events in Atom format
     */
    public function allEventsAtom(): Response
    {
        $cacheKey = 'feed:all_events:atom';

        $content = Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            $events = $this->getActiveEvents();
            return $this->generateAtom($events, 'Global Travel Monitor - Aktuelle Ereignisse', 'Aktuelle Reisesicherheitsinformationen und Ereignisse');
        });

        return response($content, 200)->header('Content-Type', 'application/atom+xml; charset=utf-8');
    }

    /**
     * Get critical/high priority events in RSS format
     */
    public function criticalEvents(): Response
    {
        $cacheKey = 'feed:critical_events:rss';

        $content = Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            $events = $this->getActiveEvents()
                ->whereIn('priority', ['high', 'critical']);

            return $this->generateRss($events, 'Critical & High Priority Events', 'High and critical priority risk management events');
        });

        return response($content, 200)->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Get events by country code
     */
    public function byCountry(string $countryCode): Response
    {
        $cacheKey = "feed:country:{$countryCode}:rss";

        try {
            $content = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($countryCode) {
                // Find country by ISO code
                $country = Country::where('iso_code', strtoupper($countryCode))
                    ->orWhere('iso3_code', strtoupper($countryCode))
                    ->first();

                if (!$country) {
                    throw new \Exception("Country not found: {$countryCode}");
                }

                // Get events related to this country
                $events = $this->getActiveEvents()
                    ->where(function ($query) use ($country) {
                        $query->where('country_id', $country->id)
                            ->orWhereHas('countries', function ($q) use ($country) {
                                $q->where('countries.id', $country->id);
                            });
                    });

                $countryName = $country->getName('en');
                return $this->generateRss(
                    $events,
                    "Events in {$countryName}",
                    "Risk management events for {$countryName}"
                );
            });

            return response($content, 200)->header('Content-Type', 'application/rss+xml; charset=utf-8');
        } catch (\Exception $e) {
            Log::error("Feed error for country {$countryCode}: " . $e->getMessage());
            return $this->generateErrorResponse("Country not found: {$countryCode}");
        }
    }

    /**
     * Get events by event type code
     */
    public function byEventType(string $typeCode): Response
    {
        $cacheKey = "feed:type:{$typeCode}:rss";

        try {
            $content = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($typeCode) {
                // Find event type by code
                $eventType = EventType::where('code', strtolower($typeCode))
                    ->where('is_active', true)
                    ->first();

                if (!$eventType) {
                    throw new \Exception("Event type not found: {$typeCode}");
                }

                // Get events of this type
                $events = $this->getActiveEvents()
                    ->where(function ($query) use ($eventType) {
                        $query->where('event_type_id', $eventType->id)
                            ->orWhereHas('eventTypes', function ($q) use ($eventType) {
                                $q->where('event_types.id', $eventType->id);
                            });
                    });

                return $this->generateRss(
                    $events,
                    "{$eventType->name} Events",
                    "Risk management events of type: {$eventType->name}"
                );
            });

            return response($content, 200)->header('Content-Type', 'application/rss+xml; charset=utf-8');
        } catch (\Exception $e) {
            Log::error("Feed error for event type {$typeCode}: " . $e->getMessage());
            return $this->generateErrorResponse("Event type not found: {$typeCode}");
        }
    }

    /**
     * Get events by region ID
     */
    public function byRegion(int $regionId): Response
    {
        $cacheKey = "feed:region:{$regionId}:rss";

        try {
            $content = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($regionId) {
                // Find region
                $region = Region::with('country')->find($regionId);

                if (!$region) {
                    throw new \Exception("Region not found: {$regionId}");
                }

                // Get events in countries that belong to this region
                $events = $this->getActiveEvents()
                    ->whereHas('countries', function ($query) use ($region) {
                        $query->where('countries.id', $region->country_id);
                    });

                $regionName = $region->getName('en');
                $countryName = $region->country->getName('en');

                return $this->generateRss(
                    $events,
                    "Events in {$regionName}, {$countryName}",
                    "Risk management events for the {$regionName} region"
                );
            });

            return response($content, 200)->header('Content-Type', 'application/rss+xml; charset=utf-8');
        } catch (\Exception $e) {
            Log::error("Feed error for region {$regionId}: " . $e->getMessage());
            return $this->generateErrorResponse("Region not found: {$regionId}");
        }
    }

    /**
     * Get base query for active events with eager loading
     */
    private function getActiveEvents()
    {
        return CustomEvent::active()
            ->notArchived()
            ->with([
                'country',
                'countries',
                'eventType',
                'eventTypes',
                'eventCategory'
            ])
            ->orderBy('start_date', 'desc')
            ->limit(self::MAX_ITEMS)
            ->get();
    }

    /**
     * Generate RSS 2.0 feed
     */
    private function generateRss($events, string $title, string $description): string
    {
        $buildDate = now()->toRfc2822String();
        $lastBuildDate = $events->first()?->updated_at?->toRfc2822String() ?? $buildDate;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">' . PHP_EOL;
        $xml .= '  <channel>' . PHP_EOL;
        $xml .= '    <title>' . $this->escapeXml($title) . '</title>' . PHP_EOL;
        $xml .= '    <link>' . $this->escapeXml($this->baseUrl) . '</link>' . PHP_EOL;
        $xml .= '    <description>' . $this->escapeXml($description) . '</description>' . PHP_EOL;
        $xml .= '    <language>en-us</language>' . PHP_EOL;
        $xml .= '    <lastBuildDate>' . $lastBuildDate . '</lastBuildDate>' . PHP_EOL;
        $xml .= '    <pubDate>' . $buildDate . '</pubDate>' . PHP_EOL;
        $xml .= '    <atom:link href="' . $this->escapeXml(url()->current()) . '" rel="self" type="application/rss+xml" />' . PHP_EOL;

        foreach ($events as $event) {
            $xml .= $this->generateRssItem($event);
        }

        $xml .= '  </channel>' . PHP_EOL;
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Generate RSS item
     */
    private function generateRssItem($event): string
    {
        $link = $this->baseUrl . '/events/' . $event->id;
        $pubDate = $event->created_at->toRfc2822String();

        // Build title with country name
        $title = $event->title;
        if ($event->countries && $event->countries->count() > 0) {
            $countryNames = $event->countries->map(fn($c) => $c->getName('de'))->join(', ');
            $title = $title . ' (' . $countryNames . ')';
        } elseif ($event->country) {
            $title = $title . ' (' . $event->country->getName('de') . ')';
        }

        // Build categories
        $categories = [];
        if ($event->priority) {
            $categories[] = 'Priority: ' . ucfirst($event->priority);
        }
        if ($event->eventTypes && $event->eventTypes->count() > 0) {
            foreach ($event->eventTypes as $eventType) {
                $categories[] = $eventType->name;
            }
        } elseif ($event->eventType) {
            $categories[] = $event->eventType->name;
        }

        // Build description with all relevant information
        // Use description, fallback to popup_content, then to 'No description available'
        $eventDescription = $event->description ?: $event->popup_content ?: null;
        $description = $eventDescription
            ? $this->escapeXml(strip_tags($eventDescription))
            : 'No description available';

        $details = [];
        if ($event->start_date) {
            $details[] = 'Start: ' . $event->start_date->format('Y-m-d H:i');
        }
        if ($event->end_date) {
            $details[] = 'End: ' . $event->end_date->format('Y-m-d H:i');
        }
        if ($event->priority) {
            $details[] = 'Priority: ' . ucfirst($event->priority);
        }
        if ($event->countries && $event->countries->count() > 0) {
            $countryNames = $event->countries->map(fn($c) => $c->getName('en'))->join(', ');
            $details[] = 'Countries: ' . $countryNames;
        } elseif ($event->country) {
            $details[] = 'Country: ' . $event->country->getName('en');
        }

        if (!empty($details)) {
            $description .= "\n\n" . implode(' | ', $details);
        }

        $xml = '    <item>' . PHP_EOL;
        $xml .= '      <title>' . $this->escapeXml($title) . '</title>' . PHP_EOL;
        $xml .= '      <link>' . $this->escapeXml($link) . '</link>' . PHP_EOL;
        $xml .= '      <guid isPermaLink="true">' . $this->escapeXml($link) . '</guid>' . PHP_EOL;
        $xml .= '      <description>' . $description . '</description>' . PHP_EOL;
        $xml .= '      <pubDate>' . $pubDate . '</pubDate>' . PHP_EOL;

        foreach ($categories as $category) {
            $xml .= '      <category>' . $this->escapeXml($category) . '</category>' . PHP_EOL;
        }

        if ($event->creator) {
            $xml .= '      <dc:creator>' . $this->escapeXml($event->creator->name) . '</dc:creator>' . PHP_EOL;
        }

        $xml .= '    </item>' . PHP_EOL;

        return $xml;
    }

    /**
     * Generate Atom 1.0 feed
     */
    private function generateAtom($events, string $title, string $subtitle): string
    {
        $updated = $events->first()?->updated_at?->toAtomString() ?? now()->toAtomString();
        $feedUrl = url()->current();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">' . PHP_EOL;
        $xml .= '  <title>' . $this->escapeXml($title) . '</title>' . PHP_EOL;
        $xml .= '  <subtitle>' . $this->escapeXml($subtitle) . '</subtitle>' . PHP_EOL;
        $xml .= '  <link href="' . $this->escapeXml($this->baseUrl) . '" rel="alternate" />' . PHP_EOL;
        $xml .= '  <link href="' . $this->escapeXml($feedUrl) . '" rel="self" />' . PHP_EOL;
        $xml .= '  <id>' . $this->escapeXml($feedUrl) . '</id>' . PHP_EOL;
        $xml .= '  <updated>' . $updated . '</updated>' . PHP_EOL;

        foreach ($events as $event) {
            $xml .= $this->generateAtomEntry($event);
        }

        $xml .= '</feed>';

        return $xml;
    }

    /**
     * Generate Atom entry
     */
    private function generateAtomEntry($event): string
    {
        $link = $this->baseUrl . '/events/' . $event->id;
        $updated = $event->updated_at->toAtomString();
        $published = $event->created_at->toAtomString();

        // Build title with country name
        $title = $event->title;
        if ($event->countries && $event->countries->count() > 0) {
            $countryNames = $event->countries->map(fn($c) => $c->getName('de'))->join(', ');
            $title = $title . ' (' . $countryNames . ')';
        } elseif ($event->country) {
            $title = $title . ' (' . $event->country->getName('de') . ')';
        }

        // Build content
        // Use description, fallback to popup_content, then to 'No description available'
        $eventDescription = $event->description ?: $event->popup_content ?: null;
        $content = $eventDescription
            ? $this->escapeXml(strip_tags($eventDescription))
            : 'No description available';

        $details = [];
        if ($event->start_date) {
            $details[] = 'Start: ' . $event->start_date->format('Y-m-d H:i');
        }
        if ($event->end_date) {
            $details[] = 'End: ' . $event->end_date->format('Y-m-d H:i');
        }
        if ($event->priority) {
            $details[] = 'Priority: ' . ucfirst($event->priority);
        }
        if ($event->countries && $event->countries->count() > 0) {
            $countryNames = $event->countries->map(fn($c) => $c->getName('en'))->join(', ');
            $details[] = 'Countries: ' . $countryNames;
        } elseif ($event->country) {
            $details[] = 'Country: ' . $event->country->getName('en');
        }

        if (!empty($details)) {
            $content .= "\n\n" . implode(' | ', $details);
        }

        $xml = '  <entry>' . PHP_EOL;
        $xml .= '    <title>' . $this->escapeXml($title) . '</title>' . PHP_EOL;
        $xml .= '    <link href="' . $this->escapeXml($link) . '" />' . PHP_EOL;
        $xml .= '    <id>' . $this->escapeXml($link) . '</id>' . PHP_EOL;
        $xml .= '    <updated>' . $updated . '</updated>' . PHP_EOL;
        $xml .= '    <published>' . $published . '</published>' . PHP_EOL;
        $xml .= '    <content type="text">' . $content . '</content>' . PHP_EOL;

        if ($event->priority) {
            $xml .= '    <category term="' . $this->escapeXml(strtolower($event->priority)) . '" label="Priority: ' . $this->escapeXml(ucfirst($event->priority)) . '" />' . PHP_EOL;
        }

        if ($event->eventTypes && $event->eventTypes->count() > 0) {
            foreach ($event->eventTypes as $eventType) {
                $xml .= '    <category term="' . $this->escapeXml($eventType->code) . '" label="' . $this->escapeXml($eventType->name) . '" />' . PHP_EOL;
            }
        } elseif ($event->eventType) {
            $xml .= '    <category term="' . $this->escapeXml($event->eventType->code) . '" label="' . $this->escapeXml($event->eventType->name) . '" />' . PHP_EOL;
        }

        if ($event->creator) {
            $xml .= '    <author>' . PHP_EOL;
            $xml .= '      <name>' . $this->escapeXml($event->creator->name) . '</name>' . PHP_EOL;
            $xml .= '    </author>' . PHP_EOL;
        }

        $xml .= '  </entry>' . PHP_EOL;

        return $xml;
    }

    /**
     * Escape XML special characters
     */
    private function escapeXml(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate error response in RSS format
     */
    private function generateErrorResponse(string $message): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<rss version="2.0">' . PHP_EOL;
        $xml .= '  <channel>' . PHP_EOL;
        $xml .= '    <title>Error</title>' . PHP_EOL;
        $xml .= '    <link>' . $this->escapeXml($this->baseUrl) . '</link>' . PHP_EOL;
        $xml .= '    <description>' . $this->escapeXml($message) . '</description>' . PHP_EOL;
        $xml .= '  </channel>' . PHP_EOL;
        $xml .= '</rss>';

        return response($xml, 404)->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
