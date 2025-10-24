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

    public function updateCompanyAddress(Request $request)
    {
        $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_additional' => 'nullable|string|max:255',
            'company_street' => 'nullable|string|max:255',
            'company_postal_code' => 'nullable|string|max:20',
            'company_city' => 'nullable|string|max:255',
            'company_country' => 'nullable|string|max:255',
        ]);

        $customer = auth('customer')->user();
        $customer->update($request->only([
            'company_name',
            'company_additional',
            'company_street',
            'company_postal_code',
            'company_city',
            'company_country',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Firmenanschrift erfolgreich gespeichert'
        ]);
    }

    public function updateBillingAddress(Request $request)
    {
        $request->validate([
            'billing_company_name' => 'nullable|string|max:255',
            'billing_additional' => 'nullable|string|max:255',
            'billing_street' => 'nullable|string|max:255',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_city' => 'nullable|string|max:255',
            'billing_country' => 'nullable|string|max:255',
        ]);

        $customer = auth('customer')->user();
        $customer->update($request->only([
            'billing_company_name',
            'billing_additional',
            'billing_street',
            'billing_postal_code',
            'billing_city',
            'billing_country',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Rechnungsadresse erfolgreich gespeichert'
        ]);
    }

    public function getCountries(Request $request)
    {
        $search = $request->get('search', '');

        $countries = \App\Models\Country::whereNotNull('name_translations')
            ->get()
            ->map(function($country) {
                $translations = is_string($country->name_translations)
                    ? json_decode($country->name_translations, true)
                    : $country->name_translations;
                return [
                    'id' => $country->id,
                    'name' => $translations['de'] ?? $translations['en'] ?? 'Unknown',
                    'iso_code' => $country->iso_code
                ];
            })
            ->filter(function($country) use ($search) {
                if (empty($search)) {
                    return true;
                }
                return stripos($country['name'], $search) !== false;
            })
            ->sortBy('name')
            ->values();

        return response()->json($countries);
    }
}
