<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CountryDetailResource;
use App\Http\Resources\Api\V1\PlaceResource;
use App\Models\City;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Regionen und Staedte eines Landes fuer die Kunden-API.
 *
 *   GET /v1/countries/{code}/regions?lang=de            – alle Regionen mit Anzahl der Staedte
 *   GET /v1/countries/{code}/cities?lang=de&region=12   – alle Staedte, optional nur einer Region
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
}
