<?php

namespace App\Http\Controllers;

use App\Services\GdacsApiService;
use App\Services\WeatherService;
use App\Services\TimezoneService;
use App\Models\DisasterEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GdacsController extends Controller
{
    private GdacsApiService $gdacsService;
    private WeatherService $weatherService;
    private TimezoneService $timezoneService;

    public function __construct(
        GdacsApiService $gdacsService,
        WeatherService $weatherService,
        TimezoneService $timezoneService
    ) {
        $this->gdacsService = $gdacsService;
        $this->weatherService = $weatherService;
        $this->timezoneService = $timezoneService;
    }

    /**
     * Lade aktuelle GDACS Events
     */
    public function fetchEvents(): JsonResponse
    {
        try {
            $result = $this->gdacsService->updateAllEvents();
            
            return response()->json([
                'success' => true,
                'message' => 'GDACS Events erfolgreich aktualisiert',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('GDACS fetch events failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der GDACS Events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lade aktuelle Events für das Dashboard
     */
    public function getDashboardEvents(): JsonResponse
    {
        try {
            // Lade GDACS Events
            $gdacsEvents = $this->gdacsService->fetchCurrentEvents();
            
            // Lade Events aus der Datenbank
            $dbEvents = DisasterEvent::active()
                ->with(['country', 'region', 'city'])
                ->orderBy('event_date', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'severity' => $event->severity,
                        'event_type' => $event->event_type,
                        'country' => $event->country?->getName('de') ?? 'Unbekannt',
                        'date' => $event->event_date->format('d/m/Y H:i'),
                        'date_iso' => $event->event_date->toIso8601String(),
                        'magnitude' => $event->magnitude,
                        'affected_population' => $event->gdacs_population_text,
                        'is_gdacs' => $event->is_gdacs,
                        'latitude' => $event->lat,
                        'longitude' => $event->lng,
                        'icon' => $this->getEventIcon($event->event_type, $event->severity),
                        'iconColor' => $this->getSeverityColor($event->severity)
                    ];
                });

            // Kombiniere GDACS und DB Events
            $allEvents = array_merge($gdacsEvents, $dbEvents->toArray());

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $allEvents,
                    'total' => count($allEvents),
                    'gdacs_count' => count($gdacsEvents),
                    'db_count' => $dbEvents->count(),
                    'last_updated' => now()->format('H:i')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard events fetch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hole Wetter- und Zeitzonen-Daten für Event
     */
    public function getEventDetails(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180'
            ]);

            $latitude = (float) $request->latitude;
            $longitude = (float) $request->longitude;

            // Hole Wetter-Daten
            $weather = $this->weatherService->getCurrentWeather($latitude, $longitude);
            
            // Hole Zeitzonen-Daten
            $timezone = $this->timezoneService->getCurrentLocalTime($latitude, $longitude);

            return response()->json([
                'success' => true,
                'data' => [
                    'weather' => $weather,
                    'timezone' => $timezone,
                    'coordinates' => [
                        'lat' => $latitude,
                        'lng' => $longitude
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Event details fetch failed', [
                'error' => $e->getMessage(),
                'coordinates' => $request->only(['latitude', 'longitude'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Event-Details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hole Wetter-Daten für mehrere Events
     */
    public function getWeatherForEvents(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'events' => 'required|array',
                'events.*.latitude' => 'required|numeric|between:-90,90',
                'events.*.longitude' => 'required|numeric|between:-180,180'
            ]);

            $events = $request->events;
            $weatherData = [];
            $timezoneData = [];

            foreach ($events as $event) {
                $lat = (float) $event['latitude'];
                $lng = (float) $event['longitude'];
                $key = "{$lat}_{$lng}";

                // Hole Wetter-Daten
                $weather = $this->weatherService->getCurrentWeather($lat, $lng);
                if ($weather) {
                    $weatherData[$key] = $weather;
                }

                // Hole Zeitzonen-Daten
                $timezone = $this->timezoneService->getCurrentLocalTime($lat, $lng);
                if ($timezone) {
                    $timezoneData[$key] = $timezone;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'weather' => $weatherData,
                    'timezone' => $timezoneData
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Weather for events fetch failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Wetter-Daten: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cache leeren
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->gdacsService->clearCache();
            $this->weatherService->clearCache();
            $this->timezoneService->clearCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Alle Caches erfolgreich geleert'
            ]);

        } catch (\Exception $e) {
            Log::error('Cache clear failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Leeren der Caches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiken abrufen
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_events' => DisasterEvent::count(),
                'active_events' => DisasterEvent::active()->count(),
                'last_week_events' => DisasterEvent::where('event_date', '>=', now()->subWeek())->count(),
                'high_risk_events' => DisasterEvent::whereIn('severity', ['high', 'critical', 'red', 'orange'])->count(),
                'manual_events' => DisasterEvent::where('is_gdacs', false)->count(),
                'gdacs_events' => DisasterEvent::where('is_gdacs', true)->count(),
                'last_updated' => now()->format('H:i')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Statistics fetch failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Statistiken: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Teste API-Verbindungen
     */
    public function testApis(): JsonResponse
    {
        try {
            $results = [
                'gdacs' => [
                    'status' => 'unknown',
                    'message' => 'GDACS API Test nicht implementiert'
                ],
                'weather' => [
                    'status' => $this->weatherService->testConnection() ? 'connected' : 'failed',
                    'message' => $this->weatherService->testConnection() ? 'OpenWeatherMap API verfügbar' : 'OpenWeatherMap API nicht verfügbar'
                ],
                'timezone' => [
                    'status' => 'connected',
                    'message' => 'Zeitzonen-Service verfügbar (mit Fallback)'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('API test failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Testen der APIs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Event Icon basierend auf Typ und Severity
     */
    private function getEventIcon(string $eventType, string $severity): string
    {
        $icons = [
            'earthquake' => 'fa-solid fa-house-crack',
            'tsunami' => 'fa-solid fa-water',
            'volcano' => 'fa-solid fa-mountain',
            'flood' => 'fa-solid fa-water',
            'cyclone' => 'fa-solid fa-wind',
            'drought' => 'fa-solid fa-sun',
            'custom' => 'fa-solid fa-exclamation-triangle',
            'unknown' => 'fa-solid fa-exclamation-circle'
        ];

        return $icons[$eventType] ?? $icons['unknown'];
    }

    /**
     * Severity Farbe
     */
    private function getSeverityColor(string $severity): string
    {
        $colors = [
            'green' => '#10b981',
            'yellow' => '#f59e0b',
            'orange' => '#f59e0b',
            'red' => '#ef4444',
            'critical' => '#ef4444',
            'high' => '#f59e0b',
            'medium' => '#f59e0b',
            'low' => '#10b981'
        ];

        return $colors[$severity] ?? '#6b7280';
    }
}
