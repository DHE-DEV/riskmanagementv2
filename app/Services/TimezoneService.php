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
        // Deutschland (grobe Bounding Box): nutze Europe/Berlin (DST-aware)
        $isGermany = $latitude >= 47.0 && $latitude <= 55.2 && $longitude >= 5.5 && $longitude <= 15.7;

        if ($isGermany) {
            $berlin = Carbon::now('Europe/Berlin');
            $offsetHours = (int) round($berlin->utcOffset() / 60);
            return [
                'timezone' => 'Europe/Berlin',
                'utc_offset' => ($offsetHours >= 0 ? "+$offsetHours:00" : "-$offsetHours:00"),
                'utc_offset_hours' => $offsetHours,
                'datetime' => now('UTC')->toISOString(),
                'local_datetime' => $berlin->toISOString(),
                'local_time' => $berlin->format('H:i'),
                'local_date' => $berlin->format('d.m.Y'),
                'abbreviation' => $berlin->format('T'),
                'time_diff_to_berlin' => 0,
                'time_diff_to_berlin_formatted' => 'Gleiche Zeit',
                'is_daytime' => $this->isDaytime($berlin),
                'is_fallback' => true
            ];
        }

        // Generische Berechnung basierend auf Längengrad (grobe Annäherung)
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
