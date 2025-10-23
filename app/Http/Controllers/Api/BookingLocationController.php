<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookingLocationController extends Controller
{
    /**
     * Get all booking locations (no filters)
     */
    public function index(Request $request)
    {
        try {
            $query = BookingLocation::query();

            // Optional: Filter by type
            if ($request->has('type') && in_array($request->type, ['online', 'stationary', 'any'])) {
                if ($request->type !== 'any') {
                    $query->where('type', $request->type);
                }
            }

            $locations = $query->get();

            return response()->json([
                'success' => true,
                'count' => $locations->count(),
                'data' => $locations,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching booking locations: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Buchungsstandorte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search booking locations by postal code and radius
     */
    public function search(Request $request)
    {
        try {
            $validated = $request->validate([
                'postal_code' => 'required|string|size:5',
                'radius' => 'required|numeric|in:5,10,20',
                'booking_type' => 'nullable|string|in:stationary,online,any',
            ]);

            $postalCode = $validated['postal_code'];
            $radius = (float) $validated['radius'];
            $bookingType = $validated['booking_type'] ?? 'any';

            // Get coordinates for postal code using Nominatim API
            $coordinates = $this->getCoordinatesFromPostalCode($postalCode);

            if (!$coordinates) {
                return response()->json([
                    'success' => false,
                    'message' => 'Postleitzahl nicht gefunden',
                ], 404);
            }

            $query = BookingLocation::query();

            // Filter by booking type
            if ($bookingType === 'online') {
                $query->online();
            } elseif ($bookingType === 'stationary') {
                // Only search stationary locations within radius
                $query->stationary()
                    ->withinRadius($coordinates['lat'], $coordinates['lng'], $radius);
            } else {
                // 'any' - get online + stationary within radius
                $stationaryInRadius = BookingLocation::stationary()
                    ->withinRadius($coordinates['lat'], $coordinates['lng'], $radius)
                    ->get();

                $onlineLocations = BookingLocation::online()->get();

                $combined = $stationaryInRadius->concat($onlineLocations);

                return response()->json([
                    'success' => true,
                    'postal_code' => $postalCode,
                    'radius_km' => $radius,
                    'booking_type' => $bookingType,
                    'center' => $coordinates,
                    'count' => $combined->count(),
                    'stationary_count' => $stationaryInRadius->count(),
                    'online_count' => $onlineLocations->count(),
                    'data' => $combined,
                ]);
            }

            $locations = $query->get();

            return response()->json([
                'success' => true,
                'postal_code' => $postalCode,
                'radius_km' => $radius,
                'booking_type' => $bookingType,
                'center' => $coordinates,
                'count' => $locations->count(),
                'data' => $locations,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error searching booking locations: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Fehler bei der Suche',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get coordinates from postal code using Nominatim API
     */
    private function getCoordinatesFromPostalCode(string $postalCode): ?array
    {
        try {
            $response = Http::timeout(10)->get('https://nominatim.openstreetmap.org/search', [
                'postalcode' => $postalCode,
                'country' => 'Germany',
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->successful() && count($response->json()) > 0) {
                $result = $response->json()[0];

                return [
                    'lat' => (float) $result['lat'],
                    'lng' => (float) $result['lon'],
                    'display_name' => $result['display_name'] ?? null,
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error geocoding postal code: ' . $e->getMessage());
            return null;
        }
    }
}
