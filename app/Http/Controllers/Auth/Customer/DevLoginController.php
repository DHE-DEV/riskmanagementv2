<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerFeaturePreauthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Lokaler Entwickler-Login ohne SSO.
 *
 * Der Auth-Server leitet nur auf dort eingetragene Callback-Adressen zurueck –
 * eine lokale .test-Domain gehoert in der Regel nicht dazu. Dieser Login meldet
 * einen vorhandenen Kunden direkt an und stellt denselben Stand her wie der
 * SSO-Callback: Stammdaten/Abo frisch aus der Passolution-API, vorgemerkte
 * Features, Anmelde-E-Mail in der Session.
 *
 * Existiert ausschliesslich bei APP_ENV=local (Route UND Controller pruefen).
 */
class DevLoginController extends Controller
{
    public function __invoke(Request $request, string $email): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);

        $customer = Customer::where('email', $email)->first();

        abort_if(! $customer, 404, "Kein Kunde mit der E-Mail {$email}.");

        // Wie im SSO-Callback: ein Fehlschlag der API darf die Anmeldung nicht blockieren.
        try {
            $customer->syncPdsAccountData(0);
            $customer->refresh();

            app(CustomerFeaturePreauthorizationService::class)->applyForCustomer($customer);
        } catch (\Throwable $e) {
            Log::warning('Dev-Login: Stammdaten-Abgleich fehlgeschlagen', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage(),
            ]);
        }

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        // Die Session-Keys des SSO-Callbacks: die Anmelde-E-Mail ist die
        // Identitaet fuer die Service-Token-Abrufe (Reisen, iframe-SSO).
        session([
            'keycloak_id_token' => null,
            'keycloak_email' => $customer->email,
        ]);

        return redirect('/customer/dashboard')
            ->with('success', 'Lokal angemeldet als '.$customer->email);
    }
}
