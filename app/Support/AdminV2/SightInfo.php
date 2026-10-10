<?php

namespace App\Support\AdminV2;

use App\Models\CustomEvent;

/**
 * Sehenswuerdigkeiten und Unternehmungen (sights): Kategorien, mehrsprachige
 * Texte und Verwaltungsangaben in "info".
 *
 * Gespeichert wird in sights.info:
 *   texts: {short_description: {de, en, nl}, description: {…}, opening_hours: {…}, admission: {…}, best_time: {…}, tips: {…}, accessibility: {…}},
 *   visit_minutes: 120,
 *   meta: {ai_generated_at, ai_model, geocoded ("osm" | "ai"), reviewed_at, reviewed_by}
 */
class SightInfo
{
    /** Kategorie => [Bezeichnung, Heroicon] */
    public const CATEGORIES = [
        'landmark' => ['Bauwerk & Wahrzeichen', 'building-library'],
        'old_town' => ['Altstadt & Viertel', 'home-modern'],
        'museum' => ['Museum & Galerie', 'photo'],
        'religious' => ['Kirche, Kloster & Tempel', 'sparkles'],
        'palace' => ['Schloss, Burg & Palast', 'building-office'],
        'archaeological' => ['Archäologische Stätte', 'archive-box'],
        'nature' => ['Natur & Landschaft', 'globe-europe-africa'],
        'national_park' => ['Nationalpark & Schutzgebiet', 'map'],
        'beach' => ['Strand & Küste', 'sun'],
        'viewpoint' => ['Aussichtspunkt', 'eye'],
        'park' => ['Park & Garten', 'sparkles'],
        'activity' => ['Aktivität & Erlebnis', 'bolt'],
        'theme_park' => ['Freizeitpark & Zoo', 'face-smile'],
        'market' => ['Markt & Einkaufen', 'shopping-bag'],
        'other' => ['Sonstiges', 'map-pin'],
    ];

    /** Bezeichnung der Kategorien fuer die API in weiteren Sprachen */
    public const CATEGORY_TRANSLATIONS = [
        'landmark' => ['en' => 'Landmark', 'nl' => 'Bouwwerk & bezienswaardigheid'],
        'old_town' => ['en' => 'Old town & district', 'nl' => 'Oude stad & wijk'],
        'museum' => ['en' => 'Museum & gallery', 'nl' => 'Museum & galerie'],
        'religious' => ['en' => 'Church, monastery & temple', 'nl' => 'Kerk, klooster & tempel'],
        'palace' => ['en' => 'Castle & palace', 'nl' => 'Kasteel & paleis'],
        'archaeological' => ['en' => 'Archaeological site', 'nl' => 'Archeologische vindplaats'],
        'nature' => ['en' => 'Nature & landscape', 'nl' => 'Natuur & landschap'],
        'national_park' => ['en' => 'National park & reserve', 'nl' => 'Nationaal park & natuurgebied'],
        'beach' => ['en' => 'Beach & coast', 'nl' => 'Strand & kust'],
        'viewpoint' => ['en' => 'Viewpoint', 'nl' => 'Uitzichtpunt'],
        'park' => ['en' => 'Park & garden', 'nl' => 'Park & tuin'],
        'activity' => ['en' => 'Activity & experience', 'nl' => 'Activiteit & belevenis'],
        'theme_park' => ['en' => 'Theme park & zoo', 'nl' => 'Pretpark & dierentuin'],
        'market' => ['en' => 'Market & shopping', 'nl' => 'Markt & winkelen'],
        'other' => ['en' => 'Other', 'nl' => 'Overig'],
    ];

    /**
     * Kategorie je Sprache: {de, en, nl}.
     *
     * @return array<string, string>
     */
    public static function categoryNames(?string $category): array
    {
        $key = isset(self::CATEGORIES[$category ?? '']) ? $category : 'other';

        return ['de' => self::CATEGORIES[$key][0]] + self::CATEGORY_TRANSLATIONS[$key];
    }

    /** Texte je Sprache: Feld => [Bezeichnung, Hinweis, Zeilen] */
    public const TEXTS = [
        'short_description' => ['Kurzbeschreibung', 'Ein bis zwei Sätze für Listen und Karten.', 2],
        'description' => ['Beschreibung', 'Was man sieht oder erlebt, Geschichte und Besonderheiten – Absätze durch Leerzeilen trennen.', 8],
        'opening_hours' => ['Öffnungszeiten', 'Allgemein und ohne Gewähr, z. B. „täglich 9–19 Uhr, im Winter kürzer“. Leer, wenn frei zugänglich oder unbekannt.', 2],
        'admission' => ['Eintritt', 'Preisrahmen oder „frei“ – ohne Gewähr.', 2],
        'best_time' => ['Beste Besuchszeit', 'Tageszeit oder Jahreszeit, z. B. „früh morgens vor den Reisegruppen“.', 2],
        'tips' => ['Tipps', 'Tickets vorab, Anfahrt, Kleiderordnung, Fotoverbot …', 3],
        'accessibility' => ['Barrierefreiheit', 'Zugänglichkeit für Rollstuhl und Kinderwagen, falls bekannt.', 2],
    ];

    public const TEXT_LIMITS = ['short_description' => 1000, 'description' => 10000];

    public const DEFAULT_TEXT_LIMIT = 2000;

    public const STATUS_AI = 'ai';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_MANUAL = 'manual';

    public const STATUSES = [
        self::STATUS_AI => 'KI-Entwurf, ungeprüft',
        self::STATUS_REVIEWED => 'Geprüft',
        self::STATUS_MANUAL => 'Von Hand gepflegt',
    ];

    /**
     * @return array<int, string>
     */
    public static function locales(): array
    {
        return CustomEvent::translationLocales();
    }

    public static function categoryLabel(?string $category): string
    {
        return self::CATEGORIES[$category ?? 'other'][0] ?? self::CATEGORIES['other'][0];
    }

    public static function categoryIcon(?string $category): string
    {
        return self::CATEGORIES[$category ?? 'other'][1] ?? 'map-pin';
    }

    public static function limit(string $field): int
    {
        return self::TEXT_LIMITS[$field] ?? self::DEFAULT_TEXT_LIMIT;
    }

    /**
     * @return array{texts: array<string, array<string, string>>, visit_minutes: string}
     */
    public static function toForm(?array $info): array
    {
        $form = ['texts' => [], 'visit_minutes' => isset($info['visit_minutes']) ? (string) $info['visit_minutes'] : ''];

        foreach (array_keys(self::TEXTS) as $field) {
            foreach (self::locales() as $locale) {
                $form['texts'][$field][$locale] = (string) ($info['texts'][$field][$locale] ?? '');
            }
        }

        return $form;
    }

    /**
     * @param  array<string, mixed>  $form
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>|null
     */
    public static function fromForm(array $form, array $meta = []): ?array
    {
        $info = [];

        foreach (array_keys(self::TEXTS) as $field) {
            foreach (self::locales() as $locale) {
                $value = RegionInfo::cleanText((string) ($form['texts'][$field][$locale] ?? ''));
                if ($value !== '') {
                    $info['texts'][$field][$locale] = $value;
                }
            }
        }

        $minutes = RegionInfo::number($form['visit_minutes'] ?? null);
        if ($minutes !== null && $minutes <= 10080) {
            $info['visit_minutes'] = $minutes;
        }

        $meta = array_filter($meta, fn ($value) => $value !== null && $value !== '');
        if ($meta !== []) {
            $info['meta'] = $meta;
        }

        return $info === [] ? null : $info;
    }

    public static function status(?array $info): string
    {
        return match (true) {
            filled($info['meta']['reviewed_at'] ?? null) => self::STATUS_REVIEWED,
            filled($info['meta']['ai_generated_at'] ?? null) => self::STATUS_AI,
            default => self::STATUS_MANUAL,
        };
    }

    /**
     * "1,5 Std." / "45 Min." / "ganzer Tag"
     */
    public static function durationLabel(?int $minutes): ?string
    {
        return match (true) {
            ! $minutes => null,
            $minutes >= 360 => 'ganzer Tag',
            $minutes >= 60 => str_replace('.', ',', rtrim(rtrim(number_format($minutes / 60, 1, '.', ''), '0'), '.')).' Std.',
            default => $minutes.' Min.',
        };
    }

    /**
     * Name zum Vergleich auf Dubletten: klein, ohne Akzente und Satzzeichen.
     */
    public static function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = (string) (class_exists(\Normalizer::class) ? \Normalizer::normalize($name, \Normalizer::FORM_D) : $name);
        $name = (string) preg_replace('/\p{Mn}+/u', '', $name);

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $name));
    }
}
