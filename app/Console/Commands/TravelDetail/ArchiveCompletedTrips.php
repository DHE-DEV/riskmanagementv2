<?php

namespace App\Console\Commands\TravelDetail;

use App\Services\TravelDetail\TripArchivalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ArchiveCompletedTrips extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'td:archive-trips
        {--days= : Archive trips completed more than N days ago (default: from config)}
        {--batch= : Batch size for processing (default: from config)}
        {--dry-run : Show what would be archived without making changes}
        {--move-to-archive : Move records to archive database instead of just marking}
        {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Archive completed travel detail trips';

    public function __construct(
        private TripArchivalService $archivalService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('travel_detail.enabled')) {
            $this->error('Travel Detail module is not enabled');
            return self::FAILURE;
        }

        if (!config('travel_detail.archival.enabled')) {
            $this->error('Archival is not enabled in configuration');
            return self::FAILURE;
        }

        $days = (int) ($this->option('days') ?? config('travel_detail.archival.days_after_completion', 30));
        $batchSize = (int) ($this->option('batch') ?? config('travel_detail.archival.batch_size', 1000));
        $dryRun = $this->option('dry-run');
        $moveToArchive = $this->option('move-to-archive');

        $cutoffDate = now()->subDays($days);

        $this->info("Finding trips completed before {$cutoffDate->toDateString()}...");

        $query = $this->archivalService->getArchivableTripsQuery($days);
        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No trips to archive.');
            return self::SUCCESS;
        }

        $this->info("Found {$totalCount} trips to archive.");

        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made.');

            // Show sample of trips that would be archived
            $sample = $query->limit(10)->get(['id', 'external_trip_id', 'provider_id', 'computed_end_at']);
            $this->table(
                ['ID', 'External ID', 'Provider', 'Completed At'],
                $sample->map(fn($t) => [
                    $t->id,
                    $t->external_trip_id,
                    $t->provider_id,
                    $t->computed_end_at?->toDateString(),
                ])
            );

            if ($totalCount > 10) {
                $this->info("... and " . ($totalCount - 10) . " more trips");
            }

            return self::SUCCESS;
        }

        // Confirm unless --force is used
        if (!$this->option('force')) {
            $action = $moveToArchive ? 'move to archive database' : 'mark as archived';
            if (!$this->confirm("Are you sure you want to {$action} {$totalCount} trips?")) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $totalArchived = 0;
        $totalMoved = 0;
        $totalFailed = 0;

        // Process in batches
        while (true) {
            $results = $this->archivalService->archiveBatch($batchSize, $moveToArchive, $days);

            if ($results['processed'] === 0) {
                break;
            }

            $totalArchived += $results['archived'];
            $totalMoved += $results['moved'];
            $totalFailed += $results['failed'];

            $bar->advance($results['processed']);
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Archival completed:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Marked as archived', $totalArchived],
                ['Moved to archive DB', $totalMoved],
                ['Failed', $totalFailed],
            ]
        );

        // Log completion
        Log::channel(config('travel_detail.logging.channel'))
            ->info('Archival command completed', [
                'archived' => $totalArchived,
                'moved' => $totalMoved,
                'failed' => $totalFailed,
                'cutoff_date' => $cutoffDate->toDateString(),
            ]);

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
