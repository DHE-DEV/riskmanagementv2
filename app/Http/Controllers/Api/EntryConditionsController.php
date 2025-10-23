<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EntryConditionsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EntryConditionsController extends Controller
{
    /**
     * Get list of countries for nationality selection
     */
    public function getCountries()
    {
        try {
            $countries = \DB::table('countries')
                ->select('iso_code as code', 'name_translations')
                ->get()
                ->map(function ($country) {
                    $nameTranslations = json_decode($country->name_translations, true);
                    return [
                        'code' => $country->code,
                        'name' => $nameTranslations['de'] ?? $nameTranslations['en'] ?? $country->code,
                    ];
                })
                ->sortBy('name')
                ->values();

            return response()->json([
                'success' => true,
                'countries' => $countries
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching countries', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching countries',
                'countries' => []
            ], 500);
        }
    }

    /**
     * Search destinations based on entry condition filters
     */
    public function search(Request $request)
    {
        $loggingEnabled = config('app.entry_conditions_logging_enabled', env('ENTRY_CONDITIONS_LOGGING_ENABLED', false));
        $logData = [];

        try {
            $filters = $request->input('filters', []);
            $nationality = $request->input('nationality', 'DE');

            // Build request body for Passolution API
            $requestBody = $this->buildRequestBody($filters);

            // Prepare log data
            if ($loggingEnabled) {
                $logData = [
                    'filters' => $filters,
                    'nationality' => $nationality,
                    'request_body' => $requestBody,
                ];
            }

            // Get API credentials from config
            $apiUrl = config('services.passolution.api_url', env('PASSOLUTION_API_URL'));
            $apiKey = config('services.passolution.api_key', env('PDS_KEY'));

            if (!$apiKey) {
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'success' => false,
                        'error_message' => 'API key not configured',
                    ]));
                }

                return response()->json([
                    'success' => false,
                    'message' => 'API key not configured'
                ], 500);
            }

            // Build query parameters
            $queryParams = [
                'lang' => 'de',
                'nationalities' => $nationality
            ];

            // Log the request body for debugging
            Log::info('Passolution API Request Body', [
                'body' => $requestBody,
                'query' => $queryParams
            ]);

            // Make request to Passolution API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($apiUrl . '/destinations?' . http_build_query($queryParams), $requestBody);

            if ($response->successful()) {
                $data = $response->json();
                $resultsCount = count($data['destinations'] ?? []);

                // Log successful request
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'response_data' => $data,
                        'response_status' => $response->status(),
                        'results_count' => $resultsCount,
                        'success' => true,
                    ]));
                }

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

                // Log failed request
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'response_status' => $response->status(),
                        'success' => false,
                        'error_message' => 'API request failed: ' . $response->body(),
                    ]));
                }

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

            // Log exception
            if ($loggingEnabled && !empty($logData)) {
                EntryConditionsLog::create(array_merge($logData, [
                    'success' => false,
                    'error_message' => $e->getMessage(),
                ]));
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get entry conditions details for a specific country and nationality
     */
    public function getDetails(Request $request)
    {
        $loggingEnabled = config('app.entry_conditions_logging_enabled', env('ENTRY_CONDITIONS_LOGGING_ENABLED', false));
        $logData = [];

        try {
            $from = $request->input('from'); // Nationality code
            $to = $request->input('to'); // Destination country code

            // Prepare log data
            if ($loggingEnabled) {
                $logData = [
                    'nationality' => $from,
                    'filters' => [],
                    'request_body' => [
                        'endpoint' => 'getDetails',
                        'from' => $from,
                        'to' => $to,
                    ],
                ];
            }

            // Validate that both are provided
            if (empty($from) || empty($to)) {
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'success' => false,
                        'error_message' => 'Both from (nationality) and to (destination) are required',
                    ]));
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Both from (nationality) and to (destination) are required'
                ], 400);
            }

            // Get API credentials from config
            $apiUrl = config('services.passolution.api_url', env('PASSOLUTION_API_URL'));
            $apiKey = config('services.passolution.api_key', env('PDS_KEY'));

            if (!$apiKey) {
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'success' => false,
                        'error_message' => 'API key not configured',
                    ]));
                }

                return response()->json([
                    'success' => false,
                    'message' => 'API key not configured'
                ], 500);
            }

            // Build query parameters
            $queryParams = [
                'lang' => 'de',
                'countries' => $to,
                'nat' => $from,
            ];

            Log::info('Passolution Details API Request', [
                'query' => $queryParams
            ]);

            // Make request to Passolution API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'text/html, application/json',
            ])->get($apiUrl . '/content/overview/html?' . http_build_query($queryParams));

            if ($response->successful()) {
                $contentType = $response->header('Content-Type');
                $body = $response->body();

                Log::info('Passolution Details API Response', [
                    'content_type' => $contentType,
                    'body_preview' => substr($body, 0, 200)
                ]);

                // Check if response is JSON or HTML
                if (strpos($contentType, 'application/json') !== false) {
                    // Response is JSON, try to extract HTML content
                    $jsonData = $response->json();

                    // Check for Passolution API structure: records array
                    if (isset($jsonData['records']) && is_array($jsonData['records']) && count($jsonData['records']) > 0) {
                        // Combine all records content
                        $htmlContent = '';
                        foreach ($jsonData['records'] as $record) {
                            if (isset($record['content'])) {
                                $htmlContent .= $record['content'];
                            }
                        }

                        if (empty($htmlContent)) {
                            $htmlContent = '<p class="text-gray-500 text-sm">Keine Informationen verfügbar</p>';
                        }
                    } elseif (isset($jsonData['html'])) {
                        $htmlContent = $jsonData['html'];
                    } elseif (isset($jsonData['content'])) {
                        $htmlContent = $jsonData['content'];
                    } elseif (isset($jsonData['data'])) {
                        $htmlContent = is_string($jsonData['data']) ? $jsonData['data'] : json_encode($jsonData['data']);
                    } else {
                        // If no recognizable structure, format the JSON nicely
                        $htmlContent = '<pre class="bg-gray-50 p-4 rounded text-xs overflow-auto">' .
                                     htmlspecialchars(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) .
                                     '</pre>';
                    }
                } else {
                    // Response is HTML
                    $htmlContent = $body;
                }

                // Log successful request
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'response_data' => strpos($contentType, 'application/json') !== false ? $response->json() : ['html_content' => substr($body, 0, 500)],
                        'response_status' => $response->status(),
                        'results_count' => 1,
                        'success' => true,
                    ]));
                }

                return response()->json([
                    'success' => true,
                    'content' => $htmlContent,
                ]);
            } else {
                Log::error('Passolution Details API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Log failed request
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'response_status' => $response->status(),
                        'success' => false,
                        'error_message' => 'API request failed: ' . $response->body(),
                    ]));
                }

                return response()->json([
                    'success' => false,
                    'message' => 'API request failed',
                    'error' => $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Entry conditions details error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Log exception
            if ($loggingEnabled && !empty($logData)) {
                EntryConditionsLog::create(array_merge($logData, [
                    'success' => false,
                    'error_message' => $e->getMessage(),
                ]));
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get entry conditions content for selected countries and nationalities
     */
    public function getContent(Request $request)
    {
        $loggingEnabled = config('app.entry_conditions_logging_enabled', env('ENTRY_CONDITIONS_LOGGING_ENABLED', false));
        $logData = [];

        try {
            $countries = $request->input('countries', []); // Array of country codes
            $nationalities = $request->input('nationalities', []); // Array of nationality codes

            // Prepare log data
            if ($loggingEnabled) {
                $logData = [
                    'nationality' => is_array($nationalities) ? implode(',', $nationalities) : $nationalities,
                    'filters' => [],
                    'request_body' => [
                        'endpoint' => 'getContent',
                        'countries' => $countries,
                        'nationalities' => $nationalities,
                    ],
                ];
            }

            // Validate that both are provided
            if (empty($countries) || empty($nationalities)) {
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'success' => false,
                        'error_message' => 'Both countries and nationalities are required',
                    ]));
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Both countries and nationalities are required'
                ], 400);
            }

            // Get API credentials from config
            $apiUrl = config('services.passolution.api_url', env('PASSOLUTION_API_URL'));
            $apiKey = config('services.passolution.api_key', env('PDS_KEY'));

            if (!$apiKey) {
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'success' => false,
                        'error_message' => 'API key not configured',
                    ]));
                }

                return response()->json([
                    'success' => false,
                    'message' => 'API key not configured'
                ], 500);
            }

            // Build query parameters
            $queryParams = [
                'lang' => 'de',
                'countries' => is_array($countries) ? implode(',', $countries) : $countries,
                'nat' => is_array($nationalities) ? implode(',', $nationalities) : $nationalities,
            ];

            Log::info('Passolution Content API Request', [
                'query' => $queryParams
            ]);

            // Make request to Passolution API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'text/html, application/json',
            ])->get($apiUrl . '/content/overview/html?' . http_build_query($queryParams));

            if ($response->successful()) {
                $contentType = $response->header('Content-Type');
                $body = $response->body();

                Log::info('Passolution Content API Response', [
                    'content_type' => $contentType,
                    'body_preview' => substr($body, 0, 200)
                ]);

                // Check if response is JSON or HTML
                if (strpos($contentType, 'application/json') !== false) {
                    // Response is JSON, try to extract HTML content
                    $jsonData = $response->json();

                    // Check for Passolution API structure: records array
                    if (isset($jsonData['records']) && is_array($jsonData['records']) && count($jsonData['records']) > 0) {
                        // Combine all records content
                        $htmlContent = '';
                        foreach ($jsonData['records'] as $record) {
                            if (isset($record['content'])) {
                                $htmlContent .= $record['content'];
                                // Add PDF download button after each content block
                                $htmlContent .= '<div class="mt-4"><button class="pdf-download-btn px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-sm font-medium" onclick="downloadPDF()"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>PDF Download</button></div>';
                            }
                        }

                        if (empty($htmlContent)) {
                            $htmlContent = '<p class="text-gray-500 text-sm">Keine Informationen verfügbar</p>';
                        }
                    } elseif (isset($jsonData['html'])) {
                        $htmlContent = $jsonData['html'];
                    } elseif (isset($jsonData['content'])) {
                        $htmlContent = $jsonData['content'];
                    } elseif (isset($jsonData['data'])) {
                        $htmlContent = is_string($jsonData['data']) ? $jsonData['data'] : json_encode($jsonData['data']);
                    } else {
                        // If no recognizable structure, format the JSON nicely
                        $htmlContent = '<pre class="bg-gray-50 p-4 rounded text-xs overflow-auto">' .
                                     htmlspecialchars(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) .
                                     '</pre>';
                    }
                } else {
                    // Response is HTML
                    $htmlContent = $body;
                }

                // Log successful request
                if ($loggingEnabled) {
                    $recordsCount = 0;
                    if (strpos($contentType, 'application/json') !== false) {
                        $jsonData = $response->json();
                        $recordsCount = isset($jsonData['records']) ? count($jsonData['records']) : 0;
                    }

                    EntryConditionsLog::create(array_merge($logData, [
                        'response_data' => strpos($contentType, 'application/json') !== false ? $response->json() : ['html_content' => substr($body, 0, 500)],
                        'response_status' => $response->status(),
                        'results_count' => $recordsCount,
                        'success' => true,
                    ]));
                }

                return response()->json([
                    'success' => true,
                    'content' => $htmlContent,
                ]);
            } else {
                Log::error('Passolution Content API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Log failed request
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'response_status' => $response->status(),
                        'success' => false,
                        'error_message' => 'API request failed: ' . $response->body(),
                    ]));
                }

                return response()->json([
                    'success' => false,
                    'message' => 'API request failed',
                    'error' => $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Entry conditions content error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Log exception
            if ($loggingEnabled && !empty($logData)) {
                EntryConditionsLog::create(array_merge($logData, [
                    'success' => false,
                    'error_message' => $e->getMessage(),
                ]));
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching content',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get PDF content for selected countries and nationalities
     */
    public function getPDF(Request $request)
    {
        $loggingEnabled = config('app.entry_conditions_logging_enabled', env('ENTRY_CONDITIONS_LOGGING_ENABLED', false));
        $logData = [];

        try {
            $countries = $request->input('countries', ''); // Comma-separated country codes
            $nationalities = $request->input('nat', ''); // Comma-separated nationality codes
            $lang = $request->input('lang', 'de');

            // Prepare log data
            if ($loggingEnabled) {
                $logData = [
                    'nationality' => $nationalities,
                    'filters' => [],
                    'request_body' => [
                        'endpoint' => 'getPDF',
                        'countries' => $countries,
                        'nationalities' => $nationalities,
                        'lang' => $lang,
                    ],
                ];
            }

            // Validate that both are provided
            if (empty($countries) || empty($nationalities)) {
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'success' => false,
                        'error_message' => 'Both countries and nationalities are required',
                    ]));
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Both countries and nationalities are required'
                ], 400);
            }

            // Get API credentials from config
            $apiUrl = config('services.passolution.api_url', env('PASSOLUTION_API_URL'));
            $apiKey = config('services.passolution.api_key', env('PDS_KEY'));

            if (!$apiKey) {
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'success' => false,
                        'error_message' => 'API key not configured',
                    ]));
                }

                return response()->json([
                    'success' => false,
                    'message' => 'API key not configured'
                ], 500);
            }

            // Build query parameters
            $queryParams = [
                'lang' => $lang,
                'countries' => $countries,
                'nat' => $nationalities,
            ];

            Log::info('Passolution PDF API Request', [
                'query' => $queryParams
            ]);

            // Make request to Passolution API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'text/html, application/json',
            ])->get($apiUrl . '/content/all/pdf?' . http_build_query($queryParams));

            if ($response->successful()) {
                $contentType = $response->header('Content-Type');
                $body = $response->body();

                Log::info('Passolution PDF API Response', [
                    'content_type' => $contentType,
                    'body_preview' => substr($body, 0, 200)
                ]);

                // Check if response is JSON or HTML
                if (strpos($contentType, 'application/json') !== false) {
                    // Response is JSON, try to extract HTML content
                    $jsonData = $response->json();

                    // Check for Passolution API structure
                    if (isset($jsonData['records']) && is_array($jsonData['records']) && count($jsonData['records']) > 0) {
                        // Combine all records content
                        $htmlContent = '';
                        foreach ($jsonData['records'] as $record) {
                            if (isset($record['content'])) {
                                $htmlContent .= $record['content'];
                            }
                        }

                        if (empty($htmlContent)) {
                            $htmlContent = '<p class="text-gray-500 text-sm">Keine Informationen verfügbar</p>';
                        }
                    } elseif (isset($jsonData['html'])) {
                        $htmlContent = $jsonData['html'];
                    } elseif (isset($jsonData['content'])) {
                        $htmlContent = $jsonData['content'];
                    } elseif (isset($jsonData['data'])) {
                        $htmlContent = is_string($jsonData['data']) ? $jsonData['data'] : json_encode($jsonData['data']);
                    } else {
                        // If no recognizable structure, format the JSON nicely
                        $htmlContent = '<pre class="bg-gray-50 p-4 rounded text-xs overflow-auto">' .
                                     htmlspecialchars(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) .
                                     '</pre>';
                    }
                } else {
                    // Response is HTML
                    $htmlContent = $body;
                }

                // Log successful request
                if ($loggingEnabled) {
                    $recordsCount = 0;
                    if (strpos($contentType, 'application/json') !== false) {
                        $recordsCount = isset($jsonData['records']) ? count($jsonData['records']) : 0;
                    }

                    try {
                        EntryConditionsLog::create(array_merge($logData, [
                            'response_data' => ['pdf_content_length' => strlen($body)],
                            'response_status' => $response->status(),
                            'results_count' => $recordsCount,
                            'success' => true,
                        ]));
                    } catch (\Exception $logException) {
                        Log::warning('Failed to log PDF request', ['error' => $logException->getMessage()]);
                    }
                }

                // Prüfe ob die Antwort ein PDF ist
                if (strpos($contentType, 'application/pdf') !== false) {
                    // Gebe PDF direkt zurück
                    return response($body, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="einreisebestimmungen.pdf"',
                    ]);
                }

                // Bereinige HTML-Content von ungültigen UTF-8 Zeichen
                $cleanContent = mb_convert_encoding($htmlContent, 'UTF-8', 'UTF-8');

                return response()->json([
                    'success' => true,
                    'content' => $cleanContent,
                ]);
            } else {
                Log::error('Passolution PDF API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Log failed request
                if ($loggingEnabled) {
                    EntryConditionsLog::create(array_merge($logData, [
                        'response_status' => $response->status(),
                        'success' => false,
                        'error_message' => 'API request failed: ' . $response->body(),
                    ]));
                }

                return response()->json([
                    'success' => false,
                    'message' => 'API request failed',
                    'error' => $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Entry conditions PDF error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Log exception
            if ($loggingEnabled && !empty($logData)) {
                EntryConditionsLog::create(array_merge($logData, [
                    'success' => false,
                    'error_message' => $e->getMessage(),
                ]));
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching PDF content',
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
