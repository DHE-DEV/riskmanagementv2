<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateAirportsToAirportCodes extends Command
{
    protected $signature = 'airports:migrate-to-codes
                            {--dry-run : Show what would be done without making changes}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Migrate data from airports table to airport_codes_1 table including airline relationships';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Show current state
        $airportsCount = DB::table('airports')->whereNull('deleted_at')->count();
        $airportCodesCount = DB::table('airport_codes_1')->whereNull('deleted_at')->count();
        $existingRelations = DB::table('airline_airport_code')->count();

        $this->info("Current state:");
        $this->table(
            ['Table', 'Count'],
            [
                ['airports', $airportsCount],
                ['airport_codes_1', $airportCodesCount],
                ['airline_airport_code (existing)', $existingRelations],
            ]
        );

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('Do you want to proceed with the migration?')) {
                $this->info('Migration cancelled.');
                return self::SUCCESS;
            }
        }

        $airports = DB::table('airports')->whereNull('deleted_at')->get();

        $updated = 0;
        $created = 0;
        $airlinesLinked = 0;
        $skipped = 0;

        $this->newLine();
        $this->info('Processing airports...');
        $bar = $this->output->createProgressBar($airports->count());
        $bar->start();

        foreach ($airports as $airport) {
            $airportCode = null;

            // Try to find matching airport_code by IATA code first
            if ($airport->iata_code) {
                $airportCode = DB::table('airport_codes_1')
                    ->where('iata_code', $airport->iata_code)
                    ->whereNull('deleted_at')
                    ->first();
            }

            // If not found by IATA, try ICAO
            if (!$airportCode && $airport->icao_code) {
                $airportCode = DB::table('airport_codes_1')
                    ->where('icao_code', $airport->icao_code)
                    ->whereNull('deleted_at')
                    ->first();
            }

            // Handle Berlin Brandenburg special case (wrong codes BEA/EDDA instead of BER/EDDB)
            if (!$airportCode && $airport->iata_code === 'BEA' && $airport->icao_code === 'EDDA') {
                $airportCode = DB::table('airport_codes_1')
                    ->where('iata_code', 'BER')
                    ->whereNull('deleted_at')
                    ->first();
            }

            if ($airportCode) {
                // Update existing airport_code with additional data
                if (!$dryRun) {
                    DB::table('airport_codes_1')
                        ->where('id', $airportCode->id)
                        ->update([
                            'city_id' => $airport->city_id ?? $airportCode->city_id,
                            'country_id' => $airport->country_id ?? $airportCode->country_id,
                            'website' => $airport->website ?? $airportCode->website,
                            'security_timeslot_url' => $airport->security_timeslot_url ?? $airportCode->security_timeslot_url,
                            'timezone' => $airport->timezone ?? $airportCode->timezone,
                            'dst_timezone' => $airport->dst_timezone ?? $airportCode->dst_timezone,
                            'is_active' => $airport->is_active ?? $airportCode->is_active ?? true,
                            'operates_24h' => $airport->operates_24h ?? $airportCode->operates_24h ?? false,
                            'lounges' => $airport->lounges ?? $airportCode->lounges,
                            'nearby_hotels' => $airport->nearby_hotels ?? $airportCode->nearby_hotels,
                            'mobility_options' => $airport->mobility_options ?? $airportCode->mobility_options,
                            'source' => $airport->source ?? $airportCode->source,
                            'updated_at' => now(),
                        ]);
                }
                $updated++;

                // Migrate airline relationships
                $airlineRelations = DB::table('airline_airport')
                    ->where('airport_id', $airport->id)
                    ->get();

                foreach ($airlineRelations as $relation) {
                    $exists = DB::table('airline_airport_code')
                        ->where('airline_id', $relation->airline_id)
                        ->where('airport_code_id', $airportCode->id)
                        ->where('direction', $relation->direction ?? 'both')
                        ->exists();

                    if (!$exists) {
                        if (!$dryRun) {
                            DB::table('airline_airport_code')->insert([
                                'airline_id' => $relation->airline_id,
                                'airport_code_id' => $airportCode->id,
                                'direction' => $relation->direction ?? 'both',
                                'terminal' => $relation->terminal,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                        $airlinesLinked++;
                    } else {
                        $skipped++;
                    }
                }
            } else {
                // Create new airport_code entry
                if (!$dryRun) {
                    $newId = DB::table('airport_codes_1')->insertGetId([
                        'ident' => $airport->icao_code ?? $airport->iata_code ?? 'UNKNOWN',
                        'type' => $airport->type ?? 'medium_airport',
                        'name' => $airport->name,
                        'latitude_deg' => $airport->lat,
                        'longitude_deg' => $airport->lng,
                        'elevation_ft' => $airport->altitude,
                        'timezone' => $airport->timezone,
                        'dst_timezone' => $airport->dst_timezone,
                        'city_id' => $airport->city_id,
                        'country_id' => $airport->country_id,
                        'icao_code' => $airport->icao_code,
                        'iata_code' => $airport->iata_code,
                        'website' => $airport->website,
                        'security_timeslot_url' => $airport->security_timeslot_url,
                        'is_active' => $airport->is_active ?? true,
                        'operates_24h' => $airport->operates_24h ?? false,
                        'lounges' => $airport->lounges,
                        'nearby_hotels' => $airport->nearby_hotels,
                        'mobility_options' => $airport->mobility_options,
                        'source' => $airport->source ?? 'migrated_from_airports',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $newId = 0; // Placeholder for dry run
                }
                $created++;

                // Migrate airline relationships for new entry
                $airlineRelations = DB::table('airline_airport')
                    ->where('airport_id', $airport->id)
                    ->get();

                foreach ($airlineRelations as $relation) {
                    if (!$dryRun && $newId) {
                        DB::table('airline_airport_code')->insert([
                            'airline_id' => $relation->airline_id,
                            'airport_code_id' => $newId,
                            'direction' => $relation->direction ?? 'both',
                            'terminal' => $relation->terminal,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $airlinesLinked++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info($dryRun ? 'DRY RUN - Summary (no changes made):' : 'Migration completed!');
        $this->table(
            ['Action', 'Count'],
            [
                ['Airport codes updated', $updated],
                ['Airport codes created', $created],
                ['Airline relationships added', $airlinesLinked],
                ['Airline relationships skipped (already exist)', $skipped],
            ]
        );

        if (!$dryRun) {
            $newRelationsCount = DB::table('airline_airport_code')->count();
            $this->newLine();
            $this->info("Total airline_airport_code entries: {$newRelationsCount}");
        }

        return self::SUCCESS;
    }
}
