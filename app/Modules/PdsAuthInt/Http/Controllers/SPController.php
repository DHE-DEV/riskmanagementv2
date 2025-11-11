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
        try {
            // Validate request / Request validieren
            $validated = $request->validate([
                'jwt' => 'required|string',
            ]);

            $jwt = $validated['jwt'];

            // Load public key from environment or file / Öffentlichen Schlüssel aus Umgebung oder Datei laden
            $publicKeyConfig = config('pdsauthint.public_key');
            $useEnvKeys = config('pdsauthint.use_env_keys', true);

            // Check if key is provided directly in config (from env var)
            // Prüfen, ob Schlüssel direkt in Config bereitgestellt wird (aus Umgebungsvariable)
            if ($useEnvKeys && is_string($publicKeyConfig) && str_starts_with($publicKeyConfig, '-----BEGIN')) {
                // Key is directly from environment variable (PASSPORT_PUBLIC_KEY or SSO_PUBLIC_KEY)
                // Schlüssel stammt direkt aus Umgebungsvariable (PASSPORT_PUBLIC_KEY oder SSO_PUBLIC_KEY)
                $publicKey = $publicKeyConfig;

                Log::debug('SSO: Using public key from environment variable');
            } else {
                // Key is a file path / Schlüssel ist ein Dateipfad
                $publicKeyPath = $publicKeyConfig;

                if (!file_exists($publicKeyPath)) {
                    Log::error('SSO public key file not found', ['path' => $publicKeyPath]);
                    return response()->json([
                        'error' => 'Configuration error',
                        'message' => 'Public key not found'
                    ], 500);
                }

                $publicKey = file_get_contents($publicKeyPath);

                Log::debug('SSO: Using public key from file', ['path' => $publicKeyPath]);
            }

            if (!$publicKey) {
                Log::error('SSO: Failed to load public key');
                return response()->json([
                    'error' => 'Configuration error',
                    'message' => 'Could not load public key'
                ], 500);
            }

            // Decode and validate JWT / JWT dekodieren und validieren
            try {
                $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));
            } catch (\Exception $e) {
                Log::warning('JWT validation failed', [
                    'error' => $e->getMessage(),
                    'jwt' => substr($jwt, 0, 50) . '...'
                ]);
                return response()->json([
                    'error' => 'Invalid token',
                    'message' => 'JWT signature validation failed'
                ], 401);
            }

            // Verify issuer / Aussteller verifizieren
            $expectedIssuer = config('pdsauthint.jwt_issuer');
            if (!isset($decoded->iss) || $decoded->iss !== $expectedIssuer) {
                Log::warning('JWT issuer mismatch', [
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
            if (!isset($decoded->aud) || $decoded->aud !== $expectedAudience) {
                Log::warning('JWT audience mismatch', [
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
            $ott = Str::random(60);

            // Store claims in cache / Claims im Cache speichern
            $cacheKey = config('pdsauthint.ott_cache_prefix') . $ott;
            $cacheTtl = config('pdsauthint.ott_ttl');

            // Convert JWT payload to array for storage / JWT-Payload für Speicherung in Array umwandeln
            $claims = json_decode(json_encode($decoded), true);

            Cache::put($cacheKey, $claims, $cacheTtl);

            Log::info('OTT generated successfully', [
                'agent_id' => $claims['sub'] ?? 'unknown',
                'customer_id' => $claims['customer_id'] ?? 'unknown',
                'ttl' => $cacheTtl
            ]);

            // Generate redirect URL / Redirect-URL generieren
            $redirectUrl = route('sso.login', ['ott' => $ott]);

            return response()->json([
                'success' => true,
                'ott' => $ott,
                'redirect_url' => $redirectUrl,
                'expires_in' => $cacheTtl
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Token exchange failed', [
                'error' => $e->getMessage(),
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
        try {
            // Validate OTT parameter / OTT-Parameter validieren
            $validated = $request->validate([
                'ott' => 'required|string|size:60',
            ]);

            $ott = $validated['ott'];
            $cacheKey = config('pdsauthint.ott_cache_prefix') . $ott;

            // Retrieve and delete claims from cache (one-time use)
            // Claims aus Cache holen und löschen (einmalige Verwendung)
            $claims = Cache::pull($cacheKey);

            if (!$claims) {
                Log::warning('Invalid or expired OTT', ['ott' => substr($ott, 0, 10) . '...']);
                return redirect()->route('login')
                    ->withErrors(['error' => 'Invalid or expired login token. Please try again.']);
            }

            // Validate required claims / Erforderliche Claims validieren
            if (!isset($claims['sub']) || !isset($claims['customer_id'])) {
                Log::error('Missing required claims in OTT', ['claims' => $claims]);
                return redirect()->route('login')
                    ->withErrors(['error' => 'Invalid authentication data.']);
            }

            $agentId = $claims['sub']; // Agent ID from IdP
            $service1CustomerId = $claims['customer_id']; // Customer ID from Service 1

            // JIT Provisioning: Find or create customer
            // JIT Provisioning: Kunden finden oder erstellen
            // Unique constraint: agent_id + service1_customer_id
            $customer = Customer::where('agent_id', $agentId)
                ->where('service1_customer_id', $service1CustomerId)
                ->first();

            if ($customer) {
                // Update existing customer / Bestehenden Kunden aktualisieren
                $customer->update([
                    'name' => $claims['name'] ?? $customer->name,
                    'email' => $claims['email'] ?? $customer->email,
                    'phone' => $claims['phone'] ?? $customer->phone,
                    'address' => $claims['address'] ?? $customer->address,
                    'account_type' => $claims['account_type'] ?? $customer->account_type,
                ]);

                Log::info('Customer updated via SSO', [
                    'customer_id' => $customer->id,
                    'agent_id' => $agentId,
                    'service1_customer_id' => $service1CustomerId
                ]);
            } else {
                // Create new customer / Neuen Kunden erstellen
                $customer = Customer::create([
                    'agent_id' => $agentId,
                    'service1_customer_id' => $service1CustomerId,
                    'name' => $claims['name'] ?? 'Unknown',
                    'email' => $claims['email'] ?? null,
                    'phone' => $claims['phone'] ?? null,
                    'address' => $claims['address'] ?? null,
                    'account_type' => $claims['account_type'] ?? 'standard',
                    'password' => bcrypt(Str::random(32)), // Random password, not used for SSO login
                ]);

                Log::info('New customer created via SSO', [
                    'customer_id' => $customer->id,
                    'agent_id' => $agentId,
                    'service1_customer_id' => $service1CustomerId
                ]);
            }

            // Log in customer / Kunden einloggen
            $guard = config('pdsauthint.customer_guard');
            Auth::guard($guard)->login($customer);

            Log::info('Customer logged in via SSO', [
                'customer_id' => $customer->id,
                'guard' => $guard
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
