<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $customer = auth('customer')->user();

        $employees = Employee::where('customer_id', $customer->id)
            ->with(['branch:id,name', 'departmentRelation:id,name', 'groups:id,name'])
            ->orderBy('last_name')
            ->get();

        // Check if owner has an employee entry (new accounts get one automatically)
        $hasOwnerEntry = $employees->contains(fn ($e) => $e->email === $customer->email);

        if (! $hasOwnerEntry) {
            // Add virtual owner entry for legacy accounts
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
                'notes' => '',
                'groups' => [],
            ];

            return response()->json(['employees' => collect([$ownerEntry])->concat($employees)]);
        }

        return response()->json(['employees' => $employees]);
    }

    public function store(Request $request)
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
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:employee_groups,id',
        ]);

        $employee = Employee::create([
            'customer_id' => auth('customer')->id(),
            ...$request->only(['salutation', 'title', 'first_name', 'last_name', 'email', 'phone', 'mobile', 'position', 'department', 'department_id', 'personnel_number', 'branch_id', 'notes']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->has('group_ids')) {
            $employee->groups()->sync($request->input('group_ids', []));
        }

        return response()->json(['success' => true, 'employee' => $employee->load(['branch:id,name', 'departmentRelation:id,name', 'groups:id,name'])]);
    }

    public function update(Request $request, Employee $employee)
    {
        if ($employee->customer_id !== auth('customer')->id()) {
            abort(403);
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
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:employee_groups,id',
        ]);

        $employee->update($request->only(['salutation', 'title', 'first_name', 'last_name', 'email', 'phone', 'mobile', 'position', 'department', 'department_id', 'personnel_number', 'branch_id', 'is_active', 'notes']));

        if ($request->has('group_ids')) {
            $employee->groups()->sync($request->input('group_ids', []));
        }

        return response()->json(['success' => true, 'employee' => $employee->load(['branch:id,name', 'departmentRelation:id,name', 'groups:id,name'])]);
    }

    public function destroy(Employee $employee)
    {
        if ($employee->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        $employee->delete();

        return response()->json(['success' => true]);
    }
}
