<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Notifications\MagicLoginNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class MagicLoginController extends Controller
{
    /**
     * Send a magic login link to the customer's email.
     */
    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
        ]);

        $customer = Customer::where('email', $request->email)->first();

        // Always show success message to prevent email enumeration
        if (!$customer) {
            return redirect()->route('customer.login')
                ->with('success', 'Falls ein Konto mit dieser E-Mail-Adresse existiert, wurde ein Login-Link gesendet.');
        }

        $expireMinutes = Config::get('auth.magic_login.expire', 15);

        $loginUrl = URL::temporarySignedRoute(
            'customer.magic-login.verify',
            Carbon::now()->addMinutes($expireMinutes),
            ['id' => $customer->id]
        );

        $customer->notify(new MagicLoginNotification($loginUrl));

        return redirect()->route('customer.login')
            ->with('success', 'Falls ein Konto mit dieser E-Mail-Adresse existiert, wurde ein Login-Link gesendet.');
    }

    /**
     * Verify magic login link and authenticate the customer.
     */
    public function verify(Request $request, int $id): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('customer.login')
                ->with('error', 'Dieser Login-Link ist ungültig oder abgelaufen.');
        }

        $customer = Customer::findOrFail($id);

        // Auto-verify email if not yet verified
        if (!$customer->hasVerifiedEmail()) {
            $customer->markEmailAsVerified();
        }

        Auth::guard('customer')->login($customer, true);

        $request->session()->regenerate();

        return redirect()->intended(route('customer.dashboard'))
            ->with('success', 'Erfolgreich angemeldet!');
    }
}
