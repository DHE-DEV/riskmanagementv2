<?php

namespace App\Http\Controllers;

use App\Services\GeolocationService;
use App\Services\ReverseGeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeolocationController extends Controller
{
    public function __construct(
        private GeolocationService $geolocationService,
        private ReverseGeocodingService $reverseGeocodingService
    ) {}

    /**
     * Finde geografische Informationen basierend auf Koordinaten
     */
    public function findLocation(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'method' => 'nullable|string|in:database,nominatim,google',
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $method = $request->get('method', 'database');

        try {
            switch ($method) {
                case 'nominatim':
                    $result = $this->reverseGeocodingService->getLocationFromCoordinates($lat, $lng);
                    break;
                case 'google':
                    $result = $this->reverseGeocodingService->getLocationFromGoogle($lat, $lng);
                    break;
                default:
                    $result = $this->geolocationService->findLocationInfo($lat, $lng);
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'method' => $method,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der geografischen Informationen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finde die nächstgelegene Stadt
     */
    public function findNearestCity(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'max_distance_km' => 'nullable|numeric|min:1|max:1000',
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $maxDistance = (int) $request->get('max_distance_km', 50);

        try {
            $city = $this->geolocationService->findNearestCity($lat, $lng, $maxDistance);

            if (! $city) {
                return response()->json([
                    'success' => false,
                    'message' => 'Keine Stadt innerhalb der angegebenen Entfernung gefunden',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'city' => [
                        'id' => $city->id,
                        'name' => $city->getName(),
                        'is_capital' => $city->is_capital,
                        'country' => $city->country ? [
                            'id' => $city->country->id,
                            'name' => $city->country->getName(),
                            'iso_code' => $city->country->iso_code,
                        ] : null,
                        'continent' => $city->country?->continent ? [
                            'id' => $city->country->continent->id,
                            'name' => $city->country->continent->getName(),
                            'code' => $city->country->continent->code,
                        ] : null,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Suchen der nächstgelegenen Stadt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finde Städte innerhalb eines Radius
     */
    public function findCitiesInRadius(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'required|numeric|min:1|max:1000',
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $radius = (int) $request->radius_km;

        try {
            $cities = $this->geolocationService->findCitiesInRadius($lat, $lng, $radius);

            return response()->json([
                'success' => true,
                'data' => [
                    'cities' => $cities->map(function ($city) {
                        return [
                            'id' => $city->id,
                            'name' => $city->getName(),
                            'distance_km' => $city->distance_km,
                            'is_capital' => $city->is_capital,
                            'country' => $city->country ? [
                                'id' => $city->country->id,
                                'name' => $city->country->getName(),
                                'iso_code' => $city->country->iso_code,
                            ] : null,
                        ];
                    }),
                    'total_count' => $cities->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Suchen der Städte im Radius',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Teste die Geolocation-Services
     */
    public function test(): JsonResponse
    {
        // Test-Koordinaten für Berlin
        $testLat = 52.5200;
        $testLng = 13.4050;

        try {
            $databaseResult = $this->geolocationService->findLocationInfo($testLat, $testLng);
            $nominatimResult = $this->reverseGeocodingService->getLocationFromCoordinates($testLat, $testLng);

            return response()->json([
                'success' => true,
                'test_coordinates' => [
                    'lat' => $testLat,
                    'lng' => $testLng,
                    'description' => 'Berlin, Deutschland',
                ],
                'results' => [
                    'database' => $databaseResult,
                    'nominatim' => $nominatimResult,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Testen der Geolocation-Services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
