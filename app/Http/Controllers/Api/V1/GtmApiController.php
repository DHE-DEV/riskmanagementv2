<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GtmCountryResource;
use App\Http\Resources\Api\V1\GtmEventResource;
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
            'risk_level' => 'nullable|string|in:high,medium,low,info',
            'country' => 'nullable|string|max:3',
            'event_category' => 'nullable|string|max:50',
            'region' => 'nullable|integer',
            'source' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $perPage = $request->integer('per_page', 25);

        $events = $this->eventService->getActiveEvents(
            priority: $request->input('risk_level'),
            countryCode: $request->input('country'),
            eventTypeCode: $request->input('event_category'),
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

    public function countriesWithEvents(): JsonResponse
    {
        $countries = $this->eventService->getCountriesWithEventCounts();

        return response()->json([
            'success' => true,
            'data' => GtmCountryResource::collection($countries),
        ]);
    }
}
