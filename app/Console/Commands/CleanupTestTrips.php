<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\TravelDetail\TdTrip;
use Illuminate\Console\Command;

class CleanupTestTrips extends Command
{
    protected $signature = 'test-trips:cleanup
        {customer_id : Kunden-ID}
        {--force : Ohne Bestätigung löschen}';

    protected $description = 'Löscht alle Testreisen eines Kunden (is_test_data = true)';

    public function handle(): int
    {
        $customerId = (int) $this->argument('customer_id');

        $customer = Customer::find($customerId);
        if (! $customer) {
            $this->error("Kunde #{$customerId} nicht gefunden.");

            return self::FAILURE;
        }

        $count = TdTrip::where('customer_id', $customerId)
            ->where('is_test_data', true)
            ->count();

        if ($count === 0) {
            $this->info("Keine Testdaten für Kunde #{$customerId} gefunden.");

            return self::SUCCESS;
        }

        $this->info("Gefunden: {$count} Testdatensätze für Kunde #{$customerId} ({$customer->company_name})");

        if (! $this->option('force') && ! $this->confirm("Alle {$count} Testdatensätze unwiderruflich löschen?")) {
            $this->info('Abgebrochen.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // In Batches löschen um Memory-Probleme zu vermeiden
        $deleted = 0;
        do {
            $batch = TdTrip::where('customer_id', $customerId)
                ->where('is_test_data', true)
                ->limit(1000)
                ->forceDelete();

            $deleted += $batch;
            $bar->advance($batch);
        } while ($batch > 0);

        $bar->finish();
        $this->newLine(2);
        $this->info("{$deleted} Testdatensätze gelöscht.");

        return self::SUCCESS;
    }
}
