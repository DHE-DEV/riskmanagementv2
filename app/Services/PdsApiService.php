<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;

/**
 * Service for making API calls to pds-api
 *
 * This service uses the PDS API token stored on the customer
 * to authenticate API requests to pds-api.
 *
 * Dieser Service verwendet den PDS API Token des Kunden,
 * um API-Anfragen an pds-api zu authentifizieren.
 */
class PdsApiService
{
    /**
     * Base URL for pds-api
     */
    protected string $baseUrl;

    /**
     * Request timeout in seconds
     */
    protected int $timeout;

    /**
     * Enable detailed logging of requests and responses
     */
    protected bool $detailedLogging;

    /**
     * Collect debug info for frontend debug panel
     */
    protected bool $debugEnabled = false;

    protected array $debugLog = [];

    /**
     * Constructor
     *
     * Base URL comes from PASSOLUTION_API_URL environment variable
     * (e.g. https://api.passolution.eu/api/v2 or https://api-dot-dataservice-development.ey.r.appspot.com/api/v2)
     */
    public function __construct()
    {
        $this->baseUrl = config('services.pds_api.base_url', 'https://api.passolution.eu/api/v2');
        $this->timeout = config('services.pds_api.timeout', 30);
        $this->detailedLogging = config('services.pds_api.detailed_logging', false);
    }

    /**
     * Enable debug log collection for frontend debug panel.
     */
    public function enableDebug(): void
    {
        $this->debugEnabled = true;
        $this->debugLog = [];
    }

    /**
     * Get collected debug log entries.
     */
    public function getDebugLog(): array
    {
        return $this->debugLog;
    }

    /**
     * Check if customer has a valid API token (SSO or OAuth)
     *
     * @param Customer $customer
     * @return bool
     */
    public function hasValidToken(Customer $customer): bool
    {
        return $customer->hasAnyActiveToken();
    }

    /**
     * Make an authenticated GET request to pds-api
     *
     * @param Customer $customer
     * @param string $endpoint
     * @param array $query
     * @return Response|null
     */
    public function get(Customer $customer, string $endpoint, array $query = []): ?Response
    {
        if (!$this->hasValidToken($customer)) {
            Log::warning('PdsApiService: Customer has no valid PDS API token', [
                'customer_id' => $customer->id,
                'endpoint' => $endpoint,
            ]);
            return null;
        }

        $url = $this->buildUrl($endpoint);
        $this->logRequest('GET', $url, $query, $customer);
        $debugStart = microtime(true);

        try {
            $response = $this->client($customer)
                ->get($url, $query);

            $this->logResponse('GET', $url, $response, $customer);
            $this->collectDebug('GET', $url, $query, $response, $debugStart);

            return $response;
        } catch (\Exception $e) {
            $this->logError('GET', $url, $e, $customer);
            $this->collectDebug('GET', $url, $query, null, $debugStart, $e->getMessage());
            return null;
        }
    }

    /**
     * Make an authenticated POST request to pds-api
     *
     * @param Customer $customer
     * @param string $endpoint
     * @param array $data
     * @return Response|null
     */
    public function post(Customer $customer, string $endpoint, array $data = []): ?Response
    {
        if (!$this->hasValidToken($customer)) {
            Log::warning('PdsApiService: Customer has no valid PDS API token', [
                'customer_id' => $customer->id,
                'endpoint' => $endpoint,
            ]);
            return null;
        }

        $url = $this->buildUrl($endpoint);
        $this->logRequest('POST', $url, $data, $customer);
        $debugStart = microtime(true);

        try {
            $response = $this->client($customer)
                ->post($url, $data);

            $this->logResponse('POST', $url, $response, $customer);
            $this->collectDebug('POST', $url, $data, $response, $debugStart);

            return $response;
        } catch (\Exception $e) {
            $this->logError('POST', $url, $e, $customer);
            $this->collectDebug('POST', $url, $data, null, $debugStart, $e->getMessage());
            return null;
        }
    }

    /**
     * Make an authenticated PUT request to pds-api
     *
     * @param Customer $customer
     * @param string $endpoint
     * @param array $data
     * @return Response|null
     */
    public function put(Customer $customer, string $endpoint, array $data = []): ?Response
    {
        if (!$this->hasValidToken($customer)) {
            Log::warning('PdsApiService: Customer has no valid PDS API token', [
                'customer_id' => $customer->id,
                'endpoint' => $endpoint,
            ]);
            return null;
        }

        $url = $this->buildUrl($endpoint);
        $this->logRequest('PUT', $url, $data, $customer);

        try {
            $response = $this->client($customer)
                ->put($url, $data);

            $this->logResponse('PUT', $url, $response, $customer);

            return $response;
        } catch (\Exception $e) {
            $this->logError('PUT', $url, $e, $customer);
            return null;
        }
    }

    /**
     * Make an authenticated DELETE request to pds-api
     *
     * @param Customer $customer
     * @param string $endpoint
     * @return Response|null
     */
    public function delete(Customer $customer, string $endpoint): ?Response
    {
        if (!$this->hasValidToken($customer)) {
            Log::warning('PdsApiService: Customer has no valid PDS API token', [
                'customer_id' => $customer->id,
                'endpoint' => $endpoint,
            ]);
            return null;
        }

        $url = $this->buildUrl($endpoint);
        $this->logRequest('DELETE', $url, [], $customer);

        try {
            $response = $this->client($customer)
                ->delete($url);

            $this->logResponse('DELETE', $url, $response, $customer);

            return $response;
        } catch (\Exception $e) {
            $this->logError('DELETE', $url, $e, $customer);
            return null;
        }
    }

    /**
     * Create an authenticated HTTP client for the customer
     * Uses the best available token (SSO token first, then OAuth token)
     *
     * @param Customer $customer
     * @return PendingRequest
     */
    protected function client(Customer $customer): PendingRequest
    {
        return Http::withToken($customer->getActiveApiToken())
            ->timeout($this->timeout)
            ->acceptJson();
    }

    /**
     * Build full URL from endpoint
     *
     * @param string $endpoint
     * @return string
     */
    protected function buildUrl(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Collect debug info for frontend debug panel.
     */
    protected function collectDebug(string $method, string $url, array $requestData, ?Response $response, float $startTime, ?string $error = null): void
    {
        if (!$this->debugEnabled) {
            return;
        }

        $entry = [
            'method' => $method,
            'url' => $url,
            'request_body' => $requestData,
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ];

        if ($response) {
            $entry['status'] = $response->status();
            $body = $response->json();
            // Truncate large response data arrays for debug display
            if (isset($body['data']) && is_array($body['data']) && count($body['data']) > 5) {
                $entry['response_body'] = array_merge($body, [
                    'data' => array_slice($body['data'], 0, 5),
                    '_truncated' => count($body['data']) . ' total items, showing first 5',
                ]);
            } else {
                $entry['response_body'] = $body;
            }
        } else {
            $entry['status'] = null;
            $entry['error'] = $error;
            $entry['response_body'] = null;
        }

        $this->debugLog[] = $entry;
    }

    /**
     * Log API request
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @param Customer $customer
     */
    protected function logRequest(string $method, string $url, array $data, Customer $customer): void
    {
        if (!$this->detailedLogging) {
            return;
        }

        Log::channel('pds_api')->info('PdsApiService: REQUEST', [
            'method' => $method,
            'url' => $url,
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'request_body' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log API response
     *
     * @param string $method
     * @param string $url
     * @param Response $response
     * @param Customer $customer
     */
    protected function logResponse(string $method, string $url, Response $response, Customer $customer): void
    {
        $logData = [
            'method' => $method,
            'url' => $url,
            'customer_id' => $customer->id,
            'status' => $response->status(),
            'successful' => $response->successful(),
        ];

        // Always log basic info
        Log::info('PdsApiService: API call completed', $logData);

        // Detailed logging if enabled
        if ($this->detailedLogging) {
            $responseBody = $response->body();
            // Truncate response body if too large (> 10KB)
            if (strlen($responseBody) > 10240) {
                $responseBody = substr($responseBody, 0, 10240) . '... [TRUNCATED]';
            }

            Log::channel('pds_api')->info('PdsApiService: RESPONSE', [
                'method' => $method,
                'url' => $url,
                'customer_id' => $customer->id,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_headers' => $response->headers(),
                'response_body' => $responseBody,
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Log API error
     *
     * @param string $method
     * @param string $url
     * @param \Exception $e
     * @param Customer $customer
     */
    protected function logError(string $method, string $url, \Exception $e, Customer $customer): void
    {
        $logData = [
            'method' => $method,
            'url' => $url,
            'customer_id' => $customer->id,
            'error' => $e->getMessage(),
        ];

        Log::error('PdsApiService: API call failed', $logData);

        // Detailed logging if enabled
        if ($this->detailedLogging) {
            Log::channel('pds_api')->error('PdsApiService: ERROR', array_merge($logData, [
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toIso8601String(),
            ]));
        }
    }

    /**
     * Get token expiration info for a customer
     *
     * @param Customer $customer
     * @return array
     */
    public function getTokenInfo(Customer $customer): array
    {
        return [
            'has_token' => !is_null($customer->pds_api_token),
            'is_valid' => $customer->hasValidPdsApiToken(),
            'expires_at' => $customer->pds_api_token_expires_at?->toIso8601String(),
            'expires_in_days' => $customer->pds_api_token_expires_at
                ? now()->diffInDays($customer->pds_api_token_expires_at, false)
                : null,
        ];
    }
}
