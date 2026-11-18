<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
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
        $locale = $this->applyRequestedLanguage();
        if ($locale === null) {
            return $this->unsupportedLanguageResponse();
        }

        $cacheKey = "feed:all_events:rss:{$locale}";

        $content = Cache::remember($cacheKey, $this->cacheDuration, function () {
            $events = $this->getActiveEvents();

            return $this->generateRss($events, __('feed.all_title'), __('feed.all_description'));
        });

        return $this->feedResponse($content, 'application/rss+xml', $locale);
    }

    /**
     * Get all active events in Atom format
     */
    public function allEventsAtom(): Response
    {
        $locale = $this->applyRequestedLanguage();
        if ($locale === null) {
            return $this->unsupportedLanguageResponse();
        }

        $cacheKey = "feed:all_events:atom:{$locale}";

        $content = Cache::remember($cacheKey, $this->cacheDuration, function () {
            $events = $this->getActiveEvents();

            return $this->generateAtom($events, __('feed.all_title'), __('feed.all_description'));
        });

        return $this->feedResponse($content, 'application/atom+xml', $locale);
    }

    /**
     * Get events by priority level
     * Valid priorities: high, medium, low, info
     */
    public function byPriority(string $priority): Response
    {
        $locale = $this->applyRequestedLanguage();
        if ($locale === null) {
            return $this->unsupportedLanguageResponse();
        }

        $validPriorities = ['high', 'medium', 'low', 'info'];
        $priority = strtolower($priority);

        if (! in_array($priority, $validPriorities)) {
            return $this->generateErrorResponse("Ungültige Priorität: {$priority}. Gültig: ".implode(', ', $validPriorities));
        }

        $cacheKey = "feed:priority:{$priority}:rss:{$locale}";

        $content = Cache::remember($cacheKey, $this->cacheDuration, function () use ($priority) {
            $events = $this->getActiveEvents()
                ->where('priority', $priority);

            $priorityName = $this->priorityLabel($priority);

            return $this->generateRss(
                $events,
                __('feed.priority_title', ['priority' => $priorityName]),
                __('feed.priority_description', ['priority' => $priorityName])
            );
        });

        return $this->feedResponse($content, 'application/rss+xml', $locale);
    }

    /**
     * Get events by country code
     */
    public function byCountry(string $countryCode): Response
    {
        $locale = $this->applyRequestedLanguage();
        if ($locale === null) {
            return $this->unsupportedLanguageResponse();
        }

        $cacheKey = "feed:country:{$countryCode}:rss:{$locale}";

        try {
            $content = Cache::remember($cacheKey, $this->cacheDuration, function () use ($countryCode) {
                // Find country by ISO code
                $country = Country::where('iso_code', strtoupper($countryCode))
                    ->orWhere('iso3_code', strtoupper($countryCode))
                    ->first();

                if (! $country) {
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

                $countryName = $country->getName(App::getLocale());

                return $this->generateRss(
                    $events,
                    __('feed.country_title', ['country' => $countryName]),
                    __('feed.country_description', ['country' => $countryName])
                );
            });

            return $this->feedResponse($content, 'application/rss+xml', $locale);
        } catch (\Exception $e) {
            Log::error("Feed error for country {$countryCode}: ".$e->getMessage());

            return $this->generateErrorResponse("Country not found: {$countryCode}");
        }
    }

    /**
     * Get events by event type code
     */
    public function byEventType(string $typeCode): Response
    {
        $locale = $this->applyRequestedLanguage();
        if ($locale === null) {
            return $this->unsupportedLanguageResponse();
        }

        $cacheKey = "feed:type:{$typeCode}:rss:{$locale}";

        try {
            $content = Cache::remember($cacheKey, $this->cacheDuration, function () use ($typeCode) {
                // Find event type by code
                $eventType = EventType::where('code', strtolower($typeCode))
                    ->where('is_active', true)
                    ->first();

                if (! $eventType) {
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
                    __('feed.type_title', ['type' => $eventType->name]),
                    __('feed.type_description', ['type' => $eventType->name])
                );
            });

            return $this->feedResponse($content, 'application/rss+xml', $locale);
        } catch (\Exception $e) {
            Log::error("Feed error for event type {$typeCode}: ".$e->getMessage());

            return $this->generateErrorResponse("Event type not found: {$typeCode}");
        }
    }

    /**
     * Get events by region ID
     */
    public function byRegion(int $regionId): Response
    {
        $locale = $this->applyRequestedLanguage();
        if ($locale === null) {
            return $this->unsupportedLanguageResponse();
        }

        $cacheKey = "feed:region:{$regionId}:rss:{$locale}";

        try {
            $content = Cache::remember($cacheKey, $this->cacheDuration, function () use ($regionId) {
                // Find region
                $region = Region::with('country')->find($regionId);

                if (! $region) {
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

                $regionName = $region->getName(App::getLocale());
                $countryName = $region->country->getName(App::getLocale());

                return $this->generateRss(
                    $events,
                    __('feed.region_title', ['region' => $regionName, 'country' => $countryName]),
                    __('feed.region_description', ['region' => $regionName])
                );
            });

            return $this->feedResponse($content, 'application/rss+xml', $locale);
        } catch (\Exception $e) {
            Log::error("Feed error for region {$regionId}: ".$e->getMessage());

            return $this->generateErrorResponse("Region not found: {$regionId}");
        }
    }

    /**
     * Get available priorities and event types as JSON
     */
    public function meta(): JsonResponse
    {
        $priorities = [
            ['code' => 'high', 'name_de' => 'Hoch', 'name_en' => 'High'],
            ['code' => 'medium', 'name_de' => 'Mittel', 'name_en' => 'Medium'],
            ['code' => 'low', 'name_de' => 'Niedrig', 'name_en' => 'Low'],
            ['code' => 'info', 'name_de' => 'Information', 'name_en' => 'Info'],
        ];

        $eventTypes = EventType::active()
            ->ordered()
            ->get(['code', 'name', 'description', 'icon', 'color'])
            ->toArray();

        return response()->json([
            'priorities' => $priorities,
            'event_types' => $eventTypes,
        ]);
    }

    /**
     * Get base query for active events with eager loading
     */
    private function getActiveEvents()
    {
        return CustomEvent::active()
            ->notArchived()
            ->global()
            ->where('start_date', '<=', now()) // Nur Events die bereits gestartet sind
            ->with([
                'country.continent',
                'country.capital',
                'countries.continent',
                'countries.capital',
                'eventType',
                'eventTypes',
                'eventCategory',
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

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:country="http://global-travel-monitor.eu/ns/country" xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#" xmlns:article="http://global-travel-monitor.eu/ns/article">'.PHP_EOL;
        $xml .= '  <channel>'.PHP_EOL;
        $xml .= '    <title>'.$this->escapeXml($title).'</title>'.PHP_EOL;
        $xml .= '    <link>'.$this->escapeXml($this->baseUrl).'</link>'.PHP_EOL;
        $xml .= '    <description>'.$this->escapeXml($description).'</description>'.PHP_EOL;
        $xml .= '    <language>'.$this->escapeXml(__('feed.language_tag')).'</language>'.PHP_EOL;
        $xml .= '    <lastBuildDate>'.$lastBuildDate.'</lastBuildDate>'.PHP_EOL;
        $xml .= '    <pubDate>'.$buildDate.'</pubDate>'.PHP_EOL;
        $xml .= '    <atom:link href="'.$this->escapeXml(url()->current()).'" rel="self" type="application/rss+xml" />'.PHP_EOL;

        foreach ($events as $event) {
            $xml .= $this->generateRssItem($event);
        }

        $xml .= '  </channel>'.PHP_EOL;
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Generate RSS item
     */
    private function generateRssItem($event): string
    {
        $link = $this->baseUrl.'/?event='.$event->id;
        $pubDate = $event->created_at->toRfc2822String();
        $title = $event->title;

        // Build categories
        $categories = [];
        if ($event->priority) {
            $categories[] = 'Priority: '.ucfirst($event->priority);
        }
        if ($event->eventTypes && $event->eventTypes->count() > 0) {
            foreach ($event->eventTypes as $eventType) {
                $categories[] = $eventType->name;
            }
        } elseif ($event->eventType) {
            $categories[] = $event->eventType->name;
        }

        // Short description = metadata summary
        $details = $this->eventDetails($event);
        $shortDescription = ! empty($details) ? implode(' | ', $details) : __('feed.no_details');

        // Full content (for <content:encoded>)
        $eventDescription = $this->eventDescription($event);
        $fullContent = $eventDescription
            ? strip_tags($eventDescription)
            : __('feed.no_description');

        $xml = '    <item>'.PHP_EOL;
        $xml .= '      <title>'.$this->escapeXml($title).'</title>'.PHP_EOL;
        $xml .= '      <link>'.$this->escapeXml($link).'</link>'.PHP_EOL;
        $xml .= '      <guid isPermaLink="true">'.$this->escapeXml($link).'</guid>'.PHP_EOL;
        $xml .= '      <description>'.$this->escapeXml($shortDescription).'</description>'.PHP_EOL;
        $xml .= '      <content:encoded><![CDATA['.$fullContent.']]></content:encoded>'.PHP_EOL;
        $xml .= '      <pubDate>'.$pubDate.'</pubDate>'.PHP_EOL;

        // Article-specific data (display period, priority, and event types)
        $xml .= '      <article:data>'.PHP_EOL;
        if ($event->start_date) {
            $xml .= '        <article:start_date>'.$event->start_date->toRfc2822String().'</article:start_date>'.PHP_EOL;
        }
        if ($event->end_date) {
            $xml .= '        <article:end_date>'.$event->end_date->toRfc2822String().'</article:end_date>'.PHP_EOL;
        }
        if ($event->priority) {
            $xml .= '        <article:priority>'.$this->escapeXml($event->priority).'</article:priority>'.PHP_EOL;
        }
        // Event types
        if ($event->eventTypes && $event->eventTypes->count() > 0) {
            foreach ($event->eventTypes as $eventType) {
                $xml .= '        <article:event_type code="'.$this->escapeXml($eventType->code).'">'.$this->escapeXml($eventType->name).'</article:event_type>'.PHP_EOL;
            }
        } elseif ($event->eventType) {
            $xml .= '        <article:event_type code="'.$this->escapeXml($event->eventType->code).'">'.$this->escapeXml($event->eventType->name).'</article:event_type>'.PHP_EOL;
        }
        $xml .= '      </article:data>'.PHP_EOL;

        foreach ($categories as $category) {
            $xml .= '      <category>'.$this->escapeXml($category).'</category>'.PHP_EOL;
        }

        if ($event->creator) {
            $xml .= '      <dc:creator>'.$this->escapeXml($event->creator->name).'</dc:creator>'.PHP_EOL;
        }

        // Add source information
        $xml .= '      <source url="'.$this->escapeXml($this->baseUrl).'">Global Travel Monitor by Passolution</source>'.PHP_EOL;

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
                $xml .= '      <enclosure url="'.$this->escapeXml($imageUrl).'" type="image/jpeg" />'.PHP_EOL;
            }
        }

        // Add detailed country data for each country
        $xml .= $this->generateCountryData($event);

        $xml .= '    </item>'.PHP_EOL;

        return $xml;
    }

    /**
     * Generate country data XML for an event
     */
    private function generateCountryData($event): string
    {
        $xml = '';
        $countries = collect();

        // Collect all countries from the event
        if ($event->countries && $event->countries->count() > 0) {
            $countries = $event->countries;
        } elseif ($event->country) {
            $countries = collect([$event->country]);
        }

        foreach ($countries as $country) {
            $xml .= '      <country:data>'.PHP_EOL;
            $xml .= '        <country:name_de>'.$this->escapeXml($country->getName('de')).'</country:name_de>'.PHP_EOL;
            $xml .= '        <country:name_en>'.$this->escapeXml($country->getName('en')).'</country:name_en>'.PHP_EOL;
            $xml .= '        <country:iso_code>'.$this->escapeXml($country->iso_code ?? '').'</country:iso_code>'.PHP_EOL;
            $xml .= '        <country:iso3_code>'.$this->escapeXml($country->iso3_code ?? '').'</country:iso3_code>'.PHP_EOL;
            $xml .= '        <country:is_eu_member>'.($country->is_eu_member ? 'true' : 'false').'</country:is_eu_member>'.PHP_EOL;
            $xml .= '        <country:is_schengen_member>'.($country->is_schengen_member ? 'true' : 'false').'</country:is_schengen_member>'.PHP_EOL;

            // Continent (from relationship)
            if ($country->continent) {
                $xml .= '        <country:continent>'.$this->escapeXml($country->continent->getName(App::getLocale())).'</country:continent>'.PHP_EOL;
            }

            // Currency
            if ($country->currency_code) {
                $xml .= '        <country:currency_code>'.$this->escapeXml($country->currency_code).'</country:currency_code>'.PHP_EOL;
            }

            // Phone prefix
            if ($country->phone_prefix) {
                $xml .= '        <country:phone_prefix>'.$this->escapeXml($country->phone_prefix).'</country:phone_prefix>'.PHP_EOL;
            }

            // Capital with coordinates (from relationship)
            if ($country->capital) {
                $xml .= '        <country:capital>'.PHP_EOL;
                $xml .= '          <country:capital_name>'.$this->escapeXml($country->capital->getName(App::getLocale())).'</country:capital_name>'.PHP_EOL;
                if ($country->capital->lat && $country->capital->lng) {
                    $xml .= '          <geo:lat>'.$country->capital->lat.'</geo:lat>'.PHP_EOL;
                    $xml .= '          <geo:long>'.$country->capital->lng.'</geo:long>'.PHP_EOL;
                }
                $xml .= '        </country:capital>'.PHP_EOL;
            }

            $xml .= '      </country:data>'.PHP_EOL;
        }

        return $xml;
    }

    /**
     * Get country cover image URL
     */
    private function getCountryImageUrl(string $countryCode): ?string
    {
        $imagePath = public_path('images/countries/'.strtolower($countryCode).'.jpg');

        if (file_exists($imagePath)) {
            return $this->baseUrl.'/images/countries/'.strtolower($countryCode).'.jpg';
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

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:article="http://global-travel-monitor.eu/ns/article" xml:lang="'.$this->escapeXml(__('feed.language_tag')).'">'.PHP_EOL;
        $xml .= '  <title>'.$this->escapeXml($title).'</title>'.PHP_EOL;
        $xml .= '  <subtitle>'.$this->escapeXml($subtitle).'</subtitle>'.PHP_EOL;
        $xml .= '  <link href="'.$this->escapeXml($this->baseUrl).'" rel="alternate" />'.PHP_EOL;
        $xml .= '  <link href="'.$this->escapeXml($feedUrl).'" rel="self" />'.PHP_EOL;
        $xml .= '  <id>'.$this->escapeXml($feedUrl).'</id>'.PHP_EOL;
        $xml .= '  <updated>'.$updated.'</updated>'.PHP_EOL;

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
        $link = $this->baseUrl.'/?event='.$event->id;
        $updated = $event->updated_at->toAtomString();
        $published = $event->created_at->toAtomString();
        $title = $event->title;

        // Build content: description in the requested language, then the metadata summary
        $eventDescription = $this->eventDescription($event);
        $content = $this->escapeXml($eventDescription ? strip_tags($eventDescription) : __('feed.no_description'));

        $details = $this->eventDetails($event);
        if (! empty($details)) {
            $content .= "\n\n".$this->escapeXml(implode(' | ', $details));
        }

        $xml = '  <entry>'.PHP_EOL;
        $xml .= '    <title>'.$this->escapeXml($title).'</title>'.PHP_EOL;
        $xml .= '    <link href="'.$this->escapeXml($link).'" />'.PHP_EOL;
        $xml .= '    <id>'.$this->escapeXml($link).'</id>'.PHP_EOL;
        $xml .= '    <updated>'.$updated.'</updated>'.PHP_EOL;
        $xml .= '    <published>'.$published.'</published>'.PHP_EOL;
        $xml .= '    <content type="text">'.$content.'</content>'.PHP_EOL;

        // Article-specific data (display period, priority, and event types)
        $xml .= '    <article:data>'.PHP_EOL;
        if ($event->start_date) {
            $xml .= '      <article:start_date>'.$event->start_date->toAtomString().'</article:start_date>'.PHP_EOL;
        }
        if ($event->end_date) {
            $xml .= '      <article:end_date>'.$event->end_date->toAtomString().'</article:end_date>'.PHP_EOL;
        }
        if ($event->priority) {
            $xml .= '      <article:priority>'.$this->escapeXml($event->priority).'</article:priority>'.PHP_EOL;
        }
        // Event types
        if ($event->eventTypes && $event->eventTypes->count() > 0) {
            foreach ($event->eventTypes as $eventType) {
                $xml .= '      <article:event_type code="'.$this->escapeXml($eventType->code).'">'.$this->escapeXml($eventType->name).'</article:event_type>'.PHP_EOL;
            }
        } elseif ($event->eventType) {
            $xml .= '      <article:event_type code="'.$this->escapeXml($event->eventType->code).'">'.$this->escapeXml($event->eventType->name).'</article:event_type>'.PHP_EOL;
        }
        $xml .= '    </article:data>'.PHP_EOL;

        if ($event->priority) {
            $xml .= '    <category term="'.$this->escapeXml(strtolower($event->priority)).'" label="Priority: '.$this->escapeXml(ucfirst($event->priority)).'" />'.PHP_EOL;
        }

        if ($event->eventTypes && $event->eventTypes->count() > 0) {
            foreach ($event->eventTypes as $eventType) {
                $xml .= '    <category term="'.$this->escapeXml($eventType->code).'" label="'.$this->escapeXml($eventType->name).'" />'.PHP_EOL;
            }
        } elseif ($event->eventType) {
            $xml .= '    <category term="'.$this->escapeXml($event->eventType->code).'" label="'.$this->escapeXml($event->eventType->name).'" />'.PHP_EOL;
        }

        if ($event->creator) {
            $xml .= '    <author>'.PHP_EOL;
            $xml .= '      <name>'.$this->escapeXml($event->creator->name).'</name>'.PHP_EOL;
            $xml .= '    </author>'.PHP_EOL;
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
                $xml .= '    <link rel="enclosure" type="image/jpeg" href="'.$this->escapeXml($imageUrl).'" />'.PHP_EOL;
            }
        }

        $xml .= '  </entry>'.PHP_EOL;

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
    private function generateErrorResponse(string $message, int $status = 404): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<rss version="2.0">'.PHP_EOL;
        $xml .= '  <channel>'.PHP_EOL;
        $xml .= '    <title>Error</title>'.PHP_EOL;
        $xml .= '    <link>'.$this->escapeXml($this->baseUrl).'</link>'.PHP_EOL;
        $xml .= '    <description>'.$this->escapeXml($message).'</description>'.PHP_EOL;
        $xml .= '  </channel>'.PHP_EOL;
        $xml .= '</rss>';

        return response($xml, $status)->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Ausgabesprache aus ?lang= setzen; ohne Parameter die Ausgangssprache (Deutsch).
     * Browser-Sprache und Sprach-Cookie gelten für Feeds bewusst nicht, damit eine
     * Feed-URL immer dieselbe Sprache liefert. Null bei nicht unterstützter Sprache.
     */
    private function applyRequestedLanguage(): ?string
    {
        $requested = request()->query('lang');

        if ($requested !== null && ! is_string($requested)) {
            return null;
        }

        $locale = $requested === null ? CustomEvent::sourceLocale() : strtolower($requested);

        if (! in_array($locale, CustomEvent::translationLocales(), true)) {
            return null;
        }

        App::setLocale($locale);

        return $locale;
    }

    private function unsupportedLanguageResponse(): Response
    {
        $requested = request()->query('lang');

        return $this->generateErrorResponse(
            'Unsupported language: '.(is_string($requested) ? $requested : '').'. Supported: '.implode(', ', CustomEvent::translationLocales()),
            400
        );
    }

    private function feedResponse(string $content, string $mimeType, string $locale): Response
    {
        return response($content, 200)
            ->header('Content-Type', $mimeType.'; charset=utf-8')
            ->header('Content-Language', $locale);
    }

    private function priorityLabel(string $priority): string
    {
        $labels = __('feed.priorities');
        $priority = strtolower($priority);

        return is_array($labels) && isset($labels[$priority]) ? $labels[$priority] : ucfirst($priority);
    }

    /**
     * Beschreibung in der Ausgabesprache. Eine Übersetzung geht der unübersetzten
     * Spalte description vor; sonst gilt die bisherige Reihenfolge.
     */
    private function eventDescription(CustomEvent $event): ?string
    {
        $locale = App::getLocale();

        if ($locale !== CustomEvent::sourceLocale() && ($translated = $event->popupContentTranslation($locale)) !== null) {
            return $translated;
        }

        return $event->description ?: $event->popup_content ?: null;
    }

    /**
     * Metadaten-Zusammenfassung (Typ, Beginn, Ende, Priorität, Länder) in der Ausgabesprache.
     *
     * @return array<int, string>
     */
    private function eventDetails(CustomEvent $event): array
    {
        $locale = App::getLocale();
        $dateFormat = __('feed.date_format');
        $details = [];

        if ($event->eventTypes && $event->eventTypes->count() > 0) {
            $details[] = __('feed.label_type').': '.$event->eventTypes->pluck('name')->join(', ');
        } elseif ($event->eventType) {
            $details[] = __('feed.label_type').': '.$event->eventType->name;
        }
        if ($event->start_date) {
            $details[] = __('feed.label_start').': '.$event->start_date->format($dateFormat);
        }
        if ($event->end_date) {
            $details[] = __('feed.label_end').': '.$event->end_date->format($dateFormat);
        }
        if ($event->priority) {
            $details[] = __('feed.label_priority').': '.$this->priorityLabel($event->priority);
        }
        if ($event->countries && $event->countries->count() > 0) {
            $details[] = __('feed.label_countries').': '.$event->countries->map(fn ($c) => $c->getName($locale))->join(', ');
        } elseif ($event->country) {
            $details[] = __('feed.label_country').': '.$event->country->getName($locale);
        }

        return $details;
    }
}
