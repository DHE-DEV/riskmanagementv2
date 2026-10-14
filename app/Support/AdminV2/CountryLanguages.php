<?php

namespace App\Support\AdminV2;

/**
 * Im Land gesprochene Sprachen (countries.languages): ISO-639-Codes, die
 * wichtigste (Amtssprache) zuerst, z. B. ["es", "ca", "eu", "gl"]. Namen
 * kommen aus intl, damit sie in jeder Sprache stimmen.
 */
class CountryLanguages
{
    /** Auswahl: ISO 639-1 und die wenigen dreistelligen Codes, die es nur so gibt. */
    public const CODES = [
        'aa', 'ab', 'af', 'ak', 'am', 'an', 'ar', 'as', 'av', 'ay', 'az', 'ba', 'be', 'bg', 'bi', 'bm', 'bn', 'bo', 'br', 'bs',
        'ca', 'ce', 'ch', 'co', 'cs', 'cv', 'cy', 'da', 'de', 'dv', 'dz', 'ee', 'el', 'en', 'eo', 'es', 'et', 'eu', 'fa', 'ff',
        'fi', 'fj', 'fo', 'fr', 'fy', 'ga', 'gd', 'gl', 'gn', 'gu', 'gv', 'ha', 'he', 'hi', 'ho', 'hr', 'ht', 'hu', 'hy', 'hz',
        'id', 'ig', 'ii', 'ik', 'is', 'it', 'iu', 'ja', 'jv', 'ka', 'kg', 'ki', 'kj', 'kk', 'kl', 'km', 'kn', 'ko', 'kr', 'ks',
        'ku', 'kv', 'kw', 'ky', 'la', 'lb', 'lg', 'li', 'ln', 'lo', 'lt', 'lu', 'lv', 'mg', 'mh', 'mi', 'mk', 'ml', 'mn', 'mr',
        'ms', 'mt', 'my', 'na', 'nb', 'nd', 'ne', 'ng', 'nl', 'nn', 'no', 'nr', 'nv', 'ny', 'oc', 'om', 'or', 'os', 'pa', 'pl',
        'ps', 'pt', 'qu', 'rm', 'rn', 'ro', 'ru', 'rw', 'sa', 'sc', 'sd', 'se', 'sg', 'si', 'sk', 'sl', 'sm', 'sn', 'so', 'sq',
        'sr', 'ss', 'st', 'su', 'sv', 'sw', 'ta', 'te', 'tg', 'th', 'ti', 'tk', 'tl', 'tn', 'to', 'tr', 'ts', 'tt', 'ty', 'ug',
        'uk', 'ur', 'uz', 've', 'vi', 'wa', 'wo', 'xh', 'yi', 'yo', 'za', 'zh', 'zu',
        'fil', 'haw', 'kri', 'pap', 'tet', 'yue',
    ];

    public static function label(string $code, string $locale = 'de'): string
    {
        $name = class_exists(\Locale::class) ? (string) \Locale::getDisplayLanguage($code, $locale) : '';

        return $name !== '' && $name !== $code ? $name : mb_strtoupper($code);
    }

    /**
     * Alle Sprachen der Auswahl, nach Namen sortiert: Code => Name.
     *
     * @return array<string, string>
     */
    public static function options(string $locale = 'de'): array
    {
        $options = [];
        foreach (self::CODES as $code) {
            $options[$code] = self::label($code, $locale);
        }
        asort($options, SORT_LOCALE_STRING);

        return $options;
    }

    /**
     * Gueltige Codes, klein, ohne Doppelte, Reihenfolge bleibt.
     *
     * @param  array<int, mixed>  $codes
     * @return array<int, string>
     */
    public static function normalize(array $codes): array
    {
        $codes = array_map(fn ($code) => mb_strtolower(trim((string) $code)), $codes);

        return array_values(array_unique(array_filter($codes, fn (string $code) => (bool) preg_match('/^[a-z]{2,3}$/', $code))));
    }

    /**
     * Einen Code aus einer Eingabe: Code ("es"), Name ("Spanisch", "Spanish")
     * oder Auswahl-Text ("Spanisch (es)").
     */
    public static function find(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/\(([a-z]{2,3})\)\s*$/i', $value, $match)) {
            return mb_strtolower($match[1]);
        }

        $needle = mb_strtolower($value);
        if (preg_match('/^[a-z]{2,3}$/', $needle) && in_array($needle, self::CODES, true)) {
            return $needle;
        }

        foreach (['de', 'en'] as $locale) {
            foreach (self::CODES as $code) {
                if (mb_strtolower(self::label($code, $locale)) === $needle) {
                    return $code;
                }
            }
        }

        return null;
    }

    /**
     * Vorschlag der KI ("es, ca" oder "Spanisch, Katalanisch") als Codes.
     *
     * @return array<int, string>
     */
    public static function parse(string $value): array
    {
        $found = [];
        foreach (preg_split('/[,;\/]+|\bund\b|\band\b/u', $value) ?: [] as $part) {
            // "Spanisch (es)" und "es (Spanisch)" – der Code in Klammern oder vorne zaehlt.
            $part = trim($part);
            $code = self::find($part) ?? self::find((string) preg_replace('/\s*\(.*\)\s*$/u', '', $part));
            if ($code !== null) {
                $found[] = $code;
            }
        }

        return self::normalize($found);
    }

    /**
     * Fuer Anzeige und KI: "Spanisch (es), Katalanisch (ca)".
     *
     * @param  array<int, string>  $codes
     */
    public static function describe(array $codes, string $locale = 'de'): ?string
    {
        return implode(', ', array_map(fn (string $code) => self::label($code, $locale).' ('.$code.')', $codes)) ?: null;
    }

    public static function reviewHint(): string
    {
        return 'Sprachen: die im Land gesprochenen Sprachen als ISO-639-1-Codes (z. B. es, ca, eu, gl), mehrere mit Komma –'
            .' zuerst die Amtssprache(n), dann weitere verbreitete Sprachen, die für Reisende wichtig sind (z. B. Englisch als Verkehrssprache).';
    }
}
