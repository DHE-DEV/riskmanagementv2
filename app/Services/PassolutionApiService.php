<?php

namespace App\Services;

use App\Models\InfosystemEntry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PassolutionApiService
{
    private string $baseUrl;

    private ?string $apiKey;

    private ?string $apiSecret;

    private array $headers;

    public function __construct()
    {
        $this->baseUrl = config('services.passolution.api_url', 'https://api.passolution.eu/api/v2');
        $this->apiKey = config('services.passolution.api_key');
        $this->apiSecret = config('services.passolution.api_secret');

        $this->headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        // Use API Key as Bearer Token
        if ($this->apiKey) {
            $this->headers['Authorization'] = 'Bearer '.$this->apiKey;
        }
    }

    /**
     * Check if API credentials are configured
     */
    public function hasValidCredentials(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Fetch general infosystem data from Passolution API
     */
    public function fetchGeneralInfo(string $lang = 'de', int $page = 1): array
    {
        if (! $this->hasValidCredentials()) {
            Log::warning('Passolution API credentials not configured');

            return [
                'success' => false,
                'error' => 'API-Zugangsdaten nicht konfiguriert. Bitte setzen Sie PASSOLUTION_API_KEY in der .env Datei.',
                'data' => null,
            ];
        }

        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->get("{$this->baseUrl}/infosystem/general", [
                    'lang' => $lang,
                    'page' => $page,
                ]);

            if (! $response->successful()) {
                Log::error('Passolution API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $response->effectiveUri(),
                ]);

                return [
                    'success' => false,
                    'error' => 'API request failed with status: '.$response->status(),
                    'data' => null,
                ];
            }

            $data = $response->json();

            Log::info('Passolution API request successful', [
                'request_id' => $data['requestid'] ?? null,
                'response_time' => $data['responsetime'] ?? null,
                'total_items' => $data['result']['total'] ?? 0,
            ]);

            return [
                'success' => true,
                'data' => $data,
                'request_id' => $data['requestid'] ?? null,
                'response_time' => $data['responsetime'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Passolution API request exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'API request failed: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Store API data in database
     */
    public function storeApiData(array $apiResponse): int
    {
        if (! isset($apiResponse['result']['data'])) {
            return 0;
        }

        $stored = 0;
        $requestId = $apiResponse['requestid'] ?? null;
        $responseTime = $apiResponse['responsetime'] ?? null;

        foreach ($apiResponse['result']['data'] as $entry) {
            try {
                $infosystemEntry = InfosystemEntry::updateOrCreate(
                    ['api_id' => $entry['id']],
                    [
                        'position' => $entry['position'],
                        'appearance' => $entry['appearance'],
                        'country_code' => $entry['country'],
                        'country_names' => $entry['country_name'],
                        'lang' => $entry['lang'],
                        'language_content' => $entry['language_content'] ?? null,
                        'language_code' => $entry['language_code'] ?? null,
                        'tagtype' => $entry['tagtype'],
                        'tagtext' => $entry['tagtext'],
                        'tagdate' => $entry['tagdate'],
                        'header' => $entry['header'],
                        'content' => $entry['content'],
                        'archive' => (bool) $entry['archive'],
                        'active' => (bool) $entry['active'],
                        'api_created_at' => $entry['created_at'],
                        'request_id' => $requestId,
                        'response_time' => $responseTime,
                    ]
                );

                $stored++;

            } catch (\Exception $e) {
                Log::error('Failed to store infosystem entry', [
                    'api_id' => $entry['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Stored infosystem entries', [
            'count' => $stored,
            'request_id' => $requestId,
        ]);

        return $stored;
    }

    /**
     * Fetch and store general info data
     */
    public function fetchAndStore(string $lang = 'de', int $page = 1): array
    {
        $apiResponse = $this->fetchGeneralInfo($lang, $page);

        if (! $apiResponse['success']) {
            return $apiResponse;
        }

        $stored = $this->storeApiData($apiResponse['data']);

        return [
            'success' => true,
            'stored' => $stored,
            'total_available' => $apiResponse['data']['result']['total'] ?? 0,
            'current_page' => $apiResponse['data']['result']['current_page'] ?? 1,
            'last_page' => $apiResponse['data']['result']['last_page'] ?? 1,
            'request_id' => $apiResponse['request_id'],
            'response_time' => $apiResponse['response_time'],
        ];
    }

    /**
     * Fetch and store multiple pages of data
     */
    public function fetchAndStoreMultiple(string $lang = 'de', int $limit = 100): array
    {
        $totalStored = 0;
        $page = 1;
        $errors = [];
        $lastPage = null;

        Log::info('Starting multi-page fetch', ['limit' => $limit, 'lang' => $lang]);

        while ($totalStored < $limit) {
            $apiResponse = $this->fetchGeneralInfo($lang, $page);

            if (! $apiResponse['success']) {
                $errors[] = "Page {$page}: ".($apiResponse['error'] ?? 'Unknown error');
                Log::error('Failed to fetch page', ['page' => $page, 'error' => $apiResponse['error'] ?? 'Unknown']);
                break;
            }

            $data = $apiResponse['data'];
            $lastPage = $data['result']['last_page'] ?? 1;
            $currentPageItems = count($data['result']['data'] ?? []);

            if ($currentPageItems === 0) {
                Log::info('No more data available', ['page' => $page]);
                break;
            }

            // Store only the amount we need to reach the limit
            $itemsToStore = min($currentPageItems, $limit - $totalStored);
            if ($itemsToStore < $currentPageItems) {
                // Slice the data array to store only what we need
                $data['result']['data'] = array_slice($data['result']['data'], 0, $itemsToStore);
            }

            $stored = $this->storeApiData($data);
            $totalStored += $stored;

            Log::info('Fetched and stored page', [
                'page' => $page,
                'stored' => $stored,
                'total_stored' => $totalStored,
                'limit' => $limit,
            ]);

            // Check if we've reached the last page or our limit
            if ($page >= $lastPage || $totalStored >= $limit) {
                break;
            }

            $page++;

            // Add a small delay to avoid overwhelming the API
            usleep(500000); // 0.5 seconds
        }

        return [
            'success' => $totalStored > 0,
            'stored' => $totalStored,
            'pages_fetched' => $page,
            'last_page' => $lastPage,
            'errors' => $errors,
            'limit' => $limit,
        ];
    }

    /**
     * Get latest entries from database
     */
    public function getLatestEntries(int $limit = 10, string $lang = 'de')
    {
        return InfosystemEntry::active()
            ->notArchived()
            ->byLanguage($lang)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get entries by country
     */
    public function getEntriesByCountry(string $countryCode, string $lang = 'de', int $limit = 10)
    {
        return InfosystemEntry::active()
            ->notArchived()
            ->byLanguage($lang)
            ->byCountry($countryCode)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_entries' => InfosystemEntry::count(),
            'active_entries' => InfosystemEntry::active()->count(),
            'entries_today' => InfosystemEntry::where('tagdate', today())->count(),
            'entries_this_week' => InfosystemEntry::where('tagdate', '>=', now()->subDays(7))->count(),
            'countries_count' => InfosystemEntry::distinct('country_code')->count(),
            'languages_count' => InfosystemEntry::distinct('lang')->count(),
        ];
    }
}
