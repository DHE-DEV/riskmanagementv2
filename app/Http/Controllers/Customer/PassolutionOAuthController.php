<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PassolutionOAuthController extends Controller
{
    /**
     * Redirect to Passolution OAuth authorization
     */
    public function redirect()
    {
        $clientId = config('services.passolution.client_id');
        $redirectUri = url('/customer/passolution/callback');

        // Generate and store state for CSRF protection
        $state = Str::random(40);
        session(['passolution_oauth_state' => $state]);

        $authorizeUrl = config('services.passolution.oauth_authorize_url');
        $authUrl = $authorizeUrl . '?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
            // Add scope if required by Passolution API
            // 'scope' => 'read write',
        ]);

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback from Passolution
     */
    public function callback(Request $request)
    {
        // Verify state to prevent CSRF
        $state = session('passolution_oauth_state');
        if (!$state || $state !== $request->state) {
            return redirect()->route('customer.dashboard')
                ->with('error', 'OAuth-Fehler: Ungültiger Status. Bitte versuchen Sie es erneut.');
        }

        // Clear state from session
        session()->forget('passolution_oauth_state');

        // Check for errors
        if ($request->has('error')) {
            return redirect()->route('customer.dashboard')
                ->with('error', 'OAuth-Fehler: ' . $request->error_description ?? $request->error);
        }

        // Exchange authorization code for access token
        try {
            // Try with Basic Auth first (most common for OAuth2)
            $tokenUrl = config('services.passolution.oauth_token_url');
            $response = Http::withBasicAuth(
                config('services.passolution.client_id'),
                config('services.passolution.client_secret')
            )->asForm()->post($tokenUrl, [
                'grant_type' => 'authorization_code',
                'redirect_uri' => url('/customer/passolution/callback'),
                'code' => $request->code,
            ]);

            if (!$response->successful()) {
                \Log::error('Passolution OAuth token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return redirect()->route('customer.dashboard')
                    ->with('error', 'Fehler beim Austausch des Authorization Codes. Bitte versuchen Sie es erneut.');
            }

            $tokenData = $response->json();

            // Calculate expiration dates
            $tokenExpiresAt = now()->addSeconds($tokenData['expires_in'] ?? 3600);
            // Refresh token expires 6 months after access token expiration
            $refreshTokenExpiresAt = $tokenExpiresAt->copy()->addMonths(6);

            // Store tokens in customer record
            $customer = auth('customer')->user();
            $customer->update([
                'passolution_access_token' => $tokenData['access_token'],
                'passolution_token_expires_at' => $tokenExpiresAt,
                'passolution_refresh_token' => $tokenData['refresh_token'] ?? null,
                'passolution_refresh_token_expires_at' => $refreshTokenExpiresAt,
            ]);

            return redirect()->route('customer.dashboard')
                ->with('success', 'Passolution-Integration erfolgreich aktiviert!');

        } catch (\Exception $e) {
            \Log::error('Passolution OAuth callback error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('customer.dashboard')
                ->with('error', 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
        }
    }

    /**
     * Disconnect Passolution integration
     */
    public function disconnect()
    {
        $customer = auth('customer')->user();
        $customer->update([
            'passolution_access_token' => null,
            'passolution_token_expires_at' => null,
            'passolution_refresh_token' => null,
            'passolution_refresh_token_expires_at' => null,
        ]);

        return redirect()->route('customer.dashboard')
            ->with('success', 'Passolution-Integration wurde deaktiviert.');
    }

    /**
     * Refresh the access token using refresh token
     */
    public function refreshToken()
    {
        $customer = auth('customer')->user();

        if (!$customer->passolution_refresh_token) {
            return response()->json([
                'success' => false,
                'message' => 'Kein Refresh Token vorhanden'
            ], 400);
        }

        try {
            $refreshUrl = config('services.passolution.oauth_refresh_url');
            $response = Http::withBasicAuth(
                config('services.passolution.client_id'),
                config('services.passolution.client_secret')
            )->asForm()->post($refreshUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $customer->passolution_refresh_token,
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token refresh fehlgeschlagen'
                ], 400);
            }

            $tokenData = $response->json();

            // Calculate new expiration dates
            $tokenExpiresAt = now()->addSeconds($tokenData['expires_in'] ?? 3600);
            $refreshTokenExpiresAt = $tokenExpiresAt->copy()->addMonths(6);

            // Update tokens
            $customer->update([
                'passolution_access_token' => $tokenData['access_token'],
                'passolution_token_expires_at' => $tokenExpiresAt,
                'passolution_refresh_token' => $tokenData['refresh_token'] ?? $customer->passolution_refresh_token,
                'passolution_refresh_token_expires_at' => $refreshTokenExpiresAt,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token erfolgreich erneuert'
            ]);

        } catch (\Exception $e) {
            \Log::error('Passolution token refresh error', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ein Fehler ist aufgetreten'
            ], 500);
        }
    }
}
