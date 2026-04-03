<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\TravelAlertOrder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, $id, $hash): RedirectResponse
    {
        $customer = Customer::findOrFail($id);

        if (! hash_equals(sha1($customer->getEmailForVerification()), (string) $hash)) {
            abort(403, 'Ungültiger Verifizierungslink.');
        }

        $hasTravelAlertOrder = TravelAlertOrder::where('email', $customer->email)->exists();

        if ($customer->hasVerifiedEmail()) {
            if ($hasTravelAlertOrder) {
                return redirect('/travel-alert')
                    ->with('success', 'Ihre E-Mail-Adresse wurde bereits bestätigt.');
            }

            return redirect()->route('customer.login')
                ->with('success', 'Ihre E-Mail-Adresse wurde bereits bestätigt. Sie können sich jetzt einloggen.');
        }

        if ($customer->markEmailAsVerified()) {
            event(new Verified($customer));
        }

        if ($hasTravelAlertOrder) {
            return redirect()->route('customer.settings')
                ->with('success', 'Ihre E-Mail-Adresse wurde erfolgreich bestätigt. Willkommen bei der Passolution Travel Information Platform!');
        }

        return redirect()->route('customer.login')
            ->with('success', 'Ihre E-Mail-Adresse wurde erfolgreich bestätigt. Sie können sich jetzt einloggen.');
    }
}
