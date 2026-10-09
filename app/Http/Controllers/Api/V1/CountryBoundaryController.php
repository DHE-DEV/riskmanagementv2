<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CountryDetailResource;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Landesgrenzen als GeoJSON fuer Karten in Apps.
 *
 *   GET /v1/countries/{code}/boundary   – ein Land als Feature
 *   GET /v1/boundaries?codes=EG,DE,...  – mehrere Laender als FeatureCollection
 *
 * Die Geometrie wird auf rund 5 km vereinfacht (wie in der Verwaltung) und je
 * Land einen Tag lang zwischengespeichert; die Grenzen aendern sich nur durch
 * den Import, dessen Stand im Cache-Schluessel steckt.
 */
class CountryBoundaryController extends Controller
{
    /** Vereinfachung in Grad – etwa 5 km, fuer Karten auf Landesebene ausreichend. */
    public const TOLERANCE = 0.05;

    /** Mehr Laender je Abruf waeren zu gross fuer eine Antwort. */
    public const MAX_CODES = 60;

    public function show(Request $request, string $code): JsonResponse
    {
        $request->validate(['lang' => 'nullable|string|max:5']);

        $country = BaseDataController::findCountry($code);

        if (! $country) {
            return response()->json(['success' => false, 'message' => 'Country not found.'], 404);
        }

        $feature = $this->feature($country, CountryDetailResource::lang($request));

        if (! $feature) {
            return response()->json(['success' => false, 'message' => 'No boundary available for this country.'], 404);
        }

        return response()->json(['success' => true, 'data' => $feature])
            ->header('Cache-Control', 'private, max-age=86400');
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'codes' => 'required|string|max:400',
            'lang' => 'nullable|string|max:5',
        ]);

        $codes = collect(explode(',', strtoupper((string) $request->query('codes'))))
            ->map(fn (string $code) => trim($code))
            ->filter(fn (string $code) => preg_match('/^[A-Z]{2,3}$/', $code) === 1)
            ->unique()
            ->values();

        if ($codes->isEmpty() || $codes->count() > self::MAX_CODES) {
            return response()->json([
                'success' => false,
                'message' => 'Please pass 1 to '.self::MAX_CODES.' ISO country codes in ?codes=, separated by commas.',
            ], 422);
        }

        $countries = Country::query()
            ->whereIn('iso_code', $codes)
            ->orWhereIn('iso3_code', $codes)
            ->orderBy('iso_code')
            ->get();

        $lang = CountryDetailResource::lang($request);
        $features = $countries->map(fn (Country $country) => $this->feature($country, $lang))->filter()->values()->all();

        return response()->json([
            'success' => true,
            'data' => ['type' => 'FeatureCollection', 'features' => $features],
            'meta' => ['requested' => $codes->count(), 'found' => count($features)],
        ])->header('Cache-Control', 'private, max-age=86400');
    }

    /**
     * GeoJSON-Feature eines Landes – aus dem Cache, sonst aus der Datenbank.
     *
     * @return array<string, mixed>|null
     */
    protected function feature(Country $country, ?string $lang): ?array
    {
        $version = (string) DB::table('country_boundaries')->max('updated_at');
        $key = 'api.boundary.'.$country->id.'.'.md5($version.self::TOLERANCE);

        $geometry = Cache::remember($key, now()->addDay(), function () use ($country) {
            // ST_Simplify gibt es nur fuer kartesische Geometrien – daher ohne SRID;
            // die Koordinaten bleiben dabei Laenge/Breite.
            $row = DB::selectOne(
                'select ST_AsGeoJSON(ST_Simplify(ST_SRID(boundary, 0), ?)) as geometry, min_lat, max_lat, min_lng, max_lng
                 from country_boundaries where country_id = ?',
                [self::TOLERANCE, $country->id],
            );

            if (! $row || ! $row->geometry) {
                return false;
            }

            return [
                'geometry' => json_decode((string) $row->geometry, true),
                'bbox' => $row->min_lng !== null ? [(float) $row->min_lng, (float) $row->min_lat, (float) $row->max_lng, (float) $row->max_lat] : null,
            ];
        });

        if (! $geometry) {
            return null;
        }

        $names = (array) ($country->name_translations ?? []);
        $name = $lang === null ? ($names ?: (object) []) : ($names[$lang] ?? $names['de'] ?? reset($names) ?: $country->iso_code);

        return [
            'type' => 'Feature',
            'properties' => [
                'iso_code' => $country->iso_code,
                'iso3_code' => $country->iso3_code,
                'name' => $name,
            ],
            'bbox' => $geometry['bbox'],
            'geometry' => $geometry['geometry'],
        ];
    }
}
