<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class KeycloakDeleteAllUsers extends Command
{
    protected $signature = 'keycloak:delete-all-users {--dry-run : Nur anzeigen, welche User gelöscht würden}';
    protected $description = 'Löscht alle Benutzer im Keycloak Realm';

    public function handle(): int
    {
        $baseUrl = config('services.keycloak.base_url');
        $realm = config('services.keycloak.realms', 'passolution');

        if (! $baseUrl) {
            $this->error('OIDC_BASE_URL ist nicht konfiguriert.');
            return 1;
        }

        // Admin-Token holen
        $tokenResponse = Http::asForm()->post("{$baseUrl}/realms/master/protocol/openid-connect/token", [
            'client_id' => 'admin-cli',
            'username' => env('KEYCLOAK_ADMIN_USER', 'admin'),
            'password' => env('KEYCLOAK_ADMIN_PASSWORD'),
            'grant_type' => 'password',
        ]);

        if (! $tokenResponse->successful()) {
            $this->error('Keycloak Admin-Login fehlgeschlagen: ' . $tokenResponse->body());
            return 1;
        }

        $token = $tokenResponse->json('access_token');
        $this->info('Admin-Token erhalten.');

        // Alle User laden
        $usersResponse = Http::withToken($token)
            ->get("{$baseUrl}/admin/realms/{$realm}/users", ['max' => 10000]);

        if (! $usersResponse->successful()) {
            $this->error('Konnte User nicht laden: ' . $usersResponse->body());
            return 1;
        }

        $users = $usersResponse->json();
        $this->info("Gefunden: " . count($users) . " User im Realm '{$realm}'");

        if (empty($users)) {
            $this->info('Keine User zum Löschen.');
            return 0;
        }

        if (! $this->option('dry-run') && ! $this->confirm('Wirklich ALLE User im Realm löschen?')) {
            $this->info('Abgebrochen.');
            return 0;
        }

        $deleted = 0;
        $errors = 0;

        foreach ($users as $user) {
            $email = $user['email'] ?? $user['username'] ?? 'unbekannt';

            if ($this->option('dry-run')) {
                $this->line("  Würde löschen: {$email} ({$user['id']})");
                $deleted++;
                continue;
            }

            $response = Http::withToken($token)
                ->delete("{$baseUrl}/admin/realms/{$realm}/users/{$user['id']}");

            if ($response->successful()) {
                $this->line("  <info>Gelöscht:</info> {$email}");
                $deleted++;
            } else {
                $this->line("  <error>Fehler:</error> {$email} - {$response->status()}");
                $errors++;
            }
        }

        $prefix = $this->option('dry-run') ? '[DRY-RUN] ' : '';
        $this->newLine();
        $this->info("{$prefix}Ergebnis: {$deleted} gelöscht, {$errors} Fehler");

        return $errors > 0 ? 1 : 0;
    }
}
