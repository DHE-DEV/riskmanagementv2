<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BranchController extends Controller
{
    public function index()
    {
        $customer = auth('customer')->user();
        $branches = $customer->branches()->orderBy('is_headquarters', 'desc')->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'branches' => $branches
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'additional' => 'nullable|string|max:255',
            'street' => 'required|string|max:255',
            'house_number' => 'nullable|string|max:50',
            'postal_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
        ]);

        $customer = auth('customer')->user();

        // Geocode the address
        $address = "{$validated['street']} {$validated['house_number']}, {$validated['postal_code']} {$validated['city']}, {$validated['country']}";
        $coordinates = $this->geocodeAddress($address);

        $branch = $customer->branches()->create([
            'name' => $validated['name'],
            'additional' => $validated['additional'] ?? null,
            'street' => $validated['street'],
            'house_number' => $validated['house_number'] ?? null,
            'postal_code' => $validated['postal_code'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'latitude' => $coordinates['lat'] ?? null,
            'longitude' => $coordinates['lon'] ?? null,
            'is_headquarters' => false,
        ]);

        return response()->json([
            'success' => true,
            'branch' => $branch,
            'message' => 'Filiale erfolgreich hinzugefügt'
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        // Check ownership
        if ($branch->customer_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'additional' => 'nullable|string|max:255',
            'street' => 'required|string|max:255',
            'house_number' => 'nullable|string|max:50',
            'postal_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
        ]);

        // Geocode the address
        $address = "{$validated['street']} {$validated['house_number']}, {$validated['postal_code']} {$validated['city']}, {$validated['country']}";
        $coordinates = $this->geocodeAddress($address);

        $branch->update([
            'name' => $validated['name'],
            'additional' => $validated['additional'],
            'street' => $validated['street'],
            'house_number' => $validated['house_number'],
            'postal_code' => $validated['postal_code'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'latitude' => $coordinates['lat'] ?? $branch->latitude,
            'longitude' => $coordinates['lon'] ?? $branch->longitude,
        ]);

        return response()->json([
            'success' => true,
            'branch' => $branch,
            'message' => 'Filiale erfolgreich aktualisiert'
        ]);
    }

    public function destroy(Request $request, Branch $branch)
    {
        // Check ownership
        if ($branch->customer_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Prevent deletion of headquarters
        if ($branch->is_headquarters) {
            return response()->json([
                'success' => false,
                'message' => 'Der Hauptsitz kann nicht gelöscht werden'
            ], 422);
        }

        // Check if scheduled deletion is requested
        if ($request->has('scheduled_deletion_at') && $request->input('scheduled_deletion_at')) {
            $scheduledDate = $request->input('scheduled_deletion_at');

            // Validate date format and that it's in the future
            $date = \Carbon\Carbon::parse($scheduledDate);
            if ($date->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Das Löschdatum muss in der Zukunft liegen'
                ], 422);
            }

            $branch->scheduled_deletion_at = $date;
            $branch->save();

            return response()->json([
                'success' => true,
                'message' => 'Löschung wurde für ' . $date->format('d.m.Y') . ' geplant'
            ]);
        }

        // Immediate deletion
        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Filiale erfolgreich gelöscht'
        ]);
    }

    public function import(Request $request)
    {
        try {
            $validated = $request->validate([
                'csv_data' => 'required|string',
            ]);

            $customer = auth('customer')->user();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Benutzer nicht authentifiziert.'
                ], 401);
            }

            // Prüfe ob Branch Management aktiviert ist
            if (!$customer->branch_management_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch Management ist nicht aktiviert.'
                ], 403);
            }

            // Dispatch Job
            \App\Jobs\ImportBranches::dispatch($customer->id, $validated['csv_data']);

            \Log::info('Branch import job dispatched', [
                'customer_id' => $customer->id,
                'csv_length' => strlen($validated['csv_data'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Import wurde gestartet. Sie erhalten eine Benachrichtigung, wenn der Import abgeschlossen ist.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Branch import validation failed', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Branch import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ein Fehler ist aufgetreten: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export()
    {
        $customer = auth('customer')->user();

        // Check if there's already a pending or processing export
        $existingExport = \App\Models\BranchExport::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingExport) {
            return response()->json([
                'success' => false,
                'message' => 'Es wird bereits ein Export durchgeführt. Bitte warten Sie, bis dieser abgeschlossen ist.'
            ], 409); // HTTP 409 Conflict
        }

        // Check daily export limit (3 per day)
        $today = \Carbon\Carbon::today();
        $exportsToday = \App\Models\BranchExport::where('customer_id', $customer->id)
            ->whereDate('created_at', $today)
            ->whereIn('status', ['completed', 'processing', 'pending'])
            ->count();

        if ($exportsToday >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Sie haben die maximale Anzahl von 3 Exporten pro Tag erreicht. Bitte versuchen Sie es morgen erneut.'
            ], 429); // HTTP 429 Too Many Requests
        }

        // Create export record with pending status
        $export = \App\Models\BranchExport::create([
            'customer_id' => $customer->id,
            'filename' => '', // Will be set by the job
            'count' => 0,
            'status' => 'pending',
            'expires_at' => \Carbon\Carbon::now()->addHours(72),
        ]);

        // Dispatch Export Job
        \App\Jobs\ExportBranches::dispatch($customer->id, $export->id);

        return response()->json([
            'success' => true,
            'message' => 'Export wurde gestartet. Sie erhalten eine Benachrichtigung mit dem Download-Link, wenn der Export abgeschlossen ist.'
        ]);
    }

    public function download(string $filename)
    {
        $customer = auth('customer')->user();

        // Security: Check if filename belongs to this customer
        if (!str_starts_with($filename, 'branch-export-' . $customer->id . '-')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if export exists and is not expired
        $export = \App\Models\BranchExport::where('customer_id', $customer->id)
            ->where('filename', $filename)
            ->first();

        if (!$export) {
            return response()->json(['success' => false, 'message' => 'Export nicht gefunden oder bereits abgelaufen'], 404);
        }

        if ($export->isExpired()) {
            return response()->json(['success' => false, 'message' => 'Diese Export-Datei ist abgelaufen und wurde gelöscht'], 410);
        }

        $path = 'exports/' . $filename;

        if (!\Storage::disk('public')->exists($path)) {
            return response()->json(['success' => false, 'message' => 'Datei nicht gefunden'], 404);
        }

        return \Storage::disk('public')->download($path, $filename);
    }

    public function cancelScheduledDeletion(Branch $branch)
    {
        // Check ownership
        if ($branch->customer_id !== auth('customer')->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Clear scheduled deletion
        $branch->scheduled_deletion_at = null;
        $branch->save();

        return response()->json([
            'success' => true,
            'message' => 'Geplante Löschung wurde abgebrochen'
        ]);
    }

    private function geocodeAddress(string $address): array
    {
        try {
            // Add User-Agent header as required by Nominatim usage policy
            $response = Http::withHeaders([
                'User-Agent' => 'Laravel-RiskManagement/1.0'
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

            \Log::info('Geocoding request for: ' . $address);
            \Log::info('Geocoding response status: ' . $response->status());

            if ($response->successful() && count($response->json()) > 0) {
                $result = $response->json()[0];
                \Log::info('Geocoding successful: lat=' . $result['lat'] . ', lon=' . $result['lon']);
                return [
                    'lat' => $result['lat'],
                    'lon' => $result['lon'],
                ];
            } else {
                \Log::warning('Geocoding returned no results for: ' . $address);
            }
        } catch (\Exception $e) {
            \Log::error('Geocoding failed: ' . $e->getMessage());
        }

        return [];
    }
}
