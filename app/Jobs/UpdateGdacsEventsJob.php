<?php

namespace App\Jobs;

use App\Services\GdacsApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class UpdateGdacsEventsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 Minuten

    /**
     * Indicate if the job should be marked as failed on timeout.
     */
    public bool $failOnTimeout = true;

    private bool $forceUpdate;

    /**
     * Create a new job instance.
     */
    public function __construct(bool $forceUpdate = false)
    {
        $this->forceUpdate = $forceUpdate;
    }

    /**
     * Execute the job.
     */
    public function handle(GdacsApiService $gdacsService): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info('GDACS Update Job started', [
                'job_id' => $this->job?->getJobId(),
                'attempt' => $this->attempts(),
                'force_update' => $this->forceUpdate,
                'queue' => $this->queue ?? 'default'
            ]);

            // Cache leeren wenn Force-Update
            if ($this->forceUpdate) {
                $gdacsService->clearCache();
                Log::info('GDACS Cache cleared (force mode)');
            }

            // Events aktualisieren
            $result = $gdacsService->updateAllEvents();
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Erfolgreiche Ausführung loggen
            Log::info('GDACS Update Job completed successfully', [
                'job_id' => $this->job?->getJobId(),
                'execution_time_ms' => $executionTime,
                'events_fetched' => $result['fetched'],
                'events_saved' => $result['saved'],
                'timestamp' => $result['timestamp'],
                'attempt' => $this->attempts()
            ]);

            // Monitoring-Daten für spätere Auswertung
            $this->recordMonitoringData($result, $executionTime, true);

        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('GDACS Update Job failed', [
                'job_id' => $this->job?->getJobId(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
                'execution_time_ms' => $executionTime,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Monitoring-Daten für Fehlerfall
            $this->recordMonitoringData(null, $executionTime, false, $e->getMessage());

            // Job als fehlgeschlagen markieren wenn alle Versuche aufgebraucht
            if ($this->attempts() >= $this->tries) {
                Log::critical('GDACS Update Job failed permanently after all attempts', [
                    'job_id' => $this->job?->getJobId(),
                    'attempts' => $this->attempts(),
                    'error' => $e->getMessage()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::critical('GDACS Update Job marked as permanently failed', [
            'job_id' => $this->job?->getJobId(),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
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
            'attempt' => $this->attempts(),
            'queue' => $this->queue ?? 'default',
            'job_id' => $this->job?->getJobId(),
        ];

        if ($success && $result) {
            $monitoringData['events_fetched'] = $result['fetched'];
            $monitoringData['events_saved'] = $result['saved'];
        }

        if (!$success && $error) {
            $monitoringData['error'] = $error;
        }

        // Monitoring-Daten in separaten Log-Channel (falls konfiguriert)
        Log::channel('gdacs_monitoring')->info('GDACS Job Execution', $monitoringData);
    }
}