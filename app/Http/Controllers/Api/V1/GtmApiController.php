<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GtmCountryResource;
use App\Http\Resources\Api\V1\GtmEventResource;
use App\Models\Continent;
use App\Models\EventType;
use App\Models\Region;
use App\Services\GtmEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GtmApiController extends Controller
{
    public function __construct(
        private GtmEventService $eventService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'priority' => 'nullable|string|in:high,medium,low,info',
            'country' => 'nullable|string|max:3',
            'event_type' => 'nullable|string|max:50',
            'region' => 'nullable|integer',
            'source' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $perPage = $request->integer('per_page', 25);

        $events = $this->eventService->getActiveEvents(
            priority: $request->input('priority'),
            countryCode: $request->input('country'),
            eventTypeCode: $request->input('event_type'),
            regionId: $request->integer('region') ?: null,
            source: $request->input('source'),
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
        );

        // Manual pagination on collection
        $page = $request->integer('page', 1);
        $total = $events->count();
        $lastPage = (int) ceil($total / $perPage);
        $paginated = $events->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => GtmEventResource::collection($paginated),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max($lastPage, 1),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $events = $this->eventService->getActiveEvents();
        $event = $events->firstWhere('uuid', $id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new GtmEventResource($event),
        ]);
    }

    public function countries(): JsonResponse
    {
        $countries = $this->eventService->getCountriesWithEventCounts();

        return response()->json([
            'success' => true,
            'data' => GtmCountryResource::collection($countries),
        ]);
    }

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

        $regions = $query->get()->map(fn ($region) => [
            'id' => $region->id,
            'name_de' => $region->getName('de'),
            'name_en' => $region->getName('en'),
            'code' => $region->code,
            'country_iso_code' => $region->country?->iso_code,
            'country_name_de' => $region->country?->getName('de'),
            'lat' => $region->lat ? (float) $region->lat : null,
            'lng' => $region->lng ? (float) $region->lng : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $regions,
        ]);
    }

    public function continents(): JsonResponse
    {
        $continents = Continent::ordered()
            ->get()
            ->map(fn ($continent) => [
                'code' => $continent->code,
                'name_de' => $continent->getName('de'),
                'name_en' => $continent->getName('en'),
                'lat' => $continent->lat ? (float) $continent->lat : null,
                'lng' => $continent->lng ? (float) $continent->lng : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => $continents,
        ]);
    }
}
