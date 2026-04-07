<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class KeycloakDeleteAllUsers extends Command
{
    protected $signature = 'keycloak:delete-all-users {--dry-run : Nur anzeigen, welche User gelöscht würden}';
    protected $description = 'Löscht alle Benutzer im Keycloak Realm';

    private string $baseUrl;
    private string $realm;
    private ?string $token = null;
    private int $tokenTime = 0;

    public function handle(): int
    {
        $this->baseUrl = config('services.keycloak.base_url');
        $this->realm = config('services.keycloak.realms', 'passolution');

        if (! $this->baseUrl) {
            $this->error('OIDC_BASE_URL ist nicht konfiguriert.');
            return 1;
        }

        $this->token = $this->getAdminToken();
        if (! $this->token) {
            return 1;
        }

        $this->info('Admin-Token erhalten.');

        if (! $this->option('dry-run') && ! $this->confirm('Wirklich ALLE User im Realm löschen?')) {
            $this->info('Abgebrochen.');
            return 0;
        }

        $deleted = 0;
        $errors = 0;
        $batch = 0;

        // In Batches von 100 laden und löschen
        while (true) {
            $this->refreshTokenIfNeeded();

            $usersResponse = Http::withToken($this->token)
                ->timeout(60)
                ->get("{$this->baseUrl}/admin/realms/{$this->realm}/users", [
                    'first' => 0,
                    'max' => 100,
                ]);

            if (! $usersResponse->successful()) {
                $this->error('Konnte User nicht laden: ' . $usersResponse->body());
                break;
            }

            $users = $usersResponse->json();

            if (empty($users)) {
                break;
            }

            $batch++;
            $this->info("Batch {$batch}: " . count($users) . " User laden...");

            foreach ($users as $user) {
                $email = $user['email'] ?? $user['username'] ?? 'unbekannt';

                if ($this->option('dry-run')) {
                    $this->line("  Würde löschen: {$email}");
                    $deleted++;
                    continue;
                }

                $this->refreshTokenIfNeeded();

                $response = Http::withToken($this->token)
                    ->timeout(10)
                    ->delete("{$this->baseUrl}/admin/realms/{$this->realm}/users/{$user['id']}");

                if ($response->successful()) {
                    $deleted++;
                } else {
                    $this->line("  <error>Fehler:</error> {$email} - {$response->status()}");
                    $errors++;
                }
            }

            $this->info("  {$deleted} gelöscht bisher...");

            // Dry-Run: nur ersten Batch anzeigen
            if ($this->option('dry-run')) {
                $this->info('[DRY-RUN] Weitere Batches übersprungen.');
                break;
            }
        }

        $prefix = $this->option('dry-run') ? '[DRY-RUN] ' : '';
        $this->newLine();
        $this->info("{$prefix}Ergebnis: {$deleted} gelöscht, {$errors} Fehler");

        return $errors > 0 ? 1 : 0;
    }

    private function refreshTokenIfNeeded(): void
    {
        if ((time() - $this->tokenTime) > 50) {
            $this->token = $this->getAdminToken();
            $this->tokenTime = time();
        }
    }

    private function getAdminToken(): ?string
    {
        $response = Http::asForm()->timeout(10)->post("{$this->baseUrl}/realms/master/protocol/openid-connect/token", [
            'client_id' => 'admin-cli',
            'username' => env('KEYCLOAK_ADMIN_USER', 'admin'),
            'password' => env('KEYCLOAK_ADMIN_PASSWORD'),
            'grant_type' => 'password',
        ]);

        if (! $response->successful()) {
            $this->error('Keycloak Admin-Login fehlgeschlagen: ' . $response->body());
            return null;
        }

        $this->tokenTime = time();
        return $response->json('access_token');
    }
}
