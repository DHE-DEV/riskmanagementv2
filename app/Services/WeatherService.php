<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class WeatherService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openweathermap.org/data/2.5';
    private int $cacheMinutes = 30; // Cache für 30 Minuten

    public function __construct()
    {
        $this->apiKey = config('services.openweathermap.api_key', 'demo_key');
    }

    /**
     * Hole aktuelles Wetter für Koordinaten
     */
    public function getCurrentWeather(float $latitude, float $longitude): ?array
    {
        $cacheKey = "weather_{$latitude}_{$longitude}";
        
        try {
            // Prüfe Cache zuerst
            $cachedWeather = Cache::get($cacheKey);
            if ($cachedWeather) {
                Log::info('Weather data loaded from cache', ['lat' => $latitude, 'lng' => $longitude]);
                return $cachedWeather;
            }

            // API-Call machen
            $response = Http::timeout(10)->get("{$this->baseUrl}/weather", [
                'lat' => $latitude,
                'lon' => $longitude,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'de'
            ]);

            if (!$response->successful()) {
                Log::error('OpenWeatherMap API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'lat' => $latitude,
                    'lng' => $longitude
                ]);
                return null;
            }

            $weatherData = $response->json();
            $processedWeather = $this->processWeatherData($weatherData);
            
            // Cache setzen
            Cache::put($cacheKey, $processedWeather, now()->addMinutes($this->cacheMinutes));
            
            Log::info('Weather data fetched and cached', [
                'lat' => $latitude,
                'lng' => $longitude,
                'temperature' => $processedWeather['temperature'] ?? null
            ]);
            
            return $processedWeather;

        } catch (\Exception $e) {
            Log::error('Weather API error', [
                'message' => $e->getMessage(),
                'lat' => $latitude,
                'lng' => $longitude
            ]);
            return null;
        }
    }

    /**
     * Hole Wetter für mehrere Koordinaten
     */
    public function getWeatherForMultipleLocations(array $coordinates): array
    {
        $results = [];
        
        foreach ($coordinates as $coord) {
            if (isset($coord['lat']) && isset($coord['lng'])) {
                $weather = $this->getCurrentWeather($coord['lat'], $coord['lng']);
                if ($weather) {
                    $results["{$coord['lat']}_{$coord['lng']}"] = $weather;
                }
            }
        }
        
        return $results;
    }

    /**
     * Verarbeite Wetter-Daten
     */
    private function processWeatherData(array $data): array
    {
        $weather = $data['weather'][0] ?? [];
        $main = $data['main'] ?? [];
        $wind = $data['wind'] ?? [];
        $sys = $data['sys'] ?? [];
        
        return [
            'temperature' => round($main['temp'] ?? 0, 1),
            'feels_like' => round($main['feels_like'] ?? 0, 1),
            'humidity' => $main['humidity'] ?? 0,
            'pressure' => $main['pressure'] ?? 0,
            'description' => $weather['description'] ?? 'Unbekannt',
            'icon' => $weather['icon'] ?? '01d',
            'wind_speed' => round(($wind['speed'] ?? 0) * 3.6, 1), // m/s zu km/h
            'wind_direction' => $wind['deg'] ?? 0,
            'visibility' => round(($data['visibility'] ?? 0) / 1000, 1), // m zu km
            'sunrise' => $sys['sunrise'] ?? null,
            'sunset' => $sys['sunset'] ?? null,
            'city_name' => $data['name'] ?? 'Unbekannt',
            'country_code' => $sys['country'] ?? null,
            'timestamp' => $data['dt'] ?? time(),
            'icon_url' => $this->getWeatherIconUrl($weather['icon'] ?? '01d'),
            'condition' => $this->getWeatherCondition($weather['main'] ?? 'Clear'),
            'severity_color' => $this->getWeatherSeverityColor($weather['main'] ?? 'Clear')
        ];
    }

    /**
     * Hole Wetter-Icon URL
     */
    private function getWeatherIconUrl(string $iconCode): string
    {
        return "https://openweathermap.org/img/wn/{$iconCode}@2x.png";
    }

    /**
     * Bestimme Wetter-Bedingung
     */
    private function getWeatherCondition(string $mainCondition): string
    {
        $conditions = [
            'Clear' => 'Klar',
            'Clouds' => 'Bewölkt',
            'Rain' => 'Regen',
            'Snow' => 'Schnee',
            'Thunderstorm' => 'Gewitter',
            'Drizzle' => 'Nieselregen',
            'Mist' => 'Nebel',
            'Smoke' => 'Rauch',
            'Haze' => 'Dunst',
            'Dust' => 'Staub',
            'Fog' => 'Nebel',
            'Sand' => 'Sandsturm',
            'Ash' => 'Asche',
            'Squall' => 'Böen',
            'Tornado' => 'Tornado'
        ];

        return $conditions[$mainCondition] ?? $mainCondition;
    }

    /**
     * Bestimme Wetter-Severity Farbe
     */
    private function getWeatherSeverityColor(string $mainCondition): string
    {
        $colors = [
            'Thunderstorm' => '#ef4444', // Rot für Gewitter
            'Tornado' => '#dc2626',      // Dunkelrot für Tornado
            'Snow' => '#3b82f6',         // Blau für Schnee
            'Rain' => '#0ea5e9',         // Hellblau für Regen
            'Drizzle' => '#06b6d4',      // Cyan für Nieselregen
            'Mist' => '#64748b',         // Grau für Nebel
            'Smoke' => '#8b5cf6',        // Lila für Rauch
            'Haze' => '#a3a3a3',         // Hellgrau für Dunst
            'Dust' => '#f59e0b',         // Orange für Staub
            'Sand' => '#f59e0b',         // Orange für Sandsturm
            'Ash' => '#6b7280',          // Grau für Asche
            'Squall' => '#f59e0b',       // Orange für Böen
            'Clouds' => '#94a3b8',       // Hellgrau für Wolken
            'Clear' => '#10b981'         // Grün für klar
        ];

        return $colors[$mainCondition] ?? '#6b7280';
    }

    /**
     * Hole Wetter-Icon für FontAwesome
     */
    public function getWeatherIcon(string $mainCondition): string
    {
        $icons = [
            'Thunderstorm' => 'fa-solid fa-bolt',
            'Tornado' => 'fa-solid fa-wind',
            'Snow' => 'fa-solid fa-snowflake',
            'Rain' => 'fa-solid fa-cloud-rain',
            'Drizzle' => 'fa-solid fa-cloud-drizzle',
            'Mist' => 'fa-solid fa-smog',
            'Smoke' => 'fa-solid fa-smog',
            'Haze' => 'fa-solid fa-smog',
            'Dust' => 'fa-solid fa-wind',
            'Sand' => 'fa-solid fa-wind',
            'Ash' => 'fa-solid fa-volcano',
            'Squall' => 'fa-solid fa-wind',
            'Clouds' => 'fa-solid fa-cloud',
            'Clear' => 'fa-solid fa-sun'
        ];

        return $icons[$mainCondition] ?? 'fa-solid fa-cloud';
    }

    /**
     * Cache leeren
     */
    public function clearCache(): void
    {
        // Alle Wetter-Cache-Einträge löschen
        $keys = Cache::get('weather_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('weather_cache_keys');
        Log::info('Weather cache cleared');
    }

    /**
     * Teste API-Verbindung
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/weather", [
                'lat' => 52.5200,
                'lon' => 13.4050,
                'appid' => $this->apiKey,
                'units' => 'metric'
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Weather API connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
