<?php

namespace App\Http\Controllers;

use App\Models\CustomEvent;
use App\Models\Country;
use App\Models\EventType;
use App\Models\Region;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EventFeedController extends Controller
{
    /**
     * Base URL for the application
     */
    private string $baseUrl;

    /**
     * Cache duration in seconds (from .env: FEED_CACHE_DURATION, default 3600)
     */
    private int $cacheDuration;

    /**
     * Maximum items per feed (from .env: FEED_MAX_ITEMS, default 100)
     */
    private int $maxItems;

    public function __construct()
    {
        $baseUrl = config('app.url');
        // Fix common URL typo: https// -> https://
        $this->baseUrl = preg_replace('#^(https?)//(?!/)#', '$1://', $baseUrl);
        $this->cacheDuration = (int) config('feed.cache_duration', 3600);
        $this->maxItems = (int) config('feed.max_items', 100);
    }

    /**
     * Get all active events in RSS format
     */
    public function allEvents(): Response
    {
        $cacheKey = 'feed:all_events:rss';

        $content = Cache::remember($cacheKey, $this->cacheDuration, function () {
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

        $content = Cache::remember($cacheKey, $this->cacheDuration, function () {
            $events = $this->getActiveEvents();
            return $this->generateAtom($events, 'Global Travel Monitor - Aktuelle Ereignisse', 'Aktuelle Reisesicherheitsinformationen und Ereignisse');
        });

        return response($content, 200)->header('Content-Type', 'application/atom+xml; charset=utf-8');
    }

    /**
     * Get high priority events in RSS format
     */
    public function criticalEvents(): Response
    {
        $cacheKey = 'feed:critical_events:rss';

        $content = Cache::remember($cacheKey, $this->cacheDuration, function () {
            $events = $this->getActiveEvents()
                ->where('priority', 'high');

            return $this->generateRss($events, 'Wichtige Ereignisse', 'Ereignisse mit hoher Priorität');
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
            $content = Cache::remember($cacheKey, $this->cacheDuration, function () use ($countryCode) {
                // Find country by ISO code
                $country = Country::where('iso_code', strtoupper($countryCode))
                    ->orWhere('iso3_code', strtoupper($countryCode))
                    ->first();

                if (!$country) {
                    throw new \Exception("Country not found: {$countryCode}");
                }

                // Get events related to this country (filter Collection)
                $events = $this->getActiveEvents()->filter(function ($event) use ($country) {
                    // Check direct country_id
                    if ($event->country_id === $country->id) {
                        return true;
                    }
                    // Check many-to-many countries relation
                    if ($event->countries && $event->countries->contains('id', $country->id)) {
                        return true;
                    }
                    return false;
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
            $content = Cache::remember($cacheKey, $this->cacheDuration, function () use ($typeCode) {
                // Find event type by code
                $eventType = EventType::where('code', strtolower($typeCode))
                    ->where('is_active', true)
                    ->first();

                if (!$eventType) {
                    throw new \Exception("Event type not found: {$typeCode}");
                }

                // Get events of this type (filter Collection)
                $events = $this->getActiveEvents()->filter(function ($event) use ($eventType) {
                    // Check direct event_type_id
                    if ($event->event_type_id === $eventType->id) {
                        return true;
                    }
                    // Check many-to-many eventTypes relation
                    if ($event->eventTypes && $event->eventTypes->contains('id', $eventType->id)) {
                        return true;
                    }
                    return false;
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
            $content = Cache::remember($cacheKey, $this->cacheDuration, function () use ($regionId) {
                // Find region
                $region = Region::with('country')->find($regionId);

                if (!$region) {
                    throw new \Exception("Region not found: {$regionId}");
                }

                // Get events in countries that belong to this region (filter Collection)
                $events = $this->getActiveEvents()->filter(function ($event) use ($region) {
                    // Check if event's countries include the region's country
                    if ($event->countries && $event->countries->contains('id', $region->country_id)) {
                        return true;
                    }
                    // Check direct country_id
                    if ($event->country_id === $region->country_id) {
                        return true;
                    }
                    return false;
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
            ->where('start_date', '<=', now()) // Nur Events die bereits gestartet sind
            ->with([
                'country',
                'countries',
                'eventType',
                'eventTypes',
                'eventCategory'
            ])
            ->orderBy('start_date', 'desc')
            ->limit($this->maxItems)
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
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . PHP_EOL;
        $xml .= '  <channel>' . PHP_EOL;
        $xml .= '    <title>' . $this->escapeXml($title) . '</title>' . PHP_EOL;
        $xml .= '    <link>' . $this->escapeXml($this->baseUrl) . '</link>' . PHP_EOL;
        $xml .= '    <description>' . $this->escapeXml($description) . '</description>' . PHP_EOL;
        $xml .= '    <language>de-DE</language>' . PHP_EOL;
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
        $link = $this->baseUrl . '/?event=' . $event->id;
        $pubDate = ($event->start_date ?? $event->created_at)->toRfc2822String();

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

        // Build short summary (for <description>)
        $details = [];
        // Event type (Typ: Sicherheit, Reiseverkehr, etc.)
        if ($event->eventTypes && $event->eventTypes->count() > 0) {
            $typeNames = $event->eventTypes->pluck('name')->join(', ');
            $details[] = 'Typ: ' . $typeNames;
        } elseif ($event->eventType) {
            $details[] = 'Typ: ' . $event->eventType->name;
        }
        if ($event->start_date) {
            $details[] = 'Beginn: ' . $event->start_date->format('d.m.Y H:i');
        }
        if ($event->end_date) {
            $details[] = 'Ende: ' . $event->end_date->format('d.m.Y H:i');
        }
        if ($event->priority) {
            $priorityTranslations = [
                'low' => 'Niedrig',
                'medium' => 'Mittel',
                'high' => 'Hoch',
                'critical' => 'Kritisch',
                'info' => 'Info',
            ];
            $priorityDe = $priorityTranslations[strtolower($event->priority)] ?? ucfirst($event->priority);
            $details[] = 'Priorität: ' . $priorityDe;
        }
        if ($event->countries && $event->countries->count() > 0) {
            $countryNames = $event->countries->map(fn($c) => $c->getName('de'))->join(', ');
            $details[] = 'Länder: ' . $countryNames;
        } elseif ($event->country) {
            $details[] = 'Land: ' . $event->country->getName('de');
        }

        // Short description = metadata summary
        $shortDescription = !empty($details) ? implode(' | ', $details) : 'Keine Details verfügbar';

        // Full content (for <content:encoded>)
        $eventDescription = $event->description ?: $event->popup_content ?: null;
        $fullContent = $eventDescription
            ? strip_tags($eventDescription)
            : 'Keine Beschreibung verfügbar';

        $xml = '    <item>' . PHP_EOL;
        $xml .= '      <title>' . $this->escapeXml($title) . '</title>' . PHP_EOL;
        $xml .= '      <link>' . $this->escapeXml($link) . '</link>' . PHP_EOL;
        $xml .= '      <guid isPermaLink="true">' . $this->escapeXml($link) . '</guid>' . PHP_EOL;
        $xml .= '      <description>' . $this->escapeXml($shortDescription) . '</description>' . PHP_EOL;
        $xml .= '      <content:encoded><![CDATA[' . $fullContent . ']]></content:encoded>' . PHP_EOL;
        $xml .= '      <pubDate>' . $pubDate . '</pubDate>' . PHP_EOL;

        foreach ($categories as $category) {
            $xml .= '      <category>' . $this->escapeXml($category) . '</category>' . PHP_EOL;
        }

        if ($event->creator) {
            $xml .= '      <dc:creator>' . $this->escapeXml($event->creator->name) . '</dc:creator>' . PHP_EOL;
        }

        // Add country image as enclosure (use first country)
        $countryCode = null;
        if ($event->countries && $event->countries->count() > 0) {
            $countryCode = strtolower($event->countries->first()->iso_code);
        } elseif ($event->country) {
            $countryCode = strtolower($event->country->iso_code);
        }

        if ($countryCode) {
            $imageUrl = $this->getCountryImageUrl($countryCode);
            if ($imageUrl) {
                $xml .= '      <enclosure url="' . $this->escapeXml($imageUrl) . '" type="image/jpeg" />' . PHP_EOL;
            }
        }

        $xml .= '    </item>' . PHP_EOL;

        return $xml;
    }

    /**
     * Get country cover image URL
     */
    private function getCountryImageUrl(string $countryCode): ?string
    {
        $imagePath = public_path('images/countries/' . strtolower($countryCode) . '.jpg');

        if (file_exists($imagePath)) {
            return $this->baseUrl . '/images/countries/' . strtolower($countryCode) . '.jpg';
        }

        return null;
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
        $link = $this->baseUrl . '/?event=' . $event->id;
        $updated = $event->updated_at->toAtomString();
        $published = ($event->start_date ?? $event->created_at)->toAtomString();

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
            : 'Keine Beschreibung verfügbar';

        $details = [];
        // Event type (Typ: Sicherheit, Reiseverkehr, etc.)
        if ($event->eventTypes && $event->eventTypes->count() > 0) {
            $typeNames = $event->eventTypes->pluck('name')->join(', ');
            $details[] = 'Typ: ' . $typeNames;
        } elseif ($event->eventType) {
            $details[] = 'Typ: ' . $event->eventType->name;
        }
        if ($event->start_date) {
            $details[] = 'Beginn: ' . $event->start_date->format('d.m.Y H:i');
        }
        if ($event->end_date) {
            $details[] = 'Ende: ' . $event->end_date->format('d.m.Y H:i');
        }
        if ($event->priority) {
            $priorityTranslations = [
                'low' => 'Niedrig',
                'medium' => 'Mittel',
                'high' => 'Hoch',
                'critical' => 'Kritisch',
                'info' => 'Info',
            ];
            $priorityDe = $priorityTranslations[strtolower($event->priority)] ?? ucfirst($event->priority);
            $details[] = 'Priorität: ' . $priorityDe;
        }
        if ($event->countries && $event->countries->count() > 0) {
            $countryNames = $event->countries->map(fn($c) => $c->getName('de'))->join(', ');
            $details[] = 'Länder: ' . $countryNames;
        } elseif ($event->country) {
            $details[] = 'Land: ' . $event->country->getName('de');
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

        // Add country image as enclosure link (use first country)
        $countryCode = null;
        if ($event->countries && $event->countries->count() > 0) {
            $countryCode = strtolower($event->countries->first()->iso_code);
        } elseif ($event->country) {
            $countryCode = strtolower($event->country->iso_code);
        }

        if ($countryCode) {
            $imageUrl = $this->getCountryImageUrl($countryCode);
            if ($imageUrl) {
                $xml .= '    <link rel="enclosure" type="image/jpeg" href="' . $this->escapeXml($imageUrl) . '" />' . PHP_EOL;
            }
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
