<?php

namespace App\Services;

use App\Models\InfosystemEntry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PassolutionApiService
{
    private string $baseUrl;

    private string $internalBaseUrl;

    private ?string $apiKey;

    private ?string $apiSecret;

    private array $headers;

    public function __construct()
    {
        $this->baseUrl = config('services.passolution.api_url', 'https://api.passolution.eu/api/v2');
        // __internal-Endpunkte liegen auf dem internen Service (api-internal),
        // nicht auf der oeffentlichen api.passolution.eu.
        $this->internalBaseUrl = config('services.passolution.internal_api_url') ?: $this->baseUrl;
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
     * Stammdaten eines Kunden über den internen Account-Endpoint abrufen.
     * Nutzt den Account-Personal-Access-Token und sucht per E-Mail.
     * Die Login-E-Mail ist i. d. R. die User-E-Mail (WebUser), daher zuerst
     * `user_email`, ersatzweise `account_email`.
     * Gibt das `account`-Array zurück oder null (nicht gefunden / Fehler).
     */
    public function fetchAccountByEmail(string $email): ?array
    {
        // Interne Routen (__internal) brauchen einen Account-Personal-Access-Token,
        // nicht den allgemeinen API-Key. Fallback auf den API-Key, falls nicht gesetzt.
        $token = config('services.passolution.internal_token') ?: $this->apiKey;

        if (! $token) {
            Log::warning('Passolution API: Stammdaten-Abruf ohne Token nicht möglich');

            return null;
        }

        $url = "{$this->internalBaseUrl}/__internal/account/info";

        foreach (['user_email', 'account_email'] as $param) {
            $reqParams = [$param => $email];
            $t0 = microtime(true);
            try {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ])
                    ->timeout(15)
                    ->get($url, $reqParams);

                \App\Support\PdsDebug::record('GET', $url, $reqParams, $response->status(), $t0, $response->json());

                if ($response->successful() && $response->json('account')) {
                    return $response->json('account');
                }
            } catch (\Exception $e) {
                \App\Support\PdsDebug::record('GET', $url, $reqParams, null, $t0, null, $e->getMessage());
                Log::error('Passolution API: Stammdaten-Abruf fehlgeschlagen', [
                    'email' => $email,
                    'param' => $param,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Passolution API: kein Account zu E-Mail gefunden', ['email' => $email]);

        return null;
    }

    /**
     * Travel-Details-/Shared-Links eines Accounts über den internen Endpoint
     * abrufen (Service-Token, kein per-User-Token mit TRAVEL_DETAILS-Scope nötig).
     * Antwortstruktur entspricht GET /api/v2/travel-details (data[]).
     * Gibt das `data`-Array zurück (leer wenn keine), oder null bei Fehler.
     *
     * @param  string|null  $startDate  optional: nur Reisen mit end_date >= $startDate
     * @param  string|null  $endDate  optional: nur Reisen mit start_date <= $endDate
     */
    public function fetchTravelDetailsByEmail(string $email, ?string $startDate = null, ?string $endDate = null): ?array
    {
        $token = config('services.passolution.internal_token') ?: $this->apiKey;

        if (! $token) {
            Log::warning('Passolution API: Travel-Details-Abruf ohne Token nicht möglich');

            return null;
        }

        $query = [
            'per_page' => 1000,
            'sort_by' => 'start_date',
            'sort_order' => 'desc',
            '__with' => '__cruise-info',
        ];
        if ($endDate) {
            $query['start_date'] = ['<=' => $endDate];
        }
        if ($startDate) {
            $query['end_date'] = ['>=' => $startDate];
        }

        $url = "{$this->internalBaseUrl}/__internal/account/travel-details";

        foreach (['user_email', 'account_email'] as $param) {
            $reqParams = array_merge([$param => $email], $query);
            $t0 = microtime(true);
            try {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ])
                    ->timeout(20)
                    ->get($url, $reqParams);

                \App\Support\PdsDebug::record('GET', $url, $reqParams, $response->status(), $t0, $response->json());

                if ($response->successful()) {
                    return $response->json('data', []);
                }
            } catch (\Exception $e) {
                \App\Support\PdsDebug::record('GET', $url, $reqParams, null, $t0, null, $e->getMessage());
                Log::error('Passolution API: Travel-Details-Abruf fehlgeschlagen', [
                    'email' => $email,
                    'param' => $param,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Passolution API: Travel-Details nicht abrufbar', ['email' => $email]);

        return null;
    }

    /**
     * Account-uebergreifender Delta-Feed der Travel-Detail-Links ueber den
     * internen Endpoint /__internal/travel-details/changes (Service-Token).
     *
     * Liefert alle Links mit last_change_at > $since und end_date >= jetzt,
     * Keyset-paginiert ueber (last_change_at, id).
     *
     * @param  string|null  $since  Wasserstand (Y-m-d H:i:s); null = von Anfang
     * @param  array|null  $cursor  ['last_change_at' => ..., 'id' => ...] der letzten Seite
     * @return array{data: array, meta: array}|null null bei Fehler
     */
    public function fetchTravelDetailChanges(?string $since, ?array $cursor = null, int $perPage = 1000): ?array
    {
        $token = config('services.passolution.internal_token') ?: $this->apiKey;

        if (! $token) {
            Log::warning('Passolution API: Delta-Abruf ohne Token nicht möglich');

            return null;
        }

        $query = ['per_page' => $perPage];
        if ($since) {
            $query['since'] = $since;
        }
        if (! empty($cursor['last_change_at']) && isset($cursor['id'])) {
            $query['cursor_last_change_at'] = $cursor['last_change_at'];
            $query['cursor_id'] = $cursor['id'];
        }

        $url = "{$this->internalBaseUrl}/__internal/travel-details/changes";
        $t0 = microtime(true);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ])
                ->timeout(60)
                ->get($url, $query);

            \App\Support\PdsDebug::record('GET', $url, $query, $response->status(), $t0, $response->json());

            if ($response->successful()) {
                return [
                    'data' => $response->json('data', []),
                    'meta' => $response->json('meta', []),
                ];
            }

            Log::error('Passolution API: Delta-Abruf fehlgeschlagen', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            \App\Support\PdsDebug::record('GET', $url, $query, null, $t0, null, $e->getMessage());
            Log::error('Passolution API: Delta-Abruf Exception', ['message' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Abo-Typ eines Accounts ueber den internen Endpunkt abrufen (Service-Token).
     * Gibt 'standard' | 'premium' zurueck, oder null (kein Abo / nicht gefunden).
     */
    public function fetchSubscriptionTypeByEmail(string $email): ?string
    {
        $token = config('services.passolution.internal_token') ?: $this->apiKey;

        if (! $token) {
            return null;
        }

        $url = "{$this->internalBaseUrl}/__internal/account/subscription";

        foreach (['user_email', 'account_email'] as $param) {
            $reqParams = [$param => $email];
            $t0 = microtime(true);
            try {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ])
                    ->timeout(15)
                    ->get($url, $reqParams);

                \App\Support\PdsDebug::record('GET', $url, $reqParams, $response->status(), $t0, $response->json());

                if ($response->successful() && $response->json('type')) {
                    return $response->json('type');
                }
            } catch (\Exception $e) {
                \App\Support\PdsDebug::record('GET', $url, $reqParams, null, $t0, null, $e->getMessage());
                Log::error('Passolution API: Abo-Abruf fehlgeschlagen', [
                    'email' => $email,
                    'param' => $param,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Stammdaten eines Accounts ueber den internen Endpoint per account_id abrufen
     * (Service-Token). Gibt das `account`-Objekt zurueck, oder null bei Fehler /
     * nicht gefunden. Pendant zu fetchAccountByEmail() fuer den ID-basierten Sync.
     */
    public function fetchAccountById($accountId): ?array
    {
        $token = config('services.passolution.internal_token') ?: $this->apiKey;

        if (! $token || ! $accountId) {
            return null;
        }

        $url = "{$this->internalBaseUrl}/__internal/account/info";
        $reqParams = ['account_id' => $accountId];
        $t0 = microtime(true);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ])
                ->timeout(15)
                ->get($url, $reqParams);

            \App\Support\PdsDebug::record('GET', $url, $reqParams, $response->status(), $t0, $response->json());

            if ($response->successful() && $response->json('account')) {
                return $response->json('account');
            }
        } catch (\Exception $e) {
            \App\Support\PdsDebug::record('GET', $url, $reqParams, null, $t0, null, $e->getMessage());
            Log::error('Passolution API: Stammdaten-Abruf per account_id fehlgeschlagen', [
                'account_id' => $accountId,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Abo-Typ eines Accounts ueber den internen Endpunkt per account_id abrufen
     * (Service-Token). Gibt 'standard' | 'premium' zurueck, oder null.
     */
    public function fetchSubscriptionTypeById($accountId): ?string
    {
        $token = config('services.passolution.internal_token') ?: $this->apiKey;

        if (! $token || ! $accountId) {
            return null;
        }

        $url = "{$this->internalBaseUrl}/__internal/account/subscription";
        $reqParams = ['account_id' => $accountId];
        $t0 = microtime(true);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ])
                ->timeout(15)
                ->get($url, $reqParams);

            \App\Support\PdsDebug::record('GET', $url, $reqParams, $response->status(), $t0, $response->json());

            if ($response->successful() && $response->json('type')) {
                return $response->json('type');
            }
        } catch (\Exception $e) {
            \App\Support\PdsDebug::record('GET', $url, $reqParams, null, $t0, null, $e->getMessage());
            Log::error('Passolution API: Abo-Abruf per account_id fehlgeschlagen', [
                'account_id' => $accountId,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
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
                        'categories' => $entry['categories'] ?? null,
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
