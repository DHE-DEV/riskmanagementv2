<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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

        if ($customer->hasVerifiedEmail()) {
            return redirect()->route('customer.login')
                ->with('success', 'Ihre E-Mail-Adresse wurde bereits bestätigt. Sie können sich jetzt einloggen.');
        }

        if ($customer->markEmailAsVerified()) {
            event(new Verified($customer));
        }

        return redirect()->route('customer.login')
            ->with('success', 'Ihre E-Mail-Adresse wurde erfolgreich bestätigt. Sie können sich jetzt einloggen.');
    }
}
