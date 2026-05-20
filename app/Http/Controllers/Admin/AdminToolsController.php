<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\KeycloakUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminToolsController extends Controller
{
    public function __construct(private KeycloakUserService $keycloak)
    {
    }

    public function show(): View
    {
        return view('admin.tools.keycloak');
    }

    public function setPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $ok = $this->keycloak->setPassword($data['email'], $data['password']);

        $customer = Customer::where('email', $data['email'])->first();
        if ($customer) {
            $customer->update(['password' => Hash::make($data['password'])]);
        }

        Log::warning('AdminTools: password reset', [
            'by_user_id' => Auth::id(),
            'target_email' => $data['email'],
            'keycloak_ok' => $ok,
            'local_customer_updated' => (bool) $customer,
        ]);

        $status = $ok
            ? "Passwort für {$data['email']} gesetzt." . ($customer ? ' Lokaler Customer aktualisiert.' : ' (kein lokaler Customer gefunden)')
            : "Keycloak-Update fehlgeschlagen. Details siehe Log.";

        return back()->with($ok ? 'status' : 'error', $status);
    }

    public function createUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
        ]);

        $keycloakId = $this->keycloak->createUser(
            $data['email'],
            $data['password'],
            $data['first_name'],
            $data['last_name'],
        );

        if (!$keycloakId) {
            return back()->with('error', 'Anlegen in Keycloak fehlgeschlagen. Details siehe Log.')->withInput();
        }

        $customer = Customer::create([
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'provider' => 'keycloak',
            'provider_id' => $keycloakId,
            'email_verified_at' => now(),
        ]);

        Log::warning('AdminTools: user created', [
            'by_user_id' => Auth::id(),
            'customer_id' => $customer->id,
            'email' => $data['email'],
            'keycloak_id' => $keycloakId,
        ]);

        return back()->with('status', "User {$data['email']} angelegt (Customer #{$customer->id}, KC {$keycloakId}).");
    }
}
