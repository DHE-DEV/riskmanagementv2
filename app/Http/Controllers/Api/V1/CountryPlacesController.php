<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AirportResource;
use App\Http\Resources\Api\V1\CountryDetailResource;
use App\Http\Resources\Api\V1\PlaceResource;
use App\Http\Resources\Api\V1\SightResource;
use App\Models\City;
use App\Models\Region;
use App\Models\Sight;
use App\Support\AdminV2\SightInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Regionen und Staedte eines Landes fuer die Kunden-API.
 *
 *   GET /v1/countries/{code}/regions?lang=de            – alle Regionen mit Anzahl der Staedte
 *   GET /v1/countries/{code}/cities?lang=de&region=12   – alle Staedte, optional nur einer Region
 *   GET /v1/countries/{code}/sights?lang=de&region=12&city=5&category=museum&highlight=1
 *                                                       – Sehenswuerdigkeiten, Highlights zuerst
 *   GET /v1/sights/{id}?lang=de                          – eine Sehenswuerdigkeit
 *   GET /v1/places?keys=region:12,city:5,sight:3&lang=de – gemerkte Orte aufloesen (Name, Land, Lage)
 */
class CountryPlacesController extends Controller
{
    public function regions(Request $request, string $code): JsonResponse
    {
        $request->validate(['lang' => 'nullable|string|max:5']);

        $country = BaseDataController::findCountry($code);
        if (! $country) {
            return response()->json(['success' => false, 'message' => 'Country not found.'], 404);
        }

        $lang = CountryDetailResource::lang($request);
        $sortLang = $lang ?? 'de';

        $regions = Region::query()
            ->where('country_id', $country->id)
            ->withCount('cities')
            ->get()
            ->sortBy(fn (Region $region) => mb_strtolower($region->getName($sortLang)), SORT_NATURAL)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $regions->map(fn (Region $region) => PlaceResource::region($region, $lang))->all(),
            'meta' => ['total' => $regions->count()],
        ]);
    }

    public function cities(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'lang' => 'nullable|string|max:5',
            'region' => 'nullable|integer',
        ]);

        $country = BaseDataController::findCountry($code);
        if (! $country) {
            return response()->json(['success' => false, 'message' => 'Country not found.'], 404);
        }

        $lang = CountryDetailResource::lang($request);
        $sortLang = $lang ?? 'de';

        $query = City::query()->where('country_id', $country->id)->with('region');

        if ($request->filled('region')) {
            $query->where('region_id', $request->integer('region'));
        }

        $cities = $query->get()
            ->sortBy(fn (City $city) => mb_strtolower($city->getName($sortLang)), SORT_NATURAL)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $cities->map(fn (City $city) => PlaceResource::city($city, $lang))->all(),
            'meta' => ['total' => $cities->count()],
        ]);
    }

    public function sights(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'lang' => 'nullable|string|max:5',
            'region' => 'nullable|integer',
            'city' => 'nullable|integer',
            'category' => ['nullable', 'string', Rule::in(array_keys(SightInfo::CATEGORIES))],
            'highlight' => 'nullable|boolean',
        ]);

        $country = BaseDataController::findCountry($code);
        if (! $country) {
            return response()->json(['success' => false, 'message' => 'Country not found.'], 404);
        }

        $lang = CountryDetailResource::lang($request);
        $sortLang = $lang ?? 'de';

        $query = Sight::query()->where('country_id', $country->id)->with(['region', 'city']);

        if ($request->filled('region')) {
            $query->where('region_id', $request->integer('region'));
        }
        if ($request->filled('city')) {
            $query->where('city_id', $request->integer('city'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }
        if ($request->boolean('highlight')) {
            $query->where('is_highlight', true);
        }

        $sights = $query->get()
            ->sortBy([
                fn (Sight $a, Sight $b) => $b->is_highlight <=> $a->is_highlight,
                fn (Sight $a, Sight $b) => strnatcasecmp($a->getName($sortLang), $b->getName($sortLang)),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $sights->map(fn (Sight $sight) => SightResource::make($sight, $lang))->all(),
            'meta' => ['total' => $sights->count()],
        ]);
    }

    public function sight(Request $request, int $id): JsonResponse
    {
        $request->validate(['lang' => 'nullable|string|max:5']);

        $sight = Sight::query()->with(['region', 'city', 'country'])->find($id);
        if (! $sight || ! $sight->country) {
            return response()->json(['success' => false, 'message' => 'Sight not found.'], 404);
        }

        $lang = CountryDetailResource::lang($request);

        return response()->json([
            'success' => true,
            'data' => SightResource::make($sight, $lang) + ['country' => ['code' => $sight->country->iso_code, 'name' => \App\Http\Resources\Api\V1\AirportResource::text($sight->country->name_translations, $lang)]],
        ]);
    }

    /**
     * Orte zu Schluesseln "region:ID", "city:ID", "sight:ID" – fuer Apps, die
     * Markierungen (besucht, Wunschliste) nur als Schluessel speichern.
     * Unbekannte Schluessel fehlen in der Antwort.
     */
    public function places(Request $request): JsonResponse
    {
        $request->validate(['keys' => 'required|string|max:20000', 'lang' => 'nullable|string|max:5']);

        $lang = CountryDetailResource::lang($request) ?? 'de';
        $ids = ['region' => [], 'city' => [], 'sight' => []];

        foreach (array_slice(explode(',', $request->string('keys')->toString()), 0, 1000) as $key) {
            [$kind, $id] = array_pad(explode(':', trim($key), 2), 2, '');
            if (isset($ids[$kind]) && ctype_digit($id)) {
                $ids[$kind][] = (int) $id;
            }
        }

        $country = fn ($country) => $country ? ['code' => $country->iso_code, 'name' => AirportResource::text($country->name_translations, $lang)] : null;
        $point = fn ($lat, $lng) => $lat !== null && $lng !== null ? ['lat' => (float) $lat, 'lng' => (float) $lng] : null;
        $text = fn ($translations) => AirportResource::text($translations, $lang);
        $places = [];

        foreach (Region::query()->whereIn('id', $ids['region'])->with('country')->get() as $region) {
            $places[] = ['key' => 'region:'.$region->id, 'kind' => 'region', 'id' => $region->id, 'name' => $text($region->name_translations),
                'country' => $country($region->country), 'region' => null, 'city' => null, 'coordinates' => $point($region->lat, $region->lng), 'category' => null, 'category_name' => null];
        }

        foreach (City::query()->whereIn('id', $ids['city'])->with(['country', 'region'])->get() as $city) {
            $places[] = ['key' => 'city:'.$city->id, 'kind' => 'city', 'id' => $city->id, 'name' => $text($city->name_translations),
                'country' => $country($city->country), 'region' => $city->region ? ['id' => $city->region->id, 'name' => $text($city->region->name_translations)] : null, 'city' => null,
                'coordinates' => $point($city->lat, $city->lng), 'category' => null, 'category_name' => null];
        }

        foreach (Sight::query()->whereIn('id', $ids['sight'])->with(['country', 'region', 'city'])->get() as $sight) {
            $places[] = ['key' => 'sight:'.$sight->id, 'kind' => 'sight', 'id' => $sight->id, 'name' => $text($sight->name_translations),
                'country' => $country($sight->country), 'region' => $sight->region ? ['id' => $sight->region->id, 'name' => $text($sight->region->name_translations)] : null,
                'city' => $sight->city ? ['id' => $sight->city->id, 'name' => $text($sight->city->name_translations)] : null,
                'coordinates' => $point($sight->lat, $sight->lng), 'category' => $sight->category, 'category_name' => $text(SightInfo::categoryNames($sight->category))];
        }

        return response()->json(['success' => true, 'data' => $places, 'meta' => ['total' => count($places)]]);
    }
}
