<?php

namespace App\Jobs\Folder;

use App\Models\Folder\Folder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateFolderStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $folderId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $folderId)
    {
        $this->folderId = $folderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $folder = Folder::withoutGlobalScope('customer')
                ->with(['participants', 'itineraries'])
                ->findOrFail($this->folderId);

            // Update folder statistics
            $folder->updateStatistics();

            // Calculate total amounts for all itineraries
            foreach ($folder->itineraries as $itinerary) {
                $itinerary->calculateTotalAmount();
            }

            // Recalculate folder total value
            $folder->updateStatistics();

            Log::info('Folder statistics calculated successfully', [
                'folder_id' => $this->folderId,
                'total_participants' => $folder->total_participants,
                'total_itineraries' => $folder->total_itineraries,
                'total_value' => $folder->total_value,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to calculate folder statistics', [
                'folder_id' => $this->folderId,
                'error' => $e->getMessage(),
            ]);

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
    public int $backoff = 30;
}
