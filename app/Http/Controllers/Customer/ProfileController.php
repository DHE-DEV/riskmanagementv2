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

    public function updateBusinessType(Request $request)
    {
        $request->validate([
            'business_type' => 'required|in:travel_agency,organizer,online_provider'
        ]);

        $customer = auth('customer')->user();
        $customer->business_type = $request->business_type;
        $customer->save();

        $labels = [
            'travel_agency' => 'Reisebüro',
            'organizer' => 'Veranstalter',
            'online_provider' => 'Online Anbieter'
        ];

        return response()->json([
            'success' => true,
            'business_type' => $customer->business_type,
            'business_type_label' => $labels[$customer->business_type] ?? $customer->business_type
        ]);
    }
}
