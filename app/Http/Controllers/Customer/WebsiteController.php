<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Website;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    public function index()
    {
        return response()->json([
            'websites' => Website::where('customer_id', auth('customer')->id())->orderBy('sort_order')->orderBy('id')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($request->boolean('is_primary')) {
            Website::where('customer_id', auth('customer')->id())->update(['is_primary' => false]);
        }

        $website = Website::create([
            'customer_id' => auth('customer')->id(),
            ...$request->only(['label', 'url', 'notes', 'branch_id']),
            'is_primary' => $request->boolean('is_primary', false),
        ]);

        return response()->json(['success' => true, 'website' => $website]);
    }

    public function update(Request $request, Website $website)
    {
        if ($website->customer_id !== auth('customer')->id()) abort(403);

        $request->validate([
            'label' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($request->boolean('is_primary')) {
            Website::where('customer_id', auth('customer')->id())->where('id', '!=', $website->id)->update(['is_primary' => false]);
        }

        $website->update($request->only(['label', 'url', 'is_primary', 'notes', 'branch_id']));

        return response()->json(['success' => true, 'website' => $website]);
    }

    public function destroy(Website $website)
    {
        if ($website->customer_id !== auth('customer')->id()) abort(403);
        $website->delete();
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        foreach ($request->ids as $i => $id) {
            Website::where('id', $id)->where('customer_id', auth('customer')->id())->update(['sort_order' => $i]);
        }
        return response()->json(['success' => true]);
    }
}
