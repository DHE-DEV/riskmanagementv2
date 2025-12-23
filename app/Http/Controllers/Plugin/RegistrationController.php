<?php

namespace App\Http\Controllers\Plugin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plugin\PluginRegistrationRequest;
use App\Mail\PluginVerificationCodeMail;
use App\Models\Customer;
use App\Models\PluginClient;
use App\Models\PluginEmailVerification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function show(): View
    {
        return view('plugin.register');
    }

    public function store(PluginRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Delete any existing verification for this email
        PluginEmailVerification::where('email', $validated['email'])
            ->whereNull('verified_at')
            ->delete();

        // Create verification record with form data
        $verification = PluginEmailVerification::createForEmail(
            $validated['email'],
            $validated
        );

        // Send verification code email
        Mail::to($validated['email'])->send(
            new PluginVerificationCodeMail(
                $validated['email'],
                $verification->code,
                $validated['contact_name']
            )
        );

        return redirect()->route('plugin.verify-email', $verification->token)
            ->with('success', 'Wir haben Ihnen einen Verifizierungscode per E-Mail gesendet.');
    }

    public function showVerify(string $token): View|RedirectResponse
    {
        $verification = PluginEmailVerification::where('token', $token)->first();

        if (!$verification) {
            return redirect()->route('plugin.register')
                ->with('error', 'Der Verifizierungslink ist ungültig. Bitte registrieren Sie sich erneut.');
        }

        if ($verification->isVerified()) {
            return redirect()->route('plugin.register')
                ->with('error', 'Diese E-Mail wurde bereits verifiziert. Bitte melden Sie sich an.');
        }

        if ($verification->isExpired()) {
            return redirect()->route('plugin.register')
                ->with('error', 'Der Verifizierungscode ist abgelaufen. Bitte registrieren Sie sich erneut.');
        }

        return view('plugin.verify-email', [
            'token' => $token,
            'email' => $verification->email,
            'remainingAttempts' => $verification->getRemainingAttempts(),
            'expiryMinutes' => $verification->getExpiryMinutes(),
        ]);
    }

    public function verify(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ], [
            'code.required' => 'Bitte geben Sie den Verifizierungscode ein.',
            'code.size' => 'Der Code muss 6 Ziffern enthalten.',
            'code.regex' => 'Der Code darf nur Ziffern enthalten.',
        ]);

        $verification = PluginEmailVerification::where('token', $token)->first();

        if (!$verification) {
            return redirect()->route('plugin.register')
                ->with('error', 'Der Verifizierungslink ist ungültig.');
        }

        if ($verification->isVerified()) {
            return redirect()->route('plugin.register')
                ->with('error', 'Diese E-Mail wurde bereits verifiziert.');
        }

        if ($verification->isExpired()) {
            return redirect()->route('plugin.register')
                ->with('error', 'Der Verifizierungscode ist abgelaufen. Bitte registrieren Sie sich erneut.');
        }

        if ($verification->hasExceededAttempts()) {
            return redirect()->route('plugin.register')
                ->with('error', 'Zu viele Fehlversuche. Bitte registrieren Sie sich erneut.');
        }

        // Check the code
        if (!$verification->verifyCode($request->input('code'))) {
            $verification->incrementAttempts();

            $remaining = $verification->getRemainingAttempts();

            if ($remaining === 0) {
                return redirect()->route('plugin.register')
                    ->with('error', 'Zu viele Fehlversuche. Bitte registrieren Sie sich erneut.');
            }

            return back()
                ->withInput()
                ->with('error', "Falscher Code. Sie haben noch {$remaining} " . ($remaining === 1 ? 'Versuch' : 'Versuche') . '.');
        }

        // Code is correct - complete registration
        $verification->markAsVerified();
        $formData = $verification->form_data;

        // Create customer account
        $customer = Customer::create([
            'name' => $formData['contact_name'],
            'email' => $formData['email'],
            'password' => Hash::make($formData['password']),
            'company_name' => $formData['company_name'],
            'company_street' => $formData['company_street'],
            'company_house_number' => $formData['company_house_number'],
            'company_postal_code' => $formData['company_postal_code'],
            'company_city' => $formData['company_city'],
            'company_country' => $formData['company_country'],
            'customer_type' => 'business',
            'business_type' => $formData['business_types'] ?? [],
            'email_verified_at' => now(), // Email is now verified
        ]);

        // Fire registered event
        event(new Registered($customer));

        // Create plugin client
        $pluginClient = PluginClient::create([
            'customer_id' => $customer->id,
            'company_name' => $formData['company_name'],
            'contact_name' => $formData['contact_name'],
            'email' => $formData['email'],
            'status' => 'active',
        ]);

        // Generate API key
        $pluginClient->generateKey();

        // Add domain
        $pluginClient->addDomain($formData['domain']);

        // Log in the customer
        Auth::guard('customer')->login($customer);

        return redirect()->route('plugin.dashboard')
            ->with('success', 'Willkommen! Ihr Plugin-Zugang wurde erstellt.');
    }

    public function resendCode(string $token): RedirectResponse
    {
        $verification = PluginEmailVerification::where('token', $token)->first();

        if (!$verification) {
            return redirect()->route('plugin.register')
                ->with('error', 'Der Verifizierungslink ist ungültig.');
        }

        if ($verification->isVerified()) {
            return redirect()->route('plugin.register')
                ->with('error', 'Diese E-Mail wurde bereits verifiziert.');
        }

        // Regenerate code and reset expiry
        $newCode = $verification->regenerateCode();

        // Send new verification code
        Mail::to($verification->email)->send(
            new PluginVerificationCodeMail(
                $verification->email,
                $newCode,
                $verification->form_data['contact_name']
            )
        );

        return back()->with('success', 'Ein neuer Verifizierungscode wurde an Ihre E-Mail-Adresse gesendet.');
    }
}
