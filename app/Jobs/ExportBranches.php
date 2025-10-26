<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Notifications\BranchExportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ExportBranches implements ShouldQueue
{
    use Queueable;

    protected int $customerId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $customer = Customer::find($this->customerId);

        if (!$customer) {
            \Log::error("Customer not found: {$this->customerId}");
            return;
        }

        // Get all branches for this customer
        $branches = $customer->branches()
            ->orderBy('is_headquarters', 'desc')
            ->orderBy('created_at')
            ->get();

        // Create CSV content
        $csv = "Name,Zusatz,Straße,Hausnummer,PLZ,Stadt,Land,Breitengrad,Längengrad,App-Code,Hauptsitz\n";

        foreach ($branches as $branch) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $this->escapeCsv($branch->name),
                $this->escapeCsv($branch->additional ?? ''),
                $this->escapeCsv($branch->street),
                $this->escapeCsv($branch->house_number ?? ''),
                $this->escapeCsv($branch->postal_code),
                $this->escapeCsv($branch->city),
                $this->escapeCsv($branch->country),
                $branch->latitude ?? '',
                $branch->longitude ?? '',
                $branch->app_code ?? '',
                $branch->is_headquarters ? 'Ja' : 'Nein'
            );
        }

        // Generate unique filename
        $filename = 'branch-export-' . $customer->id . '-' . now()->format('Y-m-d-His') . '.csv';
        $path = 'exports/' . $filename;

        // Store file
        Storage::disk('local')->put($path, $csv);

        // Send notification
        $customer->notify(new BranchExportCompleted($filename, $branches->count()));

        \Log::info("Branch export completed for customer {$this->customerId}: {$filename}");
    }

    private function escapeCsv(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return str_replace('"', '""', $value);
    }
}
