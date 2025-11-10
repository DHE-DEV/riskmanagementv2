<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Display the registration view
     */
    public function create(): View
    {
        return view('auth.customer.register');
    }

    /**
     * Handle an incoming registration request
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'Bitte geben Sie Ihren Namen ein.',
            'name.max' => 'Der Name darf maximal 255 Zeichen lang sein.',
            'email.required' => 'Bitte geben Sie eine E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse ist bereits registriert.',
            'password.required' => 'Bitte geben Sie ein Passwort ein.',
            'password.confirmed' => 'Die Passwörter stimmen nicht überein.',
        ]);

        try {
            $customer = Customer::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            event(new Registered($customer));

            Auth::guard('customer')->login($customer);

            return redirect()->route('customer.dashboard')
                ->with('success', 'Registrierung erfolgreich! Bitte bestätigen Sie Ihre E-Mail-Adresse.');
        } catch (\Exception $e) {
            return back()
                ->withInput($request->only('name', 'email'))
                ->with('error', 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
        }
    }
}
