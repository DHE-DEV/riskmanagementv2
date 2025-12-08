<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TripResource;
use App\Services\TravelDetail\DirectShareLinkService;
use App\Services\TravelDetail\TripImportService;
use App\Services\TravelDetail\PdsShareLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ShareLinkController extends Controller
{
    public function __construct(
        private DirectShareLinkService $directShareLinkService,
        private TripImportService $tripImportService,
        private PdsShareLinkService $pdsShareLinkService,
    ) {}

    /**
     * Generate a PDS share link directly from payload.
     *
     * POST /api/v1/share-links
     *
     * Query parameters:
     * - save_to_database=true: Also create the trip in database (only for itinerary format)
     *
     * Supports two payload formats:
     *
     * 1. Simple format (PDS Share-Link Schema):
     * {
     *   "trip": {
     *     "name": "Reise nach Spanien",
     *     "start_date": "2025-01-15",
     *     "end_date": "2025-01-22"
     *   },
     *   "destinations": [{"code": "ES", "type": "travel"}],
     *   "nationalities": ["DE", "AT"]
     * }
     *
     * 2. Itinerary format (Travel Detail Schema):
     * {
     *   "provider": {"id": "...", "sent_at": "..."},
     *   "trip": {
     *     "itinerary": [...],
     *     "travellers": [{"nationality": "DE", ...}]
     *   }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        // Check if feature is enabled
        if (!config('travel_detail.pds.share_link_enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'PDS share link generation is not enabled',
            ], 503);
        }

        try {
            $payload = $request->except(['save_to_database']);

            // Check save_to_database from query string OR body (more robust detection)
            $saveToDatabase = $request->query('save_to_database') === 'true'
                || $request->query('save_to_database') === '1'
                || $request->boolean('save_to_database', false);

            $hasItinerary = isset($payload['trip']['itinerary']) && is_array($payload['trip']['itinerary']);

            Log::channel(config('travel_detail.logging.channel', 'stack'))
                ->debug('ShareLinkController::store', [
                    'save_to_database' => $saveToDatabase,
                    'has_itinerary' => $hasItinerary,
                    'query_param' => $request->query('save_to_database'),
                ]);

            // If save to database is requested and we have itinerary format
            if ($saveToDatabase && $hasItinerary) {
                // Create trip in database
                $trip = $this->tripImportService->importTrip($payload);

                Log::channel(config('travel_detail.logging.channel', 'stack'))
                    ->info('Trip created for save_to_database', ['trip_id' => $trip->id]);

                // Generate share link from the created trip
                $shareLink = $this->pdsShareLinkService->generateShareLink($trip);

                // Refresh trip to get updated pds_share_url, pds_tid
                $trip->refresh();

                Log::channel(config('travel_detail.logging.channel', 'stack'))
                    ->info('Share link generation result', [
                        'trip_id' => $trip->id,
                        'share_link_created' => $shareLink !== null,
                        'trip_pds_share_url' => $trip->pds_share_url,
                        'trip_pds_tid' => $trip->pds_tid,
                    ]);

                $response = [
                    'success' => true,
                    'data' => [
                        'trip' => new TripResource($trip->load(['airLegs.segments', 'stays', 'travellers'])),
                        'share_link' => $shareLink ? [
                            'share_url' => $shareLink->share_url,
                            'tid' => $shareLink->tid,
                            'formatted_tid' => $shareLink->formatted_tid,
                        ] : null,
                    ],
                    'message' => $shareLink
                        ? 'Trip und Share-Link erfolgreich erstellt'
                        : 'Trip erstellt, aber Share-Link konnte nicht generiert werden',
                ];

                return response()->json($response, 201);
            }

            // If save_to_database requested but no itinerary - use simple format import
            if ($saveToDatabase && !$hasItinerary) {
                // Create trip from simple format
                $trip = $this->tripImportService->importFromSimpleFormat($payload);

                Log::channel(config('travel_detail.logging.channel', 'stack'))
                    ->info('Trip created from simple format for save_to_database', ['trip_id' => $trip->id]);

                // Generate share link from the created trip
                $shareLink = $this->pdsShareLinkService->generateShareLink($trip);

                // Refresh trip to get updated pds_share_url, pds_tid
                $trip->refresh();

                Log::channel(config('travel_detail.logging.channel', 'stack'))
                    ->info('Share link generation result (simple format)', [
                        'trip_id' => $trip->id,
                        'share_link_created' => $shareLink !== null,
                        'trip_pds_share_url' => $trip->pds_share_url,
                        'trip_pds_tid' => $trip->pds_tid,
                    ]);

                $response = [
                    'success' => true,
                    'data' => [
                        'trip' => new TripResource($trip->load(['travellers'])),
                        'share_link' => $shareLink ? [
                            'share_url' => $shareLink->share_url,
                            'tid' => $shareLink->tid,
                            'formatted_tid' => $shareLink->formatted_tid,
                        ] : null,
                    ],
                    'message' => $shareLink
                        ? 'Trip und Share-Link erfolgreich erstellt (Simple Format)'
                        : 'Trip erstellt, aber Share-Link konnte nicht generiert werden',
                ];

                return response()->json($response, 201);
            }

            // Just create share link without database storage
            $result = $this->directShareLinkService->generateFromPayload($payload);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Share-Link konnte nicht erstellt werden',
                    'details' => $result['details'] ?? null,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'share_url' => $result['share_url'],
                    'tid' => $result['tid'],
                    'formatted_tid' => $result['formatted_tid'],
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Share-Link konnte nicht erstellt werden',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
