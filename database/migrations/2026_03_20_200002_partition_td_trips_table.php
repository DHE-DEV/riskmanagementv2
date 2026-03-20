<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add monthly RANGE partitioning to td_trips.
 *
 * Prepares for millions of records. Partitioned by created_at for efficient
 * time-range queries and archival by dropping entire partitions.
 *
 * Trade-offs:
 * - Remaining FKs from child tables (td_air_legs, td_stays, td_transfers,
 *   td_travellers, td_pds_share_links) must be dropped.
 *   Cascade handled by TripArchivalService.
 * - Soft deletes still work (deleted records stay in their partition).
 * - created_at converted from TIMESTAMP to DATETIME for partitioning compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $partitions = $this->generateMonthlyPartitions();

        // FKs from child tables were already dropped in earlier partition migrations
        // (td_flight_segments, td_trip_locations) and the remaining ones (td_air_legs,
        // td_stays, td_transfers, td_travellers, td_pds_share_links) no longer exist.

        // 1. Fill any NULL created_at values, then convert to DATETIME NOT NULL
        DB::statement("UPDATE td_trips SET created_at = NOW() WHERE created_at IS NULL");
        DB::statement('ALTER TABLE td_trips MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE td_trips MODIFY updated_at DATETIME NULL');

        // 3. Drop unique constraint that doesn't include partition column, recreate with created_at
        DB::statement('ALTER TABLE td_trips DROP INDEX uk_provider_trip');
        DB::statement('ALTER TABLE td_trips ADD UNIQUE INDEX uk_provider_trip (provider_id, external_trip_id, created_at)');

        // 4. Change PK to composite
        DB::statement('ALTER TABLE td_trips DROP PRIMARY KEY, ADD PRIMARY KEY (id, created_at)');

        // 5. Add partitioning
        DB::statement("
            ALTER TABLE td_trips
                PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
                    {$partitions}
                )
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // 1. Remove partitioning and restore PK
        DB::statement('ALTER TABLE td_trips REMOVE PARTITIONING');
        DB::statement('ALTER TABLE td_trips DROP PRIMARY KEY, ADD PRIMARY KEY (id)');

        // 2. Restore unique constraint without created_at
        DB::statement('ALTER TABLE td_trips DROP INDEX uk_provider_trip');
        DB::statement('ALTER TABLE td_trips ADD UNIQUE INDEX uk_provider_trip (provider_id, external_trip_id)');

        // 3. Restore DATETIME → TIMESTAMP
        DB::statement('ALTER TABLE td_trips MODIFY created_at TIMESTAMP NULL');
        DB::statement('ALTER TABLE td_trips MODIFY updated_at TIMESTAMP NULL');

        // 3. Restore FKs
        $fkTables = ['td_air_legs', 'td_stays', 'td_transfers', 'td_travellers', 'td_pds_share_links'];
        foreach ($fkTables as $table) {
            Schema::table($table, function ($t) {
                $t->foreign('trip_id')->references('id')->on('td_trips')->cascadeOnDelete();
            });
        }
    }

    private function generateMonthlyPartitions(): string
    {
        $partitions = [];

        for ($year = 2025; $year <= 2055; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $key = sprintf('%d%02d', $year, $month);
                $nextYear = $month === 12 ? $year + 1 : $year;
                $nextMonth = $month === 12 ? 1 : $month + 1;
                $lessThan = sprintf('%d%02d', $nextYear, $nextMonth);

                $partitions[] = "PARTITION p{$key} VALUES LESS THAN ({$lessThan})";
            }
        }

        $partitions[] = 'PARTITION p_future VALUES LESS THAN MAXVALUE';

        return implode(",\n                    ", $partitions);
    }
};
