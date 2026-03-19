<?php

namespace App\Http\Controllers\Plugin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plugin\OnboardingRequest;
use App\Models\PluginClient;
use Illuminate\Http\RedirectResponse;
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

        $integrationType = $request->input('integration_type', 'website');

        // Create plugin client
        $pluginClient = PluginClient::create([
            'customer_id' => $customer->id,
            'company_name' => $request->validated('company_name'),
            'contact_name' => $request->validated('contact_name'),
            'email' => $customer->email,
            'status' => 'active',
            'allow_app_access' => in_array($integrationType, ['app', 'both']),
        ]);

        // Generate API key
        $pluginKey = $pluginClient->generateKey();

        // Add domain (required for website and both, optional for app-only)
        $domain = $request->validated('domain');
        if ($domain) {
            $pluginClient->addDomain($domain);
        }

        $redirectUrl = $request->input('_redirect');
        $redirect = $redirectUrl ? redirect()->to($redirectUrl) : redirect()->route('plugin.dashboard');

        return $redirect->with('success', 'Ihr Plugin-Account wurde erfolgreich erstellt!');
    }
}
