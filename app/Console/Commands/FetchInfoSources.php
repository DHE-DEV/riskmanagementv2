<?php

namespace App\Console\Commands;

use App\Jobs\FetchInfoSourceJob;
use App\Models\InfoSource;
use App\Services\FeedFetcherService;
use Illuminate\Console\Command;

class FetchInfoSources extends Command
{
    protected $signature = 'feeds:fetch
                            {--source= : Fetch a specific source by code}
                            {--all : Fetch all active sources, ignoring refresh interval}
                            {--force : Force fetch even if not active}
                            {--sync : Run synchronously instead of using queue}
                            {--queue= : Specify queue name}';

    protected $description = 'Fetch data from configured info sources (RSS feeds, APIs)';

    public function handle(FeedFetcherService $fetcher): int
    {
        $sourceCode = $this->option('source');
        $fetchAll = $this->option('all');
        $force = $this->option('force');
        $runSync = $this->option('sync');
        $queueName = $this->option('queue');

        if ($sourceCode) {
            // Fetch specific source
            $source = InfoSource::where('code', $sourceCode)->first();

            if (!$source) {
                $this->error("Source not found: {$sourceCode}");
                return Command::FAILURE;
            }

            if (!$source->is_active && !$force) {
                $this->warn("Source is not active. Use --force to fetch anyway.");
                return Command::FAILURE;
            }

            if ($runSync) {
                $this->info("Fetching: {$source->name}");
                $stats = $fetcher->fetch($source);
                $this->displayStats($stats);
            } else {
                $this->dispatchJob($source, $queueName);
                $this->info("Job dispatched for: {$source->name}");
            }

            return Command::SUCCESS;
        }

        // Fetch all sources
        $query = InfoSource::query();

        if (!$force) {
            $query->active();
        }

        if (!$fetchAll) {
            $query->needsRefresh();
        }

        $sources = $query->ordered()->get();

        if ($sources->isEmpty()) {
            $this->info('No sources need fetching.');
            return Command::SUCCESS;
        }

        if ($runSync) {
            return $this->fetchSynchronously($sources, $fetcher);
        }

        return $this->dispatchJobs($sources, $queueName);
    }

    protected function fetchSynchronously($sources, FeedFetcherService $fetcher): int
    {
        $this->info("Fetching {$sources->count()} source(s) synchronously...");
        $this->newLine();

        $totalStats = ['fetched' => 0, 'new' => 0, 'updated' => 0, 'errors' => 0];

        foreach ($sources as $source) {
            $this->line("  [{$source->code}] {$source->name}");

            $stats = $fetcher->fetch($source);

            if ($stats['errors'] > 0) {
                $this->error("    Error: {$source->last_error_message}");
            } else {
                $this->info("    Fetched: {$stats['fetched']}, New: {$stats['new']}, Updated: {$stats['updated']}");
            }

            $totalStats['fetched'] += $stats['fetched'];
            $totalStats['new'] += $stats['new'];
            $totalStats['updated'] += $stats['updated'];
            $totalStats['errors'] += $stats['errors'];
        }

        $this->newLine();
        $this->info('Summary:');
        $this->displayStats($totalStats);

        return $totalStats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function dispatchJobs($sources, ?string $queueName): int
    {
        $this->info("Dispatching {$sources->count()} job(s) to queue...");
        $this->newLine();

        foreach ($sources as $source) {
            $this->dispatchJob($source, $queueName);
            $this->line("  [{$source->code}] {$source->name} - Job dispatched");
        }

        $this->newLine();
        $this->info("All {$sources->count()} jobs dispatched to queue.");
        $this->info("Monitor progress at: /admin/queue-monitor");

        return Command::SUCCESS;
    }

    protected function dispatchJob(InfoSource $source, ?string $queueName): void
    {
        $job = new FetchInfoSourceJob($source);

        if ($queueName) {
            $job->onQueue($queueName);
        }

        dispatch($job);
    }

    protected function displayStats(array $stats): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Fetched', $stats['fetched']],
                ['New', $stats['new']],
                ['Updated', $stats['updated']],
                ['Errors', $stats['errors']],
            ]
        );
    }
}
