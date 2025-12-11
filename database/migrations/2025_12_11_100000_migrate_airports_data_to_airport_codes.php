<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Get all airports from the airports table
        $airports = DB::table('airports')->whereNull('deleted_at')->get();

        $updated = 0;
        $created = 0;
        $airlinesLinked = 0;
        $errors = [];

        foreach ($airports as $airport) {
            // Try to find matching airport_code by IATA code first
            $airportCode = null;

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

            if ($airportCode) {
                // Update existing airport_code with additional data from airports
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
                $updated++;

                // Migrate airline relationships
                $airlineRelations = DB::table('airline_airport')
                    ->where('airport_id', $airport->id)
                    ->get();

                foreach ($airlineRelations as $relation) {
                    // Check if this relationship already exists
                    $exists = DB::table('airline_airport_code')
                        ->where('airline_id', $relation->airline_id)
                        ->where('airport_code_id', $airportCode->id)
                        ->where('direction', $relation->direction ?? 'both')
                        ->exists();

                    if (!$exists) {
                        DB::table('airline_airport_code')->insert([
                            'airline_id' => $relation->airline_id,
                            'airport_code_id' => $airportCode->id,
                            'direction' => $relation->direction ?? 'both',
                            'terminal' => $relation->terminal,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $airlinesLinked++;
                    }
                }
            } else {
                // Airport not found in airport_codes_1 - create new entry
                // Handle the Berlin Brandenburg special case (wrong codes)
                if ($airport->iata_code === 'BEA' && $airport->icao_code === 'EDDA') {
                    // This is Berlin Brandenburg with wrong codes - link to BER/EDDB instead
                    $berAirport = DB::table('airport_codes_1')
                        ->where('iata_code', 'BER')
                        ->whereNull('deleted_at')
                        ->first();

                    if ($berAirport) {
                        // Update BER with data from this airport
                        DB::table('airport_codes_1')
                            ->where('id', $berAirport->id)
                            ->update([
                                'city_id' => $airport->city_id ?? $berAirport->city_id,
                                'country_id' => $airport->country_id ?? $berAirport->country_id,
                                'website' => $airport->website ?? $berAirport->website,
                                'security_timeslot_url' => $airport->security_timeslot_url ?? $berAirport->security_timeslot_url,
                                'timezone' => $airport->timezone ?? $berAirport->timezone,
                                'dst_timezone' => $airport->dst_timezone ?? $berAirport->dst_timezone,
                                'is_active' => $airport->is_active ?? $berAirport->is_active ?? true,
                                'operates_24h' => $airport->operates_24h ?? $berAirport->operates_24h ?? false,
                                'lounges' => $airport->lounges ?? $berAirport->lounges,
                                'nearby_hotels' => $airport->nearby_hotels ?? $berAirport->nearby_hotels,
                                'mobility_options' => $airport->mobility_options ?? $berAirport->mobility_options,
                                'source' => $airport->source ?? $berAirport->source,
                                'updated_at' => now(),
                            ]);
                        $updated++;

                        // Migrate airline relationships
                        $airlineRelations = DB::table('airline_airport')
                            ->where('airport_id', $airport->id)
                            ->get();

                        foreach ($airlineRelations as $relation) {
                            $exists = DB::table('airline_airport_code')
                                ->where('airline_id', $relation->airline_id)
                                ->where('airport_code_id', $berAirport->id)
                                ->where('direction', $relation->direction ?? 'both')
                                ->exists();

                            if (!$exists) {
                                DB::table('airline_airport_code')->insert([
                                    'airline_id' => $relation->airline_id,
                                    'airport_code_id' => $berAirport->id,
                                    'direction' => $relation->direction ?? 'both',
                                    'terminal' => $relation->terminal,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                $airlinesLinked++;
                            }
                        }
                        continue;
                    }
                }

                // Create new airport_code entry
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
                $created++;

                // Migrate airline relationships for new entry
                $airlineRelations = DB::table('airline_airport')
                    ->where('airport_id', $airport->id)
                    ->get();

                foreach ($airlineRelations as $relation) {
                    DB::table('airline_airport_code')->insert([
                        'airline_id' => $relation->airline_id,
                        'airport_code_id' => $newId,
                        'direction' => $relation->direction ?? 'both',
                        'terminal' => $relation->terminal,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $airlinesLinked++;
                }
            }
        }

        // Log the results
        \Log::info("Airport migration completed: Updated: $updated, Created: $created, Airlines linked: $airlinesLinked");
    }

    public function down(): void
    {
        // Remove migrated airline relationships
        DB::table('airline_airport_code')->truncate();

        // Note: We don't delete created airport_codes entries to prevent data loss
        // Manual cleanup may be needed if rolling back
    }
};
