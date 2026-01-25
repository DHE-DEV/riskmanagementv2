<?php

namespace App\Jobs\Folder;

use App\Models\Folder\FolderImportLog;
use App\Services\Folder\FolderImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFolderImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $importLogId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $importLogId)
    {
        $this->importLogId = $importLogId;
    }

    /**
     * Execute the job.
     */
    public function handle(FolderImportService $importService): void
    {
        try {
            $importLog = FolderImportLog::withoutGlobalScope('customer')
                ->findOrFail($this->importLogId);

            $success = $importService->import($importLog);

            if ($success) {
                Log::info('Folder import completed successfully', [
                    'import_log_id' => $this->importLogId,
                    'folder_id' => $importLog->folder_id,
                    'records_imported' => $importLog->records_imported,
                ]);

                // Dispatch timeline rebuild job if folder was created
                if ($importLog->folder_id) {
                    RebuildFolderTimelineJob::dispatch($importLog->folder_id);
                }
            } else {
                Log::error('Folder import failed', [
                    'import_log_id' => $this->importLogId,
                    'error_message' => $importLog->error_message,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process folder import', [
                'import_log_id' => $this->importLogId,
                'error' => $e->getMessage(),
            ]);

            // Mark import as failed
            $importLog = FolderImportLog::withoutGlobalScope('customer')
                ->find($this->importLogId);

            if ($importLog) {
                $importLog->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 120;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 600;
}
