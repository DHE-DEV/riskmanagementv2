<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EntryConditionsController extends Controller
{
    /**
     * Search destinations based on entry condition filters
     */
    public function search(Request $request)
    {
        try {
            $filters = $request->input('filters', []);

            // Build request body for Passolution API
            $requestBody = $this->buildRequestBody($filters);

            // Get API credentials from config
            $apiUrl = config('services.passolution.api_url', env('PASSOLUTION_API_URL'));
            $apiKey = env('PDS_KEY');

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not configured'
                ], 500);
            }

            // Default nationality (can be made configurable later)
            $nationality = $request->input('nationality', 'DE');

            // Build query parameters
            $queryParams = [
                'lang' => 'de',
                'nationalities' => $nationality
            ];

            // Make request to Passolution API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($apiUrl . '/destinations?' . http_build_query($queryParams), $requestBody);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'destinations' => $data['destinations'] ?? [],
                    'meta' => $data['meta'] ?? null,
                ]);
            } else {
                Log::error('Passolution API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'API request failed',
                    'error' => $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Entry conditions search error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build request body for Passolution API based on filters
     */
    private function buildRequestBody(array $filters): array
    {
        $body = [];

        // Entry documents filter
        $documents = [];
        if (!empty($filters['passport'])) {
            $documents['passport'] = ['entry_allowed' => true];
        }
        if (!empty($filters['idCard'])) {
            $documents['id_card'] = ['entry_allowed' => true];
        }
        if (!empty($filters['tempPassport'])) {
            $documents['temp_passport'] = ['entry_allowed' => true];
        }
        if (!empty($filters['tempIdCard'])) {
            $documents['temp_id_card'] = ['entry_allowed' => true];
        }
        if (!empty($filters['childPassport'])) {
            $documents['child_passport'] = ['entry_allowed' => true];
        }

        if (!empty($documents)) {
            $body['entry']['documents'] = $documents;
        }

        // Visa filters
        if (!empty($filters['visaFree'])) {
            $body['visa']['required']['status'] = 'not_required';
        }
        if (!empty($filters['eVisa'])) {
            $body['visa']['application']['e_visa']['available'] = true;
        }
        if (!empty($filters['visaOnArrival'])) {
            $body['visa']['application']['on_arrival']['available'] = true;
        }

        // Additional filters
        if (!empty($filters['noEntryForm'])) {
            $body['entry']['additional_info']['list']['registration']['status'] = 'not_required';
        }

        // Note: Insurance requirement filter not directly available in API
        // We'll need to filter this on the client side or request all and filter

        return $body;
    }
}
