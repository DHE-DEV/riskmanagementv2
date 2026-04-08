<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchContactApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        $branch = Branch::where('id', $request->input('branch_id'))
            ->where('customer_id', $customer->id)
            ->first();

        if (! $branch) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $contacts = BranchContact::where('branch_id', $branch->id)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['success' => true, 'data' => $contacts]);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'salutation' => 'nullable|string|max:20',
            'title' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'function' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $branch = Branch::where('id', $validated['branch_id'])
            ->where('customer_id', $customer->id)
            ->first();

        if (! $branch) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated['sort_order'] = BranchContact::where('branch_id', $branch->id)->max('sort_order') + 1;

        $contact = BranchContact::create($validated);

        return response()->json(['success' => true, 'data' => $contact], 201);
    }

    public function update(Request $request, BranchContact $branchContact): JsonResponse
    {
        $customer = $request->user();

        if ($branchContact->branch->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'salutation' => 'nullable|string|max:20',
            'title' => 'nullable|string|max:50',
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'function' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $branchContact->update($validated);

        return response()->json(['success' => true, 'data' => $branchContact]);
    }

    public function destroy(Request $request, BranchContact $branchContact): JsonResponse
    {
        $customer = $request->user();

        if ($branchContact->branch->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $branchContact->delete();

        return response()->json(['success' => true, 'message' => 'Deleted']);
    }
}
