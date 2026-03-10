<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Services\CustomerFeatureService;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    public function __construct(
        protected CustomerFeatureService $featureService,
    ) {}

    public function index()
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        $rules = $customer->notificationRules()
            ->with(['recipients', 'template'])
            ->latest()
            ->get();

        $templateCount = NotificationTemplate::forCustomer($customer->id)->count();
        $customTemplateCount = $customer->notificationTemplates()->count();
        $systemTemplateCount = NotificationTemplate::system()->count();

        return view('customer.notification-settings.index', compact(
            'customer',
            'rules',
            'templateCount',
            'customTemplateCount',
            'systemTemplateCount',
        ));
    }

    public function history()
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        $logs = NotificationLog::where('customer_id', $customer->id)
            ->with('notificationRule')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('customer.notification-settings.history', compact('logs'));
    }

    public function toggleNotifications(Request $request)
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        $customer->update([
            'notifications_enabled' => ! $customer->notifications_enabled,
        ]);

        return back()->with('success', $customer->notifications_enabled
            ? 'Benachrichtigungen aktiviert.'
            : 'Benachrichtigungen deaktiviert.'
        );
    }

    public function stats()
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            return response()->json(['error' => 'Nicht berechtigt'], 403);
        }

        return response()->json([
            'notifications_enabled' => $customer->notifications_enabled,
            'rules_count' => $customer->notificationRules()->count(),
            'active_rules_count' => $customer->notificationRules()->where('is_active', true)->count(),
            'templates_count' => NotificationTemplate::forCustomer($customer->id)->count(),
        ]);
    }

    public function createRule()
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        return view('customer.notification-settings.rules.form', [
            'rule' => null,
        ]);
    }

    public function editRule(int $id)
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        $rule = $customer->notificationRules()->with('recipients')->findOrFail($id);

        return view('customer.notification-settings.rules.form', [
            'rule' => $rule,
        ]);
    }

    public function templateIndex()
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        $templates = NotificationTemplate::forCustomer($customer->id)
            ->latest()
            ->get();

        return view('customer.notification-settings.templates.index', compact('templates'));
    }

    public function createTemplate()
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        return view('customer.notification-settings.templates.form', [
            'template' => null,
        ]);
    }

    public function editTemplate(int $id)
    {
        $customer = auth('customer')->user();

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403);
        }

        $template = NotificationTemplate::forCustomer($customer->id)->findOrFail($id);

        if ($template->is_system) {
            abort(403, 'System-Vorlagen können nicht bearbeitet werden.');
        }

        return view('customer.notification-settings.templates.form', [
            'template' => $template,
        ]);
    }
}
