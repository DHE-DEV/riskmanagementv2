<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerSettingsController extends Controller
{
    /**
     * GET company master data (Firmendaten).
     */
    public function show(Request $request): JsonResponse
    {
        $customer = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'company_name' => $customer->company_name,
                'company_additional' => $customer->company_additional,
                'company_street' => $customer->company_street,
                'company_house_number' => $customer->company_house_number,
                'company_postal_code' => $customer->company_postal_code,
                'company_city' => $customer->company_city,
                'company_country' => $customer->company_country,
                'billing_company_name' => $customer->billing_company_name,
                'billing_additional' => $customer->billing_additional,
                'billing_street' => $customer->billing_street,
                'billing_house_number' => $customer->billing_house_number,
                'billing_postal_code' => $customer->billing_postal_code,
                'billing_city' => $customer->billing_city,
                'billing_country' => $customer->billing_country,
                'customer_type' => $customer->customer_type,
                'business_type' => $customer->business_type,
                'branch_count' => Branch::where('customer_id', $customer->id)->count(),
                'employee_count' => Employee::where('customer_id', $customer->id)->count(),
            ],
        ]);
    }

    /**
     * PUT update company address (Firmenanschrift).
     */
    public function updateCompanyAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_additional' => 'nullable|string|max:255',
            'company_street' => 'nullable|string|max:255',
            'company_house_number' => 'nullable|string|max:20',
            'company_postal_code' => 'nullable|string|max:20',
            'company_city' => 'nullable|string|max:255',
            'company_country' => 'nullable|string|max:255',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Firmenanschrift erfolgreich aktualisiert',
        ]);
    }

    /**
     * PUT update billing address (Rechnungsadresse).
     */
    public function updateBillingAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'billing_company_name' => 'nullable|string|max:255',
            'billing_additional' => 'nullable|string|max:255',
            'billing_street' => 'nullable|string|max:255',
            'billing_house_number' => 'nullable|string|max:20',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_city' => 'nullable|string|max:255',
            'billing_country' => 'nullable|string|max:255',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Rechnungsadresse erfolgreich aktualisiert',
        ]);
    }

    /**
     * PUT update customer type.
     */
    public function updateCustomerType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_type' => 'required|in:private,business',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kundentyp erfolgreich aktualisiert',
        ]);
    }

    /**
     * PUT update business type.
     */
    public function updateBusinessType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_type' => 'required|in:travel_agency,tour_operator,corporate,insurance,other',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Geschäftstyp erfolgreich aktualisiert',
        ]);
    }
}
