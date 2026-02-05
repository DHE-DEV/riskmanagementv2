<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisumPointService
{
    protected string $baseUrl;
    protected string $userName;
    protected string $accessToken;
    protected string $language = 'de'; // Default language
    protected int $sessionTimeout = 300; // 5 minutes
    protected array $debugLog = [];

    public function __construct()
    {
        $this->baseUrl = config('services.visumpoint.base_url') ?? 'https://www.visumpoint.de/REST/ComplianceCheck/API.php';
        $this->userName = config('services.visumpoint.user_name') ?? '';
        $this->accessToken = config('services.visumpoint.access_token') ?? '';
    }

    /**
     * Get or create a session ID
     */
    protected function getSessionId(): ?string
    {
        $cacheKey = 'visumpoint_session_' . md5($this->userName);

        return Cache::remember($cacheKey, $this->sessionTimeout, function () {
            return $this->beginSession();
        });
    }

    /**
     * Clear the cached session
     */
    public function clearSession(): void
    {
        $cacheKey = 'visumpoint_session_' . md5($this->userName);
        Cache::forget($cacheKey);
    }

    /**
     * Begin a new session with the API
     */
    protected function beginSession(): ?string
    {
        $requestData = [
            'Function' => 'BeginSession',
            'AccessToken' => $this->accessToken,
            'UserName' => $this->userName,
        ];

        try {
            $response = Http::timeout(30)->post($this->baseUrl, $requestData);

            if ($response->successful()) {
                $data = $response->json();

                $this->addDebugLog('BeginSession', $requestData, $data);

                if (($data['Result'] ?? '') === 'OK' && isset($data['SID'])) {
                    return $data['SID'];
                }

                Log::warning('VisumPoint BeginSession failed', [
                    'response' => $data,
                ]);
            } else {
                $this->addDebugLog('BeginSession', $requestData, $response->json(), 'HTTP ' . $response->status());
            }

            Log::error('VisumPoint BeginSession request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->addDebugLog('BeginSession', $requestData, null, $e->getMessage());

            Log::error('VisumPoint BeginSession exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Make an API call with automatic session handling
     */
    protected function apiCall(string $function, array $params = [], bool $retry = true): array
    {
        $sid = $this->getSessionId();

        if (!$sid) {
            return [
                'success' => false,
                'error' => 'Could not establish session',
                'debugLog' => $this->debugLog,
            ];
        }

        $requestData = array_merge([
            'Function' => $function,
            'SID' => $sid,
        ], $params);

        try {
            $response = Http::timeout(30)->post($this->baseUrl, $requestData);

            if ($response->successful()) {
                $data = $response->json();

                $this->addDebugLog($function, $requestData, $data);

                // Check for session errors and retry
                if (($data['Result'] ?? '') === 'Error') {
                    $errorId = $data['ErrorDetails']['ErrorID'] ?? null;

                    if ($errorId === 'ESessionInvalid' && $retry) {
                        $this->clearSession();
                        return $this->apiCall($function, $params, false);
                    }

                    return [
                        'success' => false,
                        'error' => $data['ErrorDetails']['ErrorMessage'] ?? 'Unknown error',
                        'errorId' => $errorId,
                        'details' => $data['ErrorDetails'] ?? null,
                    ];
                }

                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            $this->addDebugLog($function, $requestData, $response->json(), 'HTTP ' . $response->status());

            return [
                'success' => false,
                'error' => 'Request failed with status ' . $response->status(),
            ];
        } catch (\Exception $e) {
            $this->addDebugLog($function, $requestData, null, $e->getMessage());

            Log::error('VisumPoint API call exception', [
                'function' => $function,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available visa types for given parameters
     */
    public function getVisaTypes(string $destinationCountry, string $nationality, string $countryOfResidence): array
    {
        return $this->apiCall('GetVisaTypes', [
            'Language' => $this->language,
            'DestinationCountry' => $destinationCountry,
            'Nationality' => $nationality,
            'CountryOfResidence' => $countryOfResidence,
        ]);
    }

    /**
     * Get details for a specific visa type
     */
    public function getVisaTypeDetails(string $visaTypeId, string $format = 'markdown'): array
    {
        return $this->apiCall('GetVisaTypeDetails', [
            'Language' => $this->language,
            'VisaTypeID' => $visaTypeId,
            'Format' => $format,
        ]);
    }

    /**
     * Get visa requirements for given parameters
     */
    public function getVisaRequirements(string $destinationCountry, string $nationality, string $countryOfResidence, ?string $visaTypeId = null, string $format = 'markdown'): array
    {
        $params = [
            'Language' => $this->language,
            'DestinationCountry' => $destinationCountry,
            'Nationality' => $nationality,
            'CountryOfResidence' => $countryOfResidence,
            'Format' => $format,
        ];

        if ($visaTypeId) {
            $params['VisaTypeID'] = $visaTypeId;
        }

        return $this->apiCall('GetVisaRequirements', $params);
    }

    /**
     * Get details for a specific visa requirement
     */
    public function getVisaRequirementDetails(string $visaRequirementId, string $format = 'markdown'): array
    {
        return $this->apiCall('GetVisaRequirementDetails', [
            'Language' => $this->language,
            'VisaRequirementID' => $visaRequirementId,
            'Format' => $format,
        ]);
    }

    /**
     * Complete visa check: Get types and their details
     */
    public function checkVisa(string $destinationCountry, string $nationality, string $countryOfResidence): array
    {
        // Step 1: Get available visa types
        $typesResult = $this->getVisaTypes($destinationCountry, $nationality, $countryOfResidence);

        if (!$typesResult['success']) {
            return $typesResult;
        }

        $visaTypeIds = $typesResult['data']['VisaTypeIDs'] ?? [];

        if (empty($visaTypeIds)) {
            return [
                'success' => true,
                'data' => [
                    'visaRequired' => false,
                    'message' => 'Kein Visum erforderlich für diese Kombination.',
                    'visaTypes' => [],
                ],
            ];
        }

        // Step 2: Get details for each visa type AND their requirements
        $visaTypes = [];
        foreach ($visaTypeIds as $typeId) {
            $detailsResult = $this->getVisaTypeDetails($typeId);

            if ($detailsResult['success']) {
                $visaTypeData = [
                    'id' => $typeId,
                    'details' => $detailsResult['data']['VisaTypeDetails'] ?? 'Keine Details verfügbar',
                    'requirements' => [],
                ];

                // Get requirements for THIS visa type
                $reqResult = $this->getVisaRequirements(
                    $destinationCountry,
                    $nationality,
                    $countryOfResidence,
                    $typeId
                );

                if ($reqResult['success']) {
                    $reqIds = $reqResult['data']['VisaRequirementIDs'] ?? [];

                    foreach ($reqIds as $reqId) {
                        $reqDetails = $this->getVisaRequirementDetails($reqId);
                        if ($reqDetails['success']) {
                            $visaTypeData['requirements'][] = [
                                'id' => $reqId,
                                'details' => $reqDetails['data']['VisaRequirementDetails'] ?? 'Keine Details verfügbar',
                            ];
                        }
                    }
                }

                $visaTypes[] = $visaTypeData;
            }
        }

        return [
            'success' => true,
            'data' => [
                'visaRequired' => true,
                'message' => 'Visum erforderlich',
                'visaTypes' => $visaTypes,
                'totalVisaTypes' => count($visaTypes),
                'destinationCountry' => $destinationCountry,
                'nationality' => $nationality,
                'countryOfResidence' => $countryOfResidence,
            ],
        ];
    }

    /**
     * Convert 2-letter ISO code to 3-letter ISO code
     */
    public static function iso2to3(string $iso2): string
    {
        $mapping = [
            'AF' => 'AFG', 'AL' => 'ALB', 'DZ' => 'DZA', 'AD' => 'AND', 'AO' => 'AGO',
            'AG' => 'ATG', 'AR' => 'ARG', 'AM' => 'ARM', 'AU' => 'AUS', 'AT' => 'AUT',
            'AZ' => 'AZE', 'BS' => 'BHS', 'BH' => 'BHR', 'BD' => 'BGD', 'BB' => 'BRB',
            'BY' => 'BLR', 'BE' => 'BEL', 'BZ' => 'BLZ', 'BJ' => 'BEN', 'BT' => 'BTN',
            'BO' => 'BOL', 'BA' => 'BIH', 'BW' => 'BWA', 'BR' => 'BRA', 'BN' => 'BRN',
            'BG' => 'BGR', 'BF' => 'BFA', 'BI' => 'BDI', 'KH' => 'KHM', 'CM' => 'CMR',
            'CA' => 'CAN', 'CV' => 'CPV', 'CF' => 'CAF', 'TD' => 'TCD', 'CL' => 'CHL',
            'CN' => 'CHN', 'CO' => 'COL', 'KM' => 'COM', 'CG' => 'COG', 'CD' => 'COD',
            'CR' => 'CRI', 'CI' => 'CIV', 'HR' => 'HRV', 'CU' => 'CUB', 'CY' => 'CYP',
            'CZ' => 'CZE', 'DK' => 'DNK', 'DJ' => 'DJI', 'DM' => 'DMA', 'DO' => 'DOM',
            'EC' => 'ECU', 'EG' => 'EGY', 'SV' => 'SLV', 'GQ' => 'GNQ', 'ER' => 'ERI',
            'EE' => 'EST', 'SZ' => 'SWZ', 'ET' => 'ETH', 'FJ' => 'FJI', 'FI' => 'FIN',
            'FR' => 'FRA', 'GA' => 'GAB', 'GM' => 'GMB', 'GE' => 'GEO', 'DE' => 'DEU',
            'GH' => 'GHA', 'GR' => 'GRC', 'GD' => 'GRD', 'GT' => 'GTM', 'GN' => 'GIN',
            'GW' => 'GNB', 'GY' => 'GUY', 'HT' => 'HTI', 'HN' => 'HND', 'HU' => 'HUN',
            'IS' => 'ISL', 'IN' => 'IND', 'ID' => 'IDN', 'IR' => 'IRN', 'IQ' => 'IRQ',
            'IE' => 'IRL', 'IL' => 'ISR', 'IT' => 'ITA', 'JM' => 'JAM', 'JP' => 'JPN',
            'JO' => 'JOR', 'KZ' => 'KAZ', 'KE' => 'KEN', 'KI' => 'KIR', 'KP' => 'PRK',
            'KR' => 'KOR', 'KW' => 'KWT', 'KG' => 'KGZ', 'LA' => 'LAO', 'LV' => 'LVA',
            'LB' => 'LBN', 'LS' => 'LSO', 'LR' => 'LBR', 'LY' => 'LBY', 'LI' => 'LIE',
            'LT' => 'LTU', 'LU' => 'LUX', 'MG' => 'MDG', 'MW' => 'MWI', 'MY' => 'MYS',
            'MV' => 'MDV', 'ML' => 'MLI', 'MT' => 'MLT', 'MH' => 'MHL', 'MR' => 'MRT',
            'MU' => 'MUS', 'MX' => 'MEX', 'FM' => 'FSM', 'MD' => 'MDA', 'MC' => 'MCO',
            'MN' => 'MNG', 'ME' => 'MNE', 'MA' => 'MAR', 'MZ' => 'MOZ', 'MM' => 'MMR',
            'NA' => 'NAM', 'NR' => 'NRU', 'NP' => 'NPL', 'NL' => 'NLD', 'NZ' => 'NZL',
            'NI' => 'NIC', 'NE' => 'NER', 'NG' => 'NGA', 'MK' => 'MKD', 'NO' => 'NOR',
            'OM' => 'OMN', 'PK' => 'PAK', 'PW' => 'PLW', 'PS' => 'PSE', 'PA' => 'PAN',
            'PG' => 'PNG', 'PY' => 'PRY', 'PE' => 'PER', 'PH' => 'PHL', 'PL' => 'POL',
            'PT' => 'PRT', 'QA' => 'QAT', 'RO' => 'ROU', 'RU' => 'RUS', 'RW' => 'RWA',
            'KN' => 'KNA', 'LC' => 'LCA', 'VC' => 'VCT', 'WS' => 'WSM', 'SM' => 'SMR',
            'ST' => 'STP', 'SA' => 'SAU', 'SN' => 'SEN', 'RS' => 'SRB', 'SC' => 'SYC',
            'SL' => 'SLE', 'SG' => 'SGP', 'SK' => 'SVK', 'SI' => 'SVN', 'SB' => 'SLB',
            'SO' => 'SOM', 'ZA' => 'ZAF', 'SS' => 'SSD', 'ES' => 'ESP', 'LK' => 'LKA',
            'SD' => 'SDN', 'SR' => 'SUR', 'SE' => 'SWE', 'CH' => 'CHE', 'SY' => 'SYR',
            'TW' => 'TWN', 'TJ' => 'TJK', 'TZ' => 'TZA', 'TH' => 'THA', 'TL' => 'TLS',
            'TG' => 'TGO', 'TO' => 'TON', 'TT' => 'TTO', 'TN' => 'TUN', 'TR' => 'TUR',
            'TM' => 'TKM', 'TV' => 'TUV', 'UG' => 'UGA', 'UA' => 'UKR', 'AE' => 'ARE',
            'GB' => 'GBR', 'US' => 'USA', 'UY' => 'URY', 'UZ' => 'UZB', 'VU' => 'VUT',
            'VA' => 'VAT', 'VE' => 'VEN', 'VN' => 'VNM', 'YE' => 'YEM', 'ZM' => 'ZMB',
            'ZW' => 'ZWE',
        ];

        return $mapping[strtoupper($iso2)] ?? $iso2;
    }

    /**
     * Check if the service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->userName) && !empty($this->accessToken);
    }

    /**
     * Get the debug log of all API calls
     */
    public function getDebugLog(): array
    {
        return $this->debugLog;
    }

    /**
     * Clear the debug log
     */
    public function clearDebugLog(): void
    {
        $this->debugLog = [];
    }

    /**
     * Add entry to debug log
     */
    protected function addDebugLog(string $function, array $request, $response, ?string $error = null): void
    {
        $this->debugLog[] = [
            'timestamp' => now()->toIso8601String(),
            'function' => $function,
            'request' => [
                'url' => $this->baseUrl,
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => $request,
                'bodyMasked' => $this->maskSensitiveData($request),
            ],
            'curl' => $this->generateCurlCommand($request),
            'curlMasked' => $this->generateCurlCommand($this->maskSensitiveData($request)),
            'response' => $response,
            'error' => $error,
        ];
    }

    /**
     * Generate a cURL command for the request
     */
    protected function generateCurlCommand(array $requestData): string
    {
        $jsonBody = json_encode($requestData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $escapedBody = str_replace("'", "'\\''", $jsonBody);

        return sprintf(
            "curl -X POST '%s' \\\n  -H 'Content-Type: application/json' \\\n  -H 'Accept: application/json' \\\n  -d '%s'",
            $this->baseUrl,
            $escapedBody
        );
    }

    /**
     * Mask sensitive data in request for debug output
     */
    protected function maskSensitiveData(array $data): array
    {
        $masked = $data;
        if (isset($masked['AccessToken'])) {
            $masked['AccessToken'] = substr($masked['AccessToken'], 0, 4) . '****';
        }
        return $masked;
    }
}
