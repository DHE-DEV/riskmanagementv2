<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create td_pds_sync_log table for tracking PDS API sync status per customer.
 *
 * Monthly RANGE partitioned for millions of records.
 * Tracks when each customer was last synced, how many trips were
 * created/updated/unchanged, and any errors encountered.
 */
return new class extends Migration
{
    public function up(): void
    {
        $partitions = $this->generateMonthlyPartitions();

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("
                CREATE TABLE td_pds_sync_log (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    customer_id BIGINT UNSIGNED NOT NULL,
                    status ENUM('running', 'success', 'partial', 'failed') NOT NULL DEFAULT 'running',
                    trips_fetched INT UNSIGNED NOT NULL DEFAULT 0,
                    trips_created INT UNSIGNED NOT NULL DEFAULT 0,
                    trips_updated INT UNSIGNED NOT NULL DEFAULT 0,
                    trips_unchanged INT UNSIGNED NOT NULL DEFAULT 0,
                    trips_total_api INT UNSIGNED NULL COMMENT 'Total trips reported by API',
                    pages_fetched INT UNSIGNED NOT NULL DEFAULT 0,
                    error_message TEXT NULL,
                    duration_ms INT UNSIGNED NULL,
                    started_at DATETIME NOT NULL,
                    completed_at DATETIME NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id, created_at),
                    INDEX idx_customer_created (customer_id, created_at),
                    INDEX idx_status (status, created_at),
                    INDEX idx_customer_status (customer_id, status)
                ) PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
                    {$partitions}
                )
            ");
        } else {
            // Fallback for non-MySQL (testing)
            DB::statement("
                CREATE TABLE td_pds_sync_log (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    customer_id BIGINT UNSIGNED NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'running',
                    trips_fetched INT UNSIGNED NOT NULL DEFAULT 0,
                    trips_created INT UNSIGNED NOT NULL DEFAULT 0,
                    trips_updated INT UNSIGNED NOT NULL DEFAULT 0,
                    trips_unchanged INT UNSIGNED NOT NULL DEFAULT 0,
                    trips_total_api INT UNSIGNED NULL,
                    pages_fetched INT UNSIGNED NOT NULL DEFAULT 0,
                    error_message TEXT NULL,
                    duration_ms INT UNSIGNED NULL,
                    started_at DATETIME NOT NULL,
                    completed_at DATETIME NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS td_pds_sync_log');
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
