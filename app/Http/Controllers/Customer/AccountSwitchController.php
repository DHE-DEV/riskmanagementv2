<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountSwitchController extends Controller
{
    /**
     * Get paginated list of accessible accounts with search.
     */
    public function accessible(Request $request): JsonResponse
    {
        $currentUser = Auth::guard('customer')->user();
        $originalCustomerId = $request->session()->get('original_customer_id', $currentUser->id);
        $originalCustomer = Customer::find($originalCustomerId);

        if ($this->isSuperAdmin($originalCustomer)) {
            $query = Customer::where('id', '!=', $originalCustomer->id);
        } else {
            $query = $originalCustomer->accessibleAccounts();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('app_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('company_postal_code', 'like', "%{$search}%")
                  ->orWhere('company_city', 'like', "%{$search}%");
            });
        }

        $accounts = $query
            ->select('customers.id', 'name', 'company_name', 'app_code', 'company_postal_code', 'company_city')
            ->orderBy('company_name')
            ->paginate(10);

        return response()->json($accounts);
    }

    /**
     * Get paginated list of agencies assigned to the current customer.
     */
    public function assignedAgencies(Request $request): JsonResponse
    {
        $customer = Auth::guard('customer')->user();

        $query = Customer::where('assign_to', $customer->id);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('app_code', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('company_postal_code', 'like', "%{$search}%")
                  ->orWhere('company_city', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $agencies = $query
            ->select('id', 'app_code', 'company_name', 'company_postal_code', 'company_city', 'email')
            ->orderBy('company_name')
            ->paginate(15);

        return response()->json($agencies);
    }

    /**
     * Switch to another customer account.
     */
    public function switch(Request $request, Customer $customer): RedirectResponse
    {
        $currentUser = Auth::guard('customer')->user();
        $originalCustomerId = $request->session()->get('original_customer_id', $currentUser->id);

        // Check if the user is switching back to their own account
        if ($customer->id === $originalCustomerId) {
            Auth::guard('customer')->login(Customer::find($originalCustomerId));

            $request->session()->forget('impersonating_customer_id');
            $request->session()->forget('original_customer_id');

            return redirect()->route('customer.dashboard')
                ->with('success', "Zurück zu Ihrem eigenen Account.");
        }

        // Check if user has access to this account
        $originalCustomer = Customer::find($originalCustomerId);

        if (!$this->isSuperAdmin($originalCustomer) && !$originalCustomer->accessibleAccounts()->where('customer_id', $customer->id)->exists()) {
            abort(403, 'Sie haben keinen Zugriff auf diesen Account.');
        }

        // Store original customer ID and switch
        $request->session()->put('original_customer_id', $originalCustomerId);
        $request->session()->put('impersonating_customer_id', $customer->id);

        Auth::guard('customer')->login($customer);

        return redirect()->route('customer.dashboard')
            ->with('success', "Gewechselt zu: {$customer->company_name}");
    }

    /**
     * Check if a customer is a super admin (can access all accounts).
     */
    private function isSuperAdmin(Customer $customer): bool
    {
        return in_array($customer->email, config('app.agentur_super_admin_emails', []));
    }

    /**
     * Switch back to own account.
     */
    public function switchBack(Request $request): RedirectResponse
    {
        $originalCustomerId = $request->session()->get('original_customer_id');

        if (!$originalCustomerId) {
            return redirect()->route('customer.dashboard');
        }

        $originalCustomer = Customer::find($originalCustomerId);

        if (!$originalCustomer) {
            abort(404);
        }

        Auth::guard('customer')->login($originalCustomer);

        $request->session()->forget('impersonating_customer_id');
        $request->session()->forget('original_customer_id');

        return redirect()->route('customer.dashboard')
            ->with('success', 'Zurück zu Ihrem eigenen Account.');
    }
}
