<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\KeycloakUserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncEmployeesToKeycloak extends Command
{
    protected $signature = 'keycloak:sync-employees
        {--limit=0 : Limit number of employees to sync}
        {--dry-run : Only show what would be synced}
        {--email= : Only sync a specific employee by email}
        {--customer= : Only sync employees of a specific customer ID}';

    protected $description = 'Sync employees with BCrypt passwords to Keycloak';

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

        if (! $this->keycloakUrl) {
            $this->error('OIDC_BASE_URL not configured.');
            return self::FAILURE;
        }

        $query = Employee::whereNotNull('password')
            ->where('password', '!=', '')
            ->where('password', 'like', '$2y$%')
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if ($email = $this->option('email')) {
            $query->where('email', $email);
        }

        if ($customerId = $this->option('customer')) {
            $query->where('customer_id', $customerId);
        }

        $total = $query->count();
        $this->info("Found {$total} employees with BCrypt password");

        if ($total === 0) {
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made');
        }

        if (! $dryRun) {
            $this->adminToken = $this->getAdminToken();
            $this->tokenTime = time();
            if (! $this->adminToken) {
                $this->error('Could not get Keycloak admin token.');
                return self::FAILURE;
            }
            $this->info('Keycloak admin token acquired');
        }

        $synced = 0;
        $skipped = 0;
        $errors = 0;

        $employees = $query->orderBy('id');
        if ($limit > 0) {
            $employees = $employees->limit($limit);
        }

        $bar = $this->output->createProgressBar($limit > 0 ? min($limit, $total) : $total);
        $bar->start();

        $employees->with('customer')->chunk(50, function ($chunk) use (&$synced, &$skipped, &$errors, $dryRun, $bar) {
            if (! $dryRun) {
                $this->refreshTokenIfNeeded();
            }

            foreach ($chunk as $employee) {
                $bar->advance();

                if (! $employee->customer) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $synced++;
                    continue;
                }

                try {
                    $this->syncToKeycloak($employee);
                    $synced++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Employee Keycloak sync failed', [
                        'employee_id' => $employee->id,
                        'email' => $employee->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Sync complete: {$synced} synced, {$skipped} skipped, {$errors} errors");

        return self::SUCCESS;
    }

    private function syncToKeycloak(Employee $employee): void
    {
        $hash = str_replace('$2y$', '$2a$', $employee->password);

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
                        'platform_customer_id' => [(string) $employee->customer_id],
                        'platform_employee_id' => [(string) $employee->id],
                    ],
                    'credentials' => [
                        [
                            'type' => 'password',
                            'hashedSaltedValue' => $hash,
                            'algorithm' => 'bcrypt',
                            'hashIterations' => 10,
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($this->adminToken)
            ->timeout(10)
            ->post("{$this->keycloakUrl}/admin/realms/{$this->realm}/partialImport", $importData);

        if ($response->status() === 401) {
            $this->adminToken = $this->getAdminToken();
            $this->tokenTime = time();
            $response = Http::withToken($this->adminToken)
                ->timeout(10)
                ->post("{$this->keycloakUrl}/admin/realms/{$this->realm}/partialImport", $importData);
        }

        if (! $response->successful()) {
            throw new \RuntimeException("Keycloak sync failed: {$response->status()}: {$response->body()}");
        }
    }

    private function refreshTokenIfNeeded(): void
    {
        if ($this->adminToken && (time() - $this->tokenTime) > 50) {
            $this->adminToken = $this->getAdminToken();
            $this->tokenTime = time();
        }
    }

    private function getAdminToken(): ?string
    {
        return KeycloakUserService::requestAdminToken();
    }
}
