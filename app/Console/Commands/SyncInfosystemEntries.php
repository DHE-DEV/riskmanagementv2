<?php

namespace App\Console\Commands;

use App\Jobs\SyncInfosystemEntriesJob;
use App\Services\PassolutionApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncInfosystemEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'infosystem:sync
        {--limit=100 : Maximum number of entries to fetch}
        {--lang=de : Language code (de, en, fr, it)}
        {--sync : Run synchronously instead of using queue}
        {--queue= : Specify queue name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync infosystem entries from Passolution API';

    public function __construct(private PassolutionApiService $apiService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('services.passolution.sync_enabled', true)) {
            $this->error('Infosystem sync is disabled. Set PASSOLUTION_SYNC_ENABLED=true in .env to enable.');

            return self::FAILURE;
        }

        if (! $this->apiService->hasValidCredentials()) {
            $this->error('Passolution API credentials not configured. Set PASSOLUTION_API_KEY in .env.');

            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $lang = $this->option('lang');
        $runSync = $this->option('sync');
        $queueName = $this->option('queue');

        if ($runSync) {
            return $this->runSynchronously($limit, $lang);
        }

        return $this->dispatchQueueJob($limit, $lang, $queueName);
    }

    private function runSynchronously(int $limit, string $lang): int
    {
        $startTime = microtime(true);
        $this->info("Starting Infosystem sync (synchronous mode, limit: {$limit}, lang: {$lang})...");

        try {
            $result = $this->apiService->fetchAndStoreMultiple($lang, $limit);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            if (! $result['success']) {
                $this->error('Sync failed: '.implode(', ', $result['errors']));

                return self::FAILURE;
            }

            $this->info('Infosystem sync completed!');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Entries Stored', $result['stored']],
                    ['Pages Fetched', $result['pages_fetched']],
                    ['Last Page', $result['last_page'] ?? 'N/A'],
                    ['Execution Time', $executionTime.'ms'],
                ]
            );

            Log::info('Infosystem entries synced via command (sync)', array_merge($result, [
                'execution_time_ms' => $executionTime,
            ]));

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync failed: '.$e->getMessage());
            Log::error('Infosystem sync command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    private function dispatchQueueJob(int $limit, string $lang, ?string $queueName): int
    {
        try {
            $this->info('Dispatching Infosystem sync job to queue...');

            $job = new SyncInfosystemEntriesJob($limit, $lang);

            if ($queueName) {
                $job->onQueue($queueName);
                $this->info("Job dispatched to queue: {$queueName}");
            } else {
                $this->info('Job dispatched to default queue');
            }

            dispatch($job);

            $this->info('Infosystem sync job successfully dispatched!');

            Log::info('Infosystem sync job dispatched', [
                'limit' => $limit,
                'lang' => $lang,
                'queue' => $queueName ?? 'default',
                'command_timestamp' => now()->toISOString(),
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to dispatch sync job: '.$e->getMessage());
            Log::error('Failed to dispatch Infosystem sync job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
