<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTripRequest;
use App\Http\Resources\Api\V1\TripResource;
use App\Models\TravelDetail\TdTrip;
use App\Services\TravelDetail\TripImportService;
use App\Services\TravelDetail\PdsShareLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function __construct(
        private TripImportService $importService,
    ) {}

    /**
     * Store or update a trip.
     *
     * POST /api/v1/trips
     */
    public function store(StoreTripRequest $request): JsonResponse
    {
        // Check if feature is enabled
        if (!config('travel_detail.enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Travel Detail module is not enabled',
            ], 503);
        }

        try {
            $trip = $this->importService->importTrip($request->validated());

            $isNewTrip = $trip->wasRecentlyCreated;

            return response()->json([
                'success' => true,
                'message' => $isNewTrip ? 'Trip created successfully' : 'Trip updated successfully',
                'data' => new TripResource($trip->load(['airLegs.segments', 'stays', 'transfers'])),
            ], $isNewTrip ? 201 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import trip',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display a trip.
     *
     * GET /api/v1/trips/{trip}
     */
    public function show(TdTrip $trip): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new TripResource($trip->load(['airLegs.segments', 'stays', 'transfers', 'tripLocations'])),
        ]);
    }

    /**
     * List trips with filtering.
     *
     * GET /api/v1/trips
     */
    public function index(Request $request): JsonResponse
    {
        $query = TdTrip::query();

        // Filter by provider
        if ($request->has('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_from')) {
            $query->where('computed_start_at', '>=', $request->start_from);
        }
        if ($request->has('start_to')) {
            $query->where('computed_start_at', '<=', $request->start_to);
        }

        // Filter currently traveling
        if ($request->boolean('currently_traveling')) {
            $query->currentlyTraveling();
        }

        // Filter upcoming
        if ($request->boolean('upcoming')) {
            $query->upcoming();
        }

        // Exclude archived
        if (!$request->boolean('include_archived')) {
            $query->notArchived();
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'computed_start_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $trips = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => TripResource::collection($trips),
            'meta' => [
                'current_page' => $trips->currentPage(),
                'per_page' => $trips->perPage(),
                'total' => $trips->total(),
                'last_page' => $trips->lastPage(),
            ],
        ]);
    }

    /**
     * Generate a PDS share link for a trip.
     *
     * POST /api/v1/trips/{trip}/share-link
     */
    public function generateShareLink(TdTrip $trip, PdsShareLinkService $shareService): JsonResponse
    {
        if (!config('travel_detail.pds.share_link_enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'PDS share link generation is not enabled',
            ], 503);
        }

        try {
            $shareLink = $shareService->generateShareLink($trip);

            if (!$shareLink) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate share link',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'share_url' => $shareLink->share_url,
                    'tid' => $shareLink->tid,
                    'formatted_tid' => $shareLink->formatted_tid,
                    'expires_at' => $shareLink->expires_at?->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate share link',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a trip.
     *
     * DELETE /api/v1/trips/{trip}
     */
    public function destroy(TdTrip $trip): JsonResponse
    {
        $trip->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trip deleted successfully',
        ]);
    }

    /**
     * Get trip import summary/statistics.
     *
     * GET /api/v1/trips/{trip}/summary
     */
    public function summary(TdTrip $trip): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->importService->getImportSummary($trip),
        ]);
    }
}
