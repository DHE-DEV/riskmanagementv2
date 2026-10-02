<?php

namespace App\Support\AdminV2;

/**
 * Liest Koordinaten aus dem, was sich aus Google Maps kopieren laesst:
 * das Zahlenpaar selbst oder ein Link auf einen Ort.
 */
class Coordinates
{
    /**
     * @return array{lat: float, lng: float}|null
     */
    public static function parse(?string $input): ?array
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        $number = '(-?\d{1,3}(?:\.\d+)?)';

        $patterns = [
            // Stecknadel im Link: …!3d48.1351!4d11.5820 – genauer als der Kartenausschnitt hinter dem @.
            '/!3d'.$number.'!4d'.$number.'/',
            // Kartenausschnitt: …/@48.1351,11.5820,12z
            '/@'.$number.','.$number.'/',
            // Parameter ll=, q=, query=, center=
            '/[?&](?:ll|q|query|center)='.$number.'(?:,|%2C)'.$number.'/i',
            // Das Zahlenpaar allein, auch in Klammern: "48.1351, 11.5820"
            '/^\(?\s*'.$number.'\s*[,;\s]\s*'.$number.'\s*\)?$/',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $input, $matches)) {
                continue;
            }

            $lat = (float) $matches[1];
            $lng = (float) $matches[2];

            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                return ['lat' => $lat, 'lng' => $lng];
            }
        }

        return null;
    }

    /**
     * Zahl ohne ueberfluessige Nullen und ohne Exponentialschreibweise.
     */
    public static function format(float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $formatted = rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');

        return $formatted === '-0' ? '0' : $formatted;
    }
}
