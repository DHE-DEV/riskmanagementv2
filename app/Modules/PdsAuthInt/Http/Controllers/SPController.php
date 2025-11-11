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
        Log::info('====== SSO EXCHANGE START (Service 2 - SP) ======');
        Log::info('SSO: Received JWT exchange request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'has_jwt' => $request->has('jwt'),
        ]);

        try {
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

            // Decode and validate JWT / JWT dekodieren und validieren
            Log::info('SSO: Attempting to decode JWT with RS256');

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
            } catch (\Exception $e) {
                Log::error('SSO: JWT decode failed', [
                    'error' => $e->getMessage(),
                    'exception_class' => get_class($e),
                    'jwt_preview' => substr($jwt, 0, 50) . '...',
                ]);
                return response()->json([
                    'error' => 'Invalid token',
                    'message' => 'JWT signature validation failed'
                ], 401);
            }

            // Verify issuer / Aussteller verifizieren
            $expectedIssuer = config('pdsauthint.jwt_issuer');
            Log::info('SSO: Verifying JWT issuer', [
                'expected' => $expectedIssuer,
                'received' => $decoded->iss ?? 'null',
            ]);

            if (!isset($decoded->iss) || $decoded->iss !== $expectedIssuer) {
                Log::error('SSO: JWT issuer mismatch', [
                    'expected' => $expectedIssuer,
                    'received' => $decoded->iss ?? 'null'
                ]);
                return response()->json([
                    'error' => 'Invalid token',
                    'message' => 'Invalid issuer'
                ], 401);
            }

            // Verify audience / Ziel verifizieren
            $expectedAudience = config('pdsauthint.jwt_audience');
            Log::info('SSO: Verifying JWT audience', [
                'expected' => $expectedAudience,
                'received' => $decoded->aud ?? 'null',
            ]);

            if (!isset($decoded->aud) || $decoded->aud !== $expectedAudience) {
                Log::error('SSO: JWT audience mismatch', [
                    'expected' => $expectedAudience,
                    'received' => $decoded->aud ?? 'null'
                ]);
                return response()->json([
                    'error' => 'Invalid token',
                    'message' => 'Invalid audience'
                ], 401);
            }

            // Verify expiration / Ablaufzeit verifizieren
            if (!isset($decoded->exp) || $decoded->exp < time()) {
                Log::warning('JWT expired', [
                    'exp' => $decoded->exp ?? 'null',
                    'now' => time()
                ]);
                return response()->json([
                    'error' => 'Invalid token',
                    'message' => 'Token expired'
                ], 401);
            }

            // Generate One-Time Token (OTT) / One-Time Token (OTT) generieren
            // 60 Zeichen für erhöhte Sicherheit / 60 characters for increased security
            Log::info('SSO: Generating OTT');
            $ott = Str::random(60);

            // Store claims in cache / Claims im Cache speichern
            $cacheKey = config('pdsauthint.ott_cache_prefix') . $ott;
            $cacheTtl = config('pdsauthint.ott_ttl');

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

            // Generate redirect URL / Redirect-URL generieren
            $redirectUrl = route('pdsauthint.login', ['ott' => $ott]);

            Log::info('====== SSO EXCHANGE SUCCESS (Service 2 - SP) ======', [
                'redirect_url' => $redirectUrl,
            ]);

            return response()->json([
                'success' => true,
                'ott' => $ott,
                'redirect_url' => $redirectUrl,
                'expires_in' => $cacheTtl
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('====== SSO EXCHANGE FAILED (Service 2 - SP) ======');
            Log::error('SSO: Validation failed', [
                'error' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('====== SSO EXCHANGE FAILED (Service 2 - SP) ======');
            Log::error('SSO: Token exchange failed', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
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
        Log::info('====== SSO LOGIN START (Service 2 - SP) ======');
        Log::info('SSO: Received login request with OTT', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'has_ott' => $request->has('ott'),
            'ott_length' => $request->has('ott') ? strlen($request->get('ott')) : 0,
        ]);

        try {
            // Validate OTT parameter / OTT-Parameter validieren
            $validated = $request->validate([
                'ott' => 'required|string|size:60',
            ]);

            $ott = $validated['ott'];
            $cacheKey = config('pdsauthint.ott_cache_prefix') . $ott;

            Log::info('SSO: Retrieving claims from cache', [
                'cache_key' => $cacheKey,
            ]);

            // Retrieve and delete claims from cache (one-time use)
            // Claims aus Cache holen und löschen (einmalige Verwendung)
            $claims = Cache::pull($cacheKey);

            if (!$claims) {
                Log::error('SSO: Invalid or expired OTT - not found in cache', [
                    'cache_key' => $cacheKey,
                    'ott_preview' => substr($ott, 0, 10) . '...'
                ]);
                return redirect()->route('login')
                    ->withErrors(['error' => 'Invalid or expired login token. Please try again.']);
            }

            Log::info('SSO: Claims retrieved from cache', [
                'sub' => $claims['sub'] ?? 'MISSING',
                'agent_id' => $claims['agent_id'] ?? 'MISSING',
                'email' => $claims['email'] ?? 'MISSING',
            ]);

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

            // JIT Provisioning: Find or create customer
            // JIT Provisioning: Kunden finden oder erstellen
            // Unique constraint: agent_id + service1_customer_id
            $customer = Customer::where('agent_id', $agentId)
                ->where('service1_customer_id', $service1CustomerId)
                ->first();

            if ($customer) {
                // Update existing customer / Bestehenden Kunden aktualisieren
                Log::info('SSO: Existing customer found - updating', [
                    'customer_id' => $customer->id,
                    'name' => $customer->name,
                ]);

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
            } else {
                // Create new customer / Neuen Kunden erstellen
                // Use email as name fallback since 'name' claim is not provided by IdP
                Log::info('SSO: No existing customer found - creating new customer');

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
            }

            // Log in customer / Kunden einloggen
            $guard = config('pdsauthint.customer_guard');

            Log::info('SSO: Logging in customer', [
                'customer_id' => $customer->id,
                'guard' => $guard,
            ]);

            Auth::guard($guard)->login($customer);

            Log::info('SSO: Customer logged in successfully', [
                'customer_id' => $customer->id,
                'guard' => $guard,
                'is_authenticated' => Auth::guard($guard)->check(),
            ]);

            // Redirect to customer dashboard / Zum Kunden-Dashboard weiterleiten
            $dashboardRoute = config('pdsauthint.customer_dashboard_route');
            return redirect()->route($dashboardRoute);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Invalid login token format.']);
        } catch (\Exception $e) {
            Log::error('SSO login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('login')
                ->withErrors(['error' => 'An error occurred during login. Please try again.']);
        }
    }
}
