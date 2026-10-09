<?php

namespace App\Http\Resources\Api\V1;

use Carbon\CarbonImmutable;

/**
 * Wetterbericht fuer die Kunden-API: aktuelles Wetter (verarbeitet vom WeatherService),
 * die naechsten 24 Stunden in 3-Stunden-Schritten und bis zu fuenf Tage mit Tiefst-/Hoechstwert.
 * Zeiten sind ISO 8601 in der Ortszeit des Standorts.
 */
class WeatherResource
{
    /**
     * @param  array<string, mixed>  $current  Ergebnis von WeatherService::getCurrentWeather
     * @param  array<string, mixed>|null  $forecast  Rohantwort von GET /forecast (3-Stunden-Schritte)
     * @return array<string, mixed>
     */
    public static function report(array $current, ?array $forecast, float $lat, float $lng, ?string $name): array
    {
        $offset = (int) ($forecast['city']['timezone'] ?? $current['timezone_offset'] ?? 0);
        $tz = self::timezone($offset);
        $list = is_array($forecast['list'] ?? null) ? $forecast['list'] : [];

        $hourly = [];
        foreach (array_slice($list, 0, 8) as $entry) {
            $hourly[] = [
                'time' => CarbonImmutable::createFromTimestampUTC((int) $entry['dt'])->setTimezone($tz)->toIso8601String(),
                'temperature' => round((float) ($entry['main']['temp'] ?? 0)),
                'icon' => (string) ($entry['weather'][0]['icon'] ?? '01d'),
                'description' => (string) ($entry['weather'][0]['description'] ?? ''),
                'precipitation_probability' => (int) round(((float) ($entry['pop'] ?? 0)) * 100),
                'wind_speed' => round(((float) ($entry['wind']['speed'] ?? 0)) * 3.6),
            ];
        }

        // Tage: je Ortsdatum Tiefst-/Hoechstwert, Symbol vom Mittag (sonst erster Eintrag), hoechste Regenwahrscheinlichkeit.
        $days = [];
        foreach ($list as $entry) {
            $local = CarbonImmutable::createFromTimestampUTC((int) $entry['dt'])->setTimezone($tz);
            $date = $local->toDateString();
            $temp = (float) ($entry['main']['temp'] ?? 0);
            $icon = (string) ($entry['weather'][0]['icon'] ?? '01d');
            $day = $days[$date] ?? ['date' => $date, 'temp_min' => $temp, 'temp_max' => $temp, 'icon' => $icon, 'description' => (string) ($entry['weather'][0]['description'] ?? ''), 'precipitation_probability' => 0, '_distance' => 99];
            $day['temp_min'] = min($day['temp_min'], $temp);
            $day['temp_max'] = max($day['temp_max'], $temp);
            $day['precipitation_probability'] = max($day['precipitation_probability'], (int) round(((float) ($entry['pop'] ?? 0)) * 100));
            $distance = abs($local->hour - 13);
            if ($distance < $day['_distance']) {
                $day['_distance'] = $distance;
                $day['icon'] = substr($icon, 0, 2).'d';
                $day['description'] = (string) ($entry['weather'][0]['description'] ?? '');
            }
            $days[$date] = $day;
        }
        $daily = array_values(array_map(function (array $day) {
            unset($day['_distance']);
            $day['temp_min'] = round($day['temp_min']);
            $day['temp_max'] = round($day['temp_max']);

            return $day;
        }, $days));

        $observed = CarbonImmutable::createFromTimestampUTC((int) ($current['timestamp'] ?? time()))->setTimezone($tz);

        return [
            'location' => [
                'name' => $name ?: ($current['city_name'] ?? null),
                'lat' => $lat,
                'lng' => $lng,
                'timezone_offset' => $offset,
            ],
            'current' => [
                'observed_at' => $observed->toIso8601String(),
                'temperature' => round((float) $current['temperature']),
                'feels_like' => round((float) $current['feels_like']),
                'temp_min' => $daily[0]['temp_min'] ?? null,
                'temp_max' => $daily[0]['temp_max'] ?? null,
                'humidity' => (int) $current['humidity'],
                'pressure' => (int) $current['pressure'],
                'wind_speed' => round((float) $current['wind_speed']),
                'wind_direction' => (int) $current['wind_direction'],
                'visibility' => (float) $current['visibility'],
                'clouds' => (int) ($current['clouds'] ?? 0),
                'description' => (string) $current['description'],
                'condition' => (string) $current['condition'],
                'icon' => (string) $current['icon'],
                'icon_url' => (string) $current['icon_url'],
                'sunrise' => isset($current['sunrise']) ? CarbonImmutable::createFromTimestampUTC((int) $current['sunrise'])->setTimezone($tz)->toIso8601String() : null,
                'sunset' => isset($current['sunset']) ? CarbonImmutable::createFromTimestampUTC((int) $current['sunset'])->setTimezone($tz)->toIso8601String() : null,
            ],
            'hourly' => $hourly,
            'daily' => $daily,
            'source' => ['name' => 'OpenWeatherMap', 'url' => 'https://openweathermap.org'],
        ];
    }

    /** Feste Zeitzone aus dem Versatz in Sekunden, z. B. +02:00. */
    private static function timezone(int $offsetSeconds): string
    {
        $sign = $offsetSeconds < 0 ? '-' : '+';
        $abs = abs($offsetSeconds);

        return sprintf('%s%02d:%02d', $sign, intdiv($abs, 3600), intdiv($abs % 3600, 60));
    }
}
