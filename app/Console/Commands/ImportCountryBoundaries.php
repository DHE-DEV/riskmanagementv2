<?php

namespace App\Console\Commands;

use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importiert Landesgrenzen aus Natural Earth (ne_10m_admin_0_countries) nach country_boundaries.
 *
 * Quelle: https://github.com/nvkelso/natural-earth-vector - Public Domain, keine Attribution noetig.
 */
class ImportCountryBoundaries extends Command
{
    protected $signature = 'countries:import-boundaries
        {--layer=subunits : Natural-Earth-Ebene: subunits oder countries}
        {--path= : Lokale GeoJSON-Datei statt Download}
        {--url= : Abweichende Download-URL}
        {--source= : Kennzeichnung der Datenquelle (Standard: natural_earth_10m_<layer>)}
        {--keep-existing : Vorhandene Grenzen nicht loeschen, nur fehlende ergaenzen}';

    protected $description = 'Ländergrenzen aus Natural Earth importieren (Standard: ne_10m_admin_0_map_subunits)';

    /**
     * Verfuegbare Ebenen.
     *
     * subunits (Standard) trennt Aussengebiete mit eigenem ISO-Code ab - Franzoesisch-Guayana,
     * Martinique, Guadeloupe, Réunion und Mayotte liegen dort als eigene Einheiten vor und
     * nicht, wie in der countries-Ebene, im Polygon des Mutterlandes.
     */
    private const LAYERS = [
        'subunits' => 'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_10m_admin_0_map_subunits.geojson',
        'countries' => 'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_10m_admin_0_countries.geojson',
    ];

    public function handle(): int
    {
        if (! array_key_exists($this->layer(), self::LAYERS)) {
            $this->error('Unbekannte Ebene: ' . $this->layer() . ' (erlaubt: ' . implode(', ', array_keys(self::LAYERS)) . ')');

            return self::FAILURE;
        }

        $geojson = $this->loadGeoJson();

        if ($geojson === null) {
            return self::FAILURE;
        }

        if (! isset($geojson['features']) || ! is_array($geojson['features'])) {
            $this->error('Die Datei enthält keine FeatureCollection.');

            return self::FAILURE;
        }

        $this->info(count($geojson['features']) . ' Features geladen.');

        [$byCountry, $unmatched] = $this->groupFeaturesByCountry($geojson['features']);

        if (empty($byCountry)) {
            $this->error('Kein einziges Feature konnte einem Land zugeordnet werden.');

            return self::FAILURE;
        }

        $this->writeBoundaries($byCountry);
        $this->report($byCountry, $unmatched);

        return self::SUCCESS;
    }

    /**
     * @return ?array<string, mixed>
     */
    private function loadGeoJson(): ?array
    {
        $path = $this->option('path');

        if ($path) {
            if (! is_readable($path)) {
                $this->error("Datei nicht lesbar: {$path}");

                return null;
            }

            $this->info("Lese {$path} ...");
            $raw = file_get_contents($path);
        } else {
            $url = $this->option('url') ?: self::LAYERS[$this->layer()];
            $this->info("Lade {$url} ...");
            $raw = @file_get_contents($url);

            if ($raw === false) {
                $this->error('Download fehlgeschlagen. Datei manuell laden und mit --path= übergeben.');

                return null;
            }
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            $this->error('GeoJSON konnte nicht geparst werden: ' . json_last_error_msg());

            return null;
        }

        return $decoded;
    }

    /**
     * Features den Laendern zuordnen und je Land zu einem MultiPolygon zusammenfassen.
     *
     * Ein Land kann mehrere Features haben (z.B. Australien plus Aussengebiete,
     * Frankreich plus Clipperton) - country_id ist unique, also werden alle
     * Polygone eines Landes in eine Geometrie gelegt.
     *
     * @param  array<int, array<string, mixed>>  $features
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function groupFeaturesByCountry(array $features): array
    {
        $countriesByIso2 = Country::query()->whereNotNull('iso_code')->pluck('id', 'iso_code');
        $countriesByIso3 = Country::query()->whereNotNull('iso3_code')->pluck('id', 'iso3_code');

        $byCountry = [];
        $unmatched = [];

        foreach ($features as $feature) {
            $props = $feature['properties'] ?? [];
            $name = $props['NAME'] ?? $props['NAME_LONG'] ?? 'unbekannt';

            $match = $this->matchCountry($props, $countriesByIso2, $countriesByIso3);

            if ($match === null) {
                $unmatched[] = $name;

                continue;
            }

            [$countryId, $iso2, $iso3] = $match;

            $polygons = $this->extractPolygons($feature['geometry'] ?? []);

            if (empty($polygons)) {
                $unmatched[] = $name . ' (keine verwertbare Geometrie)';

                continue;
            }

            if (! isset($byCountry[$countryId])) {
                $byCountry[$countryId] = [
                    'iso_a2' => $iso2,
                    'iso_a3' => $iso3,
                    'name' => $name,
                    // Bei mehreren Untereinheiten je Land ist der Name des ersten Features
                    // irrefuehrend (Grossbritannien waere sonst "N. Ireland") - dann greift
                    // der Name des Gesamtstaats.
                    'sovereignt' => $props['SOVEREIGNT'] ?? $props['ADM0_TLC'] ?? null,
                    'polygons' => [],
                    'features' => 0,
                ];
            }

            $byCountry[$countryId]['polygons'] = array_merge($byCountry[$countryId]['polygons'], $polygons);
            $byCountry[$countryId]['features']++;
        }

        return [$byCountry, $unmatched];
    }

    /**
     * ISO-Zuordnung mit Fallback-Kette.
     *
     * Natural Earth traegt bei einigen Laendern "-99" statt des ISO-Codes ein -
     * betroffen sind unter anderem Frankreich und Norwegen. Fuer diese Faelle
     * liefert die "_EH"-Variante den korrekten Code.
     *
     * @param  array<string, mixed>  $props
     * @return ?array{0: int, 1: ?string, 2: ?string}
     */
    private function matchCountry(array $props, $countriesByIso2, $countriesByIso3): ?array
    {
        // Reihenfolge von speziell nach allgemein. SU_A3/GU_A3 bezeichnen die Untereinheit
        // (MTQ = Martinique, GUF = Franz.-Guayana), ADM0_A3 dagegen den Gesamtstaat (FRA).
        // Stuende ADM0_A3 weiter vorn, landeten alle Ueberseegebiete wieder beim Mutterland.
        // Danach erst ISO_A2 - fuer das Festland selbst (Frankreich: SU_A3=FXX unbekannt,
        // ISO_A2=FR trifft) und zuletzt ADM0_A3 fuer Teile ohne eigenen Code (Korsika).
        $candidates = [
            ['iso3', $props['SU_A3'] ?? null],
            ['iso3', $props['GU_A3'] ?? null],
            ['iso2', $props['ISO_A2'] ?? null],
            ['iso2', $props['ISO_A2_EH'] ?? null],
            ['iso3', $props['ISO_A3'] ?? null],
            ['iso3', $props['ISO_A3_EH'] ?? null],
            ['iso2', $props['WB_A2'] ?? null],
            ['iso3', $props['ADM0_A3'] ?? null],
            ['iso3', $props['WB_A3'] ?? null],
        ];

        // Die dokumentierten Codes unabhaengig vom Treffer bestimmen: sonst bliebe
        // iso_a2 leer, sobald schon SU_A3 passt (bei Deutschland z.B. direkt DEU).
        $iso2 = null;
        $iso3 = null;

        foreach ($candidates as [$type, $value]) {
            if (! $this->isUsableCode($value, $type)) {
                continue;
            }

            if ($type === 'iso2') {
                $iso2 ??= strtoupper($value);
            } else {
                $iso3 ??= strtoupper($value);
            }
        }

        foreach ($candidates as [$type, $value]) {
            if (! $this->isUsableCode($value, $type)) {
                continue;
            }

            $value = strtoupper($value);
            $map = $type === 'iso2' ? $countriesByIso2 : $countriesByIso3;

            if ($map->has($value)) {
                return [(int) $map->get($value), $iso2, $iso3];
            }
        }

        return null;
    }

    private function isUsableCode(mixed $value, string $type): bool
    {
        return is_string($value)
            && $value !== '-99'
            && strlen($value) === ($type === 'iso2' ? 2 : 3);
    }

    private function layer(): string
    {
        return strtolower((string) $this->option('layer'));
    }

    private function sourceLabel(): string
    {
        return $this->option('source') ?: 'natural_earth_10m_' . $this->layer();
    }

    /**
     * Polygon-Koordinaten aus einer Geometrie ziehen. Natural Earth liefert
     * teils Polygon, teils MultiPolygon - die Zielspalte ist immer MultiPolygon.
     *
     * @param  array<string, mixed>  $geometry
     * @return array<int, mixed>
     */
    private function extractPolygons(array $geometry): array
    {
        $type = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? null;

        if (! is_array($coordinates)) {
            return [];
        }

        return match ($type) {
            'Polygon' => [$coordinates],
            'MultiPolygon' => $coordinates,
            default => [],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $byCountry
     */
    private function writeBoundaries(array $byCountry): void
    {
        if (! $this->option('keep-existing')) {
            DB::table('country_boundaries')->delete();
        }

        $bar = $this->output->createProgressBar(count($byCountry));
        $bar->start();

        foreach ($byCountry as $countryId => $data) {
            if ($this->option('keep-existing')
                && DB::table('country_boundaries')->where('country_id', $countryId)->exists()) {
                $bar->advance();

                continue;
            }

            $bbox = $this->boundingBox($data['polygons']);

            $label = $data['features'] > 1
                ? ($data['sovereignt'] ?: $data['name'])
                : $data['name'];

            $multiPolygon = json_encode([
                'type' => 'MultiPolygon',
                'coordinates' => $data['polygons'],
            ]);

            // ST_GeomFromGeoJSON erzeugt die Geometrie in der Achsenreihenfolge des
            // Referenzsystems - GeoJSON ist lng/lat, MySQL nutzt bei 4326 lat/lng.
            // Die Umrechnung uebernimmt MySQL, siehe Test in countries:verify-boundaries.
            DB::statement(
                'insert into country_boundaries
                    (country_id, iso_a2, iso_a3, name, source, source_features, boundary, min_lat, max_lat, min_lng, max_lng, created_at, updated_at)
                 values (?, ?, ?, ?, ?, ?, ST_GeomFromGeoJSON(?, 1, 4326), ?, ?, ?, ?, ?, ?)',
                [
                    $countryId,
                    $data['iso_a2'],
                    $data['iso_a3'],
                    $label,
                    $this->sourceLabel(),
                    $data['features'],
                    $multiPolygon,
                    $bbox['min_lat'],
                    $bbox['max_lat'],
                    $bbox['min_lng'],
                    $bbox['max_lng'],
                    now(),
                    now(),
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Bounding-Box aus den Rohkoordinaten (GeoJSON-Reihenfolge: lng, lat).
     *
     * @param  array<int, mixed>  $polygons
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}
     */
    private function boundingBox(array $polygons): array
    {
        $minLat = 90.0;
        $maxLat = -90.0;
        $minLng = 180.0;
        $maxLng = -180.0;

        array_walk_recursive($polygons, function ($value, $key) use (&$minLat, &$maxLat, &$minLng, &$maxLng) {
            // array_walk_recursive laeuft ueber die Zahlen; die Position im
            // Koordinatenpaar steckt im Schluessel (0 = lng, 1 = lat).
            if ($key === 0) {
                $minLng = min($minLng, (float) $value);
                $maxLng = max($maxLng, (float) $value);
            } elseif ($key === 1) {
                $minLat = min($minLat, (float) $value);
                $maxLat = max($maxLat, (float) $value);
            }
        });

        return [
            'min_lat' => round($minLat, 6),
            'max_lat' => round($maxLat, 6),
            'min_lng' => round($minLng, 6),
            'max_lng' => round($maxLng, 6),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $byCountry
     * @param  array<int, string>  $unmatched
     */
    private function report(array $byCountry, array $unmatched): void
    {
        $total = Country::count();
        $imported = count($byCountry);

        $this->info("{$imported} Länder mit Grenzen importiert (von {$total} Ländern in der Datenbank).");

        $merged = array_filter($byCountry, fn ($d) => $d['features'] > 1);

        if (! empty($merged)) {
            $this->line('Zusammengefasste Mehrfach-Features: ' . count($merged)
                . ' (z.B. Außengebiete) - ' . collect($merged)->pluck('name')->take(5)->implode(', ') . ' ...');
        }

        if (! empty($unmatched)) {
            $this->newLine();
            $this->warn(count($unmatched) . ' Features ohne passendes Land in der Datenbank:');
            $this->line('  ' . implode(', ', array_slice($unmatched, 0, 30)));
        }

        $withoutBoundary = Country::query()
            ->whereNotIn('id', array_keys($byCountry))
            ->orderBy('iso_code')
            ->pluck('iso_code')
            ->filter()
            ->values();

        if ($withoutBoundary->isNotEmpty()) {
            $this->newLine();
            $this->warn($withoutBoundary->count() . ' Länder ohne Grenze (für diese greift weiterhin die Näherung über Flughafen/Stadt):');
            $this->line('  ' . $withoutBoundary->implode(', '));
        }
    }
}
