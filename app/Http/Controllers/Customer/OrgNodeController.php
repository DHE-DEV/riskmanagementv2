<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\OrgNode;
use Illuminate\Http\Request;

class OrgNodeController extends Controller
{
    public function index()
    {
        $nodes = OrgNode::where('customer_id', auth('customer')->id())
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['nodes' => $nodes]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30',
            'relation_label' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:org_nodes,id',
            'after_id' => 'nullable|exists:org_nodes,id',
            'color' => 'nullable|string|max:20',
        ]);

        $customerId = auth('customer')->id();
        $parentId = $request->parent_id;

        if ($request->after_id) {
            $afterNode = OrgNode::where('id', $request->after_id)->where('customer_id', $customerId)->first();
            if ($afterNode) {
                $insertOrder = $afterNode->sort_order + 1;
                OrgNode::where('customer_id', $customerId)
                    ->where('parent_id', $parentId)
                    ->where('sort_order', '>=', $insertOrder)
                    ->increment('sort_order');
                $sortOrder = $insertOrder;
            } else {
                $sortOrder = (OrgNode::where('customer_id', $customerId)->where('parent_id', $parentId)->max('sort_order') ?? -1) + 1;
            }
        } else {
            $sortOrder = (OrgNode::where('customer_id', $customerId)->where('parent_id', $parentId)->max('sort_order') ?? -1) + 1;
        }

        $node = OrgNode::create([
            'customer_id' => $customerId,
            'parent_id' => $parentId,
            'name' => $request->name,
            'code' => $request->code,
            'relation_label' => $request->relation_label,
            'description' => $request->description,
            'color' => $request->color ?? '#3b82f6',
            'sort_order' => $sortOrder,
        ]);

        return response()->json(['success' => true, 'node' => $node->load('allChildren')]);
    }

    public function update(Request $request, OrgNode $orgNode)
    {
        if ($orgNode->customer_id !== auth('customer')->id()) abort(403);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30',
            'relation_label' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:20',
        ]);

        $orgNode->update($request->only(['name', 'code', 'relation_label', 'description', 'color']));

        return response()->json(['success' => true, 'node' => $orgNode]);
    }

    public function destroy(OrgNode $orgNode)
    {
        if ($orgNode->customer_id !== auth('customer')->id()) abort(403);
        $orgNode->delete();
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'parent_id' => 'nullable|integer',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $customerId = auth('customer')->id();
        foreach ($request->ids as $i => $id) {
            OrgNode::where('id', $id)->where('customer_id', $customerId)->update(['sort_order' => $i]);
        }

        return response()->json(['success' => true]);
    }

    public function move(Request $request, OrgNode $orgNode)
    {
        if ($orgNode->customer_id !== auth('customer')->id()) abort(403);

        $request->validate([
            'new_parent_id' => 'nullable|integer',
            'position' => 'required|integer|min:0',
        ]);

        $customerId = auth('customer')->id();
        $newParentId = $request->new_parent_id;

        // Remove from old position
        OrgNode::where('customer_id', $customerId)
            ->where('parent_id', $orgNode->parent_id)
            ->where('sort_order', '>', $orgNode->sort_order)
            ->decrement('sort_order');

        // Make space at new position
        OrgNode::where('customer_id', $customerId)
            ->where('parent_id', $newParentId)
            ->where('sort_order', '>=', $request->position)
            ->increment('sort_order');

        $orgNode->update([
            'parent_id' => $newParentId,
            'sort_order' => $request->position,
        ]);

        return response()->json(['success' => true]);
    }
}
