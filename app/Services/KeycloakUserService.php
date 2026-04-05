<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KeycloakUserService
{
    private string $baseUrl;
    private string $realm;

    public function __construct()
    {
        $this->baseUrl = Config::get('services.keycloak.base_url', '');
        $this->realm = Config::get('services.keycloak.realms', 'passolution');
    }

    /**
     * Sync a customer to Keycloak (create or update).
     * Returns the Keycloak user ID on success, null on failure.
     */
    public function syncCustomer(Customer $customer): ?string
    {
        if (!$this->baseUrl) {
            Log::warning('KeycloakUserService: OIDC_BASE_URL not configured');
            return null;
        }

        $token = $this->getAdminToken();
        if (!$token) {
            return null;
        }

        $nameParts = explode(' ', $customer->name ?? '', 2);
        $hash = $customer->password ? str_replace('$2y$', '$2a$', $customer->password) : null;

        $importData = [
            'ifResourceExists' => 'SKIP',
            'users' => [
                [
                    'username' => $customer->email,
                    'email' => $customer->email,
                    'emailVerified' => $customer->hasVerifiedEmail(),
                    'enabled' => true,
                    'firstName' => $nameParts[0] ?? '',
                    'lastName' => $nameParts[1] ?? '',
                    'attributes' => [
                        'platform_customer_id' => [(string) $customer->id],
                    ],
                    'credentials' => $hash ? [
                        [
                            'type' => 'password',
                            'hashedSaltedValue' => $hash,
                            'algorithm' => 'bcrypt',
                            'hashIterations' => 10,
                        ],
                    ] : [],
                ],
            ],
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/admin/realms/{$this->realm}/partialImport", $importData);

        if (!$response->successful()) {
            Log::error('Keycloak user sync failed', [
                'email' => $customer->email,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return null;
        }

        $action = $response->json('results.0.action') ?? 'UNKNOWN';
        $keycloakUserId = $response->json('results.0.id');

        if (!$keycloakUserId && ($action === 'ADDED' || $action === 'SKIPPED')) {
            $keycloakUserId = $this->getUserId($token, $customer->email);
        }

        if ($keycloakUserId) {
            $customer->update([
                'provider' => 'keycloak',
                'provider_id' => $keycloakUserId,
            ]);

            Log::info('Customer synced to Keycloak', [
                'email' => $customer->email,
                'customer_id' => $customer->id,
                'keycloak_id' => $keycloakUserId,
                'action' => $action,
            ]);
        }

        return $keycloakUserId;
    }

    private function getAdminToken(): ?string
    {
        $response = Http::asForm()->post("{$this->baseUrl}/realms/master/protocol/openid-connect/token", [
            'client_id' => 'admin-cli',
            'username' => env('KEYCLOAK_ADMIN_USER', 'admin'),
            'password' => env('KEYCLOAK_ADMIN_PASSWORD'),
            'grant_type' => 'password',
        ]);

        if (!$response->successful()) {
            Log::error('Keycloak admin login failed: ' . $response->body());
            return null;
        }

        return $response->json('access_token');
    }

    private function getUserId(string $token, string $email): ?string
    {
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/admin/realms/{$this->realm}/users", [
                'email' => $email,
                'exact' => 'true',
            ]);

        if ($response->successful() && count($response->json()) > 0) {
            return $response->json()[0]['id'] ?? null;
        }

        return null;
    }
}
