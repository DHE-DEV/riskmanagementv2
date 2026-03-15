<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::where('customer_id', auth('customer')->id())
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        return response()->json(['departments' => $departments]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $department = Department::create([
            'customer_id' => auth('customer')->id(),
            ...$request->only(['name', 'description', 'code']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json(['success' => true, 'department' => $department]);
    }

    public function update(Request $request, Department $department)
    {
        if ($department->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $department->update($request->only(['name', 'description', 'code', 'is_active']));

        return response()->json(['success' => true, 'department' => $department]);
    }

    public function destroy(Department $department)
    {
        if ($department->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        $department->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        foreach ($request->ids as $i => $id) {
            Department::where('id', $id)->where('customer_id', auth('customer')->id())->update(['sort_order' => $i]);
        }
        return response()->json(['success' => true]);
    }
}
