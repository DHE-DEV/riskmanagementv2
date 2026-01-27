<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GtmApiLogCleanupCommand extends Command
{
    protected $signature = 'gtm:cleanup-logs {--months=24 : Number of months to retain}';

    protected $description = 'Drop old partitions from gtm_api_request_logs table';

    public function handle(): int
    {
        $months = (int) $this->option('months');

        if ($months < 1) {
            $this->error('Months must be at least 1.');
            return self::FAILURE;
        }

        $cutoff = now()->subMonths($months);
        $cutoffValue = (int) $cutoff->format('Ym');

        $this->info("Dropping partitions older than {$cutoff->format('Y-m')} (value < {$cutoffValue})...");

        try {
            // Get existing partitions
            $partitions = DB::select(
                "SELECT PARTITION_NAME, PARTITION_DESCRIPTION
                 FROM INFORMATION_SCHEMA.PARTITIONS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'gtm_api_request_logs'
                   AND PARTITION_NAME IS NOT NULL
                   AND PARTITION_NAME != 'p_future'
                 ORDER BY PARTITION_DESCRIPTION ASC"
            );

            $dropped = 0;

            foreach ($partitions as $partition) {
                $partitionValue = (int) $partition->PARTITION_DESCRIPTION;

                // The PARTITION_DESCRIPTION contains the LESS THAN value,
                // so if it's <= cutoffValue, the partition contains data older than cutoff
                if ($partitionValue <= $cutoffValue) {
                    $partitionName = $partition->PARTITION_NAME;
                    $this->line("Dropping partition: {$partitionName} (< {$partitionValue})");

                    DB::statement("ALTER TABLE gtm_api_request_logs DROP PARTITION {$partitionName}");
                    $dropped++;
                }
            }

            if ($dropped === 0) {
                $this->info('No partitions to drop.');
            } else {
                $this->info("Successfully dropped {$dropped} partition(s).");
                Log::info("GTM API log cleanup: dropped {$dropped} partition(s) older than {$cutoff->format('Y-m')}");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to cleanup partitions: {$e->getMessage()}");
            Log::error("GTM API log cleanup failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
