<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Notifications\MagicLoginNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class MagicLoginController extends Controller
{
    /**
     * Send a magic login code to the customer's email.
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
            return redirect()->route('customer.login', ['code_sent' => 1, 'email' => $request->email]);
        }

        $expireMinutes = Config::get('auth.magic_login.expire', 15);

        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $customer->update([
            'login_code' => $code,
            'login_code_expires_at' => Carbon::now()->addMinutes($expireMinutes),
        ]);

        // Also generate a magic link as fallback
        $loginUrl = URL::temporarySignedRoute(
            'customer.magic-login.verify',
            Carbon::now()->addMinutes($expireMinutes),
            ['id' => $customer->id]
        );

        $customer->notify(new MagicLoginNotification($loginUrl, $code));

        return redirect()->route('customer.login', ['code_sent' => 1, 'email' => $request->email]);
    }

    /**
     * Verify the 6-digit login code.
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $customer = Customer::where('email', $request->email)
            ->whereNotNull('login_code')
            ->whereNotNull('login_code_expires_at')
            ->first();

        if (!$customer || !$customer->login_code_expires_at->isFuture() || $customer->login_code !== $request->code) {
            return response()->json([
                'success' => false,
                'message' => 'Der Code ist ungültig oder abgelaufen.',
            ], 422);
        }

        // Clear the code
        $customer->update([
            'login_code' => null,
            'login_code_expires_at' => null,
        ]);

        // Auto-verify email if not yet verified
        if (!$customer->hasVerifiedEmail()) {
            $customer->markEmailAsVerified();
        }

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'redirect' => route('customer.dashboard'),
        ]);
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

        // Clear any pending code
        $customer->update([
            'login_code' => null,
            'login_code_expires_at' => null,
        ]);

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
