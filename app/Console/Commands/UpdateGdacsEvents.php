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
        $startTime = microtime(true);
        $this->info('🔄 Starting GDACS events update (synchronous mode)...');

        try {
            // Cache leeren wenn --force Option verwendet wird
            if ($forceUpdate) {
                $this->gdacsService->clearCache();
                $this->info('🗑️  Cache cleared (force mode)');
            }

            // Events aktualisieren
            $result = $this->gdacsService->updateAllEvents();
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->info('✅ GDACS events update completed!');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Events Fetched', $result['fetched']],
                    ['Events Saved', $result['saved']],
                    ['Execution Time', $executionTime . 'ms'],
                    ['Timestamp', $result['timestamp']],
                ]
            );

            // Standard Log erstellen
            Log::channel('gdacs_sync')->info('GDACS events updated via command (sync)', array_merge($result, [
                'execution_time_ms' => $executionTime
            ]));

            // Monitoring-Daten für Analyse
            $this->recordMonitoringData($result, $executionTime, true);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->error('❌ GDACS events update failed: ' . $e->getMessage());
            Log::channel('gdacs_sync')->error('GDACS command failed (sync)', [
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTime,
                'trace' => $e->getTraceAsString()
            ]);

            // Monitoring-Daten für Fehlerfall
            $this->recordMonitoringData(null, $executionTime, false, $e->getMessage());

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

    /**
     * Record monitoring data for analysis
     */
    private function recordMonitoringData(?array $result, float $executionTime, bool $success, ?string $error = null): void
    {
        $monitoringData = [
            'timestamp' => now()->toISOString(),
            'execution_time_ms' => $executionTime,
            'success' => $success,
            'attempt' => 1,
            'queue' => 'command',
            'command_type' => 'sync',
        ];

        if ($success && $result) {
            $monitoringData['events_fetched'] = $result['fetched'];
            $monitoringData['events_saved'] = $result['saved'];
        }

        if (!$success && $error) {
            $monitoringData['error'] = $error;
        }

        // Monitoring-Daten in separaten Log-Channel
        Log::channel('gdacs_monitoring')->info('GDACS Command Execution', $monitoringData);
    }
}
