<?php

namespace App\Console\Commands;

use App\Models\InfoSource;
use App\Services\FeedFetcherService;
use Illuminate\Console\Command;

class FetchInfoSources extends Command
{
    protected $signature = 'feeds:fetch
                            {--source= : Fetch a specific source by code}
                            {--all : Fetch all active sources, ignoring refresh interval}
                            {--force : Force fetch even if not active}';

    protected $description = 'Fetch data from configured info sources (RSS feeds, APIs)';

    public function handle(FeedFetcherService $fetcher): int
    {
        $sourceCode = $this->option('source');
        $fetchAll = $this->option('all');
        $force = $this->option('force');

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

            $this->info("Fetching: {$source->name}");
            $stats = $fetcher->fetch($source);
            $this->displayStats($stats);

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

        $this->info("Fetching {$sources->count()} source(s)...");
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
