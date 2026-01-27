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
            'priority' => 'nullable|string|in:high,medium,low,info',
            'country' => 'nullable|string|max:3',
            'event_type' => 'nullable|string|max:50',
            'region' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $perPage = $request->integer('per_page', 25);

        $events = $this->eventService->getActiveEvents(
            priority: $request->input('priority'),
            countryCode: $request->input('country'),
            eventTypeCode: $request->input('event_type'),
            regionId: $request->integer('region') ?: null,
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

    public function show(int $id): JsonResponse
    {
        $events = $this->eventService->getActiveEvents();
        $event = $events->firstWhere('id', $id);

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
}
