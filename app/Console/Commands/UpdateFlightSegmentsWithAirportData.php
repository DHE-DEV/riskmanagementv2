<?php

namespace App\Console\Commands;

use App\Models\Folder\FolderFlightSegment;
use App\Services\Folder\AirportLookupService;
use Illuminate\Console\Command;

class UpdateFlightSegmentsWithAirportData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'folder:update-flight-segments-airport-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing flight segments with airport and country IDs based on airport codes';

    protected AirportLookupService $airportLookup;

    public function __construct(AirportLookupService $airportLookup)
    {
        parent::__construct();
        $this->airportLookup = $airportLookup;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update flight segments with airport and country data...');

        // Get all flight segments that don't have airport IDs yet
        $segments = FolderFlightSegment::withoutGlobalScope('customer')
            ->whereNull('departure_airport_id')
            ->orWhereNull('arrival_airport_id')
            ->get();

        if ($segments->isEmpty()) {
            $this->info('No flight segments to update.');

            return Command::SUCCESS;
        }

        $this->info("Found {$segments->count()} segments to update.");

        $bar = $this->output->createProgressBar($segments->count());
        $bar->start();

        $updated = 0;
        $failed = 0;

        foreach ($segments as $segment) {
            try {
                $updated_fields = [];

                // Lookup departure airport
                if ($segment->departure_airport_code && ! $segment->departure_airport_id) {
                    $departureAirport = $this->airportLookup->findAirportByIataCode($segment->departure_airport_code);
                    if ($departureAirport) {
                        $updated_fields['departure_airport_id'] = $departureAirport['airport_id'];
                        $updated_fields['departure_country_id'] = $departureAirport['country_id'];

                        // Fill in missing lat/lng if available
                        if (! $segment->departure_lat && $departureAirport['lat']) {
                            $updated_fields['departure_lat'] = $departureAirport['lat'];
                        }
                        if (! $segment->departure_lng && $departureAirport['lng']) {
                            $updated_fields['departure_lng'] = $departureAirport['lng'];
                        }
                        if (! $segment->departure_country_code && $departureAirport['country_code']) {
                            $updated_fields['departure_country_code'] = $departureAirport['country_code'];
                        }
                    }
                }

                // Lookup arrival airport
                if ($segment->arrival_airport_code && ! $segment->arrival_airport_id) {
                    $arrivalAirport = $this->airportLookup->findAirportByIataCode($segment->arrival_airport_code);
                    if ($arrivalAirport) {
                        $updated_fields['arrival_airport_id'] = $arrivalAirport['airport_id'];
                        $updated_fields['arrival_country_id'] = $arrivalAirport['country_id'];

                        // Fill in missing lat/lng if available
                        if (! $segment->arrival_lat && $arrivalAirport['lat']) {
                            $updated_fields['arrival_lat'] = $arrivalAirport['lat'];
                        }
                        if (! $segment->arrival_lng && $arrivalAirport['lng']) {
                            $updated_fields['arrival_lng'] = $arrivalAirport['lng'];
                        }
                        if (! $segment->arrival_country_code && $arrivalAirport['country_code']) {
                            $updated_fields['arrival_country_code'] = $arrivalAirport['country_code'];
                        }
                    }
                }

                if (! empty($updated_fields)) {
                    $segment->update($updated_fields);
                    $updated++;
                }
            } catch (\Exception $e) {
                $this->error("\nFailed to update segment {$segment->id}: ".$e->getMessage());
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully updated {$updated} segments.");
        if ($failed > 0) {
            $this->warn("Failed to update {$failed} segments.");
        }

        return Command::SUCCESS;
    }
}
