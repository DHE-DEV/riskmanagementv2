<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\EmailAddress;
use Illuminate\Http\Request;

class EmailAddressController extends Controller
{
    public function index()
    {
        return response()->json([
            'email_addresses' => EmailAddress::where('customer_id', auth('customer')->id())->orderBy('sort_order')->orderBy('id')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($request->boolean('is_primary')) {
            EmailAddress::where('customer_id', auth('customer')->id())->update(['is_primary' => false]);
        }

        $email = EmailAddress::create([
            'customer_id' => auth('customer')->id(),
            ...$request->only(['label', 'email', 'notes', 'department_id', 'branch_id']),
            'is_primary' => $request->boolean('is_primary', false),
        ]);

        return response()->json(['success' => true, 'email_address' => $email]);
    }

    public function update(Request $request, EmailAddress $emailAddress)
    {
        if ($emailAddress->customer_id !== auth('customer')->id()) abort(403);

        $request->validate([
            'label' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($request->boolean('is_primary')) {
            EmailAddress::where('customer_id', auth('customer')->id())->where('id', '!=', $emailAddress->id)->update(['is_primary' => false]);
        }

        $emailAddress->update($request->only(['label', 'email', 'is_primary', 'notes', 'department_id', 'branch_id']));

        return response()->json(['success' => true, 'email_address' => $emailAddress]);
    }

    public function destroy(EmailAddress $emailAddress)
    {
        if ($emailAddress->customer_id !== auth('customer')->id()) abort(403);
        $emailAddress->delete();
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        foreach ($request->ids as $i => $id) {
            EmailAddress::where('id', $id)->where('customer_id', auth('customer')->id())->update(['sort_order' => $i]);
        }
        return response()->json(['success' => true]);
    }
}
