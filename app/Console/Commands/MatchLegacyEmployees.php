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
    protected $description = 'Match legacy users with assignto to their parent customer as employees';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $deleteOrphans = $this->option('delete-orphans');

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made');
        }

        // Get all legacy users that have an assignto value pointing to another user
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

        // Pre-load all referenced parent IDs
        $parentIds = $assigned->pluck('assignto')->unique();
        $parents = DB::table('webold_usersweb')
            ->whereIn('id', $parentIds)
            ->get()
            ->keyBy('id');

        $this->info("Found {$parents->count()} unique parent users");

        $matched = 0;
        $skipped = 0;
        $errors = 0;
        $orphansDeleted = 0;

        $bar = $this->output->createProgressBar($assigned->count());
        $bar->start();

        foreach ($assigned as $legacyUser) {
            $bar->advance();

            $email = strtolower(trim($legacyUser->email));
            $parent = $parents->get($legacyUser->assignto);

            if (! $parent) {
                $this->newLine();
                $this->warn("  Parent ID {$legacyUser->assignto} not found for user {$email}");
                $skipped++;
                continue;
            }

            $parentEmail = strtolower(trim($parent->email ?? ''));
            if (empty($parentEmail)) {
                $this->newLine();
                $this->warn("  Parent ID {$parent->id} has no email, skipping user {$email}");
                $skipped++;
                continue;
            }

            // Find the parent customer in our system
            $parentCustomer = Customer::where('email', $parentEmail)->first();
            if (! $parentCustomer) {
                $this->newLine();
                $this->warn("  No customer found for parent email {$parentEmail}, skipping user {$email}");
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
                $this->line("  Would assign <info>{$email}</info> as employee to customer <info>{$parentCustomer->name}</info> ({$parentEmail})");
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
                ]);

                // Assign to "Mitarbeiter" group if it exists
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
                    'parent_email' => $parentEmail,
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
