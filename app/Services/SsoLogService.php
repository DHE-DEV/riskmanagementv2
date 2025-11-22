<?php

namespace App\Services;

use App\Models\SsoLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

/**
 * SSO Logging Service
 *
 * Comprehensive logging service for SSO authentication flows.
 * Tracks all steps of the SSO process including JWT validation,
 * token exchange, customer provisioning, and login attempts.
 */
class SsoLogService
{
    /**
     * Start a new SSO request and return the request ID.
     *
     * @param string|null $requestId Optional custom request ID
     * @return string The request ID
     */
    public function startRequest(?string $requestId = null): string
    {
        if (!$requestId) {
            $requestId = $this->generateRequestId();
        }

        $this->logStep(
            requestId: $requestId,
            step: 'request_started',
            status: 'info',
            data: ['request_data' => ['action' => 'SSO request initiated']]
        );

        return $requestId;
    }

    /**
     * Log a specific step in the SSO process.
     *
     * @param string $requestId The unique request identifier
     * @param string $step The step name (e.g., 'jwt_received', 'jwt_validated', 'ott_generated')
     * @param string $status The status ('success', 'error', 'info')
     * @param array $data Additional data to log
     * @return SsoLog The created log entry
     */
    public function logStep(
        string $requestId,
        string $step,
        string $status,
        array $data = []
    ): SsoLog {
        return DB::transaction(function () use ($requestId, $step, $status, $data) {
            $logData = [
                'request_id' => $requestId,
                'step' => $step,
                'status' => $status,
                'version_sp' => config('pdsauthint.version'),
                'version_idp' => $data['version_idp'] ?? null,
                'request_data' => $data['request_data'] ?? null,
                'response_data' => $data['response_data'] ?? null,
                'jwt_payload' => $data['jwt_payload'] ?? null,
                'jwt_token' => $data['jwt_token'] ?? null,
                'ott' => $data['ott'] ?? null,
                'agent_id' => $data['agent_id'] ?? null,
                'service1_customer_id' => $data['service1_customer_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
            ];

            // Capture request context automatically
            $requestContext = $this->captureRequestContext();
            $logData = array_merge($logData, $requestContext);

            return SsoLog::create($logData);
        });
    }

    /**
     * Log an error in the SSO process.
     *
     * @param string $requestId The unique request identifier
     * @param string $step The step where the error occurred
     * @param string|\Throwable $error The error message or exception
     * @param string|null $trace Optional stack trace
     * @return SsoLog The created log entry
     */
    public function logError(
        string $requestId,
        string $step,
        string|\Throwable $error,
        ?string $trace = null
    ): SsoLog {
        return DB::transaction(function () use ($requestId, $step, $error, $trace) {
            $errorMessage = $error instanceof \Throwable ? $error->getMessage() : $error;
            $errorTrace = $trace ?? ($error instanceof \Throwable ? $error->getTraceAsString() : null);

            $logData = [
                'request_id' => $requestId,
                'step' => $step,
                'status' => 'error',
                'version_sp' => config('pdsauthint.version'),
                'error_message' => $errorMessage,
                'error_trace' => $errorTrace,
                'request_data' => ['error_step' => $step],
            ];

            // Capture request context automatically
            $requestContext = $this->captureRequestContext();
            $logData = array_merge($logData, $requestContext);

            return SsoLog::create($logData);
        });
    }

    /**
     * Log a successful step in the SSO process.
     *
     * @param string $requestId The unique request identifier
     * @param string $step The step that succeeded
     * @param array $data Additional success data
     * @return SsoLog The created log entry
     */
    public function logSuccess(
        string $requestId,
        string $step,
        array $data = []
    ): SsoLog {
        if (!isset($data['response_data'])) {
            $data['response_data'] = ['success' => true, 'step' => $step];
        }

        return $this->logStep(
            requestId: $requestId,
            step: $step,
            status: 'success',
            data: $data
        );
    }

    /**
     * Log JWT receipt and payload.
     *
     * @param string $requestId The unique request identifier
     * @param string $jwt The JWT token (will be truncated for logging)
     * @param array|null $payload The decoded JWT payload
     * @return SsoLog The created log entry
     */
    public function logJwtReceived(
        string $requestId,
        string $jwt,
        ?array $payload = null
    ): SsoLog {
        return DB::transaction(function () use ($requestId, $jwt, $payload) {
            $logData = [
                'request_id' => $requestId,
                'step' => 'jwt_received',
                'status' => 'info',
                'jwt_payload' => $payload,
                'jwt_token' => substr($jwt, 0, 100) . '...', // Store truncated JWT for security
                'request_data' => [
                    'jwt_length' => strlen($jwt),
                    'jwt_parts_count' => count(explode('.', $jwt)),
                ],
            ];

            // Capture request context automatically
            $requestContext = $this->captureRequestContext();
            $logData = array_merge($logData, $requestContext);

            return SsoLog::create($logData);
        });
    }

    /**
     * Log a customer-related action (JIT provisioning, updates, etc.).
     *
     * @param string $requestId The unique request identifier
     * @param int $customerId The customer ID
     * @param string $action The action performed (e.g., 'created', 'updated', 'logged_in')
     * @param array $data Additional action data
     * @return SsoLog The created log entry
     */
    public function logCustomerAction(
        string $requestId,
        int $customerId,
        string $action,
        array $data = []
    ): SsoLog {
        return DB::transaction(function () use ($requestId, $customerId, $action, $data) {
            $logData = [
                'request_id' => $requestId,
                'customer_id' => $customerId,
                'step' => "customer_{$action}",
                'status' => 'success',
                'response_data' => array_merge(['action' => $action], $data),
            ];

            // Capture request context automatically
            $requestContext = $this->captureRequestContext();
            $logData = array_merge($logData, $requestContext);

            return SsoLog::create($logData);
        });
    }

    /**
     * Update the duration of a specific step.
     *
     * @param string $requestId The unique request identifier
     * @param string $step The step to update
     * @param float $startTime The start time (from microtime(true))
     * @return bool Whether the update was successful
     */
    public function updateDuration(
        string $requestId,
        string $step,
        float $startTime
    ): bool {
        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        return DB::transaction(function () use ($requestId, $step, $durationMs) {
            $log = SsoLog::byRequestId($requestId)
                ->byStep($step)
                ->latest()
                ->first();

            if ($log) {
                return $log->update(['duration_ms' => $durationMs]);
            }

            return false;
        });
    }

    /**
     * Log JWT validation result.
     *
     * @param string $requestId The unique request identifier
     * @param bool $isValid Whether validation succeeded
     * @param array $details Validation details (issuer, audience, expiration, etc.)
     * @return SsoLog The created log entry
     */
    public function logJwtValidation(
        string $requestId,
        bool $isValid,
        array $details = []
    ): SsoLog {
        $status = $isValid ? 'success' : 'error';

        return $this->logStep(
            requestId: $requestId,
            step: 'jwt_validated',
            status: $status,
            data: [
                'response_data' => array_merge(['valid' => $isValid], $details),
            ]
        );
    }

    /**
     * Log OTT (One-Time Token) generation.
     *
     * @param string $requestId The unique request identifier
     * @param string $ott The generated OTT
     * @param int $ttl The TTL in seconds
     * @return SsoLog The created log entry
     */
    public function logOttGenerated(
        string $requestId,
        string $ott,
        int $ttl
    ): SsoLog {
        return $this->logStep(
            requestId: $requestId,
            step: 'ott_generated',
            status: 'success',
            data: [
                'ott' => substr($ott, 0, 15) . '...', // Store truncated OTT for security
                'response_data' => [
                    'ott_length' => strlen($ott),
                    'ttl_seconds' => $ttl,
                ],
            ]
        );
    }

    /**
     * Log login attempt.
     *
     * @param string $requestId The unique request identifier
     * @param string $ott The OTT used
     * @param bool $success Whether the login succeeded
     * @param string|null $reason Reason for failure if unsuccessful
     * @return SsoLog The created log entry
     */
    public function logLoginAttempt(
        string $requestId,
        string $ott,
        bool $success,
        ?string $reason = null
    ): SsoLog {
        $status = $success ? 'success' : 'error';

        return $this->logStep(
            requestId: $requestId,
            step: 'login_attempt',
            status: $status,
            data: [
                'ott' => substr($ott, 0, 15) . '...',
                'response_data' => [
                    'success' => $success,
                    'reason' => $reason,
                ],
            ]
        );
    }

    /**
     * Get all logs for a specific request ID.
     *
     * @param string $requestId The unique request identifier
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRequestLogs(string $requestId): \Illuminate\Database\Eloquent\Collection
    {
        return SsoLog::getRequestHistory($requestId);
    }

    /**
     * Generate a unique request ID.
     *
     * @return string
     */
    protected function generateRequestId(): string
    {
        return 'sso_' . date('Ymd_His') . '_' . Str::random(20);
    }

    /**
     * Capture current request context (IP, user agent, URL, method).
     *
     * @return array
     */
    protected function captureRequestContext(): array
    {
        $request = request();

        if (!$request instanceof Request) {
            return [
                'ip_address' => null,
                'user_agent' => null,
                'url' => null,
                'method' => null,
            ];
        }

        return [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ];
    }

    /**
     * Clean up old logs (optional utility method).
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted records
     */
    public function cleanupOldLogs(int $days = 30): int
    {
        return SsoLog::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get statistics for SSO requests.
     *
     * @param int $days Number of days to analyze
     * @return array
     */
    public function getStatistics(int $days = 7): array
    {
        $logs = SsoLog::where('created_at', '>=', now()->subDays($days))->get();

        return [
            'total_requests' => $logs->pluck('request_id')->unique()->count(),
            'total_logs' => $logs->count(),
            'successful_logins' => $logs->where('step', 'login_attempt')->where('status', 'success')->count(),
            'failed_logins' => $logs->where('step', 'login_attempt')->where('status', 'error')->count(),
            'errors' => $logs->where('status', 'error')->count(),
            'average_duration_ms' => $logs->whereNotNull('duration_ms')->avg('duration_ms'),
        ];
    }
}
