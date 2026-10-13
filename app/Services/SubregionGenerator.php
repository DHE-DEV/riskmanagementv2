<?php

namespace App\Services;

use App\Models\City;
use App\Models\CustomEvent;
use App\Models\Region;
use App\Models\Sight;
use App\Models\Subregion;
use App\Services\Concerns\AsksChatGpt;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\SightInfo;
use App\Support\AdminV2\SubregionInfo;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

/**
 * Teilgebiete einer Region (Inseln, Inselgruppen, Reisegebiete) per KI
 * recherchieren und anlegen – ohne Infos, die fuellt danach
 * RegionInfoGenerator::fillMissing(). Was es im Land schon gibt (auch im
 * Papierkorb), wird nicht doppelt angelegt. Staedte der Region, die die KI
 * dem Teilgebiet zuordnet, und deren Sehenswuerdigkeiten bekommen das
 * Teilgebiet, sofern sie noch keines haben. Neu Angelegtes ist in
 * info.meta.ai_created_at gekennzeichnet; an der Region steht, wann gesucht
 * wurde (subregions_checked_at).
 */
class SubregionGenerator
{
    use AsksChatGpt;

    /** So viele Regionen fragt ein Durchgang gleichzeitig an. */
    public const PARALLEL = 2;

    /** Hoechstens so viele neue Teilgebiete je Region. */
    public const MAX_PER_REGION = 8;

    /** Liegt der Punkt der KI weiter vom Mittelpunkt der Region weg, bleibt er leer. */
    public const MAX_DISTANCE_KM = 400;

    public function __construct(protected ?ChatGptService $chat = null) {}

    protected function chat(): ChatGptService
    {
        return $this->chat ??= app(ChatGptService::class);
    }

    /**
     * @param  Collection<int, Region>  $regions
     * @return array{done: array<int, int>, created: array<int, int>, failed: array<int, string>, usage: ?array}
     */
    public function discover(Collection $regions, bool $web = false): array
    {
        $done = [];
        $created = [];
        ['answers' => $answers, 'failed' => $failed, 'usage' => $usage, 'sources' => $sources] = $this->ask(
            $regions->mapWithKeys(fn (Region $region) => [$region->id => $this->prompt($region, $web)])->all(),
            $web,
            4000,
        );

        foreach ($regions as $region) {
            if (! isset($answers[$region->id])) {
                continue;
            }

            try {
                $new = $this->store($region, $this->parse($this->stripCitations($answers[$region->id])));
                $created = [...$created, ...$new];
                $done[] = $region->id;

                if ($new !== []) {
                    $this->storeSources($region, $sources[$region->id] ?? [], $answers[$region->id], 'subregions');
                }
            } catch (Throwable $e) {
                $failed[$region->id] = $this->shortError($e);
            }
        }

        return ['done' => $done, 'created' => $created, 'failed' => $failed, 'usage' => $usage];
    }

    public function prompt(Region $region, bool $web = false): string
    {
        $region->loadMissing('country');
        $country = $region->country;
        $source = CustomEvent::sourceLocale();
        $locales = RegionInfo::locales();

        $existing = Subregion::query()->where('region_id', $region->id)->orderBy('sort_order')->get()
            ->map(fn (Subregion $subregion) => $subregion->getName('de').' ('.$subregion->kindLabel().')')
            ->implode(', ');

        $cities = $region->cities()
            ->orderByDesc('is_regional_capital')->orderByDesc('is_major')->orderByDesc('population')
            ->limit(60)->get()
            ->map(fn (City $city) => $city->getName('de'))
            ->implode(', ');

        $data = array_filter([
            'Region' => $region->getName('de').(($en = $region->name_translations['en'] ?? '') && $en !== $region->getName('de') ? " (englisch: {$en})" : ''),
            'Land' => $country?->getName('de'),
            'Kurzbeschreibung der Region' => trim((string) ($region->info['texts']['short_description'][$source] ?? '')),
            'Mittelpunkt' => $region->lat !== null && $region->lng !== null ? $region->lat.', '.$region->lng : null,
            'Schon angelegte Teilgebiete (NICHT noch einmal nennen)' => $existing ?: 'keine',
            'Städte der Region' => $cities,
        ]);

        $context = collect($data)->map(fn ($value, $label) => "{$label}: {$value}")->implode("\n");
        $languages = implode(', ', array_map(fn ($locale) => $locale.' = '.CustomEvent::localeLabel($locale, false), $locales));
        $max = self::MAX_PER_REGION;
        $research = $web
            ? ' Recherchiere dazu im Internet in mehreren verlässlichen Quellen (offizielle Tourismusseiten, Reiseveranstalter, Wikipedia). Belege jedes Teilgebiet direkt hinter seinem deutschen Namen mit einem Link in der Form ([Seite](URL)) – die Links werden automatisch entfernt und als Quellen gespeichert.'
            : '';
        $example = json_encode(['subregions' => [[
            'kind' => 'island',
            'name' => array_fill_keys($locales, '…'),
            'lat' => 28.29,
            'lng' => -16.63,
            'cities' => ['…'],
        ]]], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Du pflegst die Teilgebiete einer Region in einer Reise-App. Teilgebiete liegen zwischen Region und Stadt und helfen Reisenden, sich zu orientieren.

{$context}

Nenne nur die bekannten Reiseregionen dieser Region, die noch fehlen – Gebiete, die Reisende beim Namen kennen und gezielt als Reiseziel suchen:
- island: bekannte Ferieninseln und touristisch bedeutende Inseln
- archipelago: bekannte Inselgruppen
- area: bekannte Reiseregionen, die Reiseveranstalter in ihren Katalogen und Reiseführer als eigenes Ziel führen – große Küstenabschnitte, bekannte Gebirgs-, Seen- und Weinregionen (z. B. Costa Brava, Chianti, Gardasee, Dolomiten, Allgäu)

Nicht: Städte und Orte, Stadtteile, Verwaltungseinheiten (Provinzen, Comarcas, Bezirke, Gemeinden) – außer sie sind zugleich eine bekannte Reiseregion –, einzelne Berge, Hügel, Halbinseln, Buchten oder Täler, die nur Ortskundige kennen, kleine unbewohnte Inseln, Nationalparks und einzelne Sehenswürdigkeiten, die Region selbst, Gebiete, die überwiegend in einer anderen Region liegen, und keine zwei Namen für dasselbe Gebiet. Erfinde nichts; nenne nur, was es sicher gibt und was eine breite Zahl von Urlaubern kennt. Lieber wenige als zu viele: höchstens {$max}, die bekanntesten zuerst – die meisten Regionen haben nur zwei bis fünf solche Reiseregionen, viele keine. Gibt es keine, gib eine leere Liste zurück.{$research}

Je Teilgebiet:
- kind: island, archipelago oder area
- name: übliche Bezeichnung in allen Sprachen ({$languages})
- lat, lng: ungefährer Mittelpunkt (nur wenn bekannt, sonst null)
- cities: welche der oben genannten Städte der Region darin liegen – genau so geschrieben wie oben, sonst leer

Antworte ausschließlich mit einem JSON-Objekt in genau dieser Form (ohne Erklärungen, ohne Codeblock):
{$example}
PROMPT;
    }

    /**
     * @return array<int, array{kind: string, name: array<string, string>, lat: ?float, lng: ?float, cities: array<int, string>}>
     */
    public function parse(string $answer): array
    {
        $json = (string) preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($answer));
        $start = strpos($json, '{');
        $end = strrpos($json, '}');
        $data = $start !== false && $end !== false ? json_decode(substr($json, $start, $end - $start + 1), true) : null;

        if (! is_array($data) || ! is_array($data['subregions'] ?? null)) {
            throw new RuntimeException('Die KI hat kein gültiges JSON geliefert.');
        }

        $result = [];

        foreach (array_slice($data['subregions'], 0, self::MAX_PER_REGION) as $item) {
            $names = array_filter(array_map(fn ($name) => is_string($name) ? mb_substr(trim($name), 0, 150) : '', array_intersect_key((array) ($item['name'] ?? []), array_flip(RegionInfo::locales()))));

            if (blank($names[CustomEvent::sourceLocale()] ?? null)) {
                continue;
            }

            $lat = is_numeric($item['lat'] ?? null) && abs((float) $item['lat']) <= 90 ? (float) $item['lat'] : null;
            $lng = is_numeric($item['lng'] ?? null) && abs((float) $item['lng']) <= 180 ? (float) $item['lng'] : null;

            $result[] = [
                'kind' => SubregionInfo::kind(is_string($item['kind'] ?? null) ? $item['kind'] : null),
                'name' => $names,
                'lat' => $lat !== null && $lng !== null ? $lat : null,
                'lng' => $lat !== null && $lng !== null ? $lng : null,
                'cities' => array_values(array_filter((array) ($item['cities'] ?? []), 'is_string')),
            ];
        }

        return $result;
    }

    /**
     * Vorschlaege anlegen und zuordnen; die Region gilt danach als durchsucht.
     *
     * @param  array<int, array<string, mixed>>  $suggestions
     * @return array<int, int> IDs der neuen Teilgebiete
     */
    public function store(Region $region, array $suggestions): array
    {
        $region->loadMissing('country');
        $model = (string) ($this->chat()->lastUsage()['model'] ?? '');

        // Bekannte Namen im ganzen Land (auch im Papierkorb), dazu Region und Land selbst.
        $known = Subregion::withTrashed()->where('country_id', $region->country_id)->get()
            ->flatMap(fn (Subregion $subregion) => array_values((array) $subregion->name_translations))
            ->merge(array_values((array) $region->name_translations))
            ->merge(array_values((array) $region->country?->name_translations))
            ->flatMap(fn ($name) => $this->nameKeys((string) $name))
            ->flip();

        $cities = $region->cities()->get();
        $order = (int) Subregion::query()->where('region_id', $region->id)->max('sort_order');
        $created = [];

        foreach ($suggestions as $suggestion) {
            $names = collect($suggestion['name'])->flatMap(fn ($name) => $this->nameKeys((string) $name))->all();

            if ($names === [] || collect($names)->contains(fn ($name) => $known->has($name))) {
                continue;
            }

            [$lat, $lng] = [$suggestion['lat'], $suggestion['lng']];
            if ($lat !== null && $region->lat !== null && $region->lng !== null
                && SightGenerator::distanceKm((float) $region->lat, (float) $region->lng, $lat, $lng) > self::MAX_DISTANCE_KM) {
                [$lat, $lng] = [null, null];
            }

            $subregion = Subregion::create([
                'region_id' => $region->id,
                'country_id' => $region->country_id,
                'kind' => $suggestion['kind'],
                'name_translations' => $suggestion['name'],
                'lat' => $lat,
                'lng' => $lng,
                'sort_order' => ++$order,
                'info' => ['meta' => ['ai_created_at' => now()->toIso8601String(), 'ai_model' => $model]],
            ]);

            $this->assign($subregion, $cities, $suggestion['cities']);

            foreach ($names as $name) {
                $known->put($name, true);
            }
            $created[] = $subregion->id;
        }

        $region->forceFill(['subregions_checked_at' => now()])->save();

        return $created;
    }

    /**
     * Vergleichsschluessel eines Namens: ohne Akzente, dazu mit Umlauten als
     * ae/oe/ue – "Pyrenäen" und "Pyrenaeen" sind dasselbe Teilgebiet.
     *
     * @return array<int, string>
     */
    protected function nameKeys(string $name): array
    {
        $german = str_replace(['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'], ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss'], $name);

        return array_values(array_unique(array_filter([SightInfo::normalizeName($name), SightInfo::normalizeName($german)])));
    }

    /**
     * Genannte Staedte (und ihre Sehenswuerdigkeiten) ohne Teilgebiet dem neuen zuordnen.
     *
     * @param  Collection<int, City>  $cities
     * @param  array<int, string>  $names
     */
    protected function assign(Subregion $subregion, Collection $cities, array $names): void
    {
        $wanted = array_filter(array_map(fn ($name) => SightInfo::normalizeName($name), $names));

        $matches = $cities->filter(fn (City $city) => ! $city->subregion_id
            && collect((array) $city->name_translations)->contains(fn ($value) => in_array(SightInfo::normalizeName((string) $value), $wanted, true)));

        foreach ($matches as $city) {
            $city->forceFill(['subregion_id' => $subregion->id])->save();
        }

        if ($matches->isNotEmpty()) {
            Sight::query()->whereIn('city_id', $matches->modelKeys())->whereNull('subregion_id')->update(['subregion_id' => $subregion->id]);
        }
    }
}
