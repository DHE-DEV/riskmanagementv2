<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MigrateCustomersToKeycloak extends Command
{
    protected $signature = 'customers:migrate-to-keycloak
                            {--dry-run : Nur anzeigen, was migriert würde}
                            {--limit= : Maximale Anzahl zu migrierender Kunden}
                            {--email= : Nur einen bestimmten Kunden migrieren}';

    protected $description = 'Migriert bestehende Kunden (mit Passwort) aus der DB nach Keycloak';

    private string $baseUrl;
    private string $realm;
    private ?string $adminToken = null;

    public function handle(): int
    {
        $this->baseUrl = config('services.keycloak.base_url');
        $this->realm = config('services.keycloak.realms', 'passolution');

        if (!$this->baseUrl) {
            $this->error('OIDC_BASE_URL ist nicht konfiguriert.');
            return 1;
        }

        // Admin-Token holen
        $this->adminToken = $this->getAdminToken();
        if (!$this->adminToken) {
            $this->error('Konnte kein Admin-Token von Keycloak holen. Prüfe KEYCLOAK_ADMIN_* Variablen.');
            return 1;
        }

        // Kunden mit Passwort laden (keine Social-Login-Accounts)
        $query = Customer::whereNotNull('password')
            ->whereNotNull('email')
            ->whereNull('deleted_at');

        if ($email = $this->option('email')) {
            $query->where('email', $email);
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $customers = $query->get();

        $this->info("Gefunden: {$customers->count()} Kunden mit Passwort");

        if ($customers->isEmpty()) {
            $this->info('Keine Kunden zum Migrieren gefunden.');
            return 0;
        }

        $created = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        foreach ($customers as $customer) {
            $bar->advance();

            // Prüfen ob User bereits in Keycloak existiert
            if ($this->keycloakUserExists($customer->email)) {
                $this->line(" <comment>Übersprungen:</comment> {$customer->email} (existiert bereits)");
                $skipped++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line(" <info>Würde migrieren:</info> {$customer->email} ({$customer->name})");
                $created++;
                continue;
            }

            // User in Keycloak anlegen
            $result = $this->createKeycloakUser($customer);

            if ($result) {
                $created++;
            } else {
                $errors++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $prefix = $this->option('dry-run') ? '[DRY-RUN] ' : '';
        $this->info("{$prefix}Ergebnis: {$created} migriert, {$skipped} übersprungen, {$errors} Fehler");

        return $errors > 0 ? 1 : 0;
    }

    private function getAdminToken(): ?string
    {
        $adminUser = env('KEYCLOAK_ADMIN_USER', 'admin');
        $adminPassword = env('KEYCLOAK_ADMIN_PASSWORD');

        if (!$adminPassword) {
            $this->error('KEYCLOAK_ADMIN_PASSWORD ist nicht in .env gesetzt.');
            return null;
        }

        $response = Http::asForm()->post("{$this->baseUrl}/realms/master/protocol/openid-connect/token", [
            'client_id' => 'admin-cli',
            'username' => $adminUser,
            'password' => $adminPassword,
            'grant_type' => 'password',
        ]);

        if (!$response->successful()) {
            $this->error('Keycloak Admin-Login fehlgeschlagen: ' . $response->body());
            return null;
        }

        return $response->json('access_token');
    }

    private function keycloakUserExists(string $email): bool
    {
        $response = Http::withToken($this->adminToken)
            ->get("{$this->baseUrl}/admin/realms/{$this->realm}/users", [
                'email' => $email,
                'exact' => 'true',
            ]);

        return $response->successful() && count($response->json()) > 0;
    }

    private function getKeycloakUserId(string $email): ?string
    {
        $response = Http::withToken($this->adminToken)
            ->get("{$this->baseUrl}/admin/realms/{$this->realm}/users", [
                'email' => $email,
                'exact' => 'true',
            ]);

        if ($response->successful() && count($response->json()) > 0) {
            return $response->json()[0]['id'] ?? null;
        }

        return null;
    }

    private function createKeycloakUser(Customer $customer): bool
    {
        // Name aufteilen
        $nameParts = explode(' ', $customer->name ?? '', 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        // Keycloak User-Payload
        $userData = [
            'username' => $customer->email,
            'email' => $customer->email,
            'emailVerified' => $customer->hasVerifiedEmail(),
            'enabled' => true,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'attributes' => [
                'platform_customer_id' => [(string) $customer->id],
                'company_name' => [$customer->company_name ?? ''],
                'customer_type' => [$customer->customer_type ?? ''],
            ],
        ];

        // Bcrypt-Hash direkt übertragen (Keycloak unterstützt das)
        if ($customer->password) {
            $userData['credentials'] = [
                [
                    'type' => 'password',
                    'credentialData' => json_encode([
                        'hashIterations' => 10,
                        'algorithm' => 'bcrypt',
                    ]),
                    'secretData' => json_encode([
                        'value' => $customer->password,
                    ]),
                ],
            ];
        }

        $response = Http::withToken($this->adminToken)
            ->post("{$this->baseUrl}/admin/realms/{$this->realm}/users", $userData);

        if ($response->status() === 201) {
            // Keycloak-User-ID aus Location-Header extrahieren
            $location = $response->header('Location');
            $keycloakUserId = $location ? basename($location) : null;

            // Falls kein Location-Header, User per API suchen
            if (!$keycloakUserId) {
                $keycloakUserId = $this->getKeycloakUserId($customer->email);
            }

            // Provider-Daten im Customer speichern
            if ($keycloakUserId) {
                $customer->update([
                    'provider' => 'keycloak',
                    'provider_id' => $keycloakUserId,
                ]);
                $this->line(" <info>Migriert:</info> {$customer->email} → Keycloak ID: {$keycloakUserId}");
            } else {
                $this->line(" <info>Migriert:</info> {$customer->email} (Keycloak-ID konnte nicht ermittelt werden)");
            }

            Log::info('Customer migrated to Keycloak', [
                'email' => $customer->email,
                'customer_id' => $customer->id,
                'keycloak_id' => $keycloakUserId,
            ]);
            return true;
        }

        if ($response->status() === 409) {
            $this->line(" <comment>Übersprungen:</comment> {$customer->email} (existiert bereits)");
            return true;
        }

        $this->line(" <error>Fehler:</error> {$customer->email} - {$response->status()}: {$response->body()}");
        Log::error('Failed to migrate customer to Keycloak', [
            'email' => $customer->email,
            'status' => $response->status(),
            'response' => $response->body(),
        ]);
        return false;
    }
}
