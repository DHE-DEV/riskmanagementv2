<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PluginClient;
use App\Models\PluginDomain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PluginDomainController extends Controller
{
    /**
     * List all domains for the authenticated plugin client.
     */
    public function index(Request $request): JsonResponse
    {
        $client = $this->getClient($request);

        $query = $client->domains();

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $query->where('domain', 'like', '%' . $request->input('search') . '%');
        }

        $perPage = min((int) $request->input('per_page', 50), 200);
        $domains = $query->orderBy('domain')->paginate($perPage);

        return response()->json([
            'data' => $domains->map(fn (PluginDomain $d) => $this->formatDomain($d)),
            'meta' => [
                'current_page' => $domains->currentPage(),
                'last_page' => $domains->lastPage(),
                'per_page' => $domains->perPage(),
                'total' => $domains->total(),
            ],
        ]);
    }

    /**
     * Show a single domain.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $client = $this->getClient($request);
        $domain = $client->domains()->where('uuid', $uuid)->first();

        if (! $domain) {
            return response()->json(['error' => 'Domain nicht gefunden.'], 404);
        }

        return response()->json(['data' => $this->formatDomain($domain)]);
    }

    /**
     * Add a single domain.
     */
    public function store(Request $request): JsonResponse
    {
        $client = $this->getClient($request);

        $validator = Validator::make($request->all(), [
            'domain' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validierungsfehler.', 'details' => $validator->errors()], 422);
        }

        $normalizedDomain = $this->normalizeDomain($request->input('domain'));

        if (! $this->isValidDomain($normalizedDomain)) {
            return response()->json(['error' => 'Ungültiges Domain-Format.'], 422);
        }

        if ($client->hasDomain($normalizedDomain)) {
            return response()->json(['error' => 'Diese Domain ist bereits registriert.'], 409);
        }

        $domain = $client->domains()->create([
            'domain' => $normalizedDomain,
            'is_active' => $request->input('is_active', true),
        ]);

        return response()->json(['data' => $this->formatDomain($domain)], 201);
    }

    /**
     * Bulk import domains.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $client = $this->getClient($request);

        $validator = Validator::make($request->all(), [
            'domains' => ['required', 'array', 'min:1', 'max:1000'],
            'domains.*' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validierungsfehler.', 'details' => $validator->errors()], 422);
        }

        $results = ['created' => [], 'skipped' => [], 'invalid' => []];

        foreach ($request->input('domains') as $rawDomain) {
            $normalized = $this->normalizeDomain($rawDomain);

            if (! $this->isValidDomain($normalized)) {
                $results['invalid'][] = $rawDomain;
                continue;
            }

            if ($client->hasDomain($normalized)) {
                $results['skipped'][] = $normalized;
                continue;
            }

            $domain = $client->domains()->create([
                'domain' => $normalized,
                'is_active' => true,
            ]);

            $results['created'][] = $this->formatDomain($domain);
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'created_count' => count($results['created']),
                'skipped_count' => count($results['skipped']),
                'invalid_count' => count($results['invalid']),
            ],
        ], 201);
    }

    /**
     * Update a domain (activate/deactivate).
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $client = $this->getClient($request);
        $domain = $client->domains()->where('uuid', $uuid)->first();

        if (! $domain) {
            return response()->json(['error' => 'Domain nicht gefunden.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_active' => ['sometimes', 'boolean'],
            'domain' => ['sometimes', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validierungsfehler.', 'details' => $validator->errors()], 422);
        }

        if ($request->has('domain')) {
            $normalized = $this->normalizeDomain($request->input('domain'));

            if (! $this->isValidDomain($normalized)) {
                return response()->json(['error' => 'Ungültiges Domain-Format.'], 422);
            }

            $existing = $client->domains()
                ->where('domain', $normalized)
                ->where('id', '!=', $domain->id)
                ->exists();

            if ($existing) {
                return response()->json(['error' => 'Diese Domain ist bereits registriert.'], 409);
            }

            $domain->domain = $normalized;
        }

        if ($request->has('is_active')) {
            $domain->is_active = $request->boolean('is_active');
        }

        $domain->save();

        return response()->json(['data' => $this->formatDomain($domain)]);
    }

    /**
     * Delete a domain.
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $client = $this->getClient($request);
        $domain = $client->domains()->where('uuid', $uuid)->first();

        if (! $domain) {
            return response()->json(['error' => 'Domain nicht gefunden.'], 404);
        }

        if ($client->domains()->count() <= 1) {
            return response()->json(['error' => 'Sie müssen mindestens eine Domain behalten.'], 422);
        }

        $domain->delete();

        return response()->json(null, 204);
    }

    /**
     * Bulk delete domains by UUIDs.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $client = $this->getClient($request);

        $validator = Validator::make($request->all(), [
            'uuids' => ['required', 'array', 'min:1', 'max:1000'],
            'uuids.*' => ['required', 'string', 'uuid'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validierungsfehler.', 'details' => $validator->errors()], 422);
        }

        $uuids = $request->input('uuids');
        $totalDomains = $client->domains()->count();
        $toDelete = $client->domains()->whereIn('uuid', $uuids)->count();

        if ($totalDomains - $toDelete < 1) {
            return response()->json(['error' => 'Sie müssen mindestens eine Domain behalten.'], 422);
        }

        $deleted = $client->domains()->whereIn('uuid', $uuids)->delete();

        return response()->json([
            'data' => ['deleted_count' => $deleted],
        ]);
    }

    private function getClient(Request $request): PluginClient
    {
        return $request->attributes->get('plugin_client');
    }

    private function formatDomain(PluginDomain $domain): array
    {
        return [
            'uuid' => $domain->uuid,
            'domain' => $domain->domain,
            'is_active' => $domain->is_active,
            'created_at' => $domain->created_at->toIso8601String(),
            'updated_at' => $domain->updated_at->toIso8601String(),
        ];
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = explode(':', $domain)[0];

        return strtolower(trim($domain));
    }

    private function isValidDomain(string $domain): bool
    {
        return (bool) preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $domain);
    }
}
