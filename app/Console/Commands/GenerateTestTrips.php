<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Customer;
use App\Models\TravelDetail\TdTrip;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateTestTrips extends Command
{
    protected $signature = 'test-trips:generate
        {customer_id : Kunden-ID}
        {--trips=10000 : Anzahl Travel Alert Reisen}
        {--links=11000 : Anzahl Travel Detail Links}';

    protected $description = 'Generiert Testreisen für einen Kunden (zum Performance-Test)';

    public function handle(): int
    {
        $customerId = (int) $this->argument('customer_id');
        $tripCount = (int) $this->option('trips');
        $linkCount = (int) $this->option('links');

        $customer = Customer::find($customerId);
        if (! $customer) {
            $this->error("Kunde #{$customerId} nicht gefunden.");

            return self::FAILURE;
        }

        $existing = TdTrip::where('customer_id', $customerId)->where('is_test_data', true)->count();
        if ($existing > 0) {
            $this->warn("Es existieren bereits {$existing} Testdatensätze für diesen Kunden.");
            if (! $this->confirm('Trotzdem weitere generieren?')) {
                return self::SUCCESS;
            }
        }

        // Alle Ländercodes laden
        $countryCodes = Country::pluck('iso_code')->filter()->values()->toArray();
        if (empty($countryCodes)) {
            $this->error('Keine Länder in der Datenbank gefunden.');

            return self::FAILURE;
        }

        $totalCount = $tripCount + $linkCount;
        $this->info("Generiere {$tripCount} Travel Alert Reisen + {$linkCount} Travel Detail Links für Kunde #{$customerId} ({$customer->company_name})...");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $travelDetailsBase = rtrim(env('PASSOLUTION_TRAVEL_DETAILS_LINK', 'https://travel-details.eu'), '/');
        $batchSize = 500;
        $batch = [];
        $now = now();

        // Städte-/Reise-Namen für Variation
        $tripTypes = ['Geschäftsreise', 'Urlaubsreise', 'Dienstreise', 'Konferenzreise', 'Messebesuch', 'Kundenbesuch', 'Teamreise', 'Incentive-Reise', 'Schulungsreise', 'Projektreise'];
        $regions = ['Europa', 'Asien', 'Nordamerika', 'Südamerika', 'Afrika', 'Ozeanien', 'Nahost', 'Karibik', 'Skandinavien', 'Mittelmeer'];

        for ($i = 0; $i < $totalCount; $i++) {
            $isLink = $i >= $tripCount; // Erste $tripCount = Travel Alert, Rest = Travel Detail Links
            $tid = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));

            // Zufälliger Zeitraum: -6 Monate bis +12 Monate
            $startOffset = rand(-180, 365);
            $startDate = Carbon::today()->addDays($startOffset);
            $duration = rand(2, 21);
            $endDate = $startDate->copy()->addDays($duration);

            // 1-4 zufällige Länder pro Reise
            $numCountries = rand(1, 4);
            $tripCountries = collect($countryCodes)->random($numCountries)->values()->toArray();

            // Status basierend auf Datum
            $status = 'active';
            if ($endDate->isPast()) {
                $status = 'completed';
            }

            $tripType = $tripTypes[array_rand($tripTypes)];
            $region = $regions[array_rand($regions)];
            $tripName = "TEST: {$tripType} {$region} #{$i}";

            $record = [
                'customer_id' => $customerId,
                'provider_id' => 'test-generator',
                'external_trip_id' => 'test-' . $tid,
                'provider_name' => 'Test Generator',
                'provider_sent_at' => $now,
                'booking_reference' => 'TEST-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'trip_name' => $tripName,
                'computed_start_at' => $startDate,
                'computed_end_at' => $endDate,
                'countries_visited' => json_encode($tripCountries),
                'nationalities' => json_encode(['DE']),
                'status' => $status,
                'pds_tid' => $tid,
                'pds_share_url' => $isLink ? $travelDetailsBase . '/de?tid=' . $tid : null,
                'is_test_data' => true,
                'is_archived' => false,
                'is_cruise' => false,
                'with_minors' => false,
                'visits' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $batch[] = $record;

            if (count($batch) >= $batchSize) {
                TdTrip::insert($batch);
                $bar->advance(count($batch));
                $batch = [];
            }
        }

        // Rest einfügen
        if (! empty($batch)) {
            TdTrip::insert($batch);
            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine(2);

        $totalCreated = TdTrip::where('customer_id', $customerId)->where('is_test_data', true)->count();
        $this->info("Fertig! {$totalCreated} Testdatensätze erstellt.");
        $this->info("Davon {$tripCount} Travel Alert Reisen (ohne Share-URL) und {$linkCount} Travel Detail Links (mit Share-URL).");
        $this->newLine();
        $this->info("Zum Löschen: php artisan test-trips:cleanup {$customerId}");

        return self::SUCCESS;
    }
}
