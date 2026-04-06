<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\EmployeeGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeGroupController extends Controller
{
    public function index(): JsonResponse
    {
        $groups = EmployeeGroup::where('customer_id', auth('customer')->id())
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        return response()->json(['groups' => $groups]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $group = EmployeeGroup::create([
            'customer_id' => auth('customer')->id(),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return response()->json(['success' => true, 'group' => $group->loadCount('employees')]);
    }

    public function update(Request $request, EmployeeGroup $employeeGroup): JsonResponse
    {
        if ($employeeGroup->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        if ($employeeGroup->is_system) {
            return response()->json(['success' => false, 'message' => 'Systemgruppen können nicht bearbeitet werden.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $employeeGroup->update($request->only(['name', 'description']));

        return response()->json(['success' => true, 'group' => $employeeGroup->loadCount('employees')]);
    }

    public function destroy(EmployeeGroup $employeeGroup): JsonResponse
    {
        if ($employeeGroup->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        if ($employeeGroup->is_system) {
            return response()->json(['success' => false, 'message' => 'Systemgruppen können nicht gelöscht werden.'], 403);
        }

        $employeeGroup->delete();

        return response()->json(['success' => true]);
    }
}
