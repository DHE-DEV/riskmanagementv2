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
     * Constructor
     *
     * Base URL comes from PASSOLUTION_API_URL environment variable
     * (e.g. https://api.passolution.eu/api/v2 or https://api-dot-dataservice-development.ey.r.appspot.com/api/v2)
     */
    public function __construct()
    {
        $this->baseUrl = config('services.pds_api.base_url', 'https://api.passolution.eu/api/v2');
        $this->timeout = config('services.pds_api.timeout', 30);
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

        try {
            $response = $this->client($customer)
                ->get($this->buildUrl($endpoint), $query);

            $this->logResponse('GET', $endpoint, $response, $customer);

            return $response;
        } catch (\Exception $e) {
            $this->logError('GET', $endpoint, $e, $customer);
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

        try {
            $response = $this->client($customer)
                ->post($this->buildUrl($endpoint), $data);

            $this->logResponse('POST', $endpoint, $response, $customer);

            return $response;
        } catch (\Exception $e) {
            $this->logError('POST', $endpoint, $e, $customer);
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

        try {
            $response = $this->client($customer)
                ->put($this->buildUrl($endpoint), $data);

            $this->logResponse('PUT', $endpoint, $response, $customer);

            return $response;
        } catch (\Exception $e) {
            $this->logError('PUT', $endpoint, $e, $customer);
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

        try {
            $response = $this->client($customer)
                ->delete($this->buildUrl($endpoint));

            $this->logResponse('DELETE', $endpoint, $response, $customer);

            return $response;
        } catch (\Exception $e) {
            $this->logError('DELETE', $endpoint, $e, $customer);
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
     * Log API response
     *
     * @param string $method
     * @param string $endpoint
     * @param Response $response
     * @param Customer $customer
     */
    protected function logResponse(string $method, string $endpoint, Response $response, Customer $customer): void
    {
        Log::info('PdsApiService: API call completed', [
            'method' => $method,
            'endpoint' => $endpoint,
            'customer_id' => $customer->id,
            'status' => $response->status(),
            'successful' => $response->successful(),
        ]);
    }

    /**
     * Log API error
     *
     * @param string $method
     * @param string $endpoint
     * @param \Exception $e
     * @param Customer $customer
     */
    protected function logError(string $method, string $endpoint, \Exception $e, Customer $customer): void
    {
        Log::error('PdsApiService: API call failed', [
            'method' => $method,
            'endpoint' => $endpoint,
            'customer_id' => $customer->id,
            'error' => $e->getMessage(),
        ]);
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
