<?php

namespace App\Support\AdminV2;

use App\Models\Country;
use App\Models\CustomEvent;

/**
 * Informationen einer Region (regions.info) – nur das, was fuer diese Region
 * gilt. Was fuer das ganze Land gilt (Waehrung, Visum, Strom, Notruf,
 * Landessprache …), steht weiter nur am Land und wird nicht wiederholt.
 * Leere Felder bedeuten "wie beim Land" bzw. "nichts Regionales".
 *
 * Gespeichert wird:
 *   texts: {short_description: {de, en, nl}, description: {…}, known_for: {de: ["…"]}, best_time: {…}, …}
 *   best_months: [5, 6, 9], population: 13369393, area_km2: 70550,
 *   timezone: "America/Los_Angeles" (nur wenn sie von der des Landes abweicht),
 *   meta: {ai_generated_at, ai_model, ai_filled (zuletzt von der KI ergaenzte Felder),
 *          reviewed_at, reviewed_by, last_reviewed_at, last_reviewed_by (Pruefung vor der Ergaenzung)}
 */
class RegionInfo
{
    /** Beschreibung der Region: Feld => [Bezeichnung, Art (textarea | tags), Hinweis, Zeilen] */
    public const DESCRIPTION_TEXTS = [
        'short_description' => ['Kurzbeschreibung', 'textarea', 'Zwei bis drei Sätze, die die Region auf den Punkt bringen – für Übersichten und Teaser.', 3],
        'description' => ['Beschreibung', 'textarea', 'Ausführlich zu Lage, Landschaft, Orten, Kultur und Reisecharakter der Region – Absätze durch Leerzeilen trennen.', 10],
        'known_for' => ['Bekannt für', 'tags', '3–6 Stichworte, mit Komma getrennt – z. B. Weinbau, Dolomiten, Seen.', 1],
    ];

    /** Reiseinfos der Region – nur, was hier anders ist als im ganzen Land */
    public const TRAVEL_TEXTS = [
        'best_time' => ['Beste Reisezeit', 'textarea', 'Wann sich die Region lohnt und warum – Saison, Wetter, Veranstaltungen.', 5],
        'climate' => ['Klima', 'textarea', 'Nur, wenn das Klima der Region vom Land abweicht, z. B. Gebirge, Küste, Wüste.', 5],
        'getting_there' => ['Anreise', 'textarea', 'Flughäfen, Bahnverbindungen, Fähren und Straßen in die Region.', 5],
        'getting_around' => ['Unterwegs vor Ort', 'textarea', 'Nahverkehr, Mietwagen, Regionalbahn, Fähren zwischen Inseln, Verkehrsregeln der Region.', 5],
        'cuisine' => ['Küche & Spezialitäten', 'textarea', 'Typische Gerichte und Getränke der Region.', 5],
        'good_to_know' => ['Gut zu wissen', 'textarea', 'Regionale Besonderheiten: Kurtaxe, Umweltzonen, Feiertagsbräuche, regionale Sprache, Sicherheit.', 5],
    ];

    /** Hoechstlaenge je Textfeld */
    public const TEXT_LIMITS = [
        'short_description' => 1000,
        'description' => 20000,
        'known_for' => 500,
    ];

    public const DEFAULT_TEXT_LIMIT = 4000;

    public const MONTHS = [1 => 'Jan', 2 => 'Feb', 3 => 'Mär', 4 => 'Apr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Dez'];

    public const STATUS_EMPTY = 'empty';

    public const STATUS_AI = 'ai';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_MANUAL = 'manual';

    /** Bezeichnungen der Zustaende fuer Filter und Kennzeichnung */
    public const STATUSES = [
        self::STATUS_EMPTY => 'Ohne Infos',
        self::STATUS_AI => 'KI-Entwurf, ungeprüft',
        self::STATUS_REVIEWED => 'Geprüft',
        self::STATUS_MANUAL => 'Von Hand gepflegt',
    ];

    /**
     * Alle mehrsprachigen Textfelder: Feld => [Bezeichnung, Art, Hinweis, Zeilen].
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: int}>
     */
    public static function allTexts(): array
    {
        return self::DESCRIPTION_TEXTS + self::TRAVEL_TEXTS;
    }

    /**
     * @return array<int, string>
     */
    public static function locales(): array
    {
        return CustomEvent::translationLocales();
    }

    /** Bezeichnungen der sprachunabhaengigen Angaben */
    public const FACT_LABELS = [
        'best_months' => 'Beste Reisemonate',
        'population' => 'Einwohner',
        'area_km2' => 'Fläche',
        'timezone' => 'Zeitzone',
    ];

    /**
     * "Klima, Anreise, Einwohner" fuer eine Liste von Feldern.
     */
    public static function fieldLabels(array $fields): string
    {
        $labels = array_map(fn ($field) => $field[0], self::allTexts()) + self::FACT_LABELS;

        return implode(', ', array_map(fn ($field) => $labels[$field] ?? (string) $field, $fields));
    }

    public static function limit(string $field): int
    {
        return self::TEXT_LIMITS[$field] ?? self::DEFAULT_TEXT_LIMIT;
    }

    /**
     * Gespeicherte Angaben -> Formularwerte (alle Felder vorhanden, Stichworte
     * als kommagetrennter Text).
     *
     * @return array<string, mixed>
     */
    public static function toForm(?array $info): array
    {
        $form = [
            'texts' => [],
            'best_months' => self::months((array) ($info['best_months'] ?? [])),
            'population' => isset($info['population']) ? (string) $info['population'] : '',
            'area_km2' => isset($info['area_km2']) ? (string) $info['area_km2'] : '',
            'timezone' => (string) ($info['timezone'] ?? ''),
        ];

        foreach (self::allTexts() as $field => [, $kind]) {
            foreach (self::locales() as $locale) {
                $value = $info['texts'][$field][$locale] ?? '';
                $form['texts'][$field][$locale] = $kind === 'tags' && is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        return $form;
    }

    /**
     * Formularwerte -> gespeicherte Angaben. Leeres faellt weg; eine
     * Zeitzone gleich der des Landes wird nicht doppelt gespeichert.
     *
     * @param  array<string, mixed>  $form
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>|null
     */
    public static function fromForm(array $form, ?Country $country, array $meta = []): ?array
    {
        $info = [];

        foreach (self::allTexts() as $field => [, $kind]) {
            foreach (self::locales() as $locale) {
                $value = trim((string) ($form['texts'][$field][$locale] ?? ''));

                if ($kind === 'tags') {
                    $tags = self::tags($value);
                    if ($tags !== []) {
                        $info['texts'][$field][$locale] = $tags;
                    }
                } elseif ($value !== '') {
                    $info['texts'][$field][$locale] = self::cleanText($value);
                }
            }
        }

        if ($months = self::months((array) ($form['best_months'] ?? []))) {
            $info['best_months'] = $months;
        }

        foreach (['population', 'area_km2'] as $key) {
            $number = self::number($form[$key] ?? null);
            if ($number !== null) {
                $info[$key] = $number;
            }
        }

        $timezone = trim((string) ($form['timezone'] ?? ''));
        if ($timezone !== '' && $timezone !== (string) $country?->timezone) {
            $info['timezone'] = $timezone;
        }

        $meta = array_filter($meta, fn ($value) => $value !== null && $value !== '');
        if ($meta !== [] && $info !== []) {
            $info['meta'] = $meta;
        }

        return $info === [] ? null : $info;
    }

    /**
     * Hat die Region eigene Inhalte (ohne Verwaltungsangaben)?
     */
    public static function hasContent(?array $info): bool
    {
        return array_diff(array_keys((array) $info), ['meta']) !== [];
    }

    public static function status(?array $info): string
    {
        return match (true) {
            ! self::hasContent($info) => self::STATUS_EMPTY,
            filled($info['meta']['reviewed_at'] ?? null) => self::STATUS_REVIEWED,
            filled($info['meta']['ai_generated_at'] ?? null) => self::STATUS_AI,
            default => self::STATUS_MANUAL,
        };
    }

    /**
     * Wie viele der Textfelder (in der Ausgangssprache) gefuellt sind.
     *
     * @return array{filled: int, total: int}
     */
    public static function completeness(?array $info): array
    {
        $source = CustomEvent::sourceLocale();
        $fields = array_keys(self::allTexts());

        return [
            'filled' => count(array_filter($fields, fn ($field) => filled($info['texts'][$field][$source] ?? null))),
            'total' => count($fields),
        ];
    }

    /**
     * Was an den Angaben noch fehlt: Textfelder mit den Sprachen, in denen sie
     * leer sind, und die fehlenden Fakten. Die Zeitzone zaehlt nicht – leer
     * heisst "wie beim Land". Ohne Flaeche und Einwohner bei Reisegebieten
     * ohne feste Grenzen ($withNumbers = false).
     *
     * @return array{texts: array<string, array<int, string>>, facts: array<int, string>}
     */
    public static function missing(?array $info, bool $withNumbers = true): array
    {
        $texts = [];

        foreach (array_keys(self::allTexts()) as $field) {
            $locales = array_values(array_filter(self::locales(), fn ($locale) => blank($info['texts'][$field][$locale] ?? null)));
            if ($locales !== []) {
                $texts[$field] = $locales;
            }
        }

        $facts = array_values(array_filter(
            $withNumbers ? ['best_months', 'population', 'area_km2'] : ['best_months'],
            fn ($key) => blank($info[$key] ?? null),
        ));

        return ['texts' => $texts, 'facts' => $facts];
    }

    /**
     * Anzahl der Luecken (je Feld und Sprache bzw. Fakt).
     */
    public static function missingCount(array $missing): int
    {
        return array_sum(array_map('count', $missing['texts'])) + count($missing['facts']);
    }

    /**
     * Felder, die in $after gegenueber $before neu oder geaendert sind.
     *
     * @return array<int, string>
     */
    public static function changedFields(?array $before, array $after): array
    {
        $fields = array_filter(array_keys(self::allTexts()), fn ($field) => ($before['texts'][$field] ?? null) != ($after['texts'][$field] ?? null));
        $facts = array_filter(['best_months', 'population', 'area_km2', 'timezone'], fn ($key) => ($before[$key] ?? null) != ($after[$key] ?? null));

        return array_values([...$fields, ...$facts]);
    }

    /**
     * KI-Angaben uebernehmen und als ungepruefter KI-Entwurf kennzeichnen –
     * aber nur, wenn wirklich etwas dazukommt (sonst null). Ergaenzt die KI
     * vorhandene Angaben, merkt sich meta, welche Felder neu sind (ai_filled)
     * und wann zuvor geprueft wurde (last_reviewed_*). Was $before in meta
     * sonst hat (z. B. ai_created_at), bleibt.
     *
     * @param  array<string, mixed>  $suggestion  Angaben ohne meta
     * @param  string|null  $countryTimezone  eine gleiche Zeitzone wird nicht gespeichert
     * @param  string|null  $generatedAt  Zeitpunkt des KI-Vorschlags (Standard: jetzt)
     * @return array<string, mixed>|null
     */
    public static function applyAi(?array $before, array $suggestion, string $model, bool $overwrite = false, ?string $countryTimezone = null, ?string $generatedAt = null): ?array
    {
        unset($suggestion['meta']);
        $info = self::merge($before, $suggestion, $overwrite);

        if (($info['timezone'] ?? null) === $countryTimezone) {
            unset($info['timezone']);
        }

        $changed = self::changedFields($before, $info);

        if ($changed === []) {
            return null;
        }

        $meta = (array) ($before['meta'] ?? []);
        $reviewed = filled($meta['reviewed_at'] ?? null);

        if ($reviewed) {
            $meta['last_reviewed_at'] = $meta['reviewed_at'];
            $meta['last_reviewed_by'] = $meta['reviewed_by'] ?? null;
        }

        $meta['ai_filled'] = self::hasContent($before)
            ? array_values(array_unique([...($reviewed ? [] : (array) ($meta['ai_filled'] ?? [])), ...$changed]))
            : null;
        $meta['ai_generated_at'] = $generatedAt ?: now()->toIso8601String();
        $meta['ai_model'] = $model;
        unset($meta['reviewed_at'], $meta['reviewed_by']);

        $info['meta'] = array_filter($meta, fn ($value) => $value !== null && $value !== '' && $value !== []);

        return $info;
    }

    /**
     * Zwei Angaben zusammenfuehren: was in $current fehlt, kommt aus $new;
     * mit $overwrite gewinnt $new, wo es etwas hat.
     *
     * @return array<string, mixed>
     */
    public static function merge(?array $current, array $new, bool $overwrite): array
    {
        $result = (array) $current;

        foreach ((array) ($new['texts'] ?? []) as $field => $locales) {
            foreach ((array) $locales as $locale => $value) {
                if ($overwrite || blank($result['texts'][$field][$locale] ?? null)) {
                    $result['texts'][$field][$locale] = $value;
                }
            }
        }

        foreach (['best_months', 'population', 'area_km2', 'timezone'] as $key) {
            if (! blank($new[$key] ?? null) && ($overwrite || blank($result[$key] ?? null))) {
                $result[$key] = $new[$key];
            }
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public static function tags(string $value): array
    {
        return array_values(array_unique(array_filter(array_map(fn ($tag) => trim($tag), explode(',', $value)), fn ($tag) => $tag !== '')));
    }

    /**
     * Monate 1–12, sortiert und ohne Doppelte.
     *
     * @return array<int, int>
     */
    public static function months(array $months): array
    {
        $months = array_values(array_unique(array_filter(array_map('intval', $months), fn ($month) => $month >= 1 && $month <= 12)));
        sort($months);

        return $months;
    }

    /**
     * "12.345", "12345", 12345.0 -> 12345; Unsinn -> null.
     */
    public static function number(mixed $value): ?int
    {
        if (is_int($value) || is_float($value)) {
            return $value > 0 ? (int) round($value) : null;
        }

        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        return $digits !== '' && (int) $digits > 0 ? (int) $digits : null;
    }

    /**
     * Zeilenenden vereinheitlichen und mehr als eine Leerzeile zusammenfassen.
     */
    public static function cleanText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));

        return (string) preg_replace("/\n{3,}/", "\n\n", $text);
    }

    /**
     * "Mai, Jun, Sep" fuer eine Liste von Monaten.
     */
    public static function monthLabels(array $months): string
    {
        return implode(', ', array_map(fn ($month) => self::MONTHS[$month], self::months($months)));
    }
}
