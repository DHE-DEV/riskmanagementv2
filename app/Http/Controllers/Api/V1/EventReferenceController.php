<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\EventType;
use Illuminate\Http\JsonResponse;

class EventReferenceController extends Controller
{
    /**
     * List available event types.
     */
    public function eventTypes(): JsonResponse
    {
        $eventTypes = EventType::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($type) => [
                'code' => $type->code,
                'name' => $type->name,
                'color' => $type->color,
                'icon' => $type->icon,
            ]);

        return response()->json([
            'success' => true,
            'data' => $eventTypes,
        ]);
    }

    /**
     * List available countries with ISO codes.
     */
    public function countries(): JsonResponse
    {
        $countries = Country::orderBy('iso_code')
            ->get()
            ->map(fn ($country) => [
                'iso_code' => $country->iso_code,
                'iso3_code' => $country->iso3_code,
                'name' => $country->getName('de'),
                'name_en' => $country->getName('en'),
            ]);

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }
}
