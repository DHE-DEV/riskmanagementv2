<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhoneNumberApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $query = $customer->phoneNumbers()->orderBy('sort_order')->orderBy('id');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $phoneNumbers = $query->get();

        return response()->json([
            'success' => true,
            'data' => $phoneNumbers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'number' => 'required|string|max:50',
            'type' => 'required|in:phone,mobile,fax',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (! empty($validated['is_primary'])) {
            $customer->phoneNumbers()->update(['is_primary' => false]);
        }

        $phoneNumber = $customer->phoneNumbers()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $phoneNumber,
        ], 201);
    }

    public function update(Request $request, PhoneNumber $phoneNumber): JsonResponse
    {
        $customer = $request->user();

        if ($phoneNumber->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'number' => 'required|string|max:50',
            'type' => 'required|in:phone,mobile,fax',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (! empty($validated['is_primary'])) {
            $customer->phoneNumbers()->where('id', '!=', $phoneNumber->id)->update(['is_primary' => false]);
        }

        $phoneNumber->update($validated);

        return response()->json([
            'success' => true,
            'data' => $phoneNumber,
        ]);
    }

    public function destroy(Request $request, PhoneNumber $phoneNumber): JsonResponse
    {
        $customer = $request->user();

        if ($phoneNumber->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $phoneNumber->delete();

        return response()->json([
            'success' => true,
            'message' => 'Phone number deleted.',
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
            $customer->phoneNumbers()->where('id', $id)->update(['sort_order' => $sortOrder]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Phone numbers reordered.',
        ]);
    }
}
