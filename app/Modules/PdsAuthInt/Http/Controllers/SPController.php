<?php

namespace App\Modules\PdsAuthInt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Customer;
use App\Services\SsoLogService;

/**
 * Service Provider Controller for SSO Authentication
 *
 * SP-Controller für SSO-Authentifizierung
 * Verwaltet den SSO-Flow als Service Provider:
 * 1. JWT-Validierung und OTT-Generierung (exchangeToken)
 * 2. JIT-Provisioning und Login (handleLogin)
 *
 * Manages the SSO flow as Service Provider:
 * 1. JWT validation and OTT generation (exchangeToken)
 * 2. JIT provisioning and login (handleLogin)
 */
class SPController extends Controller
{
    /**
     * SSO Log Service for comprehensive logging.
     *
     * @var SsoLogService
     */
    protected SsoLogService $ssoLogService;

    /**
     * Constructor - Inject SsoLogService dependency.
     *
     * @param SsoLogService $ssoLogService
     */
    public function __construct(SsoLogService $ssoLogService)
    {
        $this->ssoLogService = $ssoLogService;
    }
    /**
     * Exchange JWT for One-Time Token (OTT)
     *
     * API Endpoint: POST /api/sso/exchange
     *
     * Workflow:
     * 1. Empfängt JWT vom IdP (pds-homepage)
     * 2. Validiert JWT-Signatur mit öffentlichem Schlüssel
     * 3. Verifiziert iss (Issuer), aud (Audience) und exp (Expiration)
     * 4. Generiert ein sicheres One-Time Token (OTT)
     * 5. Speichert die JWT-Claims im Cache mit OTT als Key
     * 6. Gibt OTT und Redirect-URL zurück
     *
     * Workflow:
     * 1. Receives JWT from IdP (pds-homepage)
     * 2. Validates JWT signature with public key
     * 3. Verifies iss (Issuer), aud (Audience), and exp (Expiration)
     * 4. Generates a secure One-Time Token (OTT)
     * 5. Stores JWT claims in cache with OTT as key
     * 6. Returns OTT and redirect URL
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exchangeToken(Request $request): JsonResponse
    {
        // Generate unique request ID for tracking this entire SSO flow
        $requestId = $this->ssoLogService->startRequest();

        Log::info('====== SSO EXCHANGE START (Service 2 - SP) ======', ['request_id' => $requestId]);
        Log::info('SSO: Received JWT exchange request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'has_jwt' => $request->has('jwt'),
        ]);

        try {
            // STEP 1: Log exchange request - Log incoming request with all data
            $stepStartTime = microtime(true);
            try {
                $this->ssoLogService->logStep(
                    requestId: $requestId,
                    step: 'exchange_request',
                    status: 'info',
                    data: [
                        'request_data' => [
                            'method' => $request->method(),
                            'url' => $request->fullUrl(),
                            'ip' => $request->ip(),
                            'has_jwt' => $request->has('jwt'),
                            'headers' => [
                                'user_agent' => $request->userAgent(),
                                'content_type' => $request->header('Content-Type'),
                            ],
                        ],
                    ]
                );
                $this->ssoLogService->updateDuration($requestId, 'exchange_request', $stepStartTime);
            } catch (\Exception $logException) {
                // Non-blocking: Log error but continue
                Log::warning('Failed to log exchange_request step', [
                    'error' => $logException->getMessage(),
                    'request_id' => $requestId,
                ]);
            }

            // Validate request / Request validieren
            $validated = $request->validate([
                'jwt' => 'required|string',
            ]);

            $jwt = $validated['jwt'];

            Log::info('SSO: JWT received', [
                'jwt_length' => strlen($jwt),
                'jwt_preview' => substr($jwt, 0, 50) . '...',
            ]);

            // Load public key from environment or file / Öffentlichen Schlüssel aus Umgebung oder Datei laden
            $publicKeyConfig = config('pdsauthint.public_key');
            $useEnvKeys = config('pdsauthint.use_env_keys', true);

            // Check if key is provided directly in config (from env var)
            // Prüfen, ob Schlüssel direkt in Config bereitgestellt wird (aus Umgebungsvariable)
            Log::info('SSO: Loading public key', [
                'use_env_keys' => $useEnvKeys,
                'config_is_string' => is_string($publicKeyConfig),
                'starts_with_begin' => is_string($publicKeyConfig) && str_starts_with($publicKeyConfig, '-----BEGIN'),
            ]);

            if ($useEnvKeys && is_string($publicKeyConfig) && str_starts_with($publicKeyConfig, '-----BEGIN')) {
                // Key is directly from environment variable (PASSPORT_PUBLIC_KEY or SSO_PUBLIC_KEY)
                // Schlüssel stammt direkt aus Umgebungsvariable (PASSPORT_PUBLIC_KEY oder SSO_PUBLIC_KEY)
                $publicKey = $publicKeyConfig;

                Log::info('SSO: Using public key from environment variable', [
                    'key_length' => strlen($publicKey),
                ]);
            } else {
                // Key is a file path / Schlüssel ist ein Dateipfad
                $publicKeyPath = $publicKeyConfig;

                Log::info('SSO: Attempting to load public key from file', [
                    'path' => $publicKeyPath,
                    'file_exists' => file_exists($publicKeyPath),
                ]);

                if (!file_exists($publicKeyPath)) {
                    Log::error('SSO public key file not found', ['path' => $publicKeyPath]);
                    return response()->json([
                        'error' => 'Configuration error',
                        'message' => 'Public key not found'
                    ], 500);
                }

                $publicKey = file_get_contents($publicKeyPath);

                Log::info('SSO: Public key loaded from file', [
                    'path' => $publicKeyPath,
                    'key_length' => strlen($publicKey),
                ]);
            }

            if (!$publicKey) {
                Log::error('SSO: Failed to load public key - key is empty');
                return response()->json([
                    'error' => 'Configuration error',
                    'message' => 'Could not load public key'
                ], 500);
            }

            // STEP 2: JWT validation - Log JWT decoding attempt (success or failure)
            Log::info('SSO: Attempting to decode JWT with RS256');
            $stepStartTime = microtime(true);

            try {
                $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));

                Log::info('SSO: JWT decoded successfully', [
                    'iss' => $decoded->iss ?? 'MISSING',
                    'aud' => $decoded->aud ?? 'MISSING',
                    'sub' => $decoded->sub ?? 'MISSING',
                    'agent_id' => $decoded->agent_id ?? 'MISSING',
                    'email' => $decoded->email ?? 'MISSING',
                    'exp' => isset($decoded->exp) ? date('Y-m-d H:i:s', $decoded->exp) : 'MISSING',
                ]);

                // Log successful JWT decode
                try {
                    $decodedArray = json_decode(json_encode($decoded), true);
                    $this->ssoLogService->logStep(
                        requestId: $requestId,
                        step: 'jwt_validation',
                        status: 'success',
                        data: [
                            'jwt_token' => substr($jwt, 0, 100),
                            'jwt_payload' => $decodedArray,
                            'request_data' => [
                                'algorithm' => 'RS256',
                                'jwt_length' => strlen($jwt),
                            ],
                            'response_data' => [
                                'decoded_successfully' => true,
                                'iss' => $decoded->iss ?? null,
                                'aud' => $decoded->aud ?? null,
                                'sub' => $decoded->sub ?? null,
                                'agent_id' => $decoded->agent_id ?? null,
                                'exp' => $decoded->exp ?? null,
                            ],
                        ]
                    );
                    $this->ssoLogService->updateDuration($requestId, 'jwt_validation', $stepStartTime);
                } catch (\Exception $logException) {
                    Log::warning('Failed to log jwt_validation step', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('SSO: JWT decode failed', [
                    'error' => $e->getMessage(),
                    'exception_class' => get_class($e),
                    'jwt_preview' => substr($jwt, 0, 50) . '...',
                ]);

                // Log JWT validation failure
                try {
                    $this->ssoLogService->logError(
                        requestId: $requestId,
                        step: 'jwt_validation',
                        error: $e,
                        trace: $e->getTraceAsString()
                    );
                    $this->ssoLogService->updateDuration($requestId, 'jwt_validation', $stepStartTime);
                } catch (\Exception $logException) {
                    Log::warning('Failed to log jwt_validation error', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }

                return response()->json([
                    'error' => 'Invalid token',
                    'message' => 'JWT signature validation failed'
                ], 401);
            }

            // STEP 3: JWT payload validation - Log payload validation (iss, aud, exp checks)
            $stepStartTime = microtime(true);
            $expectedIssuer = config('pdsauthint.jwt_issuer');
            $expectedAudience = config('pdsauthint.jwt_audience');

            Log::info('SSO: Verifying JWT issuer', [
                'expected' => $expectedIssuer,
                'received' => $decoded->iss ?? 'null',
            ]);

            try {
                $validationErrors = [];
                $validationSuccess = true;

                // Verify issuer / Aussteller verifizieren
                if (!isset($decoded->iss) || $decoded->iss !== $expectedIssuer) {
                    $validationErrors[] = 'issuer_mismatch';
                    $validationSuccess = false;
                    Log::error('SSO: JWT issuer mismatch', [
                        'expected' => $expectedIssuer,
                        'received' => $decoded->iss ?? 'null'
                    ]);
                }

                // Verify audience / Ziel verifizieren
                Log::info('SSO: Verifying JWT audience', [
                    'expected' => $expectedAudience,
                    'received' => $decoded->aud ?? 'null',
                ]);

                if (!isset($decoded->aud) || $decoded->aud !== $expectedAudience) {
                    $validationErrors[] = 'audience_mismatch';
                    $validationSuccess = false;
                    Log::error('SSO: JWT audience mismatch', [
                        'expected' => $expectedAudience,
                        'received' => $decoded->aud ?? 'null'
                    ]);
                }

                // Verify expiration / Ablaufzeit verifizieren
                if (!isset($decoded->exp) || $decoded->exp < time()) {
                    $validationErrors[] = 'token_expired';
                    $validationSuccess = false;
                    Log::warning('JWT expired', [
                        'exp' => $decoded->exp ?? 'null',
                        'now' => time()
                    ]);
                }

                // Log payload validation result
                try {
                    $this->ssoLogService->logStep(
                        requestId: $requestId,
                        step: 'jwt_payload_validation',
                        status: $validationSuccess ? 'success' : 'error',
                        data: [
                            'request_data' => [
                                'expected_issuer' => $expectedIssuer,
                                'expected_audience' => $expectedAudience,
                                'current_time' => time(),
                            ],
                            'response_data' => [
                                'validation_success' => $validationSuccess,
                                'received_issuer' => $decoded->iss ?? null,
                                'received_audience' => $decoded->aud ?? null,
                                'received_exp' => $decoded->exp ?? null,
                                'issuer_valid' => !in_array('issuer_mismatch', $validationErrors),
                                'audience_valid' => !in_array('audience_mismatch', $validationErrors),
                                'expiration_valid' => !in_array('token_expired', $validationErrors),
                                'validation_errors' => $validationErrors,
                            ],
                        ]
                    );
                    $this->ssoLogService->updateDuration($requestId, 'jwt_payload_validation', $stepStartTime);
                } catch (\Exception $logException) {
                    Log::warning('Failed to log jwt_payload_validation step', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }

                // If validation failed, return error response
                if (!$validationSuccess) {
                    if (in_array('issuer_mismatch', $validationErrors)) {
                        return response()->json([
                            'error' => 'Invalid token',
                            'message' => 'Invalid issuer'
                        ], 401);
                    }
                    if (in_array('audience_mismatch', $validationErrors)) {
                        return response()->json([
                            'error' => 'Invalid token',
                            'message' => 'Invalid audience'
                        ], 401);
                    }
                    if (in_array('token_expired', $validationErrors)) {
                        return response()->json([
                            'error' => 'Invalid token',
                            'message' => 'Token expired'
                        ], 401);
                    }
                }
            } catch (\Exception $e) {
                // Log payload validation error
                try {
                    $this->ssoLogService->logError(
                        requestId: $requestId,
                        step: 'jwt_payload_validation',
                        error: $e,
                        trace: $e->getTraceAsString()
                    );
                } catch (\Exception $logException) {
                    Log::warning('Failed to log jwt_payload_validation error', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
                throw $e;
            }

            // STEP 7: OTT generation - Log OTT generation
            Log::info('SSO: Generating OTT');
            $stepStartTime = microtime(true);

            try {
                $ott = Str::random(60);

                // Log OTT generation
                try {
                    $this->ssoLogService->logStep(
                        requestId: $requestId,
                        step: 'ott_generation',
                        status: 'success',
                        data: [
                            'ott' => substr($ott, 0, 15) . '...',
                            'response_data' => [
                                'ott_length' => strlen($ott),
                                'algorithm' => 'random',
                            ],
                        ]
                    );
                    $this->ssoLogService->updateDuration($requestId, 'ott_generation', $stepStartTime);
                } catch (\Exception $logException) {
                    Log::warning('Failed to log ott_generation step', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
            } catch (\Exception $e) {
                // Log OTT generation error
                try {
                    $this->ssoLogService->logError(
                        requestId: $requestId,
                        step: 'ott_generation',
                        error: $e,
                        trace: $e->getTraceAsString()
                    );
                } catch (\Exception $logException) {
                    Log::warning('Failed to log ott_generation error', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
                throw $e;
            }

            // STEP 8: Cache storage - Log OTT storage in cache
            $stepStartTime = microtime(true);
            $cacheKey = config('pdsauthint.ott_cache_prefix') . $ott;
            $cacheTtl = config('pdsauthint.ott_ttl');

            try {
                // Convert JWT payload to array for storage / JWT-Payload für Speicherung in Array umwandeln
                $claims = json_decode(json_encode($decoded), true);

                Cache::put($cacheKey, $claims, $cacheTtl);

                Log::info('SSO: OTT generated and stored in cache', [
                    'cache_key' => $cacheKey,
                    'sub' => $claims['sub'] ?? 'unknown',
                    'agent_id' => $claims['agent_id'] ?? 'unknown',
                    'email' => $claims['email'] ?? 'unknown',
                    'ttl' => $cacheTtl,
                    'ott_length' => strlen($ott),
                ]);

                // Log cache storage
                try {
                    $this->ssoLogService->logStep(
                        requestId: $requestId,
                        step: 'cache_storage',
                        status: 'success',
                        data: [
                            'ott' => substr($ott, 0, 15) . '...',
                            'agent_id' => $claims['agent_id'] ?? null,
                            'service1_customer_id' => $claims['sub'] ?? null,
                            'request_data' => [
                                'cache_key' => $cacheKey,
                                'ttl_seconds' => $cacheTtl,
                            ],
                            'response_data' => [
                                'stored_successfully' => true,
                                'sub' => $claims['sub'] ?? null,
                                'agent_id' => $claims['agent_id'] ?? null,
                                'email' => $claims['email'] ?? null,
                            ],
                        ]
                    );
                    $this->ssoLogService->updateDuration($requestId, 'cache_storage', $stepStartTime);
                } catch (\Exception $logException) {
                    Log::warning('Failed to log cache_storage step', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
            } catch (\Exception $e) {
                // Log cache storage error
                try {
                    $this->ssoLogService->logError(
                        requestId: $requestId,
                        step: 'cache_storage',
                        error: $e,
                        trace: $e->getTraceAsString()
                    );
                } catch (\Exception $logException) {
                    Log::warning('Failed to log cache_storage error', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
                throw $e;
            }

            // STEP 9: Response sent - Log successful response with OTT
            $stepStartTime = microtime(true);

            try {
                // Generate redirect URL / Redirect-URL generieren
                $redirectUrl = route('pdsauthint.login', ['ott' => $ott]);

                Log::info('====== SSO EXCHANGE SUCCESS (Service 2 - SP) ======', [
                    'redirect_url' => $redirectUrl,
                    'request_id' => $requestId,
                ]);

                // Log successful response
                try {
                    $this->ssoLogService->logStep(
                        requestId: $requestId,
                        step: 'response_sent',
                        status: 'success',
                        data: [
                            'ott' => substr($ott, 0, 15) . '...',
                            'response_data' => [
                                'success' => true,
                                'redirect_url' => $redirectUrl,
                                'expires_in' => $cacheTtl,
                                'ott_length' => strlen($ott),
                            ],
                        ]
                    );
                    $this->ssoLogService->updateDuration($requestId, 'response_sent', $stepStartTime);
                } catch (\Exception $logException) {
                    Log::warning('Failed to log response_sent step', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'ott' => $ott,
                    'redirect_url' => $redirectUrl,
                    'expires_in' => $cacheTtl
                ]);
            } catch (\Exception $e) {
                // Log response error
                try {
                    $this->ssoLogService->logError(
                        requestId: $requestId,
                        step: 'response_sent',
                        error: $e,
                        trace: $e->getTraceAsString()
                    );
                } catch (\Exception $logException) {
                    Log::warning('Failed to log response_sent error', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('====== SSO EXCHANGE FAILED (Service 2 - SP) ======', ['request_id' => $requestId]);
            Log::error('SSO: Validation failed', [
                'error' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);

            // Log validation error with full stack trace
            try {
                $this->ssoLogService->logError(
                    requestId: $requestId,
                    step: 'exchange_request',
                    error: $e,
                    trace: $e->getTraceAsString()
                );
            } catch (\Exception $logException) {
                Log::warning('Failed to log validation error', [
                    'error' => $logException->getMessage(),
                    'request_id' => $requestId,
                ]);
            }

            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('====== SSO EXCHANGE FAILED (Service 2 - SP) ======', ['request_id' => $requestId]);
            Log::error('SSO: Token exchange failed', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Log general error with full stack trace
            try {
                $this->ssoLogService->logError(
                    requestId: $requestId,
                    step: 'exchange_failed',
                    error: $e,
                    trace: $e->getTraceAsString()
                );
            } catch (\Exception $logException) {
                Log::warning('Failed to log exchange error', [
                    'error' => $logException->getMessage(),
                    'request_id' => $requestId,
                ]);
            }

            return response()->json([
                'error' => 'Server error',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Handle SSO Login with OTT
     *
     * Web Endpoint: GET /sso/login?ott=abc123...
     *
     * Workflow:
     * 1. Empfängt OTT als Query-Parameter
     * 2. Holt und löscht Claims aus Cache (Cache::pull)
     * 3. JIT (Just-In-Time) Provisioning:
     *    - Sucht Kunden nach agent_id UND service1_customer_id
     *    - Erstellt neuen Kunden, falls nicht vorhanden
     *    - Aktualisiert bestehenden Kunden
     * 4. Loggt Kunden mit Auth::guard('customer')->login() ein
     * 5. Leitet zum Kunden-Dashboard weiter
     *
     * Workflow:
     * 1. Receives OTT as query parameter
     * 2. Retrieves and deletes claims from cache (Cache::pull)
     * 3. JIT (Just-In-Time) Provisioning:
     *    - Searches for customer by agent_id AND service1_customer_id
     *    - Creates new customer if not found
     *    - Updates existing customer
     * 4. Logs in customer with Auth::guard('customer')->login()
     * 5. Redirects to customer dashboard
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function handleLogin(Request $request): RedirectResponse
    {
        // Generate unique request ID for tracking this login flow
        $requestId = $this->ssoLogService->startRequest();

        Log::info('====== SSO LOGIN START (Service 2 - SP) ======', ['request_id' => $requestId]);
        Log::info('SSO: Received login request with OTT', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'has_ott' => $request->has('ott'),
            'ott_length' => $request->has('ott') ? strlen($request->get('ott')) : 0,
        ]);

        try {
            // STEP 1: Login request - Log incoming login request
            $stepStartTime = microtime(true);
            try {
                $this->ssoLogService->logStep(
                    requestId: $requestId,
                    step: 'login_request',
                    status: 'info',
                    data: [
                        'request_data' => [
                            'method' => $request->method(),
                            'url' => $request->fullUrl(),
                            'ip' => $request->ip(),
                            'has_ott' => $request->has('ott'),
                            'ott_length' => $request->has('ott') ? strlen($request->get('ott')) : 0,
                            'headers' => [
                                'user_agent' => $request->userAgent(),
                            ],
                        ],
                    ]
                );
                $this->ssoLogService->updateDuration($requestId, 'login_request', $stepStartTime);
            } catch (\Exception $logException) {
                Log::warning('Failed to log login_request step', [
                    'error' => $logException->getMessage(),
                    'request_id' => $requestId,
                ]);
            }

            // Validate OTT parameter / OTT-Parameter validieren
            $validated = $request->validate([
                'ott' => 'required|string|size:60',
            ]);

            $ott = $validated['ott'];
            $cacheKey = config('pdsauthint.ott_cache_prefix') . $ott;

            Log::info('SSO: Retrieving claims from cache', [
                'cache_key' => $cacheKey,
            ]);

            // STEP 2: OTT validation - Log OTT retrieval from cache
            $stepStartTime = microtime(true);

            try {
                // Retrieve and delete claims from cache (one-time use)
                // Claims aus Cache holen und löschen (einmalige Verwendung)
                $claims = Cache::pull($cacheKey);

                if (!$claims) {
                    Log::error('SSO: Invalid or expired OTT - not found in cache', [
                        'cache_key' => $cacheKey,
                        'ott_preview' => substr($ott, 0, 10) . '...'
                    ]);

                    // Log OTT validation failure
                    try {
                        $this->ssoLogService->logStep(
                            requestId: $requestId,
                            step: 'ott_validation',
                            status: 'error',
                            data: [
                                'ott' => substr($ott, 0, 15) . '...',
                                'request_data' => [
                                    'cache_key' => $cacheKey,
                                ],
                                'response_data' => [
                                    'ott_found' => false,
                                    'error' => 'OTT not found in cache or expired',
                                ],
                            ]
                        );
                        $this->ssoLogService->updateDuration($requestId, 'ott_validation', $stepStartTime);
                    } catch (\Exception $logException) {
                        Log::warning('Failed to log ott_validation error', [
                            'error' => $logException->getMessage(),
                            'request_id' => $requestId,
                        ]);
                    }

                    return redirect()->route('login')
                        ->withErrors(['error' => 'Invalid or expired login token. Please try again.']);
                }

                Log::info('SSO: Claims retrieved from cache', [
                    'sub' => $claims['sub'] ?? 'MISSING',
                    'agent_id' => $claims['agent_id'] ?? 'MISSING',
                    'email' => $claims['email'] ?? 'MISSING',
                ]);

                // Log successful OTT validation
                try {
                    $this->ssoLogService->logStep(
                        requestId: $requestId,
                        step: 'ott_validation',
                        status: 'success',
                        data: [
                            'ott' => substr($ott, 0, 15) . '...',
                            'request_data' => [
                                'cache_key' => $cacheKey,
                            ],
                            'response_data' => [
                                'ott_found' => true,
                                'sub' => $claims['sub'] ?? null,
                                'agent_id' => $claims['agent_id'] ?? null,
                                'email' => $claims['email'] ?? null,
                            ],
                        ]
                    );
                    $this->ssoLogService->updateDuration($requestId, 'ott_validation', $stepStartTime);
                } catch (\Exception $logException) {
                    Log::warning('Failed to log ott_validation step', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
            } catch (\Exception $e) {
                // Log OTT validation error
                try {
                    $this->ssoLogService->logError(
                        requestId: $requestId,
                        step: 'ott_validation',
                        error: $e,
                        trace: $e->getTraceAsString()
                    );
                } catch (\Exception $logException) {
                    Log::warning('Failed to log ott_validation error', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
                throw $e;
            }

            // Validate required claims / Erforderliche Claims validieren
            if (!isset($claims['sub']) || !isset($claims['agent_id'])) {
                Log::error('Missing required claims in OTT', ['claims' => $claims]);
                return redirect()->route('login')
                    ->withErrors(['error' => 'Invalid authentication data.']);
            }

            $service1CustomerId = $claims['sub']; // Customer ID from Service 1 (JWT subject)
            $agentId = $claims['agent_id']; // Agent/Agency ID from IdP

            Log::info('SSO: Starting JIT provisioning', [
                'service1_customer_id' => $service1CustomerId,
                'agent_id' => $agentId,
            ]);

            // STEP 3: Customer lookup - Log customer search by agent_id and service1_customer_id
            $stepStartTime = microtime(true);

            try {
                // JIT Provisioning: Find or create customer
                // JIT Provisioning: Kunden finden oder erstellen
                // Unique constraint: agent_id + service1_customer_id
                $customer = Customer::where('agent_id', $agentId)
                    ->where('service1_customer_id', $service1CustomerId)
                    ->first();

                // Log customer lookup result
                try {
                    $this->ssoLogService->logStep(
                        requestId: $requestId,
                        step: 'customer_lookup',
                        status: 'success',
                        data: [
                            'agent_id' => $agentId,
                            'service1_customer_id' => $service1CustomerId,
                            'request_data' => [
                                'search_criteria' => [
                                    'agent_id' => $agentId,
                                    'service1_customer_id' => $service1CustomerId,
                                ],
                            ],
                            'response_data' => [
                                'customer_found' => $customer !== null,
                                'customer_id' => $customer?->id,
                                'customer_name' => $customer?->name,
                                'customer_email' => $customer?->email,
                            ],
                        ]
                    );
                    $this->ssoLogService->updateDuration($requestId, 'customer_lookup', $stepStartTime);
                } catch (\Exception $logException) {
                    Log::warning('Failed to log customer_lookup step', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
            } catch (\Exception $e) {
                // Log customer lookup error
                try {
                    $this->ssoLogService->logError(
                        requestId: $requestId,
                        step: 'customer_lookup',
                        error: $e,
                        trace: $e->getTraceAsString()
                    );
                } catch (\Exception $logException) {
                    Log::warning('Failed to log customer_lookup error', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
                throw $e;
            }

            if ($customer) {
                // STEP 6: Customer update - Log if existing customer is updated
                $stepStartTime = microtime(true);

                Log::info('SSO: Existing customer found - updating', [
                    'customer_id' => $customer->id,
                    'name' => $customer->name,
                ]);

                try {
                    $oldData = [
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'address' => $customer->address,
                        'account_type' => $customer->account_type,
                    ];

                    $customer->update([
                        'email' => $claims['email'] ?? $customer->email,
                        'phone' => $claims['phone'] ?? $customer->phone,
                        'address' => $claims['address'] ?? $customer->address,
                        'account_type' => $claims['account_type'] ?? $customer->account_type,
                    ]);

                    Log::info('SSO: Customer updated successfully', [
                        'customer_id' => $customer->id,
                        'agent_id' => $agentId,
                        'service1_customer_id' => $service1CustomerId
                    ]);

                    // Log customer update
                    try {
                        $this->ssoLogService->logStep(
                            requestId: $requestId,
                            step: 'customer_update',
                            status: 'success',
                            data: [
                                'customer_id' => $customer->id,
                                'agent_id' => $agentId,
                                'service1_customer_id' => $service1CustomerId,
                                'request_data' => [
                                    'old_data' => $oldData,
                                    'new_data' => [
                                        'email' => $claims['email'] ?? null,
                                        'phone' => $claims['phone'] ?? null,
                                        'address' => $claims['address'] ?? null,
                                        'account_type' => $claims['account_type'] ?? null,
                                    ],
                                ],
                                'response_data' => [
                                    'customer_id' => $customer->id,
                                    'updated_successfully' => true,
                                ],
                            ]
                        );
                        $this->ssoLogService->updateDuration($requestId, 'customer_update', $stepStartTime);
                    } catch (\Exception $logException) {
                        Log::warning('Failed to log customer_update step', [
                            'error' => $logException->getMessage(),
                            'request_id' => $requestId,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log customer update error
                    try {
                        $this->ssoLogService->logError(
                            requestId: $requestId,
                            step: 'customer_update',
                            error: $e,
                            trace: $e->getTraceAsString()
                        );
                    } catch (\Exception $logException) {
                        Log::warning('Failed to log customer_update error', [
                            'error' => $logException->getMessage(),
                            'request_id' => $requestId,
                        ]);
                    }
                    throw $e;
                }
            } else {
                // STEP 5: Customer creation - Log if new customer is created (JIT provisioning)
                $stepStartTime = microtime(true);

                Log::info('SSO: No existing customer found - creating new customer');

                try {
                    $customer = Customer::create([
                        'agent_id' => $agentId,
                        'service1_customer_id' => $service1CustomerId,
                        'name' => $claims['email'] ?? 'SSO User',
                        'email' => $claims['email'] ?? null,
                        'phone' => $claims['phone'] ?? null,
                        'address' => $claims['address'] ?? null,
                        'account_type' => $claims['account_type'] ?? 'standard',
                        'password' => bcrypt(Str::random(32)), // Random password, not used for SSO login
                    ]);

                    Log::info('SSO: New customer created successfully', [
                        'customer_id' => $customer->id,
                        'agent_id' => $agentId,
                        'service1_customer_id' => $service1CustomerId,
                        'email' => $customer->email,
                    ]);

                    // Log customer creation
                    try {
                        $this->ssoLogService->logStep(
                            requestId: $requestId,
                            step: 'customer_creation',
                            status: 'success',
                            data: [
                                'customer_id' => $customer->id,
                                'agent_id' => $agentId,
                                'service1_customer_id' => $service1CustomerId,
                                'request_data' => [
                                    'customer_data' => [
                                        'agent_id' => $agentId,
                                        'service1_customer_id' => $service1CustomerId,
                                        'email' => $claims['email'] ?? null,
                                        'phone' => $claims['phone'] ?? null,
                                        'address' => $claims['address'] ?? null,
                                        'account_type' => $claims['account_type'] ?? 'standard',
                                    ],
                                ],
                                'response_data' => [
                                    'customer_id' => $customer->id,
                                    'created_successfully' => true,
                                    'jit_provisioning' => true,
                                ],
                            ]
                        );
                        $this->ssoLogService->updateDuration($requestId, 'customer_creation', $stepStartTime);
                    } catch (\Exception $logException) {
                        Log::warning('Failed to log customer_creation step', [
                            'error' => $logException->getMessage(),
                            'request_id' => $requestId,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log customer creation error
                    try {
                        $this->ssoLogService->logError(
                            requestId: $requestId,
                            step: 'customer_creation',
                            error: $e,
                            trace: $e->getTraceAsString()
                        );
                    } catch (\Exception $logException) {
                        Log::warning('Failed to log customer_creation error', [
                            'error' => $logException->getMessage(),
                            'request_id' => $requestId,
                        ]);
                    }
                    throw $e;
                }
            }

            // STEP 4: Login attempt - Log authentication attempt
            $stepStartTime = microtime(true);
            $guard = config('pdsauthint.customer_guard');

            Log::info('SSO: Logging in customer', [
                'customer_id' => $customer->id,
                'guard' => $guard,
            ]);

            try {
                Auth::guard($guard)->login($customer);

                Log::info('SSO: Customer logged in successfully', [
                    'customer_id' => $customer->id,
                    'guard' => $guard,
                    'is_authenticated' => Auth::guard($guard)->check(),
                ]);

                // Log successful login attempt
                try {
                    $this->ssoLogService->logStep(
                        requestId: $requestId,
                        step: 'login_attempt',
                        status: 'success',
                        data: [
                            'customer_id' => $customer->id,
                            'agent_id' => $agentId,
                            'service1_customer_id' => $service1CustomerId,
                            'request_data' => [
                                'guard' => $guard,
                                'customer_id' => $customer->id,
                            ],
                            'response_data' => [
                                'login_successful' => true,
                                'is_authenticated' => Auth::guard($guard)->check(),
                            ],
                        ]
                    );
                    $this->ssoLogService->updateDuration($requestId, 'login_attempt', $stepStartTime);
                } catch (\Exception $logException) {
                    Log::warning('Failed to log login_attempt step', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
            } catch (\Exception $e) {
                // Log login attempt error
                try {
                    $this->ssoLogService->logError(
                        requestId: $requestId,
                        step: 'login_attempt',
                        error: $e,
                        trace: $e->getTraceAsString()
                    );
                } catch (\Exception $logException) {
                    Log::warning('Failed to log login_attempt error', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
                throw $e;
            }

            // STEP 5: Session creation - Log session creation (implicit with login)
            $stepStartTime = microtime(true);

            try {
                // Log session creation
                try {
                    $this->ssoLogService->logStep(
                        requestId: $requestId,
                        step: 'session_creation',
                        status: 'success',
                        data: [
                            'customer_id' => $customer->id,
                            'response_data' => [
                                'session_created' => true,
                                'session_id' => session()->getId(),
                                'guard' => $guard,
                            ],
                        ]
                    );
                    $this->ssoLogService->updateDuration($requestId, 'session_creation', $stepStartTime);
                } catch (\Exception $logException) {
                    Log::warning('Failed to log session_creation step', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
            } catch (\Exception $e) {
                // Log session creation error
                try {
                    $this->ssoLogService->logError(
                        requestId: $requestId,
                        step: 'session_creation',
                        error: $e,
                        trace: $e->getTraceAsString()
                    );
                } catch (\Exception $logException) {
                    Log::warning('Failed to log session_creation error', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
                throw $e;
            }

            // STEP 6: Redirect - Log final redirect
            $stepStartTime = microtime(true);
            $dashboardRoute = config('pdsauthint.customer_dashboard_route');

            try {
                // Log redirect
                try {
                    $this->ssoLogService->logStep(
                        requestId: $requestId,
                        step: 'redirect',
                        status: 'success',
                        data: [
                            'customer_id' => $customer->id,
                            'response_data' => [
                                'redirect_route' => $dashboardRoute,
                                'redirect_url' => route($dashboardRoute),
                            ],
                        ]
                    );
                    $this->ssoLogService->updateDuration($requestId, 'redirect', $stepStartTime);
                } catch (\Exception $logException) {
                    Log::warning('Failed to log redirect step', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }

                // Redirect to customer dashboard / Zum Kunden-Dashboard weiterleiten
                return redirect()->route($dashboardRoute);
            } catch (\Exception $e) {
                // Log redirect error
                try {
                    $this->ssoLogService->logError(
                        requestId: $requestId,
                        step: 'redirect',
                        error: $e,
                        trace: $e->getTraceAsString()
                    );
                } catch (\Exception $logException) {
                    Log::warning('Failed to log redirect error', [
                        'error' => $logException->getMessage(),
                        'request_id' => $requestId,
                    ]);
                }
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation error with full stack trace
            try {
                $this->ssoLogService->logError(
                    requestId: $requestId,
                    step: 'login_request',
                    error: $e,
                    trace: $e->getTraceAsString()
                );
            } catch (\Exception $logException) {
                Log::warning('Failed to log login validation error', [
                    'error' => $logException->getMessage(),
                    'request_id' => $requestId,
                ]);
            }

            return redirect()->route('login')
                ->withErrors(['error' => 'Invalid login token format.']);
        } catch (\Exception $e) {
            Log::error('SSO login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $requestId,
            ]);

            // Log general error with full stack trace
            try {
                $this->ssoLogService->logError(
                    requestId: $requestId,
                    step: 'login_failed',
                    error: $e,
                    trace: $e->getTraceAsString()
                );
            } catch (\Exception $logException) {
                Log::warning('Failed to log login error', [
                    'error' => $logException->getMessage(),
                    'request_id' => $requestId,
                ]);
            }

            return redirect()->route('login')
                ->withErrors(['error' => 'An error occurred during login. Please try again.']);
        }
    }
}
