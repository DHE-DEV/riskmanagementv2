<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ContinentResource;
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

        $query = Country::with('continent')->orderBy('iso_code');

        if ($request->filled('continent')) {
            $query->whereHas('continent', fn ($q) => $q->where('code', $request->input('continent')));
        }

        $countries = $query->get();

        return response()->json([
            'success' => true,
            'data' => CountryResource::collection($countries),
        ]);
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
