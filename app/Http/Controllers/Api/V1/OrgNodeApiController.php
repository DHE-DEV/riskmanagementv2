<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrgNode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrgNodeApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $nodes = OrgNode::where('customer_id', $customer->id)
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['success' => true, 'data' => $nodes]);
    }

    public function show(Request $request, OrgNode $orgNode): JsonResponse
    {
        $customer = $request->user();

        if ($orgNode->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $orgNode->load(['allChildren', 'branches']);

        return response()->json(['success' => true, 'data' => $orgNode]);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30',
            'relation_label' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:org_nodes,id',
            'after_id' => 'nullable|exists:org_nodes,id',
            'color' => 'nullable|string|max:20',
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $afterId = $validated['after_id'] ?? null;

        if ($afterId) {
            $afterNode = OrgNode::findOrFail($afterId);
            $sortOrder = $afterNode->sort_order + 1;

            OrgNode::where('customer_id', $customer->id)
                ->where('parent_id', $parentId)
                ->where('sort_order', '>=', $sortOrder)
                ->increment('sort_order');
        } else {
            $sortOrder = OrgNode::where('customer_id', $customer->id)
                ->where('parent_id', $parentId)
                ->max('sort_order') ?? 0;
            $sortOrder++;
        }

        $orgNode = OrgNode::create([
            'customer_id' => $customer->id,
            'parent_id' => $parentId,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'relation_label' => $validated['relation_label'] ?? null,
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? '#3b82f6',
            'sort_order' => $sortOrder,
        ]);

        return response()->json(['success' => true, 'data' => $orgNode], 201);
    }

    public function update(Request $request, OrgNode $orgNode): JsonResponse
    {
        $customer = $request->user();

        if ($orgNode->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:30',
            'relation_label' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:20',
        ]);

        $orgNode->update($validated);

        return response()->json(['success' => true, 'data' => $orgNode]);
    }

    public function destroy(Request $request, OrgNode $orgNode): JsonResponse
    {
        $customer = $request->user();

        if ($orgNode->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $orgNode->delete();

        return response()->json(['success' => true, 'message' => 'Deleted']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'parent_id' => 'nullable|integer',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        foreach ($validated['ids'] as $index => $id) {
            OrgNode::where('id', $id)
                ->where('customer_id', $customer->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    public function move(Request $request, OrgNode $orgNode): JsonResponse
    {
        $customer = $request->user();

        if ($orgNode->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'new_parent_id' => 'nullable|integer',
            'position' => 'required|integer|min:0',
        ]);

        $oldParentId = $orgNode->parent_id;
        $oldSortOrder = $orgNode->sort_order;
        $newParentId = $validated['new_parent_id'] ?? null;
        $newPosition = $validated['position'];

        // Remove from old position: decrement siblings after old position
        OrgNode::where('customer_id', $customer->id)
            ->where('parent_id', $oldParentId)
            ->where('sort_order', '>', $oldSortOrder)
            ->decrement('sort_order');

        // Make space at new position: increment siblings at or after new position
        OrgNode::where('customer_id', $customer->id)
            ->where('parent_id', $newParentId)
            ->where('sort_order', '>=', $newPosition)
            ->increment('sort_order');

        // Update the node
        $orgNode->update([
            'parent_id' => $newParentId,
            'sort_order' => $newPosition,
        ]);

        return response()->json(['success' => true, 'data' => $orgNode->fresh()]);
    }
}
