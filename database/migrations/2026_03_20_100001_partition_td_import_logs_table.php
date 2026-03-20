<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add monthly RANGE partitioning to td_import_logs.
 *
 * This table is append-only with no foreign keys – ideal for partitioning.
 * Enables fast partition pruning on time-range queries and efficient
 * purging of old log data by dropping entire partitions.
 *
 * created_at is converted from TIMESTAMP to DATETIME because MySQL
 * does not allow timezone-dependent types in partition expressions.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $partitions = $this->generateMonthlyPartitions();

        // 1. Convert created_at from TIMESTAMP to DATETIME (required for partitioning)
        DB::statement('ALTER TABLE td_import_logs MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

        // 2. Ensure composite PK (id, created_at)
        $keys = DB::select("SHOW KEYS FROM td_import_logs WHERE Key_name = 'PRIMARY'");
        $pkColumns = array_map(fn ($k) => $k->Column_name, $keys);

        if (! in_array('created_at', $pkColumns)) {
            DB::statement('ALTER TABLE td_import_logs DROP PRIMARY KEY, ADD PRIMARY KEY (id, created_at)');
        }

        // 3. Add partitioning
        DB::statement("
            ALTER TABLE td_import_logs
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

        DB::statement('ALTER TABLE td_import_logs REMOVE PARTITIONING');
        DB::statement('ALTER TABLE td_import_logs DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        DB::statement('ALTER TABLE td_import_logs MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
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
