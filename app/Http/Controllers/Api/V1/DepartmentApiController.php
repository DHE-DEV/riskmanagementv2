<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $query = Department::where('customer_id', $customer->id)
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $departments = $query->get();

        return response()->json(['success' => true, 'data' => $departments]);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $department = Department::create([
            'customer_id' => $customer->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'code' => $validated['code'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => Department::where('customer_id', $customer->id)->max('sort_order') + 1,
        ]);

        return response()->json(['success' => true, 'data' => $department], 201);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $customer = $request->user();

        if ($department->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
            'code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return response()->json(['success' => true, 'data' => $department]);
    }

    public function destroy(Request $request, Department $department): JsonResponse
    {
        $customer = $request->user();

        if ($department->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $department->delete();

        return response()->json(['success' => true, 'message' => 'Deleted']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        foreach ($validated['ids'] as $index => $id) {
            Department::where('id', $id)
                ->where('customer_id', $customer->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
