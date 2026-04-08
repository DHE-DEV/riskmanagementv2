<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmployeeGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeGroupApiController extends Controller
{
    /**
     * GET /api/v1/employee-groups
     * List all employee groups for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $groups = EmployeeGroup::where('customer_id', $customer->id)
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    /**
     * GET /api/v1/employee-groups/{employeeGroup}
     * Show a single employee group with its employees.
     */
    public function show(Request $request, EmployeeGroup $employeeGroup): JsonResponse
    {
        if ($employeeGroup->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $employeeGroup->loadCount('employees');
        $employeeGroup->load('employees:id,first_name,last_name,email');

        return response()->json([
            'success' => true,
            'data' => $employeeGroup,
        ]);
    }

    /**
     * POST /api/v1/employee-groups
     * Create a new employee group.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $group = EmployeeGroup::create([
            'customer_id' => $request->user()->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $group,
        ], 201);
    }

    /**
     * PUT /api/v1/employee-groups/{employeeGroup}
     * Update an existing employee group.
     */
    public function update(Request $request, EmployeeGroup $employeeGroup): JsonResponse
    {
        if ($employeeGroup->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($employeeGroup->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Systemgruppen können nicht bearbeitet werden',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $employeeGroup->update($request->only(['name', 'description']));

        return response()->json([
            'success' => true,
            'data' => $employeeGroup,
        ]);
    }

    /**
     * DELETE /api/v1/employee-groups/{employeeGroup}
     * Delete an employee group.
     */
    public function destroy(Request $request, EmployeeGroup $employeeGroup): JsonResponse
    {
        if ($employeeGroup->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($employeeGroup->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Systemgruppen können nicht gelöscht werden',
            ], 403);
        }

        $employeeGroup->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
