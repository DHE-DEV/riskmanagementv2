<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ImportLegacyClientAccounts extends Command
{
    protected $signature = 'import:legacy-client-accounts {--dry-run : Only show what would be imported} {--limit=0 : Limit number of records}';
    protected $description = 'Import active companies from webold_client_accounts into customers table';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made');
        }

        $query = DB::table('webold_client_accounts')
            ->whereNull('deleted_at')
            ->orderBy('id');

        $total = $query->count();
        $this->info("Found {$total} active client accounts");

        if ($limit > 0) {
            $query->limit($limit);
        }

        $imported = 0;
        $skipped = 0;
        $updated = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($limit > 0 ? $limit : $total);
        $bar->start();

        $query->chunk(100, function ($chunk) use (&$imported, &$skipped, &$updated, &$errors, $dryRun, $bar) {
            foreach ($chunk as $account) {
                $bar->advance();

                $email = strtolower(trim($account->email ?? ''));

                // Skip accounts without email
                if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    continue;
                }

                // Check if already imported by legacy_client_account_id
                $existingByLegacyId = Customer::where('legacy_client_account_id', $account->id)->first();
                if ($existingByLegacyId) {
                    $skipped++;
                    continue;
                }

                // Check if customer with same email already exists (from webold_usersweb import)
                $existingByEmail = Customer::where('email', $email)->first();

                if ($dryRun) {
                    if ($existingByEmail) {
                        $this->newLine();
                        $this->line("  Would update existing customer <info>{$email}</info> with legacy_client_account_id={$account->id}");
                        $updated++;
                    } else {
                        $this->newLine();
                        $this->line("  Would create new customer <info>{$account->name}</info> ({$email})");
                        $imported++;
                    }
                    continue;
                }

                try {
                    if ($existingByEmail) {
                        // Update existing customer with legacy fields
                        $existingByEmail->update([
                            'legacy_client_account_id' => $account->id,
                            'legacy_passolution_company_id' => $account->passolution_company_id,
                            'legacy_account_id' => $account->account_id,
                            'legacy_organization_id' => $account->organization_id,
                            'legacy_language_id' => $account->language_id,
                            'company_name' => $existingByEmail->company_name ?: $account->name,
                            'company_street' => $existingByEmail->company_street ?: $this->parseStreet($account->address_line_1),
                            'company_house_number' => $existingByEmail->company_house_number ?: $this->parseHouseNumber($account->address_line_1),
                            'company_postal_code' => $existingByEmail->company_postal_code ?: $account->zip_code,
                            'company_city' => $existingByEmail->company_city ?: $account->city,
                            'company_country' => $existingByEmail->company_country ?: $account->country,
                            'phone' => $existingByEmail->phone ?: $account->phone,
                        ]);
                        $updated++;
                    } else {
                        // Create new customer from client account
                        $name = $account->name ?: trim(($account->first_name ?? '') . ' ' . ($account->last_name ?? ''));
                        if (empty($name)) {
                            $name = $email;
                        }

                        $customer = new Customer();
                        $customer->fill([
                            'name' => $name,
                            'email' => $email,
                            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                            'phone' => $account->phone,
                            'customer_type' => 'business',
                            'company_name' => $account->name,
                            'company_street' => $this->parseStreet($account->address_line_1),
                            'company_house_number' => $this->parseHouseNumber($account->address_line_1),
                            'company_postal_code' => $account->zip_code,
                            'company_city' => $account->city,
                            'company_country' => $account->country,
                            'legacy_client_account_id' => $account->id,
                            'legacy_passolution_company_id' => $account->passolution_company_id,
                            'legacy_account_id' => $account->account_id,
                            'legacy_organization_id' => $account->organization_id,
                            'legacy_language_id' => $account->language_id,
                            'email_verified_at' => now(),
                            'branch_management_active' => true,
                        ]);
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

                        // Create owner employee from client account contact
                        $ownerEmployee = Employee::create([
                            'customer_id' => $customer->id,
                            'first_name' => $account->first_name ?? $name,
                            'last_name' => $account->last_name ?? '',
                            'email' => $email,
                            'phone' => $account->phone ?? '',
                            'position' => 'Inhaber / Administrator',
                            'is_active' => true,
                            'legacy_client_account_id' => $account->id,
                        ]);

                        $ownerEmployee->groups()->attach($adminGroup->id);

                        $imported++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Legacy client account import failed', [
                        'account_id' => $account->id,
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done: {$imported} created, {$updated} updated, {$skipped} skipped, {$errors} errors");

        return self::SUCCESS;
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
