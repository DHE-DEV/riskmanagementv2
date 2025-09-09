<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\DisasterEvent;
use App\Models\Country;
use Carbon\Carbon;

class GdacsApiService
{
    private string $baseUrl = 'https://www.gdacs.org/xml/rss.xml';
    private int $cacheMinutes = 15; // Cache für 15 Minuten

    /**
     * Lade aktuelle GDACS Events
     */
    public function fetchCurrentEvents(): array
    {
        try {
            // Prüfe Cache zuerst
            $cacheKey = 'gdacs_events_' . date('Y-m-d-H');
            $cachedEvents = Cache::get($cacheKey);
            
            if ($cachedEvents) {
                Log::channel('gdacs_sync')->info('GDACS events loaded from cache', [
                    'cache_key' => $cacheKey,
                    'event_count' => count($cachedEvents)
                ]);
                return $cachedEvents;
            }

            // API-Call machen
            $response = Http::timeout(30)->get($this->baseUrl);
            
            if (!$response->successful()) {
                Log::error('GDACS API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $xml = simplexml_load_string($response->body());
            if (!$xml) {
                Log::error('Failed to parse GDACS XML response');
                return [];
            }

            $events = $this->parseGdacsXml($xml);
            
            // Cache setzen
            Cache::put($cacheKey, $events, now()->addMinutes($this->cacheMinutes));
            
            Log::channel('gdacs_sync')->info('GDACS events fetched and cached', [
                'count' => count($events),
                'cache_key' => $cacheKey,
                'cache_minutes' => $this->cacheMinutes
            ]);
            return $events;

        } catch (\Exception $e) {
            Log::channel('gdacs_sync')->error('GDACS API error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $this->baseUrl
            ]);
            return [];
        }
    }

    /**
     * Parse GDACS XML Response
     */
    private function parseGdacsXml(\SimpleXMLElement $xml): array
    {
        $events = [];
        
        if (!isset($xml->channel) || !isset($xml->channel->item)) {
            return $events;
        }

        foreach ($xml->channel->item as $item) {
            try {
                $event = $this->parseGdacsItem($item);
                if ($event) {
                    $events[] = $event;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to parse GDACS item', [
                    'item' => $item->title ?? 'Unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $events;
    }

    /**
     * Parse einzelnes GDACS Item
     */
    private function parseGdacsItem(\SimpleXMLElement $item): ?array
    {
        $title = (string) $item->title;
        $description = (string) $item->description;
        $link = (string) $item->link;
        $pubDate = (string) $item->pubDate;
        
        // Extrahiere Event-Typ und Severity aus dem Titel
        $eventInfo = $this->extractEventInfo($title, $description);
        
        if (!$eventInfo) {
            return null;
        }

        // Extrahiere Koordinaten aus XML-Elementen oder Beschreibung
        $coordinates = $this->extractCoordinatesFromXml($item) ?: $this->extractCoordinates($description);
        
        // Extrahiere Land/Region
        $location = $this->extractLocation($title, $description);
        
        // Fallback-Koordinaten wenn keine gefunden wurden
        if (!$coordinates['lat'] && !$coordinates['lng'] && $location['country']) {
            $coordinates = $this->getCountryFallbackCoordinates($location['country']);
        }
        
        // Extrahiere Magnitude und konvertiere zu numerischem Wert
        $magnitude = $this->extractMagnitude($title, $description);
        $magnitude = $this->convertMagnitudeToNumeric($magnitude);
        
        // Extrahiere betroffene Bevölkerung
        $affectedPopulation = $this->extractAffectedPopulation($description);

        return [
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'pub_date' => $pubDate,
            'event_type' => $eventInfo['type'],
            'severity' => $eventInfo['severity'],
            'latitude' => $coordinates['lat'] ?? null,
            'longitude' => $coordinates['lng'] ?? null,
            'country' => $location['country'] ?? null,
            'region' => $location['region'] ?? null,
            'city' => $location['city'] ?? null,
            'magnitude' => $magnitude,
            'affected_population' => $affectedPopulation,
            'gdacs_population_text' => $affectedPopulation,
            'is_gdacs' => true,
            'source' => 'GDACS',
            'raw_data' => [
                'title' => $title,
                'description' => $description,
                'link' => $link
            ]
        ];
    }

    /**
     * Extrahiere Event-Informationen aus Titel und Beschreibung
     */
    private function extractEventInfo(string $title, string $description): ?array
    {
        $titleLower = strtolower($title);
        $descLower = strtolower($description);
        
        // Event-Typ bestimmen
        $eventType = 'unknown';
        if (str_contains($titleLower, 'earthquake') || str_contains($descLower, 'earthquake')) {
            $eventType = 'earthquake';
        } elseif (str_contains($titleLower, 'tsunami') || str_contains($descLower, 'tsunami')) {
            $eventType = 'tsunami';
        } elseif (str_contains($titleLower, 'volcano') || str_contains($descLower, 'volcano')) {
            $eventType = 'volcano';
        } elseif (str_contains($titleLower, 'flood') || str_contains($descLower, 'flood')) {
            $eventType = 'flood';
        } elseif (str_contains($titleLower, 'cyclone') || str_contains($descLower, 'cyclone')) {
            $eventType = 'cyclone';
        } elseif (str_contains($titleLower, 'drought') || str_contains($descLower, 'drought')) {
            $eventType = 'drought';
        }

        // Severity bestimmen und zu DB-Format konvertieren
        $severity = 'low'; // Default für green
        if (str_contains($titleLower, 'red') || str_contains($descLower, 'red alert')) {
            $severity = 'critical';
        } elseif (str_contains($titleLower, 'orange') || str_contains($descLower, 'orange alert')) {
            $severity = 'high';
        } elseif (str_contains($titleLower, 'yellow') || str_contains($descLower, 'yellow alert')) {
            $severity = 'medium';
        }

        return [
            'type' => $eventType,
            'severity' => $severity
        ];
    }

    /**
     * Extrahiere Koordinaten aus XML-Elementen (geo:lat, geo:long, georss:point)
     */
    private function extractCoordinatesFromXml(\SimpleXMLElement $item): array
    {
        $coordinates = ['lat' => null, 'lng' => null];
        
        // Namespace für geo-Elemente registrieren
        $namespaces = $item->getNamespaces(true);
        
        // Versuche geo:Point zu finden
        if (isset($namespaces['geo'])) {
            $geoElements = $item->children($namespaces['geo']);
            if (isset($geoElements->Point)) {
                $point = $geoElements->Point;
                $pointChildren = $point->children($namespaces['geo']);
                if (isset($pointChildren->lat) && isset($pointChildren->long)) {
                    $coordinates['lat'] = (float) $pointChildren->lat;
                    $coordinates['lng'] = (float) $pointChildren->long;
                    return $coordinates;
                }
            }
        }
        
        // Versuche georss:point zu finden (Format: "lat lng")
        if (isset($namespaces['georss'])) {
            $georssElements = $item->children($namespaces['georss']);
            if (isset($georssElements->point)) {
                $pointString = (string) $georssElements->point;
                $coords = explode(' ', trim($pointString));
                if (count($coords) === 2) {
                    $coordinates['lat'] = (float) $coords[0];
                    $coordinates['lng'] = (float) $coords[1];
                    return $coordinates;
                }
            }
        }
        
        return $coordinates;
    }

    /**
     * Extrahiere Koordinaten aus der Beschreibung
     */
    private function extractCoordinates(string $description): array
    {
        $coordinates = ['lat' => null, 'lng' => null];
        
        // Suche nach Koordinaten-Patterns
        $patterns = [
            '/lat[itude]*[:\s]*([+-]?\d+\.?\d*)/i',
            '/lon[gitude]*[:\s]*([+-]?\d+\.?\d*)/i',
            '/coordinates[:\s]*([+-]?\d+\.?\d*)[,\s]+([+-]?\d+\.?\d*)/i',
            '/location[:\s]*([+-]?\d+\.?\d*)[,\s]+([+-]?\d+\.?\d*)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                if (count($matches) >= 2) {
                    $coordinates['lat'] = (float) $matches[1];
                }
                if (count($matches) >= 3) {
                    $coordinates['lng'] = (float) $matches[2];
                }
                break;
            }
        }

        return $coordinates;
    }

    /**
     * Extrahiere Land/Region aus Titel und Beschreibung
     */
    private function extractLocation(string $title, string $description): array
    {
        $location = ['country' => null, 'region' => null, 'city' => null];
        
        // Suche nach "in [Country]" Pattern
        if (preg_match('/in\s+([A-Z][a-z\s]+?)(?:\s|$|,|\.)/', $title, $matches)) {
            $location['country'] = trim($matches[1]);
        }
        
        // Suche nach spezifischen Ländern
        $countries = ['Indonesia', 'Japan', 'Philippines', 'New Zealand', 'Chile', 'Peru', 'Mexico', 'United States', 'Canada'];
        foreach ($countries as $country) {
            if (str_contains($title, $country) || str_contains($description, $country)) {
                $location['country'] = $country;
                break;
            }
        }

        return $location;
    }

    /**
     * Extrahiere Magnitude aus Titel und Beschreibung
     */
    private function extractMagnitude(string $title, string $description): ?string
    {
        // Suche nach Magnitude-Patterns
        $patterns = [
            '/magnitude[:\s]*(\d+\.?\d*)/i',
            '/(\d+\.?\d*)[Mm]\s*magnitude/i',
            '/magnitude\s*(\d+\.?\d*)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $title . ' ' . $description, $matches)) {
                return $matches[1] . 'M';
            }
        }

        return null;
    }

    /**
     * Konvertiere Magnitude-String zu numerischem Wert
     */
    private function convertMagnitudeToNumeric(?string $magnitude): ?float
    {
        if (!$magnitude) {
            return null;
        }
        
        // Entferne alle nicht-numerischen Zeichen außer Punkt und Minus
        $numericMagnitude = preg_replace('/[^0-9.-]/', '', $magnitude);
        
        // Konvertiere zu Float
        if (is_numeric($numericMagnitude)) {
            return (float) $numericMagnitude;
        }
        
        return null;
    }

    /**
     * Extrahiere betroffene Bevölkerung
     */
    private function extractAffectedPopulation(string $description): ?string
    {
        // Suche nach Bevölkerung-Patterns
        $patterns = [
            '/(\d+)\s*(?:thousand|k)\s*(?:people|inhabitants|affected)/i',
            '/(\d+)\s*(?:million|m)\s*(?:people|inhabitants|affected)/i',
            '/population[:\s]*(\d+[,\d]*)\s*(?:people|inhabitants)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                return $matches[1] . ' affected';
            }
        }

        return null;
    }

    /**
     * Speichere GDACS Events in der Datenbank
     */
    public function saveEventsToDatabase(array $events): int
    {
        $savedCount = 0;
        
        foreach ($events as $eventData) {
            try {
                // Prüfe ob Event bereits existiert (basierend auf GDACS Map Link)
                $existingEvent = DisasterEvent::where('gdacs_map_link', $eventData['link'])
                    ->where('is_gdacs', true)
                    ->first();


                if ($existingEvent) {
                    // Update bestehendes Event
                    $existingEvent->update([
                        'severity' => $eventData['severity'],
                        'magnitude' => $eventData['magnitude'],
                        'affected_population' => $eventData['affected_population'],
                        'gdacs_population_text' => $eventData['gdacs_population_text'],
                        'updated_at' => now()
                    ]);
                    $savedCount++;
                } else {
                    // Erstelle neues Event
                    DisasterEvent::create([
                        'title' => $eventData['title'],
                        'description' => $eventData['description'],
                        'event_type' => $eventData['event_type'],
                        'severity' => $eventData['severity'],
                        'lat' => $eventData['latitude'],
                        'lng' => $eventData['longitude'],
                        'magnitude' => $eventData['magnitude'],
                        'affected_population' => $eventData['affected_population'],
                        'gdacs_population_text' => $eventData['gdacs_population_text'],
                        'event_date' => Carbon::parse($eventData['pub_date']),
                        'gdacs_map_link' => $eventData['link'],
                        'external_sources' => 'gdacs',
                        'last_updated' => now(),
                        'is_gdacs' => true,
                        'country_id' => $this->findCountryId($eventData['country']),
                        'raw_data' => json_encode($eventData['raw_data'])
                    ]);
                    $savedCount++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to save GDACS event', [
                    'event' => $eventData['title'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $savedCount;
    }

    /**
     * Finde Country ID basierend auf Namen
     */
    private function findCountryId(?string $countryName): ?int
    {
        if (!$countryName) {
            return null;
        }

        $country = Country::where('name_translations', 'like', "%{$countryName}%")
            ->orWhere('iso_code', 'like', "%{$countryName}%")
            ->first();

        return $country?->id;
    }

    /**
     * Fallback-Koordinaten für Länder
     */
    private function getCountryFallbackCoordinates(string $country): array
    {
        $coordinates = [
            'China' => ['lat' => 35.8617, 'lng' => 104.1954],
            'United States' => ['lat' => 37.0902, 'lng' => -95.7129],
            'Bolivia' => ['lat' => -16.2902, 'lng' => -63.5887],
            'Brazil' => ['lat' => -14.2350, 'lng' => -51.9253],
            'Madagascar' => ['lat' => -18.7669, 'lng' => 46.8691],
            'Sudan' => ['lat' => 12.8628, 'lng' => 30.2176],
            'Ethiopia' => ['lat' => 9.1450, 'lng' => 40.4897],
            'Mexico' => ['lat' => 23.6345, 'lng' => -102.5528],
            'Canada' => ['lat' => 56.1304, 'lng' => -106.3468],
            'Australia' => ['lat' => -25.2744, 'lng' => 133.7751],
            'Russia' => ['lat' => 61.5240, 'lng' => 105.3188],
            'Mongolia' => ['lat' => 47.0659, 'lng' => 103.8467]
        ];

        return $coordinates[$country] ?? ['lat' => null, 'lng' => null];
    }

    /**
     * Aktualisiere alle GDACS Events
     */
    public function updateAllEvents(): array
    {
        $events = $this->fetchCurrentEvents();
        $savedCount = $this->saveEventsToDatabase($events);
        
        return [
            'fetched' => count($events),
            'saved' => $savedCount,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Cache leeren
     */
    public function clearCache(): void
    {
        $cacheKey = 'gdacs_events_' . date('Y-m-d-H');
        Cache::forget($cacheKey);
        Log::info('GDACS cache cleared');
    }
}
