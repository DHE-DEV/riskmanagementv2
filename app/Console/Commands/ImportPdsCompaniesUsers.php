<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportPdsCompaniesUsers extends Command
{
    protected $signature = 'import:pds-companies-users {file=PDS-Companies-Users.csv : Path to CSV file} {--dry-run : Only show what would be imported}';
    protected $description = 'Import companies as customers and their users as employees from PDS CSV export';

    public function handle(): int
    {
        $file = $this->argument('file');
        $dryRun = $this->option('dry-run');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made');
        }

        $rows = $this->parseCsv($file);
        $this->info("Parsed " . count($rows) . " rows from CSV");

        // Group rows by account_id to get unique companies
        $grouped = collect($rows)->groupBy('account_id');
        $this->info("Found " . $grouped->count() . " unique companies");

        $customersCreated = 0;
        $customersSkipped = 0;
        $employeesCreated = 0;
        $employeesSkipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($grouped->count());
        $bar->start();

        foreach ($grouped as $accountId => $users) {
            $bar->advance();
            $first = $users->first();

            // Skip if customer with this legacy_account_id already exists
            if (Customer::where('legacy_account_id', $accountId)->exists()) {
                $customersSkipped++;
                $employeesSkipped += $users->count();
                continue;
            }

            $email = strtolower(trim($first['account_email'] ?? ''));
            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->newLine();
                $this->warn("Skipping account {$accountId} ({$first['account_name']}): invalid email '{$email}'");
                $customersSkipped++;
                $employeesSkipped += $users->count();
                continue;
            }

            if ($dryRun) {
                $customersCreated++;
                $employeesCreated += $users->count();
                continue;
            }

            try {
                $name = $first['account_name'] ?: trim(($first['account_first_name'] ?? '') . ' ' . ($first['account_last_name'] ?? ''));
                if (empty($name)) {
                    $name = $email;
                }

                $customer = new Customer();
                $customer->fill([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'phone' => $first['account_phone'] ?: null,
                    'customer_type' => 'business',
                    'company_name' => $first['account_name'] ?: null,
                    'company_street' => $this->parseStreet($first['account_address']),
                    'company_house_number' => $this->parseHouseNumber($first['account_address']),
                    'company_postal_code' => $first['account_zip'] ?: null,
                    'company_city' => $first['account_city'] ?: null,
                    'company_country' => $first['account_country'] ?: null,
                    'legacy_account_id' => $accountId,
                    'email_verified_at' => now(),
                ]);
                $customer->saveQuietly();

                // Create default groups
                $adminGroup = EmployeeGroup::create([
                    'customer_id' => $customer->id,
                    'name' => 'Administratoren',
                    'description' => 'Systemadministratoren in der Passolution Travel Information Platform',
                    'is_system' => true,
                ]);

                $staffGroup = EmployeeGroup::create([
                    'customer_id' => $customer->id,
                    'name' => 'Mitarbeiter',
                    'description' => 'Mitarbeiter der Organisation',
                ]);

                $customersCreated++;

                // Import users as employees
                foreach ($users as $user) {
                    try {
                        $userEmail = strtolower(trim($user['user_email'] ?? ''));
                        if (empty($userEmail) || ! filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                            $employeesSkipped++;
                            continue;
                        }

                        // Skip duplicate email within same customer
                        if (Employee::where('customer_id', $customer->id)->where('email', $userEmail)->exists()) {
                            $employeesSkipped++;
                            continue;
                        }

                        $employee = Employee::create([
                            'customer_id' => $customer->id,
                            'first_name' => $user['user_first_name'] ?? '',
                            'last_name' => $user['user_last_name'] ?? '',
                            'email' => $userEmail,
                            'phone' => $user['user_phone'] ?: null,
                            'mobile' => $user['user_mobile'] ?: null,
                            'is_active' => ($user['user_active'] ?? '0') == '1',
                            'legacy_usersweb_id' => $user['user_id'],
                        ]);

                        // Assign to appropriate group based on roles
                        $roles = $user['roles'] ?? '';
                        $isAdmin = str_contains($roles, 'account_manager') || str_contains($roles, 'office_manager');
                        $employee->groups()->attach($isAdmin ? $adminGroup->id : $staffGroup->id);

                        $employeesCreated++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('PDS user import failed', [
                            'account_id' => $accountId,
                            'user_id' => $user['user_id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('PDS company import failed', [
                    'account_id' => $accountId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done: {$customersCreated} customers created, {$customersSkipped} skipped");
        $this->info("      {$employeesCreated} employees created, {$employeesSkipped} skipped, {$errors} errors");

        return self::SUCCESS;
    }

    private function parseCsv(string $file): array
    {
        $rows = [];
        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }

        fclose($handle);
        return $rows;
    }

    private function parseStreet(?string $address): ?string
    {
        if (empty($address)) {
            return null;
        }

        if (preg_match('/^(.+?)\s+(\d+.*)$/u', trim($address), $matches)) {
            return trim($matches[1]);
        }

        return trim($address);
    }

    private function parseHouseNumber(?string $address): ?string
    {
        if (empty($address)) {
            return null;
        }

        if (preg_match('/^.+?\s+(\d+.*)$/u', trim($address), $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
