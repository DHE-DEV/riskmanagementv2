<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $partitions = [];

        // Generate 360 monthly partitions from 202601 to 205512
        for ($year = 2026; $year <= 2055; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $partitionKey = sprintf('%d%02d', $year, $month);

                // Calculate next month boundary
                $nextYear = $month === 12 ? $year + 1 : $year;
                $nextMonth = $month === 12 ? 1 : $month + 1;
                $lessThan = sprintf('%d%02d', $nextYear, $nextMonth);

                $partitions[] = "PARTITION p{$partitionKey} VALUES LESS THAN ({$lessThan})";
            }
        }

        // Add future catch-all partition
        $partitions[] = 'PARTITION p_future VALUES LESS THAN MAXVALUE';

        $partitionsSql = implode(",\n    ", $partitions);

        DB::statement("
            CREATE TABLE gtm_api_request_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                customer_id BIGINT UNSIGNED NOT NULL,
                token_id BIGINT UNSIGNED NULL,
                method VARCHAR(10) NOT NULL,
                endpoint VARCHAR(255) NOT NULL,
                query_params JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(512) NULL,
                response_status SMALLINT UNSIGNED NOT NULL,
                response_time_ms INT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id, created_at),
                INDEX idx_customer_created (customer_id, created_at),
                INDEX idx_created_at (created_at),
                INDEX idx_response_status (response_status, created_at)
            ) PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
                {$partitionsSql}
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS gtm_api_request_logs');
    }
};
