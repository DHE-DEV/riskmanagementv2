<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\OrganizationChangeNotifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        $customer = auth('customer')->user();
        $branches = $customer->branches()
            ->with(['orgNodes:org_nodes.id,org_nodes.name', 'phoneNumbers', 'emailAddresses', 'websites', 'contacts'])
            ->orderBy('is_headquarters', 'desc')->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'branches' => $branches,
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
            'location_type' => ['nullable', Rule::in(array_keys(Branch::LOCATION_TYPES))],
            'start_date' => 'nullable|date',
            'org_node_ids' => 'nullable|array',
            'org_node_ids.*' => 'exists:org_nodes,id',
            'org_node_data' => 'nullable|array',
            'org_node_data.*.id' => 'exists:org_nodes,id',
            'org_node_data.*.customer_number' => 'nullable|string|max:100',
            'org_node_data.*.contract_number' => 'nullable|string|max:100',
            'org_node_data.*.start_date' => 'nullable|date',
            'org_node_data.*.end_date' => 'nullable|date',
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
            'location_type' => $validated['location_type'] ?? 'agency',
            'start_date' => $validated['start_date'] ?? null,
        ]);

        $this->syncOrgNodes($branch, $validated);
        app(OrganizationChangeNotifier::class)->branchCreated($branch);

        return response()->json([
            'success' => true,
            'branch' => $branch->load('orgNodes:org_nodes.id,org_nodes.name'),
            'message' => 'Filiale erfolgreich hinzugefügt',
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
            'location_type' => ['nullable', Rule::in(array_keys(Branch::LOCATION_TYPES))],
            'start_date' => 'nullable|date',
            'org_node_ids' => 'nullable|array',
            'org_node_ids.*' => 'exists:org_nodes,id',
            'org_node_data' => 'nullable|array',
            'org_node_data.*.id' => 'exists:org_nodes,id',
            'org_node_data.*.customer_number' => 'nullable|string|max:100',
            'org_node_data.*.contract_number' => 'nullable|string|max:100',
            'org_node_data.*.start_date' => 'nullable|date',
            'org_node_data.*.end_date' => 'nullable|date',
        ]);

        // Geocode the address
        $address = "{$validated['street']} {$validated['house_number']}, {$validated['postal_code']} {$validated['city']}, {$validated['country']}";
        $coordinates = $this->geocodeAddress($address);

        $before = $branch->only(array_keys(OrganizationChangeNotifier::BRANCH_FIELDS));

        $branch->update([
            'name' => $validated['name'],
            'additional' => $validated['additional'] ?? null,
            'street' => $validated['street'],
            'house_number' => $validated['house_number'] ?? null,
            'postal_code' => $validated['postal_code'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'latitude' => $coordinates['lat'] ?? $branch->latitude,
            'longitude' => $coordinates['lon'] ?? $branch->longitude,
            'location_type' => $validated['location_type'] ?? $branch->location_type,
            'start_date' => array_key_exists('start_date', $validated) ? $validated['start_date'] : $branch->start_date,
        ]);

        $this->syncOrgNodes($branch, $validated);
        app(OrganizationChangeNotifier::class)->branchUpdated($branch, $before);

        return response()->json([
            'success' => true,
            'branch' => $branch->load('orgNodes:org_nodes.id,org_nodes.name'),
            'message' => 'Filiale erfolgreich aktualisiert',
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
                'message' => 'Der Hauptsitz kann nicht gelöscht werden',
            ], 422);
        }

        // Enddatum (Abrechnung) ist Pflicht und liegt in der Zukunft; ohne
        // Loeschtermin wird sofort (weich) geloescht.
        $validated = $request->validate([
            'end_date' => 'required|date|after:today',
            'scheduled_deletion_at' => 'nullable|date|after:today',
            'deletion_reason' => 'nullable|string|max:2000',
        ], [
            'end_date.required' => 'Bitte ein Enddatum angeben.',
            'end_date.after' => 'Das Enddatum muss in der Zukunft liegen.',
            'scheduled_deletion_at.after' => 'Das Löschdatum muss in der Zukunft liegen.',
        ]);

        $notifier = app(OrganizationChangeNotifier::class);
        $before = $branch->only(array_keys(OrganizationChangeNotifier::BRANCH_FIELDS));
        $scheduledAt = filled($validated['scheduled_deletion_at'] ?? null) ? Carbon::parse($validated['scheduled_deletion_at'])->startOfDay() : null;

        $branch->fill([
            'end_date' => $validated['end_date'],
            'deletion_reason' => $validated['deletion_reason'] ?? null,
            'deletion_requested_by' => $notifier->actor($branch->customer)['label'],
            'scheduled_deletion_at' => $scheduledAt,
        ])->save();

        $notifier->branchDeletionRequested($branch, $before, $scheduledAt, $validated['deletion_reason'] ?? null);

        if ($scheduledAt) {
            return response()->json([
                'success' => true,
                'message' => 'Löschung wurde für '.$scheduledAt->format('d.m.Y').' geplant',
            ]);
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Filiale erfolgreich gelöscht',
        ]);
    }

    public function import(Request $request)
    {
        try {
            $validated = $request->validate([
                'csv_data' => 'required|string',
            ]);

            $customer = auth('customer')->user();

            if (! $customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Benutzer nicht authentifiziert.',
                ], 401);
            }

            // Prüfe ob Branch Management aktiviert ist
            if (! $customer->branch_management_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch Management ist nicht aktiviert.',
                ], 403);
            }

            // Dispatch Job
            \App\Jobs\ImportBranches::dispatch($customer->id, $validated['csv_data']);

            \Log::info('Branch import job dispatched', [
                'customer_id' => $customer->id,
                'csv_length' => strlen($validated['csv_data']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Import wurde gestartet. Sie erhalten eine Benachrichtigung, wenn der Import abgeschlossen ist.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Branch import validation failed', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler: '.json_encode($e->errors()),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Branch import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ein Fehler ist aufgetreten: '.$e->getMessage(),
            ], 500);
        }
    }

    public function exportStatus()
    {
        $customer = auth('customer')->user();

        // Check daily export limit (3 per day)
        $today = \Carbon\Carbon::today();
        $exportsToday = \App\Models\BranchExport::where('customer_id', $customer->id)
            ->whereDate('created_at', $today)
            ->whereIn('status', ['completed', 'processing', 'pending'])
            ->count();

        $maxExportsPerDay = 3;
        $remainingExports = max(0, $maxExportsPerDay - $exportsToday);

        return response()->json([
            'success' => true,
            'exports_today' => $exportsToday,
            'max_exports' => $maxExportsPerDay,
            'remaining_exports' => $remainingExports,
        ]);
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
                'message' => 'Es wird bereits ein Export durchgeführt. Bitte warten Sie, bis dieser abgeschlossen ist.',
            ], 409); // HTTP 409 Conflict
        }

        // Check daily export limit (3 per day)
        $today = \Carbon\Carbon::today();
        $exportsToday = \App\Models\BranchExport::where('customer_id', $customer->id)
            ->whereDate('created_at', $today)
            ->whereIn('status', ['completed', 'processing', 'pending'])
            ->count();

        $maxExportsPerDay = 3;
        $remainingExports = $maxExportsPerDay - $exportsToday;

        if ($exportsToday >= $maxExportsPerDay) {
            return response()->json([
                'success' => false,
                'message' => "Sie haben heute bereits {$exportsToday} von {$maxExportsPerDay} möglichen Exporten durchgeführt. Bitte versuchen Sie es morgen erneut.",
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
            'message' => 'Export wurde gestartet. Sie erhalten eine Benachrichtigung mit dem Download-Link, wenn der Export abgeschlossen ist.',
        ]);
    }

    public function download(string $filename)
    {
        $customer = auth('customer')->user();

        // Security: Check if filename belongs to this customer
        if (! str_starts_with($filename, 'branch-export-'.$customer->id.'-')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if export exists and is not expired
        $export = \App\Models\BranchExport::where('customer_id', $customer->id)
            ->where('filename', $filename)
            ->first();

        if (! $export) {
            return response()->json(['success' => false, 'message' => 'Export nicht gefunden oder bereits abgelaufen'], 404);
        }

        if ($export->isExpired()) {
            return response()->json(['success' => false, 'message' => 'Diese Export-Datei ist abgelaufen und wurde gelöscht'], 410);
        }

        $path = 'exports/'.$filename;

        if (! \Storage::disk('public')->exists($path)) {
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

        // Geplante Loeschung samt Enddatum und Grund zuruecknehmen
        $branch->fill(['scheduled_deletion_at' => null, 'end_date' => null, 'deletion_reason' => null, 'deletion_requested_by' => null])->save();
        app(OrganizationChangeNotifier::class)->branchDeletionCancelled($branch);

        return response()->json([
            'success' => true,
            'message' => 'Geplante Löschung wurde abgebrochen',
        ]);
    }

    private function syncOrgNodes($branch, $validated): void
    {
        $nodeData = collect($validated['org_node_data'] ?? []);
        $nodeIds = $validated['org_node_ids'] ?? [];

        $syncData = [];
        foreach ($nodeIds as $nodeId) {
            $extra = $nodeData->firstWhere('id', $nodeId);
            $syncData[$nodeId] = [
                'customer_number' => $extra['customer_number'] ?? null,
                'contract_number' => $extra['contract_number'] ?? null,
                'start_date' => $extra['start_date'] ?? null,
                'end_date' => $extra['end_date'] ?? null,
            ];
        }

        $branch->orgNodes()->sync($syncData);
    }

    private function geocodeAddress(string $address): array
    {
        try {
            // Add User-Agent header as required by Nominatim usage policy
            $response = Http::withHeaders([
                'User-Agent' => 'Laravel-RiskManagement/1.0',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

            \Log::info('Geocoding request for: '.$address);
            \Log::info('Geocoding response status: '.$response->status());

            if ($response->successful() && count($response->json()) > 0) {
                $result = $response->json()[0];
                \Log::info('Geocoding successful: lat='.$result['lat'].', lon='.$result['lon']);

                return [
                    'lat' => $result['lat'],
                    'lon' => $result['lon'],
                ];
            } else {
                \Log::warning('Geocoding returned no results for: '.$address);
            }
        } catch (\Exception $e) {
            \Log::error('Geocoding failed: '.$e->getMessage());
        }

        return [];
    }
}
