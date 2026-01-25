<?php

namespace App\Jobs\Folder;

use App\Models\Folder\Folder;
use App\Services\Folder\TimelineBuilderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RebuildFolderTimelineJob implements ShouldQueue
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
    public function handle(TimelineBuilderService $timelineBuilder): void
    {
        try {
            $folder = Folder::withoutGlobalScope('customer')
                ->with([
                    'itineraries.flightServices.segments',
                    'itineraries.hotelServices',
                    'itineraries.shipServices',
                    'itineraries.carRentalServices',
                    'itineraries.participants',
                ])
                ->findOrFail($this->folderId);

            $locationsCreated = $timelineBuilder->rebuildForFolder($folder);

            Log::info('Folder timeline rebuilt successfully', [
                'folder_id' => $this->folderId,
                'locations_created' => $locationsCreated,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to rebuild folder timeline', [
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
    public int $backoff = 60;
}
