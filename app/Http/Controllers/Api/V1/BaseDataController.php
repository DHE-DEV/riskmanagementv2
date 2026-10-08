<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ContinentResource;
use App\Http\Resources\Api\V1\CountryDetailResource;
use App\Http\Resources\Api\V1\CountryResource;
use App\Http\Resources\Api\V1\EventCategoryResource;
use App\Http\Resources\Api\V1\RegionResource;
use App\Models\Continent;
use App\Models\Country;
use App\Models\EventType;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaseDataController extends Controller
{
    public function continents(): JsonResponse
    {
        $continents = Continent::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => ContinentResource::collection($continents),
        ]);
    }

    public function countries(Request $request): JsonResponse
    {
        $request->validate([
            'continent' => 'nullable|string|max:2',
        ]);

        $query = Country::with(['continent', 'heroImage'])->orderBy('iso_code');

        if ($request->filled('continent')) {
            $query->whereHas('continent', fn ($q) => $q->where('code', $request->input('continent')));
        }

        $countries = $query->get();

        return response()->json([
            'success' => true,
            'data' => CountryResource::collection($countries),
        ]);
    }

    /**
     * Alle Angaben eines Landes – per ISO-2- oder ISO-3-Code.
     * Optional ?lang=de|en|nl (nur diese Sprache) und ?year= fuer die Feiertage.
     */
    public function country(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'lang' => 'nullable|string|max:5',
            'year' => 'nullable|integer|min:1970|max:2100',
        ]);

        $country = self::findCountry($code);

        if (! $country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found.',
            ], 404);
        }

        $country->load(['continent', 'parentCountry', 'images', 'taxiApps', 'mobileOperators', 'holidays.regions']);

        return response()->json([
            'success' => true,
            'data' => (new CountryDetailResource($country))->toArray($request),
        ]);
    }

    /**
     * Land per ISO-2- oder ISO-3-Code, Gross-/Kleinschreibung egal.
     */
    public static function findCountry(string $code): ?Country
    {
        $code = strtoupper(trim($code));

        if (! preg_match('/^[A-Z]{2,3}$/', $code)) {
            return null;
        }

        return Country::query()
            ->where(strlen($code) === 2 ? 'iso_code' : 'iso3_code', $code)
            ->first();
    }

    public function regions(Request $request): JsonResponse
    {
        $request->validate([
            'country' => 'nullable|string|max:3',
        ]);

        $query = Region::with('country')->orderBy('country_id');

        if ($request->filled('country')) {
            $code = $request->input('country');
            $query->whereHas('country', fn ($q) => $q->where('iso_code', $code)->orWhere('iso3_code', $code));
        }

        $regions = $query->get();

        return response()->json([
            'success' => true,
            'data' => RegionResource::collection($regions),
        ]);
    }

    public function eventCategories(): JsonResponse
    {
        $categories = EventType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => EventCategoryResource::collection($categories),
        ]);
    }
}
