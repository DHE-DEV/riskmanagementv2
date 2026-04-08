<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmailAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailAddressApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $query = $customer->emailAddresses()->orderBy('sort_order')->orderBy('id');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $emailAddresses = $query->get();

        return response()->json([
            'success' => true,
            'data' => $emailAddresses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (! empty($validated['is_primary'])) {
            $customer->emailAddresses()->update(['is_primary' => false]);
        }

        $emailAddress = $customer->emailAddresses()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $emailAddress,
        ], 201);
    }

    public function update(Request $request, EmailAddress $emailAddress): JsonResponse
    {
        $customer = $request->user();

        if ($emailAddress->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (! empty($validated['is_primary'])) {
            $customer->emailAddresses()->where('id', '!=', $emailAddress->id)->update(['is_primary' => false]);
        }

        $emailAddress->update($validated);

        return response()->json([
            'success' => true,
            'data' => $emailAddress,
        ]);
    }

    public function destroy(Request $request, EmailAddress $emailAddress): JsonResponse
    {
        $customer = $request->user();

        if ($emailAddress->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $emailAddress->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email address deleted.',
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
            $customer->emailAddresses()->where('id', $id)->update(['sort_order' => $sortOrder]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email addresses reordered.',
        ]);
    }
}
