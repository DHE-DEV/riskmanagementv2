# SSO Logging Service - Usage Guide

## Overview

The SSO Logging Service provides comprehensive logging capabilities for SSO authentication flows. It tracks every step of the SSO process, including JWT validation, token exchange, customer provisioning, and login attempts.

## Files Created

1. **Model**: `/app/Models/SsoLog.php`
2. **Service**: `/app/Services/SsoLogService.php`
3. **Migration**: `/database/migrations/2025_11_21_112201_create_sso_logs_table.php`

## Features

- Automatic capture of request context (IP, user agent, URL, method)
- Comprehensive logging for all SSO steps
- Error tracking with stack traces
- Performance monitoring (duration tracking)
- Flexible querying with scopes
- Customer relationship tracking
- Statistics and reporting

## Basic Usage

### 1. Inject the Service (Recommended)

```php
use App\Services\SsoLogService;

class SPController extends Controller
{
    protected $ssoLogService;

    public function __construct(SsoLogService $ssoLogService)
    {
        $this->ssoLogService = $ssoLogService;
    }
}
```

### 2. Start a New SSO Request

```php
// Start a new SSO request
$requestId = $this->ssoLogService->startRequest();

// Or provide a custom request ID
$requestId = $this->ssoLogService->startRequest('custom_request_id');
```

### 3. Log JWT Receipt

```php
$jwt = $request->input('jwt');
$payload = JWT::decode($jwt, new Key($publicKey, 'RS256'));

// Convert payload to array
$payloadArray = json_decode(json_encode($payload), true);

$this->ssoLogService->logJwtReceived($requestId, $jwt, $payloadArray);
```

### 4. Log JWT Validation

```php
// Log successful validation
$this->ssoLogService->logJwtValidation($requestId, true, [
    'issuer' => $payload->iss,
    'audience' => $payload->aud,
    'expiration' => date('Y-m-d H:i:s', $payload->exp)
]);

// Log failed validation
$this->ssoLogService->logJwtValidation($requestId, false, [
    'reason' => 'Invalid issuer',
    'expected' => $expectedIssuer,
    'received' => $payload->iss
]);
```

### 5. Log OTT Generation

```php
$ott = Str::random(60);
$ttl = 300; // 5 minutes in seconds

$this->ssoLogService->logOttGenerated($requestId, $ott, $ttl);
```

### 6. Log Customer Actions (JIT Provisioning)

```php
// Log customer creation
$this->ssoLogService->logCustomerAction($requestId, $customer->id, 'created', [
    'agent_id' => $agentId,
    'service1_customer_id' => $service1CustomerId,
    'email' => $customer->email
]);

// Log customer update
$this->ssoLogService->logCustomerAction($requestId, $customer->id, 'updated', [
    'fields_updated' => ['email', 'phone', 'address']
]);

// Log successful login
$this->ssoLogService->logCustomerAction($requestId, $customer->id, 'logged_in', [
    'guard' => 'customer'
]);
```

### 7. Log Login Attempts

```php
// Log successful login
$this->ssoLogService->logLoginAttempt($requestId, $ott, true);

// Log failed login
$this->ssoLogService->logLoginAttempt($requestId, $ott, false, 'Invalid or expired OTT');
```

### 8. Log Errors

```php
// Log with exception
try {
    // Some SSO operation
} catch (\Exception $e) {
    $this->ssoLogService->logError($requestId, 'jwt_validation', $e);
}

// Log with custom error message
$this->ssoLogService->logError($requestId, 'customer_lookup', 'Customer not found', $stackTrace);
```

### 9. Log Generic Steps

```php
// Log any custom step
$this->ssoLogService->logStep($requestId, 'cache_check', 'info', [
    'request_data' => ['cache_key' => $cacheKey],
    'response_data' => ['found' => true]
]);

// Log success
$this->ssoLogService->logSuccess($requestId, 'token_exchange', [
    'response_data' => ['ott' => $ottPreview]
]);
```

### 10. Track Duration

```php
$startTime = microtime(true);

// Perform some operation
performExpensiveOperation();

// Update duration
$this->ssoLogService->updateDuration($requestId, 'expensive_operation', $startTime);
```

## Querying Logs

### Get All Logs for a Request

```php
$logs = $this->ssoLogService->getRequestLogs($requestId);

foreach ($logs as $log) {
    echo "{$log->step} - {$log->status} - {$log->created_at}\n";
}
```

### Using Model Scopes

```php
use App\Models\SsoLog;

// Get logs by request ID
$logs = SsoLog::byRequestId($requestId)->get();

// Get error logs only
$errors = SsoLog::errors()->get();

// Get success logs only
$successes = SsoLog::success()->get();

// Get logs by status
$infoLogs = SsoLog::byStatus('info')->get();

// Get logs by step
$jwtLogs = SsoLog::byStep('jwt_validated')->get();

// Get recent logs (last 7 days by default)
$recentLogs = SsoLog::recent()->get();

// Get recent logs for specific period
$last30Days = SsoLog::recent(30)->get();

// Combine scopes
$recentErrors = SsoLog::recent(7)->errors()->get();
```

### Get Request History

```php
// Get complete history for a request, ordered chronologically
$history = SsoLog::getRequestHistory($requestId);
```

## Statistics and Reporting

```php
// Get statistics for last 7 days
$stats = $this->ssoLogService->getStatistics(7);

echo "Total requests: {$stats['total_requests']}\n";
echo "Total logs: {$stats['total_logs']}\n";
echo "Successful logins: {$stats['successful_logins']}\n";
echo "Failed logins: {$stats['failed_logins']}\n";
echo "Total errors: {$stats['errors']}\n";
echo "Average duration: {$stats['average_duration_ms']}ms\n";
```

## Cleanup Old Logs

```php
// Clean up logs older than 30 days
$deletedCount = $this->ssoLogService->cleanupOldLogs(30);

echo "Deleted {$deletedCount} old log entries\n";
```

## Complete Example in SPController

```php
use App\Services\SsoLogService;

class SPController extends Controller
{
    protected $ssoLogService;

    public function __construct(SsoLogService $ssoLogService)
    {
        $this->ssoLogService = $ssoLogService;
    }

    public function exchangeToken(Request $request): JsonResponse
    {
        // Start logging
        $requestId = $this->ssoLogService->startRequest();

        try {
            // Validate request
            $validated = $request->validate(['jwt' => 'required|string']);
            $jwt = $validated['jwt'];

            // Log JWT receipt
            $this->ssoLogService->logJwtReceived($requestId, $jwt);

            // Decode JWT
            $startTime = microtime(true);
            $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));

            // Track duration
            $this->ssoLogService->updateDuration($requestId, 'jwt_decode', $startTime);

            // Convert to array
            $payload = json_decode(json_encode($decoded), true);

            // Log with full payload
            $this->ssoLogService->logJwtValidation($requestId, true, [
                'issuer' => $decoded->iss,
                'audience' => $decoded->aud,
                'subject' => $decoded->sub
            ]);

            // Generate OTT
            $ott = Str::random(60);
            $ttl = 300;

            // Log OTT generation
            $this->ssoLogService->logOttGenerated($requestId, $ott, $ttl);

            // Store in cache
            Cache::put("sso_ott_{$ott}", $payload, $ttl);

            // Log success
            $this->ssoLogService->logSuccess($requestId, 'token_exchange', [
                'response_data' => ['ott_generated' => true, 'ttl' => $ttl]
            ]);

            return response()->json([
                'success' => true,
                'ott' => $ott,
                'redirect_url' => route('pdsauthint.login', ['ott' => $ott])
            ]);

        } catch (\Exception $e) {
            // Log error
            $this->ssoLogService->logError($requestId, 'token_exchange', $e);

            return response()->json([
                'error' => 'Token exchange failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function handleLogin(Request $request): RedirectResponse
    {
        $requestId = $this->ssoLogService->startRequest();

        try {
            $validated = $request->validate(['ott' => 'required|string|size:60']);
            $ott = $validated['ott'];

            // Log login attempt start
            $this->ssoLogService->logStep($requestId, 'login_start', 'info', [
                'ott' => substr($ott, 0, 15) . '...'
            ]);

            // Retrieve claims from cache
            $claims = Cache::pull("sso_ott_{$ott}");

            if (!$claims) {
                $this->ssoLogService->logLoginAttempt($requestId, $ott, false, 'Invalid or expired OTT');
                return redirect()->route('login')->withErrors(['error' => 'Invalid or expired token']);
            }

            // Find or create customer (JIT provisioning)
            $customer = Customer::where('agent_id', $claims['agent_id'])
                ->where('service1_customer_id', $claims['sub'])
                ->first();

            if ($customer) {
                // Log customer update
                $customer->update(['email' => $claims['email']]);
                $this->ssoLogService->logCustomerAction($requestId, $customer->id, 'updated', [
                    'email' => $claims['email']
                ]);
            } else {
                // Log customer creation
                $customer = Customer::create([
                    'agent_id' => $claims['agent_id'],
                    'service1_customer_id' => $claims['sub'],
                    'email' => $claims['email'],
                    'name' => $claims['email'],
                    'password' => bcrypt(Str::random(32))
                ]);
                $this->ssoLogService->logCustomerAction($requestId, $customer->id, 'created', [
                    'agent_id' => $claims['agent_id']
                ]);
            }

            // Log in customer
            Auth::guard('customer')->login($customer);

            // Log successful login
            $this->ssoLogService->logLoginAttempt($requestId, $ott, true);
            $this->ssoLogService->logCustomerAction($requestId, $customer->id, 'logged_in');

            return redirect()->route('customer.dashboard');

        } catch (\Exception $e) {
            $this->ssoLogService->logError($requestId, 'login', $e);
            return redirect()->route('login')->withErrors(['error' => 'Login failed']);
        }
    }
}
```

## Database Schema

The `sso_logs` table includes:

- **request_id**: Unique identifier for tracking complete SSO flows
- **customer_id**: Foreign key to customers table
- **step**: Current step in SSO flow (e.g., 'jwt_received', 'login_attempt')
- **status**: Status of the step ('success', 'error', 'warning', 'info')
- **jwt_payload**: Decoded JWT payload as JSON
- **jwt_token**: Raw JWT token (truncated)
- **ott**: One-time token
- **agent_id**: Agent identifier from SSO provider
- **service1_customer_id**: Customer ID from external SSO provider
- **request_data**: Request data as JSON
- **response_data**: Response data as JSON
- **error_message**: Error message if step failed
- **error_trace**: Full stack trace for debugging
- **ip_address**: Client IP address
- **user_agent**: Client user agent string
- **url**: Full request URL
- **method**: HTTP method
- **duration_ms**: Duration in milliseconds
- **timestamps**: created_at, updated_at

## Best Practices

1. **Always start with startRequest()** to generate a unique request ID
2. **Use try-catch blocks** and log errors with logError()
3. **Track performance** for critical operations using updateDuration()
4. **Don't store full JWTs or OTTs** - they're automatically truncated for security
5. **Use meaningful step names** that describe the action
6. **Include relevant context** in request_data and response_data
7. **Clean up old logs** periodically using cleanupOldLogs()
8. **Use scopes** for efficient querying

## Security Considerations

- JWT tokens are automatically truncated to first 100 characters
- OTTs are automatically truncated to first 15 characters
- Sensitive data should not be stored in request_data or response_data
- Consider implementing log rotation and archival strategies
- Use appropriate database indexes for performance with large log volumes

## Troubleshooting

### Service not found
Make sure AppServiceProvider is registered and the service is bound as singleton.

### Database errors
Run the migration: `php artisan migrate`

### Relationship not working
Ensure the customers table exists and has the id column.

### Performance issues
- Use indexes (already included in migration)
- Clean up old logs regularly
- Consider archiving logs to separate storage
