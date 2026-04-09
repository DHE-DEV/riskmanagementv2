<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ResetImportData extends Command
{
    protected $signature = 'import:reset {--force : Skip confirmation}';
    protected $description = 'Delete all Customers, Employees, EmployeeGroups and Keycloak users';

    private string $keycloakUrl;
    private string $realm;
    private ?string $adminToken = null;

    public function handle(): int
    {
        $this->keycloakUrl = config('services.keycloak.base_url', '');
        $this->realm = config('services.keycloak.realms', 'passolution');

        $customerCount = Customer::count();
        $employeeCount = Employee::count();
        $groupCount = EmployeeGroup::count();

        $this->warn("This will delete:");
        $this->line("  - {$employeeCount} Employees");
        $this->line("  - {$groupCount} Employee Groups");
        $this->line("  - {$customerCount} Customers");
        $this->line("  - All Keycloak users in realm '{$this->realm}'");

        if (! $this->option('force') && ! $this->confirm('Are you sure you want to delete everything?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        // 1. Delete Keycloak users
        $this->deleteKeycloakUsers();

        // 2. Delete employees (pivot table entries cascade via detach)
        $this->info('Deleting employees...');
        DB::table('employee_employee_group')->truncate();
        Employee::query()->delete();
        $this->info("  Done.");

        // 3. Delete employee groups
        $this->info('Deleting employee groups...');
        EmployeeGroup::query()->delete();
        $this->info("  Done.");

        // 4. Delete customers
        $this->info('Deleting customers...');
        Customer::query()->forceDelete();
        $this->info("  Done.");

        $this->newLine();
        $this->info('Reset complete.');

        return self::SUCCESS;
    }

    private function deleteKeycloakUsers(): void
    {
        if (! $this->keycloakUrl) {
            $this->warn('Keycloak not configured, skipping.');

            return;
        }

        $this->adminToken = $this->getAdminToken();
        if (! $this->adminToken) {
            $this->error('Could not get Keycloak admin token. Skipping Keycloak cleanup.');

            return;
        }

        $this->info("Deleting Keycloak users in realm '{$this->realm}'...");

        $deleted = 0;
        $errors = 0;
        $tokenTime = time();

        // Paginate through all users
        while (true) {
            // Refresh token if needed
            if (time() - $tokenTime > 50) {
                $this->adminToken = $this->getAdminToken();
                $tokenTime = time();
            }

            $response = Http::withToken($this->adminToken)
                ->timeout(15)
                ->get("{$this->keycloakUrl}/admin/realms/{$this->realm}/users", [
                    'first' => 0,
                    'max' => 100,
                ]);

            if (! $response->successful()) {
                $this->error("Failed to fetch users: {$response->status()}");
                break;
            }

            $users = $response->json();
            if (empty($users)) {
                break;
            }

            foreach ($users as $user) {
                $delResponse = Http::withToken($this->adminToken)
                    ->timeout(10)
                    ->delete("{$this->keycloakUrl}/admin/realms/{$this->realm}/users/{$user['id']}");

                if ($delResponse->successful()) {
                    $deleted++;
                } else {
                    $errors++;
                    $this->warn("  Failed to delete {$user['username']}: {$delResponse->status()}");
                }
            }

            $this->line("  Deleted {$deleted} Keycloak users so far...");
        }

        $this->info("  Keycloak: {$deleted} deleted, {$errors} errors.");
    }

    private function getAdminToken(): ?string
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
}
