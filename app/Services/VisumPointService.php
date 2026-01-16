<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisumPointService
{
    protected string $baseUrl;
    protected string $organization;
    protected string $accessToken;
    protected int $sessionTimeout = 300; // 5 minutes

    public function __construct()
    {
        $this->baseUrl = config('services.visumpoint.base_url') ?? 'https://www.visumpoint.de/REST/ComplianceCheck/API.php';
        $this->organization = config('services.visumpoint.organization') ?? '';
        $this->accessToken = config('services.visumpoint.access_token') ?? '';
    }

    /**
     * Get or create a session ID
     */
    protected function getSessionId(): ?string
    {
        $cacheKey = 'visumpoint_session_' . md5($this->organization);

        return Cache::remember($cacheKey, $this->sessionTimeout, function () {
            return $this->beginSession();
        });
    }

    /**
     * Clear the cached session
     */
    public function clearSession(): void
    {
        $cacheKey = 'visumpoint_session_' . md5($this->organization);
        Cache::forget($cacheKey);
    }

    /**
     * Begin a new session with the API
     */
    protected function beginSession(): ?string
    {
        try {
            $response = Http::timeout(30)->post($this->baseUrl, [
                'Function' => 'BeginSession',
                'Organization' => $this->organization,
                'AccessToken' => $this->accessToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['Result'] ?? '') === 'OK' && isset($data['SID'])) {
                    return $data['SID'];
                }

                Log::warning('VisumPoint BeginSession failed', [
                    'response' => $data,
                ]);
            }

            Log::error('VisumPoint BeginSession request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
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
            ];
        }

        try {
            $requestData = array_merge([
                'Function' => $function,
                'SID' => $sid,
            ], $params);

            $response = Http::timeout(30)->post($this->baseUrl, $requestData);

            if ($response->successful()) {
                $data = $response->json();

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

            return [
                'success' => false,
                'error' => 'Request failed with status ' . $response->status(),
            ];
        } catch (\Exception $e) {
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
            'DestinationCountry' => $destinationCountry,
            'Nationality' => $nationality,
            'CountryOfResidence' => $countryOfResidence,
        ]);
    }

    /**
     * Get details for a specific visa type
     */
    public function getVisaTypeDetails(string $visaTypeId): array
    {
        return $this->apiCall('GetVisaTypeDetails', [
            'VisaTypeID' => $visaTypeId,
        ]);
    }

    /**
     * Get visa requirements for given parameters
     */
    public function getVisaRequirements(string $destinationCountry, string $nationality, string $countryOfResidence, ?string $visaTypeId = null): array
    {
        $params = [
            'DestinationCountry' => $destinationCountry,
            'Nationality' => $nationality,
            'CountryOfResidence' => $countryOfResidence,
        ];

        if ($visaTypeId) {
            $params['VisaTypeID'] = $visaTypeId;
        }

        return $this->apiCall('GetVisaRequirements', $params);
    }

    /**
     * Get details for a specific visa requirement
     */
    public function getVisaRequirementDetails(string $visaRequirementId): array
    {
        return $this->apiCall('GetVisaRequirementDetails', [
            'VisaRequirementID' => $visaRequirementId,
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

        // Step 2: Get details for each visa type
        $visaTypes = [];
        foreach ($visaTypeIds as $typeId) {
            $detailsResult = $this->getVisaTypeDetails($typeId);

            if ($detailsResult['success']) {
                $visaTypes[] = [
                    'id' => $typeId,
                    'details' => $detailsResult['data']['VisaTypeDetails'] ?? 'Keine Details verfügbar',
                ];
            }
        }

        // Step 3: Get requirements (optional, for first visa type)
        $requirements = [];
        if (!empty($visaTypeIds)) {
            $reqResult = $this->getVisaRequirements(
                $destinationCountry,
                $nationality,
                $countryOfResidence,
                $visaTypeIds[0]
            );

            if ($reqResult['success']) {
                $reqIds = $reqResult['data']['VisaRequirementIDs'] ?? [];

                foreach ($reqIds as $reqId) {
                    $reqDetails = $this->getVisaRequirementDetails($reqId);
                    if ($reqDetails['success']) {
                        $requirements[] = [
                            'id' => $reqId,
                            'details' => $reqDetails['data']['VisaRequirementDetails'] ?? 'Keine Details verfügbar',
                        ];
                    }
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'visaRequired' => true,
                'message' => 'Visum erforderlich',
                'visaTypes' => $visaTypes,
                'requirements' => $requirements,
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
        return !empty($this->organization) && !empty($this->accessToken);
    }
}
