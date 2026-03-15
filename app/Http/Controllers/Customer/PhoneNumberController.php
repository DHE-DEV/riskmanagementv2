<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\PhoneNumber;
use Illuminate\Http\Request;

class PhoneNumberController extends Controller
{
    public function index()
    {
        return response()->json([
            'phone_numbers' => PhoneNumber::where('customer_id', auth('customer')->id())->orderBy('sort_order')->orderBy('id')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:100',
            'number' => 'required|string|max:50',
            'type' => 'required|in:phone,mobile,fax',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($request->boolean('is_primary')) {
            PhoneNumber::where('customer_id', auth('customer')->id())->update(['is_primary' => false]);
        }

        $phone = PhoneNumber::create([
            'customer_id' => auth('customer')->id(),
            ...$request->only(['label', 'number', 'type', 'notes', 'department_id', 'branch_id']),
            'is_primary' => $request->boolean('is_primary', false),
        ]);

        return response()->json(['success' => true, 'phone_number' => $phone]);
    }

    public function update(Request $request, PhoneNumber $phoneNumber)
    {
        if ($phoneNumber->customer_id !== auth('customer')->id()) abort(403);

        $request->validate([
            'label' => 'required|string|max:100',
            'number' => 'required|string|max:50',
            'type' => 'required|in:phone,mobile,fax',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($request->boolean('is_primary')) {
            PhoneNumber::where('customer_id', auth('customer')->id())->where('id', '!=', $phoneNumber->id)->update(['is_primary' => false]);
        }

        $phoneNumber->update($request->only(['label', 'number', 'type', 'is_primary', 'notes', 'department_id', 'branch_id']));

        return response()->json(['success' => true, 'phone_number' => $phoneNumber]);
    }

    public function destroy(PhoneNumber $phoneNumber)
    {
        if ($phoneNumber->customer_id !== auth('customer')->id()) abort(403);
        $phoneNumber->delete();
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        foreach ($request->ids as $i => $id) {
            PhoneNumber::where('id', $id)->where('customer_id', auth('customer')->id())->update(['sort_order' => $i]);
        }
        return response()->json(['success' => true]);
    }
}
