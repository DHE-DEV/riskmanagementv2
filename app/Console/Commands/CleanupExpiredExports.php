<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BranchExport;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupExpiredExports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exports:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired branch export files (older than 72 hours)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Find all expired exports
        $expiredExports = BranchExport::where('expires_at', '<=', $now)->get();

        if ($expiredExports->isEmpty()) {
            $this->info('No expired exports found.');
            return 0;
        }

        $count = 0;
        foreach ($expiredExports as $export) {
            $filePath = 'exports/' . $export->filename;

            // Delete file from storage
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
                $this->info("Deleted file: {$export->filename}");
            }

            // Delete database record
            $export->delete();
            $count++;
        }

        $this->info("Successfully cleaned up {$count} expired export(s).");
        return 0;
    }
}
