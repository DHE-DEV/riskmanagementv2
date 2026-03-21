<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add monthly RANGE partitioning to td_flight_segments.
 *
 * High-volume table with many segments per trip. Partitioned by created_at
 * for consistent archival alignment with the trip lifecycle.
 *
 * Trade-offs:
 * - FKs to td_trips and td_air_legs must be dropped (MySQL constraint;
 *   cascade handled by TripArchivalService)
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

        // 1. Drop FKs (MySQL does not allow FKs on partitioned tables)
        //    Referential integrity is enforced by TripArchivalService cascade logic.
        $fks = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_NAME = 'td_flight_segments' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND TABLE_SCHEMA = DATABASE()");
        if (count($fks) > 0) {
            Schema::table('td_flight_segments', function ($table) use ($fks) {
                foreach ($fks as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
            });
        }

        // 2. Convert created_at from TIMESTAMP to DATETIME NOT NULL (required for partitioning)
        DB::statement('ALTER TABLE td_flight_segments MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

        // 3. Change PK to composite
        DB::statement('ALTER TABLE td_flight_segments DROP PRIMARY KEY, ADD PRIMARY KEY (id, created_at)');

        // 4. Add partitioning
        DB::statement("
            ALTER TABLE td_flight_segments
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
        DB::statement('ALTER TABLE td_flight_segments REMOVE PARTITIONING');
        DB::statement('ALTER TABLE td_flight_segments DROP PRIMARY KEY, ADD PRIMARY KEY (id)');

        // 2. Restore DATETIME → TIMESTAMP
        DB::statement('ALTER TABLE td_flight_segments MODIFY created_at TIMESTAMP NULL DEFAULT NULL');

        // 3. Restore FKs
        Schema::table('td_flight_segments', function ($table) {
            $table->foreign('air_leg_id')->references('id')->on('td_air_legs')->cascadeOnDelete();
            $table->foreign('trip_id')->references('id')->on('td_trips')->cascadeOnDelete();
        });
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
