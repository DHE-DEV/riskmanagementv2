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
    protected $description = 'Import users from webold_usersweb as employees into existing customers (matched via assignto → legacy_client_account_id)';

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
        $this->info("Found {$total} active users with email in webold_usersweb");

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made');
        }

        // Pre-load customers by legacy_client_account_id for fast lookup
        $customersByLegacyId = Customer::whereNotNull('legacy_client_account_id')
            ->get()
            ->keyBy('legacy_client_account_id');

        $this->info("Loaded {$customersByLegacyId->count()} customers with legacy_client_account_id");

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

        $employeesCreated = 0;
        $employeesUpdated = 0;
        $skipped = 0;
        $noCustomer = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($limit > 0 ? $limit : $total);
        $bar->start();

        $users->chunk(50, function ($chunk) use (&$employeesCreated, &$employeesUpdated, &$skipped, &$noCustomer, &$errors, $dryRun, $bar, $customersByLegacyId) {
            // Refresh Keycloak token before each chunk
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

                // Find parent customer via assignto → legacy_client_account_id
                $parentCustomer = null;
                if (! empty($legacyUser->assignto)) {
                    $parentCustomer = $customersByLegacyId->get($legacyUser->assignto);
                }

                if (! $parentCustomer) {
                    $noCustomer++;
                    continue;
                }

                // If employee with this email already exists under this customer, update legacy ID + Keycloak sync
                $existingEmployee = Employee::where('customer_id', $parentCustomer->id)->where('email', $email)->first();
                if ($existingEmployee) {
                    if ($dryRun) {
                        $employeesUpdated++;
                        continue;
                    }

                    try {
                        $existingEmployee->update([
                            'legacy_usersweb_id' => $legacyUser->id,
                        ]);

                        if ($this->adminToken && $legacyUser->password) {
                            $this->syncToKeycloak($existingEmployee, $parentCustomer, $legacyUser->password);
                        }

                        $employeesUpdated++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('Legacy user update failed', [
                            'legacy_id' => $legacyUser->id,
                            'email' => $email,
                            'employee_id' => $existingEmployee->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    continue;
                }

                if ($dryRun) {
                    $employeesCreated++;
                    continue;
                }

                try {
                    $this->importAsEmployee($legacyUser, $email, $parentCustomer);
                    $employeesCreated++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Legacy user import failed', [
                        'legacy_id' => $legacyUser->id,
                        'email' => $email,
                        'customer_id' => $parentCustomer->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Import complete: {$employeesCreated} created, {$employeesUpdated} updated (legacy ID + Keycloak), {$skipped} skipped, {$noCustomer} no matching customer, {$errors} errors");

        return self::SUCCESS;
    }

    private function importAsEmployee(object $legacyUser, string $email, Customer $customer): void
    {
        // Create Employee under parent customer
        $employee = Employee::create([
            'customer_id' => $customer->id,
            'first_name' => $legacyUser->forename ?? '',
            'last_name' => $legacyUser->surname ?? '',
            'email' => $email,
            'phone' => $legacyUser->phone ?? '',
            'position' => '',
            'is_active' => $legacyUser->active == 1,
            'legacy_usersweb_id' => $legacyUser->id,
        ]);

        // Assign to "Mitarbeiter" group
        $staffGroup = EmployeeGroup::where('customer_id', $customer->id)
            ->where('name', 'Mitarbeiter')
            ->first();

        if ($staffGroup) {
            $employee->groups()->attach($staffGroup->id);
        }

        // Sync to Keycloak
        if ($this->adminToken && $legacyUser->password) {
            $this->syncToKeycloak($employee, $customer, $legacyUser->password);
        }
    }

    private function syncToKeycloak(Employee $employee, Customer $customer, string $md5Hash): void
    {
        $importData = [
            'ifResourceExists' => 'SKIP',
            'users' => [
                [
                    'username' => $employee->email,
                    'email' => $employee->email,
                    'emailVerified' => true,
                    'enabled' => true,
                    'firstName' => $employee->first_name,
                    'lastName' => $employee->last_name,
                    'attributes' => [
                        'platform_customer_id' => [(string) $customer->id],
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

            if ($response->status() === 401) {
                $this->adminToken = $this->getKeycloakAdminToken();
                $this->tokenTime = time();
                $response = Http::withToken($this->adminToken)
                    ->timeout(10)
                    ->post("{$this->keycloakUrl}/admin/realms/{$this->realm}/partialImport", $importData);
            }

            if (! $response->successful()) {
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
        if ($this->adminToken && (time() - $this->tokenTime) > 50) {
            $this->adminToken = $this->getKeycloakAdminToken();
            $this->tokenTime = time();
        }
    }

    private function getKeycloakAdminToken(): ?string
    {
        return KeycloakUserService::requestAdminToken();
    }
}
