<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Services\KeycloakUserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportLegacyUsers extends Command
{
    protected $signature = 'import:legacy-users {--limit=0 : Limit number of users to import} {--dry-run : Only show what would be imported} {--last : Import die letzten statt die ersten User} {--email= : Nur einen bestimmten User importieren}';
    protected $description = 'Import users from webold_usersweb into customers table and Keycloak';

    private string $keycloakUrl;
    private string $realm;
    private ?string $adminToken = null;
    private int $tokenTime = 0;

    public function handle(): int
    {
        $this->keycloakUrl = config('services.keycloak.base_url', '');
        $this->realm = config('services.keycloak.realms', 'passolution');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $query = DB::table('webold_usersweb')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('active', '!=', 0);

        if ($email = $this->option('email')) {
            $query->where('email', $email);
        }

        $total = $query->count();
        $this->info("Found {$total} users with email in webold_usersweb");

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made');
        }

        // Get admin token for Keycloak
        if (! $dryRun && $this->keycloakUrl) {
            $this->adminToken = $this->getKeycloakAdminToken();
            $this->tokenTime = time();
            if (! $this->adminToken) {
                $this->error('Could not get Keycloak admin token. Continuing without Keycloak sync.');
            } else {
                $this->info('Keycloak admin token acquired');
            }
        }

        $users = $this->option('last') ? $query->orderByDesc('id') : $query->orderBy('id');
        if ($limit > 0) {
            $users = $users->limit($limit);
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($limit > 0 ? $limit : $total);
        $bar->start();

        $users->chunk(50, function ($chunk) use (&$imported, &$skipped, &$errors, $dryRun, $bar) {
            // Neuen Token vor jedem Chunk holen
            if (! $dryRun && $this->keycloakUrl) {
                $this->adminToken = $this->getKeycloakAdminToken();
                $this->tokenTime = time();
            }

            foreach ($chunk as $legacyUser) {
                $bar->advance();

                $email = strtolower(trim($legacyUser->email));
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    continue;
                }

                // Skip if customer already exists with this email
                if (Customer::where('email', $email)->exists()) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $imported++;
                    continue;
                }

                try {
                    $this->importUser($legacyUser, $email);
                    $imported++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Legacy user import failed', [
                        'legacy_id' => $legacyUser->id,
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Import complete: {$imported} imported, {$skipped} skipped, {$errors} errors");

        return self::SUCCESS;
    }

    private function importUser(object $legacyUser, string $email): void
    {
        $name = trim(($legacyUser->forename ?? '') . ' ' . ($legacyUser->surname ?? ''));
        if (empty($name)) {
            $name = $legacyUser->realname ?? $legacyUser->username ?? $email;
        }

        // Store a random bcrypt password (will be replaced on first login)
        // The actual MD5 hash goes into legacy_password_md5
        $bcryptPassword = Hash::make(\Illuminate\Support\Str::random(32));

        // Determine customer type
        $customerType = $legacyUser->accounttype == 1 ? 'private' : 'business';

        // Create Customer - use withoutEvents to prevent the booted() hook
        // from running (we'll create groups manually after)
        $customer = new Customer();
        $customer->fill([
            'name' => $name,
            'email' => $email,
            'password' => $bcryptPassword,
            'phone' => $legacyUser->phone ?? null,
            'customer_type' => $customerType,
            'company_name' => $legacyUser->realname ?? null,
            'company_street' => $legacyUser->street ?? null,
            'company_postal_code' => $legacyUser->zip ?? null,
            'company_city' => $legacyUser->city ?? null,
            'company_country' => $legacyUser->land ?? 'DE',
            'email_verified_at' => now(),
            'branch_management_active' => true,
        ]);
        // Save without triggering events (to avoid duplicate group creation for existing emails)
        $customer->saveQuietly();

        // Create default groups
        $adminGroup = EmployeeGroup::create([
            'customer_id' => $customer->id,
            'name' => 'Administratoren',
            'description' => 'Systemadministratoren in der Passolution Travel Information Platform',
            'is_system' => true,
        ]);

        EmployeeGroup::create([
            'customer_id' => $customer->id,
            'name' => 'Mitarbeiter',
            'description' => 'Mitarbeiter der Organisation',
        ]);

        // Create Employee entry for owner
        $ownerEmployee = Employee::create([
            'customer_id' => $customer->id,
            'first_name' => $legacyUser->forename ?? $name,
            'last_name' => $legacyUser->surname ?? '',
            'email' => $email,
            'phone' => $legacyUser->phone ?? '',
            'position' => 'Inhaber / Administrator',
            'is_active' => $legacyUser->active === '1',
        ]);

        $ownerEmployee->groups()->attach($adminGroup->id);

        // Store legacy MD5 hash for migration login
        if ($legacyUser->password) {
            DB::table('customers')
                ->where('id', $customer->id)
                ->update(['legacy_password_md5' => $legacyUser->password]);
        }

        // Sync to Keycloak
        if ($this->adminToken && $legacyUser->password) {
            $this->syncToKeycloak($customer, $legacyUser->password);
        }
    }

    private function syncToKeycloak(Customer $customer, string $md5Hash): void
    {
        $nameParts = explode(' ', $customer->name ?? '', 2);

        // Import user with MD5 credential via custom md5 password hash provider
        $importData = [
            'ifResourceExists' => 'SKIP',
            'users' => [
                [
                    'username' => $customer->email,
                    'email' => $customer->email,
                    'emailVerified' => true,
                    'enabled' => true,
                    'firstName' => $nameParts[0] ?? '',
                    'lastName' => $nameParts[1] ?? '',
                    'attributes' => [
                        'platform_customer_id' => [(string) $customer->id],
                        'legacy_id' => [(string) $customer->id],
                    ],
                    'credentials' => [
                        [
                            'type' => 'password',
                            'hashedSaltedValue' => $md5Hash,
                            'algorithm' => 'md5',
                            'hashIterations' => 1,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $this->refreshTokenIfNeeded();

            $response = Http::withToken($this->adminToken)
                ->timeout(10)
                ->post("{$this->keycloakUrl}/admin/realms/{$this->realm}/partialImport", $importData);

            // Token abgelaufen → erneuern und erneut versuchen
            if ($response->status() === 401) {
                $this->adminToken = $this->getKeycloakAdminToken();
                $this->tokenTime = time();
                $response = Http::withToken($this->adminToken)
                    ->timeout(10)
                    ->post("{$this->keycloakUrl}/admin/realms/{$this->realm}/partialImport", $importData);
            }

            if ($response->successful()) {
                $keycloakUserId = $response->json('results.0.id');

                if (! $keycloakUserId) {
                    $keycloakUserId = $this->getKeycloakUserId($customer->email);
                }

                if ($keycloakUserId) {
                    $customer->update([
                        'provider' => 'keycloak',
                        'provider_id' => $keycloakUserId,
                    ]);
                }
            } else {
                throw new \RuntimeException("Keycloak sync failed: {$response->status()}: {$response->body()}");
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException("Keycloak sync failed: {$e->getMessage()}");
        }
    }

    private function refreshTokenIfNeeded(): void
    {
        // Token alle 50 Sekunden erneuern (Keycloak default: 60 Sek Lebensdauer)
        if ($this->adminToken && (time() - $this->tokenTime) > 50) {
            $this->adminToken = $this->getKeycloakAdminToken();
            $this->tokenTime = time();
        }
    }

    private function getKeycloakAdminToken(): ?string
    {
        try {
            $response = Http::asForm()->timeout(10)->post(
                "{$this->keycloakUrl}/realms/master/protocol/openid-connect/token",
                [
                    'client_id' => 'admin-cli',
                    'username' => env('KEYCLOAK_ADMIN_USER', 'admin'),
                    'password' => env('KEYCLOAK_ADMIN_PASSWORD'),
                    'grant_type' => 'password',
                ]
            );

            return $response->successful() ? $response->json('access_token') : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getKeycloakUserId(string $email): ?string
    {
        try {
            $response = Http::withToken($this->adminToken)
                ->timeout(10)
                ->get("{$this->keycloakUrl}/admin/realms/{$this->realm}/users", [
                    'email' => $email,
                    'exact' => 'true',
                ]);

            if ($response->successful() && count($response->json()) > 0) {
                return $response->json()[0]['id'] ?? null;
            }
        } catch (\Exception $e) {
            // ignore
        }

        return null;
    }
}
