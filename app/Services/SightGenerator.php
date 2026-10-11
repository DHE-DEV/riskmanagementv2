<?php

namespace App\Services;

use App\Models\City;
use App\Models\CustomEvent;
use App\Models\Region;
use App\Models\Sight;
use App\Models\Subregion;
use App\Support\AdminV2\SightInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Sehenswuerdigkeiten einer Region per KI vorschlagen und als ungepruefte
 * KI-Entwuerfe anlegen. Die Koordinaten der KI werden mit OpenStreetMap
 * (Nominatim) abgeglichen; Dubletten im Land werden uebersprungen.
 * Statt einer Region geht auch ein Teilgebiet (Insel, Reisegebiet) – die
 * Eintraege haengen dann an der Region und am Teilgebiet.
 */
class SightGenerator
{
    /** So viele Regionen fragt ein Durchgang gleichzeitig an. */
    public const PARALLEL = 2;

    /** Weicht der OSM-Treffer weiter ab, bleibt der Punkt der KI. */
    public const GEOCODE_MAX_KM = 25;

    protected float $lastGeocode = 0;

    public function __construct(protected ?ChatGptService $chat = null) {}

    protected function chat(): ChatGptService
    {
        return $this->chat ??= app(ChatGptService::class);
    }

    /**
     * Mehrere Regionen fuellen. Faellt die gemeinsame Anfrage aus, wird
     * jede Region einzeln versucht.
     *
     * @param  Collection<int, Region|Subregion>  $regions
     * @return array{done: array<int, int>, failed: array<int, string>, created: int, usage: ?array}
     */
    public function fill(Collection $regions): array
    {
        $done = [];
        $failed = [];
        $created = 0;
        $answers = [];
        $options = ['timeout' => 280, 'max_tokens' => 9000];

        try {
            $answers = $this->chat()->sendPrompts($regions->mapWithKeys(fn (Region|Subregion $region) => ['r'.$region->id => $this->prompt($region)])->all(), $options);
        } catch (Throwable) {
            foreach ($regions as $region) {
                try {
                    $answers['r'.$region->id] = $this->chat()->sendPrompt($this->prompt($region), $options);
                } catch (Throwable $e) {
                    $failed[$region->id] = $this->shortError($e);
                }
            }
        }

        foreach ($regions as $region) {
            if (! isset($answers['r'.$region->id])) {
                continue;
            }

            try {
                $created += $this->store($region, $this->parse($answers['r'.$region->id]));
                $done[] = $region->id;
            } catch (Throwable $e) {
                $failed[$region->id] = $this->shortError($e);
            }
        }

        return ['done' => $done, 'failed' => $failed, 'created' => $created, 'usage' => $this->chat()->lastUsage()];
    }

    /**
     * Vorschlaege als Sehenswuerdigkeiten anlegen (ohne Dubletten im Land).
     *
     * @param  array<int, array<string, mixed>>  $suggestions
     * @return int angelegte Eintraege
     */
    public function store(Region|Subregion $place, array $suggestions): int
    {
        $subregion = $place instanceof Subregion ? $place : null;
        $region = $subregion?->region ?? $place;
        $region->loadMissing('country');
        $existing = Sight::withTrashed()->where('country_id', $region->country_id)->get()
            ->flatMap(fn (Sight $sight) => array_map(fn ($name) => SightInfo::normalizeName((string) $name), array_values((array) $sight->name_translations)))
            ->filter()->flip();

        $cities = $region->cities()->get();
        $order = (int) Sight::where('region_id', $region->id)->max('sort_order');
        $model = (string) ($this->chat()->lastUsage()['model'] ?? '');
        $created = 0;

        foreach ($suggestions as $suggestion) {
            $names = array_filter(array_map(fn ($name) => SightInfo::normalizeName((string) $name), $suggestion['name']));
            if ($names === [] || collect($names)->contains(fn ($name) => $existing->has($name))) {
                continue;
            }

            $city = $this->matchCity($cities, (string) ($suggestion['city'] ?? ''));
            [$lat, $lng, $geocoded] = $this->locate($suggestion, $region, $city, $subregion);

            Sight::create([
                'name_translations' => $suggestion['name'],
                'country_id' => $region->country_id,
                'region_id' => $region->id,
                'subregion_id' => $subregion?->id ?? $city?->subregion_id,
                'city_id' => $city?->id,
                'category' => $suggestion['category'],
                'is_highlight' => $suggestion['is_highlight'],
                'sort_order' => ++$order,
                'lat' => $lat,
                'lng' => $lng,
                'address' => $suggestion['address'],
                'website_url' => $suggestion['website'],
                'info' => SightInfo::fromForm(['texts' => $suggestion['texts'], 'visit_minutes' => $suggestion['visit_minutes']], [
                    'ai_generated_at' => now()->toIso8601String(),
                    'ai_model' => $model,
                    'geocoded' => $geocoded,
                ]),
            ]);

            foreach ($names as $name) {
                $existing->put($name, true);
            }
            $created++;
        }

        return $created;
    }

    public function prompt(Region|Subregion $region): string
    {
        if ($region instanceof Subregion) {
            return $this->subregionPrompt($region);
        }

        $region->loadMissing('country');
        $country = $region->country;
        $source = CustomEvent::sourceLocale();
        $locales = SightInfo::locales();

        $cities = $region->cities()->orderByDesc('is_regional_capital')->orderByDesc('population')->limit(25)->get()
            ->map(fn (City $city) => $city->getName('de'))->implode(', ');
        $known = Sight::where('region_id', $region->id)->get()->map(fn (Sight $sight) => $sight->getName('de'))->implode(', ');
        $regionText = trim((string) ($region->info['texts']['short_description'][$source] ?? ''));

        $context = collect(array_filter([
            'Region' => $region->getName('de').(($en = $region->name_translations['en'] ?? '') && $en !== $region->getName('de') ? " (englisch: {$en})" : ''),
            'Land' => $country?->getName('de'),
            'Kurzbeschreibung der Region' => $regionText,
            'Städte der Region in unserer Datenbank' => $cities,
            'Bereits erfasst (nicht noch einmal vorschlagen)' => $known,
        ]))->map(fn ($value, $label) => "{$label}: {$value}")->implode("\n");

        return $this->promptText($context, 'einer Region', 'Wähle je nach touristischer Bedeutung der Region 8 bis 15 Einträge (kleine, wenig besuchte Regionen 3 bis 6)', 'diese gehören zur Region, auch ohne Stadt');
    }

    /**
     * Prompt fuer ein Teilgebiet: Eintraege, die auf der Insel bzw. im Reisegebiet liegen.
     */
    public function subregionPrompt(Subregion $subregion): string
    {
        $subregion->loadMissing(['region.country']);
        $region = $subregion->region;
        $source = CustomEvent::sourceLocale();
        $kind = $subregion->kindLabel();

        $cities = $region->cities()->orderByDesc('population')->limit(25)->get()->map(fn (City $city) => $city->getName('de'))->implode(', ');
        $known = Sight::where('region_id', $region->id)->get()->map(fn (Sight $sight) => $sight->getName('de'))->implode(', ');

        $context = collect(array_filter([
            $kind => $subregion->getName('de').(($en = $subregion->name_translations['en'] ?? '') && $en !== $subregion->getName('de') ? " (englisch: {$en})" : ''),
            'Region' => $region->getName('de'),
            'Land' => $region->country?->getName('de'),
            'Kurzbeschreibung' => trim((string) ($subregion->info['texts']['short_description'][$source] ?? '')),
            'Städte der Region in unserer Datenbank' => $cities,
            'In der Region bereits erfasst (nicht noch einmal vorschlagen)' => $known,
        ]))->map(fn ($value, $label) => "{$label}: {$value}")->implode("\n");

        return $this->promptText($context, "eines Teilgebiets ({$kind}) einer Region", "Alle Einträge müssen tatsächlich in diesem Teilgebiet liegen ({$subregion->getName('de')}). Wähle je nach touristischer Bedeutung 4 bis 8 Einträge (kleine, wenig besuchte Inseln oder Gebiete 3 bis 5)", 'diese gehören zum Teilgebiet, auch ohne Stadt', 'Markiere höchstens einen Eintrag als is_highlight – nur, wenn er weit über die Region hinaus bekannt ist.');
    }

    protected function promptText(string $context, string $what, string $amount, string $natureHint, string $highlights = 'Markiere die 3 bis 5 bekanntesten als is_highlight.'): string
    {
        $locales = SightInfo::locales();
        $categories = collect(SightInfo::CATEGORIES)->map(fn ($category, $key) => "{$key} ({$category[0]})")->implode(', ');
        $texts = collect(SightInfo::TEXTS)->map(fn ($text, $key) => "{$key}: {$text[0]} – {$text[1]}")->implode("\n");
        $languages = implode(', ', array_map(fn ($locale) => $locale.' = '.CustomEvent::localeLabel($locale, false), $locales));
        $example = json_encode(['sights' => [[
            'name' => array_fill_keys($locales, '…'),
            'category' => 'landmark',
            'is_highlight' => true,
            'city' => 'Name der Stadt oder des Ortes',
            'address' => '…',
            'lat' => 45.4343,
            'lng' => 12.3388,
            'website' => 'https://… oder null',
            'visit_minutes' => 120,
            'texts' => ['short_description' => array_fill_keys($locales, '…'), 'description' => array_fill_keys($locales, '…')],
        ]]], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Du stellst für eine Reise-App die wichtigsten Sehenswürdigkeiten und Unternehmungen {$what} zusammen. Reisende können sie auf ihre Wunschliste setzen und als besucht markieren.

{$context}

{$amount}: bekannte Bauwerke, Altstädte, Museen, Naturlandschaften, Nationalparks und Schutzgebiete ({$natureHint}), Strände, Aussichtspunkte und typische Erlebnisse. Nur reale, eindeutig benannte Orte – nichts erfinden. {$highlights}

Je Eintrag:
- name: der gebräuchliche Name in allen Sprachen ({$languages})
- category: genau einer dieser Schlüssel: {$categories}
- city: der Ort, in dem er liegt (bevorzugt ein Name aus der Städteliste oben); null bei Natur ohne Ort
- address: kurze Adresse oder Lagebeschreibung
- lat, lng: Koordinaten in Dezimalgrad, möglichst genau
- website: nur die offizielle Website, wenn du sie sicher kennst, sonst null
- visit_minutes: übliche Besuchsdauer in Minuten
- texts – jeweils in allen Sprachen, sachlich, ohne Werbesprache und ohne Markdown:
{$texts}
  Öffnungszeiten, Eintritt und Barrierefreiheit nur angeben, wenn du sie einigermaßen sicher kennst – sonst weglassen. Die Kurzbeschreibung und Beschreibung sind Pflicht; die Beschreibung umfasst zwei bis vier Sätze.

Antworte ausschließlich mit einem JSON-Objekt in genau dieser Form (ohne Erklärungen, ohne Codeblock):
{$example}
PROMPT;
    }

    /**
     * Antwort der KI -> Liste geprufter Vorschlaege.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $answer): array
    {
        $json = (string) preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($answer));
        $start = strpos($json, '{');
        $end = strrpos($json, '}');
        $data = $start !== false && $end !== false ? json_decode(substr($json, $start, $end - $start + 1), true) : null;

        if (! is_array($data) || ! is_array($data['sights'] ?? null)) {
            throw new RuntimeException('Die KI hat kein gültiges JSON geliefert.');
        }

        $locales = SightInfo::locales();
        $result = [];

        foreach ($data['sights'] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = [];
            foreach ($locales as $locale) {
                $value = trim((string) (is_array($item['name'] ?? null) ? ($item['name'][$locale] ?? '') : ($locale === 'de' ? ($item['name'] ?? '') : '')));
                if ($value !== '') {
                    $name[$locale] = mb_substr($value, 0, 255);
                }
            }
            if (! isset($name['de'])) {
                continue;
            }

            $texts = [];
            foreach (array_keys(SightInfo::TEXTS) as $field) {
                foreach ($locales as $locale) {
                    $value = $item['texts'][$field][$locale] ?? null;
                    $texts[$field][$locale] = is_string($value) ? mb_substr(trim($value), 0, SightInfo::limit($field)) : '';
                }
            }

            $lat = is_numeric($item['lat'] ?? null) ? (float) $item['lat'] : null;
            $lng = is_numeric($item['lng'] ?? null) ? (float) $item['lng'] : null;
            $website = is_string($item['website'] ?? null) && filter_var($item['website'], FILTER_VALIDATE_URL) && str_starts_with($item['website'], 'http') ? mb_substr($item['website'], 0, 500) : null;

            $result[] = [
                'name' => $name,
                'category' => isset(SightInfo::CATEGORIES[$item['category'] ?? '']) ? $item['category'] : 'other',
                'is_highlight' => (bool) ($item['is_highlight'] ?? false),
                'city' => is_string($item['city'] ?? null) ? trim($item['city']) : '',
                'address' => is_string($item['address'] ?? null) && trim($item['address']) !== '' ? mb_substr(trim($item['address']), 0, 500) : null,
                'lat' => $lat !== null && abs($lat) <= 90 ? $lat : null,
                'lng' => $lng !== null && abs($lng) <= 180 ? $lng : null,
                'website' => $website,
                'visit_minutes' => $item['visit_minutes'] ?? null,
                'texts' => $texts,
            ];
        }

        if ($result === []) {
            throw new RuntimeException('Die KI hat keine Sehenswürdigkeiten geliefert.');
        }

        return $result;
    }

    /**
     * Stadt der Region zum Ortsnamen der KI (deutsch oder englisch, ohne Akzente).
     *
     * @param  Collection<int, City>  $cities
     */
    protected function matchCity(Collection $cities, string $name): ?City
    {
        $wanted = SightInfo::normalizeName($name);

        if ($wanted === '') {
            return null;
        }

        return $cities->first(fn (City $city) => collect((array) $city->name_translations)->contains(fn ($value) => SightInfo::normalizeName((string) $value) === $wanted));
    }

    /**
     * Koordinaten: OSM-Treffer, wenn er zum Punkt der KI passt (oder die KI keinen
     * hat), sonst der Punkt der KI.
     *
     * @param  array<string, mixed>  $suggestion
     * @return array{0: ?float, 1: ?float, 2: ?string}
     */
    protected function locate(array $suggestion, Region $region, ?City $city, ?Subregion $subregion = null): array
    {
        $ai = $suggestion['lat'] !== null && $suggestion['lng'] !== null ? [$suggestion['lat'], $suggestion['lng']] : null;
        $place = $city?->getName('en') ?: $city?->getName('de') ?: $suggestion['city'] ?: $subregion?->getName('en');

        // Erst der deutsche, dann der englische Name – OSM kennt oft nur den einen.
        foreach (array_unique(array_filter([$suggestion['name']['de'] ?? null, $suggestion['name']['en'] ?? null])) as $name) {
            $hit = $this->geocode($name, $place, $region);

            if ($hit && (! $ai || self::distanceKm($ai[0], $ai[1], $hit[0], $hit[1]) <= self::GEOCODE_MAX_KM)) {
                return [$hit[0], $hit[1], 'osm'];
            }
        }

        return $ai ? [$ai[0], $ai[1], 'ai'] : [null, null, null];
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    protected function geocode(string $name, ?string $place, Region $region): ?array
    {
        if (! config('services.nominatim.enabled', true)) {
            return null;
        }

        // Nominatim erlaubt hoechstens eine Anfrage je Sekunde.
        $wait = (float) config('services.nominatim.throttle', 1.1) - (microtime(true) - $this->lastGeocode);
        if ($wait > 0) {
            usleep((int) ($wait * 1_000_000));
        }
        $this->lastGeocode = microtime(true);

        try {
            $response = Http::withHeaders(['User-Agent' => (string) config('services.nominatim.user_agent', 'Passolution Travel Information Platform (platform.passolution.de)')])
                ->timeout(15)
                ->get((string) config('services.nominatim.url', 'https://nominatim.openstreetmap.org').'/search', [
                    'q' => implode(', ', array_filter([$name, $place, $region->getName('en') ?: $region->getName('de')])),
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => strtolower((string) $region->country?->iso_code),
                ]);

            $first = $response->successful() ? ($response->json()[0] ?? null) : null;

            return $first && is_numeric($first['lat'] ?? null) ? [(float) $first['lat'], (float) $first['lon']] : null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    protected function shortError(Throwable $e): string
    {
        return mb_substr(str_replace('Fehler bei der Kommunikation mit ChatGPT: ', '', $e->getMessage()), 0, 200);
    }
}
