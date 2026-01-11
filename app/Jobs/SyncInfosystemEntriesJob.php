<?php

namespace App\Jobs;

use App\Services\PassolutionApiService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncInfosystemEntriesJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

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

    private int $limit;

    private string $lang;

    /**
     * Create a new job instance.
     */
    public function __construct(int $limit = 100, string $lang = 'de')
    {
        $this->limit = $limit;
        $this->lang = $lang;
    }

    /**
     * Execute the job.
     */
    public function handle(PassolutionApiService $apiService): void
    {
        $startTime = microtime(true);

        try {
            Log::info('Infosystem Sync Job started', [
                'job_id' => $this->job?->getJobId(),
                'attempt' => $this->attempts(),
                'limit' => $this->limit,
                'lang' => $this->lang,
                'queue' => $this->queue ?? 'default',
            ]);

            if (! $apiService->hasValidCredentials()) {
                throw new Exception('Passolution API credentials not configured');
            }

            $result = $apiService->fetchAndStoreMultiple($this->lang, $this->limit);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Infosystem Sync Job completed successfully', [
                'job_id' => $this->job?->getJobId(),
                'execution_time_ms' => $executionTime,
                'entries_stored' => $result['stored'],
                'pages_fetched' => $result['pages_fetched'],
                'attempt' => $this->attempts(),
            ]);

        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Infosystem Sync Job failed', [
                'job_id' => $this->job?->getJobId(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
                'execution_time_ms' => $executionTime,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->attempts() >= $this->tries) {
                Log::critical('Infosystem Sync Job failed permanently after all attempts', [
                    'job_id' => $this->job?->getJobId(),
                    'attempts' => $this->attempts(),
                    'error' => $e->getMessage(),
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
        Log::critical('Infosystem Sync Job marked as permanently failed', [
            'job_id' => $this->job?->getJobId(),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
