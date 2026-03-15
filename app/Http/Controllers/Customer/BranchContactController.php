<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BranchContact;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchContactController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
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

        $branch = Branch::where('id', $request->branch_id)->where('customer_id', auth('customer')->id())->firstOrFail();

        $contact = $branch->contacts()->create($request->only([
            'salutation', 'title', 'first_name', 'last_name', 'function',
            'department', 'phone', 'mobile', 'fax', 'email', 'notes'
        ]));

        return response()->json(['success' => true, 'contact' => $contact]);
    }

    public function update(Request $request, BranchContact $branchContact)
    {
        $branch = $branchContact->branch;
        if ($branch->customer_id !== auth('customer')->id()) abort(403);

        $request->validate([
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

        $branchContact->update($request->only([
            'salutation', 'title', 'first_name', 'last_name', 'function',
            'department', 'phone', 'mobile', 'fax', 'email', 'notes'
        ]));

        return response()->json(['success' => true, 'contact' => $branchContact]);
    }

    public function destroy(BranchContact $branchContact)
    {
        if ($branchContact->branch->customer_id !== auth('customer')->id()) abort(403);
        $branchContact->delete();
        return response()->json(['success' => true]);
    }
}
