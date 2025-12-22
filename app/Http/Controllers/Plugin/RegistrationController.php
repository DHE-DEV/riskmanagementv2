<?php

namespace App\Http\Controllers\Plugin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plugin\PluginRegistrationRequest;
use App\Mail\PluginKeyMail;
use App\Models\Customer;
use App\Models\PluginClient;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
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

        // Create customer account (always business customer for plugin users)
        $customer = Customer::create([
            'name' => $validated['contact_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company_name' => $validated['company_name'],
            'company_street' => $validated['company_street'],
            'company_house_number' => $validated['company_house_number'],
            'company_postal_code' => $validated['company_postal_code'],
            'company_city' => $validated['company_city'],
            'company_country' => $validated['company_country'],
            'customer_type' => 'business',
        ]);

        // Fire registered event (for email verification if needed)
        event(new Registered($customer));

        // Create plugin client
        $pluginClient = PluginClient::create([
            'customer_id' => $customer->id,
            'company_name' => $validated['company_name'],
            'contact_name' => $validated['contact_name'],
            'email' => $validated['email'],
            'status' => 'active',
        ]);

        // Generate API key
        $pluginClient->generateKey();

        // Add domain
        $pluginClient->addDomain($validated['domain']);

        // Send welcome email with key and snippet
        Mail::to($customer->email)->send(new PluginKeyMail($pluginClient));

        // Log in the customer
        Auth::guard('customer')->login($customer);

        return redirect()->route('plugin.dashboard')
            ->with('success', 'Willkommen! Ihr Plugin-Zugang wurde erstellt. Die Zugangsdaten wurden auch an Ihre E-Mail-Adresse gesendet.');
    }
}
