<?php

namespace App\Console\Commands;

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
    protected $signature = 'gdacs:update-events {--force : Force update even if cache is valid}';

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
        $this->info('🔄 Starting GDACS events update...');

        try {
            // Cache leeren wenn --force Option verwendet wird
            if ($this->option('force')) {
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
            Log::info('GDACS events updated via command', $result);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ GDACS events update failed: ' . $e->getMessage());
            Log::error('GDACS command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }
}
