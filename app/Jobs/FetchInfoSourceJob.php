<?php

namespace App\Jobs;

use App\Models\InfoSource;
use App\Services\FeedFetcherService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchInfoSourceJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Indicate if the job should be marked as failed on timeout.
     */
    public bool $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public InfoSource $infoSource
    ) {}

    /**
     * Get the display name for the queued job.
     */
    public function displayName(): string
    {
        return "FetchInfoSource: {$this->infoSource->name}";
    }

    /**
     * Execute the job.
     */
    public function handle(FeedFetcherService $fetcher): void
    {
        $startTime = microtime(true);

        try {
            Log::info('FetchInfoSourceJob started', [
                'job_id' => $this->job?->getJobId(),
                'source_id' => $this->infoSource->id,
                'source_code' => $this->infoSource->code,
                'source_name' => $this->infoSource->name,
                'attempt' => $this->attempts(),
            ]);

            $stats = $fetcher->fetch($this->infoSource);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($stats['errors'] > 0) {
                Log::warning('FetchInfoSourceJob completed with errors', [
                    'job_id' => $this->job?->getJobId(),
                    'source_code' => $this->infoSource->code,
                    'execution_time_ms' => $executionTime,
                    'stats' => $stats,
                    'error_message' => $this->infoSource->last_error_message,
                ]);
            } else {
                Log::info('FetchInfoSourceJob completed successfully', [
                    'job_id' => $this->job?->getJobId(),
                    'source_code' => $this->infoSource->code,
                    'execution_time_ms' => $executionTime,
                    'fetched' => $stats['fetched'],
                    'new' => $stats['new'],
                    'updated' => $stats['updated'],
                ]);
            }

        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('FetchInfoSourceJob failed', [
                'job_id' => $this->job?->getJobId(),
                'source_code' => $this->infoSource->code,
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
                'execution_time_ms' => $executionTime,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::critical('FetchInfoSourceJob permanently failed', [
            'source_id' => $this->infoSource->id,
            'source_code' => $this->infoSource->code,
            'source_name' => $this->infoSource->name,
            'exception' => $exception->getMessage(),
        ]);

        // Update source with error
        $this->infoSource->update([
            'last_error_message' => $exception->getMessage(),
            'last_fetched_at' => now(),
        ]);
    }
}
