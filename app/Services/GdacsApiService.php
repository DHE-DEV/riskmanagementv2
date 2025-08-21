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
                Log::info('GDACS events loaded from cache');
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
            
            Log::info('GDACS events fetched and cached', ['count' => count($events)]);
            return $events;

        } catch (\Exception $e) {
            Log::error('GDACS API error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

        // Extrahiere Koordinaten aus der Beschreibung
        $coordinates = $this->extractCoordinates($description);
        
        // Extrahiere Land/Region
        $location = $this->extractLocation($title, $description);
        
        // Extrahiere Magnitude
        $magnitude = $this->extractMagnitude($title, $description);
        
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

        // Severity bestimmen
        $severity = 'green';
        if (str_contains($titleLower, 'red') || str_contains($descLower, 'red alert')) {
            $severity = 'red';
        } elseif (str_contains($titleLower, 'orange') || str_contains($descLower, 'orange alert')) {
            $severity = 'orange';
        } elseif (str_contains($titleLower, 'yellow') || str_contains($descLower, 'yellow alert')) {
            $severity = 'yellow';
        }

        return [
            'type' => $eventType,
            'severity' => $severity
        ];
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
                // Prüfe ob Event bereits existiert (basierend auf Titel und Datum)
                $existingEvent = DisasterEvent::where('title', $eventData['title'])
                    ->where('event_date', Carbon::parse($eventData['pub_date']))
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
                        'gdacs_link' => $eventData['link'],
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

        $country = Country::where('name', 'like', "%{$countryName}%")
            ->orWhere('iso_code', 'like', "%{$countryName}%")
            ->first();

        return $country?->id;
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
