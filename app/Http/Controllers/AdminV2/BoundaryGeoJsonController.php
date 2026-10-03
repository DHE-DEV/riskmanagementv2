<?php

namespace App\Http\Controllers\AdminV2;

use App\Http\Controllers\Controller;
use App\Models\Continent;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Laendergrenzen als GeoJSON fuer die Karten im Stammdaten-Bereich – alle
 * Laender eines Kontinents oder ein einzelnes Land, vereinfacht auf eine
 * Aufloesung, die fuer die Uebersichtskarte reicht.
 */
class BoundaryGeoJsonController extends Controller
{
    /** Vereinfachung in Grad – etwa 5 km, fuer Karten auf Kontinent- und Landesebene ausreichend. */
    public const TOLERANCE = 0.05;

    public function continent(Continent $continent): JsonResponse
    {
        return $this->respond('continent', $continent->id, fn () => $this->features(
            Country::query()->where('continent_id', $continent->id)->pluck('id')->all(),
        ));
    }

    public function country(Country $country): JsonResponse
    {
        return $this->respond('country', $country->id, fn () => $this->features([$country->id]));
    }

    /**
     * @param  callable(): array<int, array<string, mixed>>  $features
     */
    protected function respond(string $kind, int $id, callable $features): JsonResponse
    {
        // Die Grenzen aendern sich nur durch den Import – der Stand der Tabelle macht den Schluessel.
        $version = (string) DB::table('country_boundaries')->max('updated_at');
        $key = 'adminv2.boundaries.'.$kind.'.'.$id.'.'.md5($version.self::TOLERANCE);

        $collection = Cache::remember($key, now()->addDay(), fn () => ['type' => 'FeatureCollection', 'features' => $features()]);

        return response()->json($collection)->header('Cache-Control', 'private, max-age=3600');
    }

    /**
     * @param  array<int, int>  $countryIds
     * @return array<int, array<string, mixed>>
     */
    protected function features(array $countryIds): array
    {
        if ($countryIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($countryIds), '?'));

        // ST_Simplify gibt es nur fuer kartesische Geometrien – daher ohne SRID;
        // die Koordinaten bleiben dabei Laenge/Breite.
        $rows = DB::select(
            "select c.id, c.iso_code, c.name_translations,
                    ST_AsGeoJSON(ST_Simplify(ST_SRID(b.boundary, 0), ?)) as geometry
             from country_boundaries b
             join countries c on c.id = b.country_id
             where b.country_id in ({$placeholders})",
            [self::TOLERANCE, ...$countryIds],
        );

        return array_map(function ($row) {
            $names = json_decode((string) $row->name_translations, true) ?: [];

            return [
                'type' => 'Feature',
                'properties' => [
                    'id' => (int) $row->id,
                    'iso' => $row->iso_code,
                    'name' => $names['de'] ?? $names['en'] ?? $row->iso_code,
                ],
                'geometry' => json_decode((string) $row->geometry, true),
            ];
        }, $rows);
    }
}
