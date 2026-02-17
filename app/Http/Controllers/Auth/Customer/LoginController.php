<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Display the login view
     */
    public function create(): View
    {
        return view('auth.customer.login');
    }

    /**
     * Handle an incoming authentication request
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'password.required' => 'Bitte geben Sie Ihr Passwort ein.',
        ]);

        $this->ensureIsNotRateLimited($request);

        if (!Auth::guard('customer')->attempt(
            $request->only('email', 'password'),
            $request->boolean('remember')
        )) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('Die eingegebenen Anmeldedaten sind ungültig.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $customer = Auth::guard('customer')->user();

        if (!$customer->hasVerifiedEmail()) {
            Auth::guard('customer')->logout();

            throw ValidationException::withMessages([
                'email' => 'Bitte bestätigen Sie zuerst Ihre E-Mail-Adresse, bevor Sie sich einloggen.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('customer.dashboard'))
            ->with('success', 'Erfolgreich angemeldet!');
    }

    /**
     * Destroy an authenticated session
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login')
            ->with('success', 'Erfolgreich abgemeldet!');
    }

    /**
     * Ensure the login request is not rate limited
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());
    }
}
