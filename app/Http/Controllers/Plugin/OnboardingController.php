<?php

namespace App\Http\Controllers\Plugin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plugin\OnboardingRequest;
use App\Mail\PluginKeyMail;
use App\Models\PluginClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(): View
    {
        $customer = auth('customer')->user();

        // If already onboarded, redirect to dashboard
        if ($customer->hasPluginClient()) {
            return redirect()->route('plugin.dashboard');
        }

        return view('plugin.onboarding', [
            'customer' => $customer,
        ]);
    }

    public function store(OnboardingRequest $request): RedirectResponse
    {
        $customer = auth('customer')->user();

        // Prevent duplicate plugin clients
        if ($customer->hasPluginClient()) {
            return redirect()->route('plugin.dashboard')
                ->with('info', 'Sie haben bereits einen Plugin-Account.');
        }

        // Create plugin client
        $pluginClient = PluginClient::create([
            'customer_id' => $customer->id,
            'company_name' => $request->validated('company_name'),
            'contact_name' => $request->validated('contact_name'),
            'email' => $customer->email,
            'status' => 'active',
        ]);

        // Generate API key
        $pluginKey = $pluginClient->generateKey();

        // Add domain
        $pluginClient->addDomain($request->validated('domain'));

        // Send welcome email with key and snippet
        Mail::to($customer->email)->send(new PluginKeyMail($pluginClient));

        return redirect()->route('plugin.dashboard')
            ->with('success', 'Ihr Plugin-Account wurde erfolgreich erstellt! Die Zugangsdaten wurden an Ihre E-Mail-Adresse gesendet.');
    }
}
