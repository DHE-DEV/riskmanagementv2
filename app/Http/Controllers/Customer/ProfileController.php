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
            'business_types' => 'array',
            'business_types.*' => 'in:travel_agency,organizer,online_provider,mobile_travel_consultant'
        ]);

        $customer = auth('customer')->user();
        $customer->business_type = $request->business_types ?? [];
        $customer->save();

        $labels = [
            'travel_agency' => 'Reisebüro',
            'organizer' => 'Veranstalter',
            'online_provider' => 'Online Anbieter',
            'mobile_travel_consultant' => 'Mobiler Reiseberater'
        ];

        $businessTypeLabels = array_map(function($type) use ($labels) {
            return $labels[$type] ?? $type;
        }, $customer->business_type ?? []);

        return response()->json([
            'success' => true,
            'business_types' => $customer->business_type,
            'business_type_labels' => $businessTypeLabels
        ]);
    }
}
