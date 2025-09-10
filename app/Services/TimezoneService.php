<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TimezoneService
{
    private string $baseUrl = 'http://worldtimeapi.org/api';
    private int $cacheMinutes = 60; // Cache für 1 Stunde

    /**
     * Hole Zeitzone für Koordinaten
     */
    public function getTimezoneForCoordinates(float $latitude, float $longitude): ?array
    {
        $cacheKey = "timezone_{$latitude}_{$longitude}";
        
        try {
            // Prüfe Cache zuerst
            $cachedTimezone = Cache::get($cacheKey);
            if ($cachedTimezone) {
                Log::info('Timezone data loaded from cache', ['lat' => $latitude, 'lng' => $longitude]);
                return $cachedTimezone;
            }

            // Verwende robuste Fallback-Berechnung (keine externe API, zuverlässiger für DE)
            $processedTimezone = $this->getTimezoneFallback($latitude, $longitude);

            // Cache setzen
            Cache::put($cacheKey, $processedTimezone, now()->addMinutes($this->cacheMinutes));

            Log::info('Timezone data calculated and cached', [
                'lat' => $latitude,
                'lng' => $longitude,
                'timezone' => $processedTimezone['timezone'] ?? null
            ]);

            return $processedTimezone;

        } catch (\Exception $e) {
            Log::error('Timezone calculation error', [
                'message' => $e->getMessage(),
                'lat' => $latitude,
                'lng' => $longitude
            ]);
            
            // Letzter Fallback
            return $this->getTimezoneFallback($latitude, $longitude);
        }
    }

    /**
     * Fallback-Methode für Zeitzonen-Berechnung
     */
    private function getTimezoneFallback(float $latitude, float $longitude): ?array
    {
        // Versuche zuerst eine bekannte Zeitzone basierend auf Koordinaten zu finden
        $timezone = $this->getTimezoneByCoordinates($latitude, $longitude);
        
        if ($timezone) {
            try {
                $localTime = Carbon::now($timezone);
                $utcOffset = $localTime->getOffset() / 3600; // Convert seconds to hours
                
                return [
                    'timezone' => $timezone,
                    'utc_offset' => sprintf('%+03d:00', $utcOffset),
                    'utc_offset_hours' => (int) $utcOffset,
                    'datetime' => now('UTC')->toISOString(),
                    'local_datetime' => $localTime->toISOString(),
                    'local_time' => $localTime->format('H:i'),
                    'local_date' => $localTime->format('d.m.Y'),
                    'abbreviation' => $localTime->format('T'),
                    'time_diff_to_berlin' => (int) $utcOffset - $this->getBerlinOffset(),
                    'time_diff_to_berlin_formatted' => $this->formatTimeDifference((int) $utcOffset - $this->getBerlinOffset()),
                    'is_daytime' => $this->isDaytime($localTime),
                    'is_fallback' => true
                ];
            } catch (\Exception $e) {
                // Fallback zur alten Methode wenn Zeitzone ungültig ist
            }
        }

        // Fallback zur alten groben Berechnung
        $timezoneOffset = (int) round($longitude / 15);
        $timezoneName = $this->getTimezoneNameByOffset($timezoneOffset);
        $nowUtc = Carbon::now('UTC');
        $localTime = $nowUtc->copy()->addHours($timezoneOffset);

        return [
            'timezone' => $timezoneName,
            'utc_offset' => ($timezoneOffset >= 0 ? "+$timezoneOffset:00" : "-$timezoneOffset:00"),
            'utc_offset_hours' => $timezoneOffset,
            'datetime' => $nowUtc->toISOString(),
            'local_datetime' => $localTime->toISOString(),
            'local_time' => $localTime->format('H:i'),
            'local_date' => $localTime->format('d.m.Y'),
            'abbreviation' => $this->getTimezoneAbbreviation($timezoneOffset),
            'time_diff_to_berlin' => $timezoneOffset - $this->getBerlinOffset(),
            'time_diff_to_berlin_formatted' => $this->formatTimeDifference($timezoneOffset - $this->getBerlinOffset()),
            'is_daytime' => $this->isDaytime($localTime),
            'is_fallback' => true
        ];
    }

    /**
     * Bestimme Zeitzone basierend auf Koordinaten
     */
    private function getTimezoneByCoordinates(float $latitude, float $longitude): ?string
    {
        // Deutschland
        if ($latitude >= 47.0 && $latitude <= 55.2 && $longitude >= 5.5 && $longitude <= 15.7) {
            return 'Europe/Berlin';
        }

        // USA - Continental US
        if ($latitude >= 24.0 && $latitude <= 49.0 && $longitude >= -125.0 && $longitude <= -66.0) {
            if ($longitude >= -75.0) return 'America/New_York';      // Eastern
            if ($longitude >= -87.0) return 'America/Chicago';       // Central  
            if ($longitude >= -115.0) return 'America/Denver';       // Mountain
            return 'America/Los_Angeles';                            // Pacific
        }

        // Mexiko
        if ($latitude >= 14.5 && $latitude <= 32.7 && $longitude >= -118.4 && $longitude <= -86.7) {
            if ($longitude >= -102.0) return 'America/Mexico_City';   // Central Standard Time (most of Mexico)
            if ($longitude >= -107.0) return 'America/Chihuahua';     // Mountain Standard Time  
            if ($longitude >= -115.0) return 'America/Mazatlan';      // Mountain Standard Time (Sinaloa, etc.)
            return 'America/Tijuana';                                 // Pacific Standard Time
        }

        // Kanada
        if ($latitude >= 41.0 && $latitude <= 83.0 && $longitude >= -141.0 && $longitude <= -52.0) {
            if ($longitude >= -60.0) return 'America/Halifax';       // Atlantic
            if ($longitude >= -70.0) return 'America/Toronto';       // Eastern
            if ($longitude >= -90.0) return 'America/Winnipeg';      // Central
            if ($longitude >= -110.0) return 'America/Edmonton';     // Mountain
            if ($longitude >= -125.0) return 'America/Vancouver';    // Pacific
            return 'America/Whitehorse';                             // Yukon
        }

        // Großbritannien
        if ($latitude >= 49.9 && $latitude <= 60.9 && $longitude >= -8.2 && $longitude <= 1.8) {
            return 'Europe/London';
        }

        // Frankreich
        if ($latitude >= 42.3 && $latitude <= 51.1 && $longitude >= -5.1 && $longitude <= 9.6) {
            return 'Europe/Paris';
        }

        // Spanien
        if ($latitude >= 35.2 && $latitude <= 43.8 && $longitude >= -9.3 && $longitude <= 4.3) {
            return 'Europe/Madrid';
        }

        // Italien
        if ($latitude >= 35.5 && $latitude <= 47.1 && $longitude >= 6.6 && $longitude <= 18.5) {
            return 'Europe/Rome';
        }

        // Japan
        if ($latitude >= 24.0 && $latitude <= 46.0 && $longitude >= 123.0 && $longitude <= 146.0) {
            return 'Asia/Tokyo';
        }

        // China
        if ($latitude >= 18.0 && $latitude <= 54.0 && $longitude >= 73.0 && $longitude <= 135.0) {
            return 'Asia/Shanghai';
        }

        // Australien
        if ($latitude >= -44.0 && $latitude <= -10.0 && $longitude >= 113.0 && $longitude <= 154.0) {
            if ($longitude >= 143.0) return 'Australia/Sydney';      // Eastern
            if ($longitude >= 129.0) return 'Australia/Adelaide';    // Central
            return 'Australia/Perth';                                // Western
        }

        // Brasilien
        if ($latitude >= -34.0 && $latitude <= 5.3 && $longitude >= -74.0 && $longitude <= -28.8) {
            if ($longitude >= -38.0) return 'America/Sao_Paulo';     // Brasília Time
            if ($longitude >= -49.0) return 'America/Campo_Grande';  // Amazon Time
            if ($longitude >= -58.0) return 'America/Cuiaba';        // Amazon Time
            return 'America/Rio_Branco';                             // Acre Time
        }

        // Indien
        if ($latitude >= 6.0 && $latitude <= 37.1 && $longitude >= 68.1 && $longitude <= 97.4) {
            return 'Asia/Kolkata';
        }

        // Russland (vereinfacht)
        if ($latitude >= 41.0 && $latitude <= 82.0 && $longitude >= 19.6 && $longitude <= -169.0) {
            if ($longitude >= 142.0) return 'Asia/Vladivostok';      // Vladivostok Time
            if ($longitude >= 125.0) return 'Asia/Yakutsk';          // Yakutsk Time
            if ($longitude >= 105.0) return 'Asia/Irkutsk';          // Irkutsk Time
            if ($longitude >= 85.0) return 'Asia/Krasnoyarsk';       // Krasnoyarsk Time
            if ($longitude >= 65.0) return 'Asia/Omsk';              // Omsk Time
            if ($longitude >= 58.0) return 'Asia/Yekaterinburg';     // Yekaterinburg Time
            return 'Europe/Moscow';                                  // Moscow Time
        }

        // Afrika - GMT (UTC+0) Zone
        if ($latitude >= -35.0 && $latitude <= 37.0 && $longitude >= -18.0 && $longitude <= 52.0) {
            // Westafrika (GMT+0): Ghana, Guinea, Mali, Senegal, etc.
            if ($longitude <= 1.0) return 'Africa/Accra';           // GMT+0
            // Zentralafrika (GMT+1): Nigeria, Kamerun, etc.
            if ($longitude <= 30.0) return 'Africa/Lagos';          // GMT+1
            // Ostafrika (GMT+3): Kenia, Äthiopien, etc.
            return 'Africa/Nairobi';                                 // GMT+3
        }

        return null;
    }

    /**
     * Verarbeite Zeitzonen-Daten
     */
    private function processTimezoneData(array $data, float $latitude, float $longitude): array
    {
        $datetime = $data['datetime'] ?? now()->toISOString();
        $utcOffset = $data['utc_offset'] ?? '00:00';
        $timezone = $data['timezone'] ?? 'UTC';
        $abbreviation = $data['abbreviation'] ?? 'UTC';
        
        // Parse UTC Offset
        $offsetHours = $this->parseUtcOffset($utcOffset);
        
        // Lokale Zeit berechnen
        $utcTime = Carbon::parse($datetime);
        $localTime = $utcTime->copy()->addHours($offsetHours);
        
        // Zeitdifferenz zu Berlin berechnen (UTC+1/+2)
        $berlinOffset = $this->getBerlinOffset();
        $timeDiffToBerlin = $offsetHours - $berlinOffset;
        
        return [
            'timezone' => $timezone,
            'utc_offset' => $utcOffset,
            'utc_offset_hours' => $offsetHours,
            'datetime' => $datetime,
            'local_datetime' => $localTime->toISOString(),
            'local_time' => $localTime->format('H:i'),
            'local_date' => $localTime->format('d.m.Y'),
            'abbreviation' => $abbreviation,
            'time_diff_to_berlin' => $timeDiffToBerlin,
            'time_diff_to_berlin_formatted' => $this->formatTimeDifference($timeDiffToBerlin),
            'is_daytime' => $this->isDaytime($localTime),
            'sunrise' => $data['sunrise'] ?? null,
            'sunset' => $data['sunset'] ?? null,
            'is_fallback' => false
        ];
    }

    /**
     * Parse UTC Offset String
     */
    private function parseUtcOffset(string $utcOffset): int
    {
        // Format: "+01:00" oder "-05:00"
        if (preg_match('/^([+-])(\d{1,2}):(\d{2})$/', $utcOffset, $matches)) {
            $sign = $matches[1] === '+' ? 1 : -1;
            $hours = (int) $matches[2];
            $minutes = (int) $matches[3];
            return $sign * ($hours + $minutes / 60);
        }
        
        return 0;
    }

    /**
     * Hole Berlin UTC Offset (Sommer/Winterzeit)
     */
    private function getBerlinOffset(): int
    {
        $now = Carbon::now();
        $berlinTime = $now->setTimezone('Europe/Berlin');
        
        // Prüfe ob Sommerzeit
        return $berlinTime->isDST() ? 2 : 1;
    }

    /**
     * Formatiere Zeitdifferenz
     */
    private function formatTimeDifference(int $hours): string
    {
        if ($hours === 0) {
            return 'Gleiche Zeit';
        }
        
        $absHours = abs($hours);
        $sign = $hours > 0 ? '+' : '-';
        
        if ($absHours === 1) {
            return "{$sign}1 Stunde";
        }
        
        return "{$sign}{$absHours} Stunden";
    }

    /**
     * Prüfe ob es Tag ist
     */
    private function isDaytime(Carbon $time): bool
    {
        $hour = $time->hour;
        return $hour >= 6 && $hour <= 18;
    }

    /**
     * Hole Zeitzonen-Name basierend auf Offset
     */
    private function getTimezoneNameByOffset(int $offset): string
    {
        $timezones = [
            -12 => 'UTC-12',
            -11 => 'UTC-11',
            -10 => 'UTC-10',
            -9 => 'UTC-9',
            -8 => 'UTC-8',
            -7 => 'UTC-7',
            -6 => 'UTC-6',
            -5 => 'UTC-5',
            -4 => 'UTC-4',
            -3 => 'UTC-3',
            -2 => 'UTC-2',
            -1 => 'UTC-1',
            0 => 'UTC',
            1 => 'UTC+1',
            2 => 'UTC+2',
            3 => 'UTC+3',
            4 => 'UTC+4',
            5 => 'UTC+5',
            6 => 'UTC+6',
            7 => 'UTC+7',
            8 => 'UTC+8',
            9 => 'UTC+9',
            10 => 'UTC+10',
            11 => 'UTC+11',
            12 => 'UTC+12'
        ];

        return $timezones[$offset] ?? 'UTC';
    }

    /**
     * Hole Zeitzonen-Abkürzung
     */
    private function getTimezoneAbbreviation(int $offset): string
    {
        $abbreviations = [
            -12 => 'IDLW',
            -11 => 'NT',
            -10 => 'HST',
            -9 => 'AKST',
            -8 => 'PST',
            -7 => 'MST',
            -6 => 'CST',
            -5 => 'EST',
            -4 => 'AST',
            -3 => 'BRT',
            -2 => 'AT',
            -1 => 'WAT',
            0 => 'UTC',
            1 => 'CET',
            2 => 'EET',
            3 => 'MSK',
            4 => 'GST',
            5 => 'PKT',
            6 => 'BST',
            7 => 'ICT',
            8 => 'CST',
            9 => 'JST',
            10 => 'AEST',
            11 => 'SBT',
            12 => 'NZST'
        ];

        return $abbreviations[$offset] ?? 'UTC';
    }

    /**
     * Hole aktuelle lokale Zeit für Koordinaten
     */
    public function getCurrentLocalTime(float $latitude, float $longitude): ?array
    {
        $timezoneData = $this->getTimezoneForCoordinates($latitude, $longitude);
        
        if (!$timezoneData) {
            return null;
        }

        return [
            'local_time' => $timezoneData['local_time'],
            'local_date' => $timezoneData['local_date'],
            'local_datetime' => $timezoneData['local_datetime'] ?? null,
            'timezone' => $timezoneData['timezone'],
            'abbreviation' => $timezoneData['abbreviation'],
            'time_diff_to_berlin' => $timezoneData['time_diff_to_berlin_formatted'],
            'is_daytime' => $timezoneData['is_daytime'],
            'utc_offset_hours' => $timezoneData['utc_offset_hours'] ?? null
        ];
    }

    /**
     * Cache leeren
     */
    public function clearCache(): void
    {
        // Alle Zeitzonen-Cache-Einträge löschen
        $keys = Cache::get('timezone_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('timezone_cache_keys');
        Log::info('Timezone cache cleared');
    }
}
