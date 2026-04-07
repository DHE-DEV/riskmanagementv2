<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use Illuminate\Console\Command;

class ResetImportedCustomers extends Command
{
    protected $signature = 'customers:reset-imported {--dry-run : Nur anzeigen, was gelöscht würde}';
    protected $description = 'Löscht alle importierten Customers (inkl. Employees und Groups) für einen sauberen Neuimport';

    public function handle(): int
    {
        $customers = Customer::whereNotNull('email')->get();
        $count = $customers->count();

        $this->info("Gefunden: {$count} Customers");

        if ($count === 0) {
            $this->info('Keine Customers zum Löschen.');
            return 0;
        }

        if (! $this->option('dry-run') && ! $this->confirm("Wirklich ALLE {$count} Customers (inkl. Employees und Groups) löschen?")) {
            $this->info('Abgebrochen.');
            return 0;
        }

        $deleted = 0;

        foreach ($customers as $customer) {
            if ($this->option('dry-run')) {
                $this->line("  Würde löschen: {$customer->email}");
                $deleted++;
                continue;
            }

            Employee::where('customer_id', $customer->id)->delete();
            EmployeeGroup::where('customer_id', $customer->id)->delete();
            $customer->forceDelete();
            $deleted++;
        }

        $prefix = $this->option('dry-run') ? '[DRY-RUN] ' : '';
        $this->info("{$prefix}Ergebnis: {$deleted} Customers gelöscht");

        return 0;
    }
}
