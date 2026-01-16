<?php

namespace App\Http\Controllers\Plugin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plugin\AddDomainRequest;
use App\Models\PluginUsageEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(): View
    {
        $customer = auth('customer')->user();
        $pluginClient = $customer->pluginClient;

        if (!$pluginClient) {
            return redirect()->route('plugin.onboarding');
        }

        // Get stats for last 30 days
        $stats = $this->getUsageStats($pluginClient);

        return view('plugin.dashboard', [
            'customer' => $customer,
            'pluginClient' => $pluginClient,
            'activeKey' => $pluginClient->activeKey,
            'domains' => $pluginClient->domains,
            'embedSnippet' => $pluginClient->getEmbedSnippet(),
            'stats' => $stats,
            'active' => 'plugin-dashboard',
        ]);
    }

    public function addDomain(AddDomainRequest $request): RedirectResponse
    {
        $customer = auth('customer')->user();
        $pluginClient = $customer->pluginClient;

        if (!$pluginClient) {
            return redirect()->route('plugin.onboarding');
        }

        $domain = $request->validated('domain');

        // Check if domain already exists
        if ($pluginClient->hasDomain($domain)) {
            return back()->with('error', 'Diese Domain ist bereits registriert.');
        }

        $pluginClient->addDomain($domain);

        return back()->with('success', 'Domain erfolgreich hinzugefügt.');
    }

    public function removeDomain(int $domainId): RedirectResponse
    {
        $customer = auth('customer')->user();
        $pluginClient = $customer->pluginClient;

        if (!$pluginClient) {
            return redirect()->route('plugin.onboarding');
        }

        $domain = $pluginClient->domains()->find($domainId);

        if (!$domain) {
            return back()->with('error', 'Domain nicht gefunden.');
        }

        // Ensure at least one domain remains
        if ($pluginClient->domains()->count() <= 1) {
            return back()->with('error', 'Sie müssen mindestens eine Domain behalten.');
        }

        $domain->delete();

        return back()->with('success', 'Domain erfolgreich entfernt.');
    }

    public function regenerateKey(): RedirectResponse
    {
        $customer = auth('customer')->user();
        $pluginClient = $customer->pluginClient;

        if (!$pluginClient) {
            return redirect()->route('plugin.onboarding');
        }

        $pluginClient->generateKey();

        return back()->with('success', 'Neuer API-Key wurde generiert. Der alte Key ist nicht mehr gültig.');
    }

    public function toggleAppAccess(): RedirectResponse
    {
        $customer = auth('customer')->user();
        $pluginClient = $customer->pluginClient;

        if (!$pluginClient) {
            return redirect()->route('plugin.onboarding');
        }

        $pluginClient->update([
            'allow_app_access' => !$pluginClient->allow_app_access
        ]);

        return back()->with('success', $pluginClient->allow_app_access
            ? 'App-Zugang aktiviert'
            : 'App-Zugang deaktiviert');
    }

    protected function getUsageStats($pluginClient): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        // Daily embed views for last 30 days
        $dailyStats = PluginUsageEvent::where('plugin_client_id', $pluginClient->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->where('event_type', 'embed_view')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Total events last 30 days
        $totalEvents = PluginUsageEvent::where('plugin_client_id', $pluginClient->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        // Events by type
        $eventsByType = PluginUsageEvent::where('plugin_client_id', $pluginClient->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        // Top domains
        $topDomains = PluginUsageEvent::where('plugin_client_id', $pluginClient->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select('domain', DB::raw('COUNT(*) as count'))
            ->groupBy('domain')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'domain')
            ->toArray();

        return [
            'daily' => $dailyStats,
            'total' => $totalEvents,
            'by_type' => $eventsByType,
            'top_domains' => $topDomains,
        ];
    }
}
