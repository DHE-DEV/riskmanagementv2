<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchLegacyEmployees extends Command
{
    protected $signature = 'import:match-legacy-employees {--dry-run : Only show what would be changed} {--delete-orphans : Soft-delete customer accounts that become employees}';
    protected $description = 'Match legacy users with assignto to their parent customer (via webold_client_accounts) as employees';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $deleteOrphans = $this->option('delete-orphans');

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made');
        }

        // Get all legacy users that have an assignto value
        $assigned = DB::table('webold_usersweb')
            ->whereNotNull('assignto')
            ->where('assignto', '!=', 0)
            ->where('assignto', '!=', '')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        $this->info("Found {$assigned->count()} legacy users with assignto set");

        if ($assigned->isEmpty()) {
            return self::SUCCESS;
        }

        // Pre-load all customers mapped by legacy_client_account_id
        $customersByLegacyId = Customer::whereNotNull('legacy_client_account_id')
            ->get()
            ->keyBy('legacy_client_account_id');

        $this->info("Found {$customersByLegacyId->count()} customers with legacy_client_account_id");

        $matched = 0;
        $skipped = 0;
        $errors = 0;
        $orphansDeleted = 0;

        $bar = $this->output->createProgressBar($assigned->count());
        $bar->start();

        foreach ($assigned as $legacyUser) {
            $bar->advance();

            $email = strtolower(trim($legacyUser->email));

            // Find the parent customer via assignto → legacy_client_account_id
            $parentCustomer = $customersByLegacyId->get($legacyUser->assignto);

            if (! $parentCustomer) {
                $skipped++;
                continue;
            }

            // Skip if this user's email is the same as the parent customer (they're the owner)
            if ($email === strtolower($parentCustomer->email)) {
                $skipped++;
                continue;
            }

            // Check if this user already exists as employee under the parent customer
            if (Employee::where('customer_id', $parentCustomer->id)->where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->newLine();
                $this->line("  Would assign <info>{$email}</info> as employee to <info>{$parentCustomer->name}</info> (legacy_client_account_id={$legacyUser->assignto})");
                $matched++;
                continue;
            }

            try {
                // Create employee under parent customer
                $employee = Employee::create([
                    'customer_id' => $parentCustomer->id,
                    'first_name' => $legacyUser->forename ?? '',
                    'last_name' => $legacyUser->surname ?? '',
                    'email' => $email,
                    'phone' => $legacyUser->phone ?? '',
                    'position' => '',
                    'is_active' => $legacyUser->active == 1,
                    'legacy_usersweb_id' => $legacyUser->id,
                ]);

                // Assign to "Mitarbeiter" group
                $staffGroup = EmployeeGroup::where('customer_id', $parentCustomer->id)
                    ->where('name', 'Mitarbeiter')
                    ->first();

                if ($staffGroup) {
                    $employee->groups()->attach($staffGroup->id);
                }

                // Soft-delete the orphaned customer account if requested
                if ($deleteOrphans) {
                    $orphanCustomer = Customer::where('email', $email)
                        ->where('id', '!=', $parentCustomer->id)
                        ->first();

                    if ($orphanCustomer) {
                        $orphanCustomer->delete();
                        $orphansDeleted++;
                    }
                }

                $matched++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Legacy employee matching failed', [
                    'legacy_id' => $legacyUser->id,
                    'email' => $email,
                    'assignto' => $legacyUser->assignto,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done: {$matched} matched, {$skipped} skipped, {$errors} errors");

        if ($deleteOrphans) {
            $this->info("Orphan customers soft-deleted: {$orphansDeleted}");
        }

        return self::SUCCESS;
    }
}
