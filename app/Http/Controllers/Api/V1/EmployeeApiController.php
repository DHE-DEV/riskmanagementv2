<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeApiController extends Controller
{
    /**
     * GET /api/v1/employees
     * List employees with filters and optional pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $query = Employee::where('customer_id', $customer->id)
            ->with(['branch:id,name', 'departmentRelation:id,name', 'groups:id,name'])
            ->orderBy('last_name');

        // Search filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Branch filter
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->query('branch_id'));
        }

        // Department filter
        if ($request->has('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }

        // Group filter
        if ($groupId = $request->query('group_id')) {
            $query->whereHas('groups', function ($q) use ($groupId) {
                $q->where('employee_groups.id', $groupId);
            });
        }

        // Active filter
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = (int) $request->query('per_page', 50);
        $perPage = min($perPage, 200);

        if ($perPage === 0) {
            // Return all without pagination
            $employees = $query->get();

            // Handle virtual owner entry
            $employees = $this->prependOwnerIfNeeded($customer, $employees);

            return response()->json([
                'success' => true,
                'data' => $employees,
            ]);
        }

        // Paginated response
        $paginated = $query->paginate($perPage);

        // Handle virtual owner entry on first page
        if ($paginated->currentPage() === 1) {
            $items = collect($paginated->items());
            $items = $this->prependOwnerIfNeeded($customer, $items);
            $paginated->setCollection($items);
        }

        return $paginated->toResponse($request);
    }

    /**
     * GET /api/v1/employees/{employee}
     * Show a single employee.
     */
    public function show(Request $request, Employee $employee): JsonResponse
    {
        if ($employee->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $employee->load(['branch:id,name', 'departmentRelation:id,name', 'groups:id,name']);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    /**
     * POST /api/v1/employees
     * Create a new employee.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'salutation' => 'nullable|string|in:herr,frau,divers',
            'title' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'personnel_number' => 'nullable|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'active_from' => 'nullable|date',
            'active_until' => 'nullable|date|after_or_equal:active_from',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:employee_groups,id',
        ]);

        $employee = Employee::create([
            'customer_id' => $request->user()->id,
            ...$request->only([
                'salutation', 'title', 'first_name', 'last_name', 'email',
                'phone', 'mobile', 'position', 'department', 'department_id',
                'personnel_number', 'branch_id', 'notes', 'active_from', 'active_until',
            ]),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->has('group_ids')) {
            $employee->groups()->sync($request->input('group_ids', []));
        }

        $employee->load(['branch:id,name', 'departmentRelation:id,name', 'groups:id,name']);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ], 201);
    }

    /**
     * PUT /api/v1/employees/{employee}
     * Update an existing employee.
     */
    public function update(Request $request, Employee $employee): JsonResponse
    {
        if ($employee->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'salutation' => 'nullable|string|in:herr,frau,divers',
            'title' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'personnel_number' => 'nullable|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'active_from' => 'nullable|date',
            'active_until' => 'nullable|date|after_or_equal:active_from',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:employee_groups,id',
        ]);

        $employee->update($request->only([
            'salutation', 'title', 'first_name', 'last_name', 'email',
            'phone', 'mobile', 'position', 'department', 'department_id',
            'personnel_number', 'branch_id', 'is_active', 'active_from',
            'active_until', 'notes',
        ]));

        if ($request->has('group_ids')) {
            $employee->groups()->sync($request->input('group_ids', []));
        }

        $employee->load(['branch:id,name', 'departmentRelation:id,name', 'groups:id,name']);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    /**
     * DELETE /api/v1/employees/{employee}
     * Delete an employee.
     */
    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        if ($employee->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $employee->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Prepend a virtual owner entry if no employee matches the customer's email.
     */
    private function prependOwnerIfNeeded($customer, $employees)
    {
        $hasOwnerEntry = $employees->contains(fn ($e) => $e->email === $customer->email);

        if (! $hasOwnerEntry) {
            $nameParts = explode(' ', $customer->name, 2);
            $ownerEntry = [
                'id' => 'owner',
                'is_owner' => true,
                'salutation' => '',
                'title' => '',
                'first_name' => $nameParts[0] ?? '',
                'last_name' => $nameParts[1] ?? '',
                'email' => $customer->email,
                'phone' => $customer->phone ?? '',
                'mobile' => '',
                'position' => 'Inhaber / Administrator',
                'department' => '',
                'department_id' => null,
                'department_relation' => null,
                'personnel_number' => '',
                'branch_id' => null,
                'branch' => null,
                'is_active' => true,
                'active_from' => null,
                'active_until' => null,
                'is_currently_active' => true,
                'notes' => '',
                'groups' => [],
            ];

            return collect([$ownerEntry])->concat($employees);
        }

        return $employees;
    }
}
