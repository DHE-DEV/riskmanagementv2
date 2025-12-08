<?php

namespace App\Console\Commands\TravelDetail;

use App\Models\TravelDetail\TdImportLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneImportLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'td:prune-logs
        {--days= : Delete logs older than N days (default: from config)}
        {--dry-run : Show what would be deleted without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Prune old travel detail import logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('travel_detail.enabled')) {
            $this->error('Travel Detail module is not enabled');
            return self::FAILURE;
        }

        $days = (int) ($this->option('days') ?? config('travel_detail.import_logs.retention_days', 90));
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subDays($days);

        $this->info("Finding import logs older than {$cutoffDate->toDateString()}...");

        $query = TdImportLog::where('created_at', '<', $cutoffDate);
        $count = $query->count();

        if ($count === 0) {
            $this->info('No logs to prune.');
            return self::SUCCESS;
        }

        $this->info("Found {$count} logs to delete.");

        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made.');

            // Show breakdown by status
            $breakdown = TdImportLog::where('created_at', '<', $cutoffDate)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();

            $this->table(
                ['Status', 'Count'],
                $breakdown->map(fn($row) => [$row->status, $row->count])
            );

            return self::SUCCESS;
        }

        // Delete logs
        $deleted = $query->delete();

        $this->info("Deleted {$deleted} import logs.");

        Log::channel(config('travel_detail.logging.channel'))
            ->info('Pruned import logs', [
                'deleted' => $deleted,
                'cutoff_date' => $cutoffDate->toDateString(),
            ]);

        return self::SUCCESS;
    }
}
