<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function updateCustomerType(Request $request)
    {
        $request->validate([
            'customer_type' => 'required|in:business,private'
        ]);

        $customer = auth('customer')->user();
        $customer->customer_type = $request->customer_type;
        $customer->save();

        return response()->json([
            'success' => true,
            'customer_type' => $customer->customer_type,
            'customer_type_label' => $customer->customer_type === 'business' ? 'Firmenkunde' : 'Privatkunde'
        ]);
    }
}
