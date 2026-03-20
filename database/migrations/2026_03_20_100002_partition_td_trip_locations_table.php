<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add monthly RANGE partitioning to td_trip_locations.
 *
 * This is the highest-volume table (many rows per trip) and the primary
 * target for geo+time range queries. Partitioning enables fast pruning
 * on time-based queries and efficient archival by dropping partitions.
 *
 * Trade-offs:
 * - FK to td_trips must be dropped (MySQL constraint; cascade handled by TripArchivalService)
 * - SPATIAL INDEX on POINT column must be dropped (MySQL does not support spatial indexes
 *   on partitioned tables). Fallback: (lat, lng) index with Haversine formula is already
 *   implemented in the application layer.
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

        // 1. Drop SPATIAL INDEX and POINT column (not supported on partitioned tables)
        DB::statement('DROP INDEX idx_point ON td_trip_locations');
        DB::statement('ALTER TABLE td_trip_locations DROP COLUMN point');

        // 2. Drop FK to td_trips (MySQL does not allow FKs on partitioned tables)
        //    Referential integrity is enforced by TripArchivalService cascade logic.
        Schema::table('td_trip_locations', function ($table) {
            $table->dropForeign(['trip_id']);
        });

        // 3. Convert created_at from TIMESTAMP to DATETIME (required for partitioning)
        DB::statement('ALTER TABLE td_trip_locations MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

        // 4. Change PK to composite
        DB::statement('ALTER TABLE td_trip_locations DROP PRIMARY KEY, ADD PRIMARY KEY (id, created_at)');

        // 5. Add partitioning
        DB::statement("
            ALTER TABLE td_trip_locations
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

        // 1. Remove partitioning and restore simple PK
        DB::statement('ALTER TABLE td_trip_locations REMOVE PARTITIONING');
        DB::statement('ALTER TABLE td_trip_locations DROP PRIMARY KEY, ADD PRIMARY KEY (id)');

        // 2. Restore DATETIME → TIMESTAMP
        DB::statement('ALTER TABLE td_trip_locations MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');

        // 3. Restore FK to td_trips
        Schema::table('td_trip_locations', function ($table) {
            $table->foreign('trip_id')->references('id')->on('td_trips')->cascadeOnDelete();
        });

        // 4. Restore POINT column and SPATIAL INDEX
        DB::statement('ALTER TABLE td_trip_locations ADD COLUMN point POINT NOT NULL SRID 4326 AFTER lng');
        DB::statement('UPDATE td_trip_locations SET point = ST_SRID(ST_Point(lng, lat), 4326)');
        DB::statement('CREATE SPATIAL INDEX idx_point ON td_trip_locations (point)');
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
