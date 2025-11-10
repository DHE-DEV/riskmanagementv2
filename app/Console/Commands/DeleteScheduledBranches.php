<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use Carbon\Carbon;

class DeleteScheduledBranches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'branches:delete-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete branches that are scheduled for deletion';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Find all branches with scheduled_deletion_at <= now
        $branches = Branch::whereNotNull('scheduled_deletion_at')
            ->where('scheduled_deletion_at', '<=', $now)
            ->get();

        if ($branches->isEmpty()) {
            $this->info('No branches scheduled for deletion.');
            return 0;
        }

        $count = 0;
        foreach ($branches as $branch) {
            $this->info("Deleting branch: {$branch->name} (ID: {$branch->id})");
            $branch->delete();
            $count++;
        }

        $this->info("Successfully deleted {$count} branch(es).");
        return 0;
    }
}
