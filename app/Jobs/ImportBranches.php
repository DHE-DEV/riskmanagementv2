<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Notifications\BranchImportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportBranches implements ShouldQueue
{
    use Queueable;

    protected $customerId;
    protected $csvData;

    /**
     * Create a new job instance.
     */
    public function __construct($customerId, $csvData)
    {
        $this->customerId = $customerId;
        $this->csvData = $csvData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $customer = Customer::find($this->customerId);
        if (!$customer) {
            Log::error('Customer not found for branch import', ['customer_id' => $this->customerId]);
            return;
        }

        $lines = explode("\n", $this->csvData);
        $imported = 0;
        $failed = 0;
        $errors = [];

        // Überspringe Header-Zeile
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            try {
                // Parse CSV-Zeile
                $values = str_getcsv($line);
                if (!$values || count($values) < 6) {
                    $failed++;
                    continue;
                }

                $branchData = [
                    'name' => $values[0] ?? '',
                    'additional' => $values[1] ?? null,
                    'street' => $values[2] ?? '',
                    'house_number' => $values[3] ?? null,
                    'postal_code' => $values[4] ?? '',
                    'city' => $values[5] ?? '',
                    'country' => $values[6] ?? 'Deutschland',
                ];

                // Geocode the address
                $address = "{$branchData['street']} {$branchData['house_number']}, {$branchData['postal_code']} {$branchData['city']}, {$branchData['country']}";
                $coordinates = $this->geocodeAddress($address);

                // Create branch
                $customer->branches()->create([
                    'name' => $branchData['name'],
                    'additional' => $branchData['additional'],
                    'street' => $branchData['street'],
                    'house_number' => $branchData['house_number'],
                    'postal_code' => $branchData['postal_code'],
                    'city' => $branchData['city'],
                    'country' => $branchData['country'],
                    'latitude' => $coordinates['lat'] ?? null,
                    'longitude' => $coordinates['lon'] ?? null,
                    'is_headquarters' => false,
                ]);

                $imported++;

                // Rate limiting für Nominatim API
                usleep(1000000); // 1 Sekunde Pause
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Zeile " . ($i + 1) . ": " . $e->getMessage();
                Log::error('Branch import error', [
                    'line' => $i + 1,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Sende Benachrichtigung an Kunden
        $customer->notify(new BranchImportCompleted($imported, $failed, $errors));

        Log::info('Branch import completed', [
            'customer_id' => $this->customerId,
            'imported' => $imported,
            'failed' => $failed
        ]);
    }

    private function geocodeAddress(string $address): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Laravel-RiskManagement/1.0'
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->successful() && count($response->json()) > 0) {
                $result = $response->json()[0];
                return [
                    'lat' => $result['lat'],
                    'lon' => $result['lon'],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Geocoding failed: ' . $e->getMessage());
        }

        return [];
    }
}
