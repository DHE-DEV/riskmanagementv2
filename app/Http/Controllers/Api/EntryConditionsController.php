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

        // Build entry filters (documents)
        $entryFilters = [];

        // Additional info that applies to all entry filters
        $additionalInfo = [];
        if (!empty($filters['noEntryForm'])) {
            $additionalInfo = [
                'additional_info' => [
                    'list' => [
                        'registration' => [
                            'status' => 'not_required'
                        ]
                    ]
                ]
            ];
        }

        if (!empty($filters['passport'])) {
            $entryFilters[] = array_merge([
                'documents' => [
                    'passport' => [
                        'entry_allowed' => true
                    ]
                ]
            ], $additionalInfo);
        }

        if (!empty($filters['idCard'])) {
            $entryFilters[] = array_merge([
                'documents' => [
                    'id_card' => [
                        'entry_allowed' => true
                    ]
                ]
            ], $additionalInfo);
        }

        if (!empty($filters['tempPassport'])) {
            $entryFilters[] = array_merge([
                'documents' => [
                    'temporary_passport' => [
                        'entry_allowed' => true
                    ]
                ]
            ], $additionalInfo);
        }

        if (!empty($filters['tempIdCard'])) {
            $entryFilters[] = array_merge([
                'documents' => [
                    'temporary_id_card' => [
                        'entry_allowed' => true
                    ]
                ]
            ], $additionalInfo);
        }

        if (!empty($filters['childPassport'])) {
            $entryFilters[] = array_merge([
                'documents' => [
                    'child_passport' => [
                        'entry_allowed' => true
                    ]
                ]
            ], $additionalInfo);
        }

        if (!empty($entryFilters)) {
            $body['entry'] = [
                'operator' => 'OR',
                'filters' => $entryFilters
            ];
        }

        // Build visa filters
        $visaFilters = [];

        if (!empty($filters['visaFree'])) {
            $visaFilters[] = [
                'required' => [
                    'status' => 'not_required'
                ]
            ];
        }

        if (!empty($filters['eVisa'])) {
            $visaFilters[] = [
                'application' => [
                    'e_visa' => [
                        'available' => true
                    ]
                ],
                'required' => [
                    'status' => 'not_required'
                ]
            ];
        }

        if (!empty($filters['visaOnArrival'])) {
            $visaFilters[] = [
                'application' => [
                    'on_arrival' => [
                        'available' => true
                    ]
                ],
                'required' => [
                    'status' => 'not_required'
                ]
            ];
        }

        if (!empty($visaFilters)) {
            $body['visa'] = [
                'operator' => 'OR',
                'filters' => $visaFilters
            ];
        }

        // Build health filters (insurance requirement)
        if (!empty($filters['noInsurance'])) {
            $body['health'] = [
                'operator' => 'AND',
                'filters' => [
                    [
                        'medication' => [
                            'information' => [
                                'insurance' => [
                                    'status' => 'not_required'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        // Add language and sort
        $body['language'] = 'de';
        $body['sort_by'] = 'name';

        return $body;
    }
}
