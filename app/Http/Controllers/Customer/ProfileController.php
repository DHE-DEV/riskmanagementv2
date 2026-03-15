<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function updatePersonal(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email,' . auth('customer')->id(),
            'phone' => 'nullable|string|max:50',
        ]);

        $customer = auth('customer')->user();
        $customer->update($request->only(['name', 'email', 'phone']));

        return response()->json([
            'success' => true,
            'message' => 'Persönliche Daten erfolgreich aktualisiert'
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $customer = auth('customer')->user();

        // Delete old avatar
        if ($customer->avatar && Storage::disk('public')->exists($customer->avatar)) {
            Storage::disk('public')->delete($customer->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $customer->avatar = $path;
        $customer->save();

        return response()->json([
            'success' => true,
            'avatar_url' => Storage::disk('public')->url($path),
            'message' => 'Profilbild erfolgreich hochgeladen',
        ]);
    }

    public function deleteAvatar()
    {
        $customer = auth('customer')->user();

        if ($customer->avatar && Storage::disk('public')->exists($customer->avatar)) {
            Storage::disk('public')->delete($customer->avatar);
        }

        $customer->avatar = null;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Profilbild entfernt',
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $customer = auth('customer')->user();

        if (!Hash::check($request->current_password, $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Das aktuelle Passwort ist nicht korrekt.',
            ], 422);
        }

        $customer->password = Hash::make($request->password);
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Passwort erfolgreich geändert',
        ]);
    }

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
            'business_types.*' => 'in:travel_agency,organizer,online_provider,mobile_travel_consultant,cooperation,software_provider,other'
        ]);

        $customer = auth('customer')->user();
        $customer->business_type = $request->business_types ?? [];
        $customer->save();

        $labels = [
            'travel_agency' => 'Reisebüro',
            'organizer' => 'Veranstalter',
            'online_provider' => 'Online Anbieter',
            'mobile_travel_consultant' => 'Mobiler Reiseberater',
            'cooperation' => 'Kooperation',
            'software_provider' => 'Softwareanbieter',
            'other' => 'Sonstiges',
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
            'company_house_number' => 'nullable|string|max:20',
            'company_postal_code' => 'nullable|string|max:20',
            'company_city' => 'nullable|string|max:255',
            'company_country' => 'nullable|string|max:255',
        ]);

        $customer = auth('customer')->user();
        $customer->update($request->only([
            'company_name',
            'company_additional',
            'company_street',
            'company_house_number',
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
            'billing_house_number' => 'nullable|string|max:20',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_city' => 'nullable|string|max:255',
            'billing_country' => 'nullable|string|max:255',
        ]);

        $customer = auth('customer')->user();
        $customer->update($request->only([
            'billing_company_name',
            'billing_additional',
            'billing_street',
            'billing_house_number',
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

    public function toggleHideProfileCompletion(Request $request)
    {
        $request->validate([
            'hide' => 'required|boolean'
        ]);

        $customer = auth('customer')->user();
        $customer->hide_profile_completion = $request->hide;
        $customer->save();

        return response()->json([
            'success' => true,
            'hide_profile_completion' => $customer->hide_profile_completion
        ]);
    }

    public function toggleDirectoryListing(Request $request)
    {
        $request->validate([
            'active' => 'required|boolean'
        ]);

        $customer = auth('customer')->user();
        $customer->directory_listing_active = $request->active;
        $customer->save();

        return response()->json([
            'success' => true,
            'directory_listing_active' => $customer->directory_listing_active
        ]);
    }

    public function toggleBranchManagement(Request $request)
    {
        $request->validate([
            'active' => 'required|boolean'
        ]);

        $customer = auth('customer')->user();
        $customer->branch_management_active = $request->active;
        $customer->save();

        return response()->json([
            'success' => true,
            'branch_management_active' => $customer->branch_management_active
        ]);
    }
}
