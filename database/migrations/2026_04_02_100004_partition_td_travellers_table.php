<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add monthly RANGE partitioning to td_travellers.
 *
 * Trade-offs:
 * - FK to td_trips already dropped by partition_td_trips migration.
 * - Unique constraint uk_trip_traveller must include created_at for partitioning.
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

        // Drop any remaining FKs
        $fks = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_NAME = 'td_travellers' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND TABLE_SCHEMA = DATABASE()");
        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE td_travellers DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // Convert timestamps to DATETIME
        DB::statement("UPDATE td_travellers SET created_at = NOW() WHERE created_at IS NULL");
        DB::statement('ALTER TABLE td_travellers MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE td_travellers MODIFY updated_at DATETIME NULL');

        // Drop unique constraint that doesn't include partition column, recreate with created_at
        DB::statement('ALTER TABLE td_travellers DROP INDEX uk_trip_traveller');
        DB::statement('ALTER TABLE td_travellers ADD UNIQUE INDEX uk_trip_traveller (trip_id, external_traveller_id, created_at)');

        // Change PK to composite
        DB::statement('ALTER TABLE td_travellers DROP PRIMARY KEY, ADD PRIMARY KEY (id, created_at)');

        // Add partitioning
        DB::statement("
            ALTER TABLE td_travellers
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

        DB::statement('ALTER TABLE td_travellers REMOVE PARTITIONING');
        DB::statement('ALTER TABLE td_travellers DROP PRIMARY KEY, ADD PRIMARY KEY (id)');

        // Restore unique constraint without created_at
        DB::statement('ALTER TABLE td_travellers DROP INDEX uk_trip_traveller');
        DB::statement('ALTER TABLE td_travellers ADD UNIQUE INDEX uk_trip_traveller (trip_id, external_traveller_id)');

        DB::statement('ALTER TABLE td_travellers MODIFY created_at TIMESTAMP NULL');
        DB::statement('ALTER TABLE td_travellers MODIFY updated_at TIMESTAMP NULL');

        Schema::table('td_travellers', function ($t) {
            $t->foreign('trip_id')->references('id')->on('td_trips')->cascadeOnDelete();
        });
    }

    private function generateMonthlyPartitions(): string
    {
        $partitions = [];

        for ($year = 2025; $year <= 2030; $year++) {
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
