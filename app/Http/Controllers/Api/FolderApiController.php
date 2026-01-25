<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder\Folder;
use App\Services\Folder\FolderProximityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FolderApiController extends Controller
{
    protected FolderProximityService $proximityService;

    public function __construct(FolderProximityService $proximityService)
    {
        $this->middleware('auth:customer');
        $this->proximityService = $proximityService;
    }

    /**
     * Get map locations for customer's folders.
     */
    public function getMapLocations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'folder_id' => 'nullable|uuid|exists:folder_folders,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location_types' => 'nullable|array',
            'location_types.*' => 'string|in:flight_departure,flight_arrival,hotel,cruise_embark,cruise_disembark,cruise_port,car_pickup,car_return',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $locations = $this->proximityService->getMapLocations(
                $request->input('folder_id'),
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('location_types')
            );

            return response()->json([
                'success' => true,
                'data' => $locations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch map locations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get travelers near a specific point.
     */
    public function getTravelersNearPoint(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'required|numeric|min:1|max:1000',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'nationalities' => 'nullable|array',
            'nationalities.*' => 'string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $travelers = $this->proximityService->findTravelersNearPoint(
                $request->input('lat'),
                $request->input('lng'),
                $request->input('radius_km'),
                $request->input('start_time'),
                $request->input('end_time'),
                $request->input('nationalities')
            );

            return response()->json([
                'success' => true,
                'data' => $travelers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to find travelers near point',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get travelers in a specific country.
     */
    public function getTravelersInCountry(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country_code' => 'required|string|size:2',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'nationalities' => 'nullable|array',
            'nationalities.*' => 'string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $travelers = $this->proximityService->findTravelersInCountry(
                $request->input('country_code'),
                $request->input('start_time'),
                $request->input('end_time'),
                $request->input('nationalities')
            );

            return response()->json([
                'success' => true,
                'data' => $travelers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to find travelers in country',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get affected folders within radius.
     */
    public function getAffectedFolders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'required|numeric|min:1|max:1000',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $folders = $this->proximityService->getAffectedFolders(
                $request->input('lat'),
                $request->input('lng'),
                $request->input('radius_km'),
                $request->input('start_time'),
                $request->input('end_time')
            );

            return response()->json([
                'success' => true,
                'data' => $folders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get affected folders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get traveler count statistics.
     */
    public function getTravelerStatistics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $statistics = $this->proximityService->getTravelerCountStatistics(
                $request->input('start_time'),
                $request->input('end_time')
            );

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get traveler statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get folder details.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $folder = Folder::with([
                'folderCustomer',
                'participants',
                'itineraries.flightServices.segments',
                'itineraries.hotelServices',
                'itineraries.shipServices',
                'itineraries.carRentalServices',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $folder,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Folder not found',
            ], 404);
        }
    }

    /**
     * List all folders for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Folder::with(['folderCustomer', 'participants'])
                ->orderByDesc('created_at');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('folder_number', 'like', "%{$search}%")
                        ->orWhere('folder_name', 'like', "%{$search}%");
                });
            }

            $folders = $query->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $folders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch folders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
