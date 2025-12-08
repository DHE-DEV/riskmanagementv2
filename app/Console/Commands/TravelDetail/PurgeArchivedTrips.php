<?php

namespace App\Console\Commands\TravelDetail;

use App\Models\TravelDetail\TdTrip;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurgeArchivedTrips extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'td:purge-archived
        {--years= : Delete archived trips older than N years (default: from config)}
        {--batch=1000 : Batch size for deletion}
        {--dry-run : Show what would be deleted without making changes}
        {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Permanently delete archived trips older than the retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('travel_detail.enabled')) {
            $this->error('Travel Detail module is not enabled');
            return self::FAILURE;
        }

        $years = (int) ($this->option('years') ?? config('travel_detail.retention.purge_archived_after_years', 2));
        $batchSize = (int) $this->option('batch');
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subYears($years);

        $this->info("Finding archived trips older than {$cutoffDate->toDateString()} ({$years} years)...");

        $query = TdTrip::where('is_archived', true)
            ->where('computed_end_at', '<', $cutoffDate);

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No archived trips to purge.');
            return self::SUCCESS;
        }

        $this->info("Found {$totalCount} archived trips to permanently delete.");

        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made.');

            // Show breakdown by provider
            $breakdown = TdTrip::where('is_archived', true)
                ->where('computed_end_at', '<', $cutoffDate)
                ->selectRaw('provider_id, COUNT(*) as count, MIN(computed_end_at) as oldest, MAX(computed_end_at) as newest')
                ->groupBy('provider_id')
                ->get();

            $this->table(
                ['Provider', 'Count', 'Oldest Trip', 'Newest Trip'],
                $breakdown->map(fn($row) => [
                    $row->provider_id,
                    $row->count,
                    $row->oldest?->toDateString(),
                    $row->newest?->toDateString(),
                ])
            );

            // Calculate related records that would be deleted
            $tripIds = $query->limit(10000)->pluck('id');
            $this->newLine();
            $this->info('Related records that would be deleted:');
            $this->table(
                ['Table', 'Estimated Count'],
                [
                    ['td_air_legs', DB::table('td_air_legs')->whereIn('trip_id', $tripIds)->count()],
                    ['td_flight_segments', DB::table('td_flight_segments')->whereIn('trip_id', $tripIds)->count()],
                    ['td_stays', DB::table('td_stays')->whereIn('trip_id', $tripIds)->count()],
                    ['td_transfers', DB::table('td_transfers')->whereIn('trip_id', $tripIds)->count()],
                    ['td_trip_locations', DB::table('td_trip_locations')->whereIn('trip_id', $tripIds)->count()],
                    ['td_pds_share_links', DB::table('td_pds_share_links')->whereIn('trip_id', $tripIds)->count()],
                ]
            );

            return self::SUCCESS;
        }

        // Confirm unless --force is used
        if (!$this->option('force')) {
            $this->warn('WARNING: This will PERMANENTLY DELETE data that cannot be recovered!');
            if (!$this->confirm("Are you sure you want to delete {$totalCount} archived trips and all related data?")) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $totalDeleted = 0;
        $totalFailed = 0;

        // Process in batches
        while (true) {
            $tripIds = TdTrip::where('is_archived', true)
                ->where('computed_end_at', '<', $cutoffDate)
                ->limit($batchSize)
                ->pluck('id');

            if ($tripIds->isEmpty()) {
                break;
            }

            try {
                DB::transaction(function () use ($tripIds) {
                    // Delete related records first (foreign keys will cascade, but explicit is clearer)
                    DB::table('td_pds_share_links')->whereIn('trip_id', $tripIds)->delete();
                    DB::table('td_trip_locations')->whereIn('trip_id', $tripIds)->delete();
                    DB::table('td_transfers')->whereIn('trip_id', $tripIds)->delete();
                    DB::table('td_stays')->whereIn('trip_id', $tripIds)->delete();

                    // Delete flight segments (need air_leg_ids first)
                    $airLegIds = DB::table('td_air_legs')->whereIn('trip_id', $tripIds)->pluck('id');
                    DB::table('td_flight_segments')->whereIn('air_leg_id', $airLegIds)->delete();
                    DB::table('td_air_legs')->whereIn('trip_id', $tripIds)->delete();

                    // Finally delete the trips (force delete to bypass soft deletes)
                    TdTrip::whereIn('id', $tripIds)->forceDelete();
                });

                $totalDeleted += $tripIds->count();
            } catch (\Exception $e) {
                $totalFailed += $tripIds->count();
                Log::channel(config('travel_detail.logging.channel'))
                    ->error('Failed to purge archived trips batch', [
                        'error' => $e->getMessage(),
                        'trip_ids' => $tripIds->toArray(),
                    ]);
            }

            $bar->advance($tripIds->count());
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Purge completed:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Permanently deleted', $totalDeleted],
                ['Failed', $totalFailed],
            ]
        );

        // Log completion
        Log::channel(config('travel_detail.logging.channel'))
            ->info('Purge archived trips completed', [
                'deleted' => $totalDeleted,
                'failed' => $totalFailed,
                'cutoff_date' => $cutoffDate->toDateString(),
                'years' => $years,
            ]);

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
