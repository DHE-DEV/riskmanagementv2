<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebsiteApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $query = $customer->websites()->orderBy('sort_order')->orderBy('id');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $websites = $query->get();

        return response()->json([
            'success' => true,
            'data' => $websites,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (! empty($validated['is_primary'])) {
            $customer->websites()->update(['is_primary' => false]);
        }

        $website = $customer->websites()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $website,
        ], 201);
    }

    public function update(Request $request, Website $website): JsonResponse
    {
        $customer = $request->user();

        if ($website->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (! empty($validated['is_primary'])) {
            $customer->websites()->where('id', '!=', $website->id)->update(['is_primary' => false]);
        }

        $website->update($validated);

        return response()->json([
            'success' => true,
            'data' => $website,
        ]);
    }

    public function destroy(Request $request, Website $website): JsonResponse
    {
        $customer = $request->user();

        if ($website->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $website->delete();

        return response()->json([
            'success' => true,
            'message' => 'Website deleted.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        foreach ($validated['ids'] as $sortOrder => $id) {
            $customer->websites()->where('id', $id)->update(['sort_order' => $sortOrder]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Websites reordered.',
        ]);
    }
}
