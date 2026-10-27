<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mail\CustomerAddressChangedMail;
use App\Models\Branch;
use App\Models\Customer;
use App\Services\OrganizationChangeNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function updatePersonal(Request $request)
    {
        $employeeId = session('logged_in_employee_id');

        if ($employeeId) {
            $employee = \App\Models\Employee::find($employeeId);
            if (! $employee) {
                return response()->json(['success' => false, 'message' => 'Mitarbeiter nicht gefunden'], 404);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:50',
            ]);

            $nameParts = explode(' ', $request->name, 2);
            $employee->update([
                'first_name' => $nameParts[0] ?? '',
                'last_name' => $nameParts[1] ?? '',
                'phone' => $request->phone,
            ]);

            session([
                'logged_in_employee_name' => $employee->first_name.' '.$employee->last_name,
            ]);
        } else {
            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:50',
            ]);

            $customer = auth('customer')->user();
            $customer->update($request->only(['name', 'phone']));
        }

        return response()->json([
            'success' => true,
            'message' => 'Persönliche Daten erfolgreich aktualisiert',
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

        if (! Hash::check($request->current_password, $customer->password)) {
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
            'customer_type' => 'required|in:business,private',
        ]);

        $customer = auth('customer')->user();
        $customer->customer_type = $request->customer_type;
        $customer->save();

        return response()->json([
            'success' => true,
            'customer_type' => $customer->customer_type,
            'customer_type_label' => $customer->customer_type === 'business' ? 'Firmenkunde' : 'Privatkunde',
        ]);
    }

    public function updateBusinessType(Request $request)
    {
        $request->validate([
            'business_types' => 'array',
            'business_types.*' => 'in:travel_agency,organizer,online_provider,mobile_travel_consultant,cooperation,software_provider,other',
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

        $businessTypeLabels = array_map(function ($type) use ($labels) {
            return $labels[$type] ?? $type;
        }, $customer->business_type ?? []);

        return response()->json([
            'success' => true,
            'business_types' => $customer->business_type,
            'business_type_labels' => $businessTypeLabels,
        ]);
    }

    public function updateCompanyAddress(Request $request)
    {
        return $this->saveAddress($request, auth('customer')->user(), CustomerAddressChangedMail::TYPE_COMPANY);
    }

    public function updateBillingAddress(Request $request)
    {
        return $this->saveAddress($request, auth('customer')->user(), CustomerAddressChangedMail::TYPE_BILLING);
    }

    /**
     * Zentrale aendert die Firmenanschrift eines ihrer Bueros (Einstellungen ›
     * Organisationsstruktur › Agenturen) – nur mit Freigabe „Darf eigene
     * Adresse aendern“.
     */
    public function updateSubAccountAddress(Request $request, Customer $subAccount)
    {
        $agency = auth('customer')->user();

        abort_unless($subAccount->assign_to === $agency->id && $agency->canEditOwnAddress(), 403);

        // Agentur oder Reiseberater – Tag in den Karten der Organisationsstruktur
        $type = $request->validate(['location_type' => ['nullable', Rule::in(array_keys(Branch::LOCATION_TYPES))]])['location_type'] ?? null;
        if ($type) {
            $subAccount->update(['location_type' => $type]);
        }

        return $this->saveAddress($request, $subAccount, CustomerAddressChangedMail::TYPE_COMPANY, authorized: true);
    }

    /**
     * Adresse speichern und bei Aenderung die Infomails ausloesen. Ohne Freigabe
     * darf der Kunde nur eine noch leere Adresse erstmals erfassen.
     */
    protected function saveAddress(Request $request, Customer $customer, string $type, bool $authorized = false)
    {
        $fields = array_keys(CustomerAddressChangedMail::FIELDS[$type]);
        $request->validate(array_combine($fields, array_map(
            fn ($field) => 'nullable|string|max:'.(str_ends_with($field, 'house_number') ? 20 : 255),
            $fields,
        )));

        if (! $authorized && ! $customer->canChangeAddress($type)) {
            return response()->json([
                'success' => false,
                'message' => 'Die Adresse kann nicht selbst geändert werden. Bitte wenden Sie sich an info@passolution.de.',
            ], 403);
        }

        $before = $customer->only($fields);
        $customer->update($request->only($fields));
        app(OrganizationChangeNotifier::class)->customerAddressChanged($customer, $type, $before, $customer->only($fields));

        return response()->json([
            'success' => true,
            'message' => CustomerAddressChangedMail::TYPE_LABELS[$type].' erfolgreich gespeichert',
            'address' => $customer->only($fields),
        ]);
    }

    public function getCountries(Request $request)
    {
        $search = $request->get('search', '');

        $countries = \App\Models\Country::whereNotNull('name_translations')
            ->get()
            ->map(function ($country) {
                $translations = is_string($country->name_translations)
                    ? json_decode($country->name_translations, true)
                    : $country->name_translations;

                return [
                    'id' => $country->id,
                    'name' => $translations['de'] ?? $translations['en'] ?? 'Unknown',
                    'iso_code' => $country->iso_code,
                ];
            })
            ->filter(function ($country) use ($search) {
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
            'hide' => 'required|boolean',
        ]);

        $customer = auth('customer')->user();
        $customer->hide_profile_completion = $request->hide;
        $customer->save();

        return response()->json([
            'success' => true,
            'hide_profile_completion' => $customer->hide_profile_completion,
        ]);
    }

    public function toggleDirectoryListing(Request $request)
    {
        $request->validate([
            'active' => 'required|boolean',
        ]);

        $customer = auth('customer')->user();
        $customer->directory_listing_active = $request->active;
        $customer->save();

        return response()->json([
            'success' => true,
            'directory_listing_active' => $customer->directory_listing_active,
        ]);
    }

    public function toggleBranchManagement(Request $request)
    {
        $request->validate([
            'active' => 'required|boolean',
        ]);

        $customer = auth('customer')->user();
        $customer->branch_management_active = $request->active;
        $customer->save();

        return response()->json([
            'success' => true,
            'branch_management_active' => $customer->branch_management_active,
        ]);
    }
}
