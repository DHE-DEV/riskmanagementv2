<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BranchApiController extends Controller
{
    /**
     * GET /api/v1/branches
     * List all branches for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $query = $customer->branches()
            ->with(['orgNodes:org_nodes.id,org_nodes.name', 'phoneNumbers', 'emailAddresses', 'websites', 'contacts'])
            ->orderBy('is_headquarters', 'desc')
            ->orderBy('created_at');

        // Optional search filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('street', 'like', "%{$search}%");
            });
        }

        // Optional city filter
        if ($city = $request->query('city')) {
            $query->where('city', $city);
        }

        $branches = $query->get();

        return response()->json([
            'success' => true,
            'data' => $branches,
        ]);
    }

    /**
     * GET /api/v1/branches/{branch}
     * Show a single branch.
     */
    public function show(Request $request, Branch $branch): JsonResponse
    {
        if ($branch->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $branch->load(['orgNodes', 'phoneNumbers', 'emailAddresses', 'websites', 'contacts']);

        return response()->json([
            'success' => true,
            'data' => $branch,
        ]);
    }

    /**
     * POST /api/v1/branches
     * Create a new branch.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'additional' => 'nullable|string|max:255',
            'street' => 'required|string|max:255',
            'house_number' => 'nullable|string|max:50',
            'postal_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'org_node_ids' => 'nullable|array',
            'org_node_ids.*' => 'exists:org_nodes,id',
            'org_node_data' => 'nullable|array',
            'org_node_data.*.id' => 'exists:org_nodes,id',
            'org_node_data.*.customer_number' => 'nullable|string|max:100',
            'org_node_data.*.contract_number' => 'nullable|string|max:100',
            'org_node_data.*.start_date' => 'nullable|date',
            'org_node_data.*.end_date' => 'nullable|date',
        ]);

        $customer = $request->user();

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

        $this->syncOrgNodes($branch, $validated);

        $branch->load('orgNodes:org_nodes.id,org_nodes.name');

        return response()->json([
            'success' => true,
            'data' => $branch,
            'message' => 'Filiale erfolgreich hinzugefügt',
        ], 201);
    }

    /**
     * PUT /api/v1/branches/{branch}
     * Update an existing branch.
     */
    public function update(Request $request, Branch $branch): JsonResponse
    {
        if ($branch->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'additional' => 'nullable|string|max:255',
            'street' => 'required|string|max:255',
            'house_number' => 'nullable|string|max:50',
            'postal_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
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

        $this->syncOrgNodes($branch, $validated);

        $branch->load('orgNodes:org_nodes.id,org_nodes.name');

        return response()->json([
            'success' => true,
            'data' => $branch,
            'message' => 'Adresse erfolgreich aktualisiert',
        ]);
    }

    /**
     * DELETE /api/v1/branches/{branch}
     * Delete a branch (immediate or scheduled).
     */
    public function destroy(Request $request, Branch $branch): JsonResponse
    {
        if ($branch->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($branch->is_headquarters) {
            return response()->json([
                'success' => false,
                'message' => 'Der Hauptsitz kann nicht gelöscht werden',
            ], 422);
        }

        // Scheduled deletion
        if ($request->has('scheduled_deletion_at') && $request->input('scheduled_deletion_at')) {
            $scheduledDate = $request->input('scheduled_deletion_at');

            $date = \Carbon\Carbon::parse($scheduledDate);
            if ($date->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Das Löschdatum muss in der Zukunft liegen',
                ], 422);
            }

            $branch->scheduled_deletion_at = $date;
            $branch->save();

            return response()->json([
                'success' => true,
                'message' => 'Löschung wurde für ' . $date->format('d.m.Y') . ' geplant',
            ]);
        }

        // Immediate deletion
        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Filiale erfolgreich gelöscht',
        ]);
    }

    /**
     * POST /api/v1/branches/{branch}/cancel-deletion
     * Cancel a scheduled deletion.
     */
    public function cancelScheduledDeletion(Request $request, Branch $branch): JsonResponse
    {
        if ($branch->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $branch->scheduled_deletion_at = null;
        $branch->save();

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
            $response = Http::withHeaders([
                'User-Agent' => 'Laravel-RiskManagement/1.0',
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
