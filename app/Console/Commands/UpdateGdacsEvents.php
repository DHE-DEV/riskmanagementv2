<?php

namespace App\Console\Commands;

use App\Jobs\UpdateGdacsEventsJob;
use App\Services\GdacsApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateGdacsEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gdacs:update-events {--force : Force update even if cache is valid} {--sync : Run synchronously instead of using queue} {--queue= : Specify queue name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update GDACS events from the API';

    private GdacsApiService $gdacsService;

    public function __construct(GdacsApiService $gdacsService)
    {
        parent::__construct();
        $this->gdacsService = $gdacsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $forceUpdate = $this->option('force');
        $runSync = $this->option('sync');
        $queueName = $this->option('queue');

        if ($runSync) {
            // Synchroner Modus - direkt ausführen
            return $this->runSynchronously($forceUpdate);
        }

        // Queue Job dispatchen
        return $this->dispatchQueueJob($forceUpdate, $queueName);
    }

    private function runSynchronously(bool $forceUpdate): int
    {
        $this->info('🔄 Starting GDACS events update (synchronous mode)...');

        try {
            // Cache leeren wenn --force Option verwendet wird
            if ($forceUpdate) {
                $this->gdacsService->clearCache();
                $this->info('🗑️  Cache cleared (force mode)');
            }

            // Events aktualisieren
            $result = $this->gdacsService->updateAllEvents();

            $this->info('✅ GDACS events update completed!');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Events Fetched', $result['fetched']],
                    ['Events Saved', $result['saved']],
                    ['Timestamp', $result['timestamp']],
                ]
            );

            // Log erstellen
            Log::channel('gdacs_sync')->info('GDACS events updated via command (sync)', $result);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ GDACS events update failed: ' . $e->getMessage());
            Log::channel('gdacs_sync')->error('GDACS command failed (sync)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }

    private function dispatchQueueJob(bool $forceUpdate, ?string $queueName): int
    {
        try {
            $this->info('📋 Dispatching GDACS update job to queue...');

            $job = new UpdateGdacsEventsJob($forceUpdate);

            if ($queueName) {
                $job->onQueue($queueName);
                $this->info("📤 Job dispatched to queue: {$queueName}");
            } else {
                $this->info('📤 Job dispatched to default queue');
            }

            dispatch($job);

            $this->info('✅ GDACS update job successfully dispatched!');
            
            Log::channel('gdacs_sync')->info('GDACS update job dispatched', [
                'force_update' => $forceUpdate,
                'queue' => $queueName ?? 'default',
                'command_timestamp' => now()->toISOString()
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to dispatch GDACS update job: ' . $e->getMessage());
            
            Log::channel('gdacs_sync')->error('Failed to dispatch GDACS job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }
}
